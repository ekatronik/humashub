<?php
// admin/modul-fotovideo/laporan.php
require_once __DIR__ . '/../../includes/auth.php';
checkAccess(['Super Admin', 'Pranata Humas', 'Operator Foto/Video']);

$filter_year = $_GET['year'] ?? date('Y');

// Ambil daftar tahun yang tersedia
$years_data = $pdo->query("SELECT DISTINCT YEAR(event_date) as yr FROM documentation ORDER BY yr DESC")->fetchAll();
$available_years = array_column($years_data, 'yr');
if (empty($available_years)) $available_years = [date('Y')];

// Summary by Location (Filter by year if not 'all')
$where_year = ($filter_year === 'all') ? "" : "WHERE YEAR(event_date) = '$filter_year'";
$summary_loc = $pdo->query("SELECT location_type, COUNT(*) as total FROM documentation $where_year GROUP BY location_type")->fetchAll(PDO::FETCH_KEY_PAIR);
$total_kegiatan = array_sum($summary_loc) ?: 0;

// Summary Monthly
$chart_datasets = [];
$months = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
$colors = ['#27ae60', '#e67e22', '#3498db', '#9b59b6', '#e74c3c', '#f1c40f', '#1abc9c', '#34495e'];

if ($filter_year === 'all') {
    $monthly_data = $pdo->query("SELECT YEAR(event_date) as thn, MONTH(event_date) as bln, COUNT(*) as total 
                                 FROM documentation 
                                 GROUP BY YEAR(event_date), MONTH(event_date) 
                                 ORDER BY thn ASC, bln ASC")->fetchAll();
    
    $data_by_year = [];
    foreach ($monthly_data as $row) {
        $data_by_year[$row['thn']][$row['bln'] - 1] = (int)$row['total'];
    }

    $idx = 0;
    foreach ($data_by_year as $year => $vals) {
        $year_data = array_fill(0, 12, 0);
        foreach ($vals as $m => $v) $year_data[$m] = $v;
        
        $color = $colors[$idx % count($colors)];
        $chart_datasets[] = [
            'label' => "Tahun $year",
            'data' => $year_data,
            'backgroundColor' => $color . 'CC', // CC is ~80% opacity for bars
            'borderRadius' => 4
        ];
        $idx++;
    }
} else {
    $monthly_data = $pdo->query("SELECT MONTH(event_date) as bln, COUNT(*) as total 
                                 FROM documentation 
                                 WHERE YEAR(event_date) = '$filter_year' 
                                 GROUP BY MONTH(event_date)")->fetchAll();
    
    $chart_monthly = array_fill(0, 12, 0);
    foreach ($monthly_data as $row) {
        $chart_monthly[$row['bln'] - 1] = (int)$row['total'];
    }
    
    $chart_datasets[] = [
        'label' => "Jumlah Kegiatan ($filter_year)",
        'data' => $chart_monthly,
        'backgroundColor' => 'rgba(39, 174, 96, 0.8)',
        'borderRadius' => 4
    ];
}

// Summary Rektorat
$where_year_att = ($filter_year === 'all') ? "" : "AND documentation_id IN (SELECT id FROM documentation WHERE YEAR(event_date) = '$filter_year')";
$summary_rek = $pdo->query("SELECT position, COUNT(*) as total FROM documentation_attendance 
                            WHERE level = 'Rektorat' $where_year_att
                            GROUP BY position")->fetchAll(PDO::FETCH_KEY_PAIR);

// Summary Fakultas
$summary_fak = $pdo->query("SELECT position, COUNT(*) as total FROM documentation_attendance 
                            WHERE level = 'Fakultas' $where_year_att
                            GROUP BY position")->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Foto/Video | Humas Hub</title>
    <link rel="stylesheet" href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) == 'admin') ? '../' : '../../'; ?>assets/themes/<?php echo get_setting('app_theme', 'default'); ?>/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .report-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px; }
        @media (max-width: 900px) { .report-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body data-theme="<?php echo get_setting('theme_mode', 'light'); ?>">
    <?php include '../common/sidebar.php'; ?>

    <div class="main-content">
        <header class="content-header" style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px;">
            <div>
                <h1 class="main-title">Laporan Dokumentasi</h1>
                <p class="sub-title">Ringkasan statistik arsip foto dan video kegiatan.</p>
            </div>
            <button onclick="window.print()" class="btn btn-primary no-print"><i class="fas fa-print"></i> Cetak Laporan</button>
        </header>

        <form method="GET" class="filter-bar no-print" style="background: white; padding: 20px; border-radius: 15px; margin-bottom: 30px; display: flex; gap: 15px; align-items: flex-end; box-shadow: var(--shadow);">
            <div class="filter-group">
                <label style="display: block; font-size: 12px; margin-bottom: 5px;">Pilih Tahun</label>
                <select name="year" class="stitch-select" onchange="this.form.submit()">
                    <option value="all" <?php echo $filter_year === 'all' ? 'selected' : ''; ?>>Semua Tahun</option>
                    <?php foreach ($available_years as $y): ?>
                        <option value="<?php echo $y; ?>" <?php echo $filter_year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>

        <!-- Grafik Bulanan -->
        <div class="card" style="margin-bottom: 30px;">
            <h3 style="font-size: 16px; margin-bottom: 20px; color: var(--navy);">Statistik Kegiatan Bulanan (<?php echo ($filter_year === 'all') ? 'Semua Tahun' : 'Tahun ' . $filter_year; ?>)</h3>
            <div style="height: 300px;">
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>

        <div class="report-grid">
            <!-- Lokasi -->
            <div class="card">
                <h3 style="font-size: 16px; margin-bottom: 20px; color: var(--navy);">Distribusi Lokasi</h3>
                <div style="height: 250px; display: flex; justify-content: center;">
                    <canvas id="locChart"></canvas>
                </div>
            </div>
            
            <div class="card">
                <h3 style="font-size: 16px; margin-bottom: 20px; color: var(--navy);">Tabel Lokasi</h3>
                <table class="stitch-table">
                    <thead>
                        <tr><th>Jenis Lokasi</th><th>Total Kegiatan</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>Internal Kampus</td><td><?php echo $summary_loc['Internal Kampus'] ?? 0; ?></td></tr>
                        <tr><td>Lokal Daerah</td><td><?php echo $summary_loc['Lokal Daerah'] ?? 0; ?></td></tr>
                        <tr><td>Nasional</td><td><?php echo $summary_loc['Nasional'] ?? 0; ?></td></tr>
                        <tr><td>Internasional</td><td><?php echo $summary_loc['Internasional'] ?? 0; ?></td></tr>
                        <tr style="font-weight: bold; background: #f8fafc;"><td>Total</td><td><?php echo $total_kegiatan; ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="report-grid">
            <!-- Rektorat -->
            <div class="card">
                <h3 style="font-size: 16px; margin-bottom: 20px; color: var(--navy);">Kehadiran Pimpinan Rektorat</h3>
                <div style="height: 250px; display: flex; justify-content: center;">
                    <canvas id="rekChart"></canvas>
                </div>
            </div>

            <!-- Fakultas -->
            <div class="card">
                <h3 style="font-size: 16px; margin-bottom: 20px; color: var(--navy);">Kehadiran Pimpinan Fakultas</h3>
                <div style="height: 250px; display: flex; justify-content: center;">
                    <canvas id="fakChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
    // 1. Monthly Chart (Bar)
    new Chart(document.getElementById('monthlyChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: <?php echo json_encode($chart_datasets); ?>
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });

    // 2. Location Chart (Doughnut)
    new Chart(document.getElementById('locChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Internal', 'Lokal', 'Nasional', 'Internasional'],
            datasets: [{
                data: [
                    <?php echo $summary_loc['Internal Kampus'] ?? 0; ?>,
                    <?php echo $summary_loc['Lokal Daerah'] ?? 0; ?>,
                    <?php echo $summary_loc['Nasional'] ?? 0; ?>,
                    <?php echo $summary_loc['Internasional'] ?? 0; ?>
                ],
                backgroundColor: ['#3498db', '#2ecc71', '#f1c40f', '#e74c3c'],
                borderWidth: 0
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });

    // 3. Rektorat Chart (Pie)
    new Chart(document.getElementById('rekChart').getContext('2d'), {
        type: 'pie',
        data: {
            labels: <?php echo json_encode(array_keys($summary_rek)); ?>,
            datasets: [{
                data: <?php echo json_encode(array_values($summary_rek)); ?>,
                backgroundColor: ['#8e44ad', '#2980b9', '#16a085', '#d35400', '#c0392b', '#7f8c8d'],
                borderWidth: 0
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { boxWidth: 12, font: {size: 10} } } } }
    });

    // 4. Fakultas Chart (Pie)
    new Chart(document.getElementById('fakChart').getContext('2d'), {
        type: 'pie',
        data: {
            labels: <?php echo json_encode(array_keys($summary_fak)); ?>,
            datasets: [{
                data: <?php echo json_encode(array_values($summary_fak)); ?>,
                backgroundColor: ['#1abc9c', '#f39c12', '#34495e', '#e67e22', '#9b59b6', '#bdc3c7', '#2c3e50', '#27ae60'],
                borderWidth: 0
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { boxWidth: 12, font: {size: 10} } } } }
    });
    </script>
    
    <style>
        @media print {
            .sidebar, .no-print { display: none !important; }
            .main-content { margin-left: 0 !important; padding: 0 !important; }
            .card { box-shadow: none !important; border: 1px solid #ddd; break-inside: avoid; }
        }
    </style>
</body>
</html>
