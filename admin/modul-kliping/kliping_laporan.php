<?php
// admin/kliping_laporan.php
require_once __DIR__ . '/../../includes/auth.php';
checkAccess(['Super Admin', 'Pranata Humas', 'Operator Kliping']);

// 1. Filter Tahun
$filter_year = $_GET['year'] ?? date('Y');

// Ambil daftar tahun yang tersedia
$years_data = $pdo->query("SELECT DISTINCT YEAR(clipping_date) as yr FROM clippings ORDER BY yr DESC")->fetchAll();
$available_years = array_column($years_data, 'yr');
if (empty($available_years)) $available_years = [date('Y')];

// 2. Query Data Grafik Bulanan
$chart_datasets = [];
$months = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
$colors = ['#006837', '#fbb03b', '#3498db', '#e74c3c', '#9b59b6', '#2ecc71', '#f1c40f', '#1abc9c', '#34495e'];

if ($filter_year === 'all') {
    $monthly_data = $pdo->query("SELECT YEAR(clipping_date) as thn, MONTH(clipping_date) as bln, COUNT(*) as total 
                                 FROM clippings 
                                 GROUP BY YEAR(clipping_date), MONTH(clipping_date) 
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
            'borderColor' => $color,
            'backgroundColor' => $color . '22', // 22 is ~13% opacity
            'fill' => false,
            'tension' => 0.4
        ];
        $idx++;
    }
} else {
    $monthly_data = $pdo->query("SELECT MONTH(clipping_date) as bln, COUNT(*) as total 
                                 FROM clippings 
                                 WHERE YEAR(clipping_date) = '$filter_year' 
                                 GROUP BY MONTH(clipping_date)")->fetchAll();
    
    $chart_monthly = array_fill(0, 12, 0);
    foreach ($monthly_data as $row) {
        $chart_monthly[$row['bln'] - 1] = (int)$row['total'];
    }
    
    $chart_datasets[] = [
        'label' => "Jumlah Kliping ($filter_year)",
        'data' => $chart_monthly,
        'borderColor' => '#006837',
        'backgroundColor' => 'rgba(0, 104, 55, 0.1)',
        'fill' => true,
        'tension' => 0.4
    ];
}

// 3. Grafik Berdasarkan Media
$where_year_media = ($filter_year === 'all') ? "" : "WHERE YEAR(c.clipping_date) = '$filter_year'";
$media_stats = $pdo->query("SELECT IFNULL(m.media_name, 'Media Lainnya') as media_name, COUNT(c.id) as total 
                            FROM clippings c 
                            LEFT JOIN media m ON c.media_id = m.id 
                            $where_year_media
                            GROUP BY c.media_id 
                            ORDER BY total DESC")->fetchAll();

// 4. Grafik Berdasarkan Kategori
$where_year_cat = ($filter_year === 'all') ? "" : "WHERE YEAR(c.clipping_date) = '$filter_year'";
$cat_stats = $pdo->query("SELECT IFNULL(cat.name, 'Tanpa Kategori') as name, COUNT(DISTINCT c.id) as total 
                          FROM clippings c 
                          LEFT JOIN clipping_category_rel rel ON c.id = rel.clipping_id 
                          LEFT JOIN categories cat ON rel.category_id = cat.id 
                          $where_year_cat
                          GROUP BY cat.id 
                          ORDER BY total DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kliping | Humas Hub</title>
    <link rel="stylesheet" href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) == 'admin') ? '../' : '../../'; ?>assets/themes/<?php echo get_setting('app_theme', 'default'); ?>/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body data-theme="<?php echo get_setting('theme_mode', 'light'); ?>">
    <?php include '../common/sidebar.php'; ?>

    <div class="main-content">
        <header style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-end;">
            <div>
                <h1 style="font-weight: 700; color: var(--dark);">Laporan & Analisis Kliping</h1>
                <p style="color: var(--text-muted);">Visualisasi data pengarsipan kliping media cetak.</p>
            </div>
            <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                <label for="year" style="font-weight: 600; font-size: 14px;">Tahun:</label>
                <select name="year" id="year" class="stitch-select" onchange="this.form.submit()" style="padding: 8px 15px; border-radius: 8px; border: 1px solid #ddd;">
                    <option value="all" <?php echo $filter_year === 'all' ? 'selected' : ''; ?>>Semua Tahun</option>
                    <?php foreach ($available_years as $y): ?>
                        <option value="<?php echo $y; ?>" <?php echo $filter_year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </header>

        <div class="card" style="margin-bottom: 30px;">
            <h3>Grafik Pertumbuhan Bulanan (<?php echo ($filter_year === 'all') ? 'Perbandingan Tahun' : $filter_year; ?>)</h3>
            <div style="height: 300px;">
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>

        <div class="grid-2">
            <div class="card">
                <h3>Berdasarkan Media</h3>
                <?php if (empty($media_stats)): ?>
                    <div style="height: 300px; display: flex; align-items: center; justify-content: center; background: #f8fafc; border-radius: 12px; color: #94a3b8; font-weight: 600;">
                        <i class="fas fa-chart-pie" style="margin-right: 10px;"></i> Belum ada data media.
                    </div>
                <?php else: ?>
                    <div style="height: 300px;">
                        <canvas id="mediaChart"></canvas>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card">
                <h3>Berdasarkan Kategori</h3>
                <?php if (empty($cat_stats)): ?>
                    <div style="height: 300px; display: flex; align-items: center; justify-content: center; background: #f8fafc; border-radius: 12px; color: #94a3b8; font-weight: 600;">
                        <i class="fas fa-chart-bar" style="margin-right: 10px;"></i> Belum ada data kategori.
                    </div>
                <?php else: ?>
                    <div style="height: 300px;">
                        <canvas id="categoryChart"></canvas>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    // 1. Monthly Chart
    const ctxMonthly = document.getElementById('monthlyChart').getContext('2d');
    new Chart(ctxMonthly, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: <?php echo json_encode($chart_datasets); ?>
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    // 2. Media Chart
    <?php if (!empty($media_stats)): ?>
    const ctxMedia = document.getElementById('mediaChart').getContext('2d');
    new Chart(ctxMedia, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($media_stats, 'media_name')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($media_stats, 'total')); ?>,
                backgroundColor: ['#006837', '#fbb03b', '#3498db', '#e74c3c', '#9b59b6']
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
    <?php endif; ?>

    // 3. Category Chart
    <?php if (!empty($cat_stats)): ?>
    const ctxCat = document.getElementById('categoryChart').getContext('2d');
    new Chart(ctxCat, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($cat_stats, 'name')); ?>,
            datasets: [{
                label: 'Total Kliping',
                data: <?php echo json_encode(array_column($cat_stats, 'total')); ?>,
                backgroundColor: [
                    'rgba(251, 176, 59, 0.8)',
                    'rgba(52, 152, 219, 0.8)',
                    'rgba(46, 204, 113, 0.8)',
                    'rgba(155, 89, 182, 0.8)',
                    'rgba(231, 76, 60, 0.8)',
                    'rgba(26, 188, 156, 0.8)',
                    'rgba(52, 73, 94, 0.8)'
                ],
                borderColor: [
                    '#fbb03b', '#3498db', '#2ecc71', '#9b59b6', '#e74c3c', '#1abc9c', '#34495e'
                ],
                borderWidth: 1,
                borderRadius: 8,
                barThickness: 40
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    padding: 12,
                    cornerRadius: 8
                }
            },
            scales: {
                y: { 
                    beginAtZero: true, 
                    ticks: { stepSize: 1, font: { weight: '600' } },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    ticks: { font: { weight: '600' } },
                    grid: { display: false }
                }
            }
        }
    });
    <?php endif; ?>
    </script>
</body>
</html>
