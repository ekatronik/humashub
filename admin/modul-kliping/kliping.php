<?php
// admin/kliping.php (Dashboard Modul Kliping)
require_once __DIR__ . '/../../includes/auth.php';
checkAccess(['Super Admin', 'Pranata Humas', 'Operator Kliping']);

// Statistik
$total_kliping = $pdo->query("SELECT COUNT(*) FROM clippings")->fetchColumn();
$total_media = $pdo->query("SELECT COUNT(*) FROM media WHERE media_type = 'cetak'")->fetchColumn();
$total_kategori = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();

// Grafik Bulanan (Semua Tahun yang ada data)
$monthly_data = $pdo->query("SELECT YEAR(clipping_date) as thn, MONTH(clipping_date) as bln, COUNT(*) as total 
                             FROM clippings 
                             GROUP BY thn, bln 
                             ORDER BY thn ASC, bln ASC")->fetchAll();

$months = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
$colors = ['#006837', '#fbb03b', '#3498db', '#e74c3c', '#9b59b6'];
$chart_datasets = [];
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
        'backgroundColor' => $color . '22',
        'fill' => false,
        'tension' => 0.4
    ];
    $idx++;
}
if (empty($chart_datasets)) {
    $chart_datasets[] = ['label' => 'No Data', 'data' => array_fill(0, 12, 0)];
}

// 5 Kliping Terakhir (Berdasarkan Tanggal)
$recent_clippings = $pdo->query("SELECT c.*, m.media_name, GROUP_CONCAT(cat.name SEPARATOR ', ') as category_names 
                                 FROM clippings c 
                                 LEFT JOIN media m ON c.media_id = m.id 
                                 LEFT JOIN clipping_category_rel rel ON c.id = rel.clipping_id
                                 LEFT JOIN categories cat ON rel.category_id = cat.id 
                                 GROUP BY c.id
                                 ORDER BY c.clipping_date DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Kliping | Humas Hub</title>
    <link rel="stylesheet" href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) == 'admin') ? '../' : '../../'; ?>assets/themes/<?php echo get_setting('app_theme', 'default'); ?>/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body data-theme="<?php echo get_setting('theme_mode', 'light'); ?>">
    <?php include '../common/sidebar.php'; ?>

    <div class="main-content">
        <header style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1 style="font-weight: 700; color: var(--dark);">Dashboard Arsip Kliping</h1>
                <p style="color: var(--text-muted);">Ringkasan data dan aktivitas modul kliping cetak.</p>
            </div>
            <a href="kliping_tambah.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Tambah Kliping Baru
            </a>
        </header>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card green">
                <div class="stat-icon">
                    <i class="far fa-file-alt"></i>
                </div>
                <div class="stat-info">
                    <p>Total Kliping</p>
                    <h3><?php echo number_format($total_kliping); ?> <i class="fas fa-chart-line" style="font-size: 14px; opacity: 0.7;"></i></h3>
                </div>
            </div>
            <div class="stat-card blue">
                <div class="stat-icon">
                    <i class="fas fa-globe"></i>
                </div>
                <div class="stat-info">
                    <p>Media Terdaftar</p>
                    <h3><?php echo $total_media; ?> <i class="fas fa-bolt" style="font-size: 14px; opacity: 0.7;"></i></h3>
                </div>
            </div>
            <div class="stat-card orange">
                <div class="stat-icon">
                    <i class="fas fa-tags"></i>
                </div>
                <div class="stat-info">
                    <p>Kategori Aktif</p>
                    <h3><?php echo $total_kategori; ?> <i class="fas fa-arrow-up" style="font-size: 14px; opacity: 0.7;"></i></h3>
                </div>
            </div>
        </div>

        <div class="dashboard-layout" style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-top: 30px;">
            <!-- Kolom Kiri: 5 Kliping Terakhir -->
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <h3 style="font-size: 18px; color: var(--navy);">Update Terkini</h3>
                    <a href="kliping_daftar.php" class="btn btn-primary" style="padding: 8px 16px; font-size: 12px;">SEMUA DATA</a>
                </div>
                <div class="table-wrapper" style="margin-top: 0; border: none; box-shadow: none; border-radius: 0;">
                    <table class="stitch-table">
                        <thead>
                            <tr>
                                <th>Informasi Berita</th>
                                <th>Media</th>
                                <th>Kategori</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_clippings as $rc): ?>
                            <tr>
                                <td>
                                    <div class="col-judul" style="font-size: 14px;"><?php echo $rc['title']; ?></div>
                                    <div class="col-meta" style="font-size: 11px;">
                                        <i class="fas fa-calendar-alt"></i> <?php echo tgl_indo($rc['clipping_date']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: var(--navy); font-size: 13px;">
                                        <?php echo $rc['media_name'] ?? '<span style="color:#94a3b8; font-weight:400; font-style:italic;">Media dihapus</span>'; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($rc['category_names']): ?>
                                        <span class="badge badge-soft-1"><?php echo $rc['category_names']; ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-soft-1">Uncategorized</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Kolom Kanan: Grafik Bulanan -->
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <h3 style="font-size: 18px; color: var(--navy);">Statistik</h3>
                </div>
                <div style="height: 250px;">
                    <canvas id="monthlyChart"></canvas>
                </div>
                <div style="margin-top: 20px; text-align: center;">
                    <a href="kliping_laporan.php" style="font-size: 12px; color: var(--primary); text-decoration: none; font-weight: 700;">LIHAT LAPORAN <i class="fas fa-chevron-right"></i></a>
                </div>
            </div>
        </div>
    </div>

    <script>
    const ctxMonthly = document.getElementById('monthlyChart').getContext('2d');
    new Chart(ctxMonthly, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: <?php echo json_encode($chart_datasets); ?>
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false,
            plugins: { 
                legend: { 
                    display: true,
                    position: 'bottom',
                    labels: { boxWidth: 12, font: { size: 10 } }
                } 
            },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
    </script>
</body>
</html>
