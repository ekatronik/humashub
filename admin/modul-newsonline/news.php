<?php
// admin/news.php (Dashboard Modul Berita Online)
require_once __DIR__ . '/../../includes/auth.php';
checkAccess(['Super Admin', 'Pranata Humas', 'Operator Berita Online']);

// Statistik
$total_news = $pdo->query("SELECT COUNT(*) FROM news_online")->fetchColumn();
$total_rilis = $pdo->query("SELECT COUNT(*) FROM news_online WHERE source_type = 'Rilis Humas'")->fetchColumn();
$total_liputan = $pdo->query("SELECT COUNT(*) FROM news_online WHERE source_type = 'Liputan Wartawan'")->fetchColumn();

// Grafik Bulanan (Semua Tahun yang ada data)
$monthly_data = $pdo->query("SELECT YEAR(news_date) as thn, MONTH(news_date) as bln, COUNT(*) as total 
                             FROM news_online 
                             GROUP BY thn, bln 
                             ORDER BY thn ASC, bln ASC")->fetchAll();

$months = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
$colors = ['#27ae60', '#e67e22', '#3498db', '#9b59b6', '#e74c3c'];
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

// 5 Berita Terakhir
$recent_news = $pdo->query("SELECT n.*, m.media_name, cat.name as category_name 
                            FROM news_online n 
                            JOIN media m ON n.media_id = m.id 
                            JOIN categories cat ON n.category_id = cat.id 
                            ORDER BY n.created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Berita Online | Humas Hub</title>
    <link rel="stylesheet" href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) == 'admin') ? '../' : '../../'; ?>assets/themes/<?php echo get_setting('app_theme', 'default'); ?>/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body data-theme="<?php echo get_setting('theme_mode', 'light'); ?>">
    <?php include '../common/sidebar.php'; ?>

    <div class="main-content">
        <header style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1 style="font-weight: 700; color: var(--dark);">Dashboard Berita Online</h1>
                <p style="color: var(--text-muted);">Ringkasan data publikasi media daring.</p>
            </div>
            <a href="news_tambah.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Tambah Berita
            </a>
        </header>

        <div class="stats-grid">
            <div class="stat-card green">
                <div class="stat-icon"><i class="fas fa-globe"></i></div>
                <div class="stat-info">
                    <p>Total Berita</p>
                    <h3><?php echo number_format($total_news); ?></h3>
                </div>
            </div>
            <div class="stat-card blue">
                <div class="stat-icon"><i class="fas fa-bullhorn"></i></div>
                <div class="stat-info">
                    <p>Rilis Humas</p>
                    <h3><?php echo number_format($total_rilis); ?></h3>
                </div>
            </div>
            <div class="stat-card orange">
                <div class="stat-icon"><i class="fas fa-microphone"></i></div>
                <div class="stat-info">
                    <p>Liputan Wartawan</p>
                    <h3><?php echo number_format($total_liputan); ?></h3>
                </div>
            </div>
        </div>

        <div class="dashboard-layout" style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-top: 30px;">
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <h3 style="font-size: 18px; color: var(--navy);">Update Berita Terkini</h3>
                    <a href="news_daftar.php" class="btn btn-primary" style="padding: 8px 16px; font-size: 12px;">SEMUA DATA</a>
                </div>
                <div class="table-wrapper" style="margin-top: 0; border: none; box-shadow: none; border-radius: 0;">
                    <table class="stitch-table">
                        <thead>
                            <tr>
                                <th>Berita</th>
                                <th>Media</th>
                                <th>Sumber</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_news as $rn): ?>
                            <tr>
                                <td>
                                    <div class="col-judul" style="font-size: 14px;"><?php echo $rn['title']; ?></div>
                                    <div class="col-meta" style="font-size: 11px;">
                                        <i class="fas fa-calendar-alt"></i> <?php echo tgl_indo($rn['news_date']); ?>
                                    </div>
                                </td>
                                <td><div style="font-weight: 600; color: var(--navy); font-size: 13px;"><?php echo $rn['media_name']; ?></div></td>
                                <td><span class="badge <?php echo $rn['source_type'] == 'Rilis Humas' ? 'badge-soft-1' : 'badge-soft-3'; ?>"><?php echo $rn['source_type']; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <h3 style="font-size: 18px; color: var(--navy); margin-bottom: 25px;">Tren Publikasi</h3>
                <div style="height: 250px;">
                    <canvas id="newsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
    const ctx = document.getElementById('newsChart').getContext('2d');
    new Chart(ctx, {
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
