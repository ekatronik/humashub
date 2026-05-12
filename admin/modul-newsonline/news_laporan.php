<?php
// admin/news_laporan.php
require_once __DIR__ . '/../../includes/auth.php';
checkAccess(['Super Admin', 'Pranata Humas', 'Operator Berita Online']);

// 1. Filter Tahun
$filter_year = $_GET['year'] ?? date('Y');

// Ambil daftar tahun yang tersedia
$years_data = $pdo->query("SELECT DISTINCT YEAR(news_date) as yr FROM news_online ORDER BY yr DESC")->fetchAll();
$available_years = array_column($years_data, 'yr');
if (empty($available_years)) $available_years = [date('Y')];

// 2. Query Data Grafik Bulanan
$chart_datasets = [];
$months = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
$colors = ['#27ae60', '#e67e22', '#3498db', '#9b59b6', '#e74c3c', '#f1c40f', '#1abc9c', '#34495e'];

if ($filter_year === 'all') {
    $monthly_data = $pdo->query("SELECT YEAR(news_date) as thn, MONTH(news_date) as bln, COUNT(*) as total 
                                 FROM news_online 
                                 GROUP BY YEAR(news_date), MONTH(news_date) 
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
            'backgroundColor' => $color . '22',
            'fill' => false,
            'tension' => 0.3
        ];
        $idx++;
    }
} else {
    $monthly_data = $pdo->query("SELECT MONTH(news_date) as bln, COUNT(*) as total 
                                 FROM news_online 
                                 WHERE YEAR(news_date) = '$filter_year' 
                                 GROUP BY MONTH(news_date)")->fetchAll();
    
    $chart_monthly = array_fill(0, 12, 0);
    foreach ($monthly_data as $row) {
        $chart_monthly[$row['bln'] - 1] = (int)$row['total'];
    }
    
    $chart_datasets[] = [
        'label' => "Jumlah Berita ($filter_year)",
        'data' => $chart_monthly,
        'borderColor' => '#27ae60',
        'backgroundColor' => 'rgba(39, 174, 96, 0.1)',
        'fill' => true,
        'tension' => 0.3
    ];
}

// 3. Data berdasarkan Media (Filter by year if not 'all')
$where_year = ($filter_year === 'all') ? "" : "WHERE YEAR(n.news_date) = '$filter_year'";
$media_stats = $pdo->query("SELECT m.media_name, COUNT(*) as total 
                            FROM news_online n 
                            JOIN media m ON n.media_id = m.id 
                            $where_year
                            GROUP BY n.media_id 
                            ORDER BY total DESC LIMIT 5")->fetchAll();

// 4. Data berdasarkan Kategori
$category_stats = $pdo->query("SELECT cat.name, COUNT(*) as total 
                               FROM news_online n 
                               JOIN categories cat ON n.category_id = cat.id 
                               $where_year
                               GROUP BY n.category_id 
                               ORDER BY total DESC")->fetchAll();

// 5. Data berdasarkan Sumber
$source_stats = $pdo->query("SELECT source_type, COUNT(*) as total 
                             FROM news_online 
                             $where_year
                             GROUP BY source_type")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Berita Online | Humas Hub</title>
    <link rel="stylesheet" href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) == 'admin') ? '../' : '../../'; ?>assets/themes/<?php echo get_setting('app_theme', 'default'); ?>/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body data-theme="<?php echo get_setting('theme_mode', 'light'); ?>">
    <?php include '../common/sidebar.php'; ?>

    <div class="main-content">
        <header style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-end;">
            <div>
                <h1 class="main-title">Laporan Analisis Berita Online</h1>
                <p class="sub-title">Visualisasi data publikasi media daring (<?php echo ($filter_year === 'all') ? 'Semua Tahun' : 'Tahun ' . $filter_year; ?>).</p>
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

        <div class="grid-2">
            <!-- Grafik Bulanan -->
            <div class="card">
                <h3 style="margin-bottom: 20px;">Trend Publikasi Bulanan</h3>
                <div style="height: 300px;">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>

            <!-- Grafik Sumber Berita -->
            <div class="card">
                <h3 style="margin-bottom: 20px;">Distribusi Sumber Berita</h3>
                <div style="height: 300px; display: flex; justify-content: center;">
                    <canvas id="sourceChart"></canvas>
                </div>
            </div>

            <!-- Grafik per Media -->
            <div class="card">
                <h3 style="margin-bottom: 20px;">Top 5 Media Daring</h3>
                <div style="height: 300px;">
                    <canvas id="mediaChart"></canvas>
                </div>
            </div>

            <!-- Grafik per Kategori -->
            <div class="card">
                <h3 style="margin-bottom: 20px;">Kategori Berita Populer</h3>
                <div style="height: 300px;">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
    // 1. Line Chart
    new Chart(document.getElementById('monthlyChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: <?php echo json_encode($chart_datasets); ?>
        },
        options: { maintainAspectRatio: false }
    });

    // 2. Pie Chart
    new Chart(document.getElementById('sourceChart'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($source_stats, 'source_type')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($source_stats, 'total')); ?>,
                backgroundColor: ['#2ecc71', '#e67e22']
            }]
        },
        options: { maintainAspectRatio: false }
    });

    // 3. Bar Chart Media
    new Chart(document.getElementById('mediaChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($media_stats, 'media_name')); ?>,
            datasets: [{
                label: 'Berita',
                data: <?php echo json_encode(array_column($media_stats, 'total')); ?>,
                backgroundColor: '#3498db'
            }]
        },
        options: { maintainAspectRatio: false, indexAxis: 'y' }
    });

    // 4. Bar Chart Kategori
    new Chart(document.getElementById('categoryChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($category_stats, 'name')); ?>,
            datasets: [{
                label: 'Berita',
                data: <?php echo json_encode(array_column($category_stats, 'total')); ?>,
                backgroundColor: '#9b59b6'
            }]
        },
        options: { maintainAspectRatio: false }
    });
    </script>
</body>
</html>
