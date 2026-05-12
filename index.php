<?php
// index.php (Command Center Dashboard)
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/settings_helper.php';

// Load global settings
load_settings($pdo);

// Maintenance Mode Check
if (get_setting('maintenance_mode', '0') == '1') {
    die("Sistem sedang dalam pemeliharaan. Silakan kembali lagi nanti.");
}

// Fetch data for dashboard
$news = $pdo->query("SELECT n.*, m.media_name 
                    FROM news_online n 
                    LEFT JOIN media m ON n.media_id = m.id 
                    ORDER BY n.news_date DESC LIMIT 5")->fetchAll();
$clippings = $pdo->query("SELECT c.*, cat.name as category_name, m.media_name 
                          FROM clippings c 
                          LEFT JOIN categories cat ON c.category_id = cat.id 
                          LEFT JOIN media m ON c.media_id = m.id 
                          ORDER BY c.clipping_date DESC LIMIT 5")->fetchAll();
$photos = $pdo->query("SELECT * FROM documentation ORDER BY event_date DESC LIMIT 4")->fetchAll();

$theme = get_setting('app_theme', 'default');
$mode = get_setting('theme_mode', 'light');
$primary_color = get_setting('theme_primary_color', '#d32f2f');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo get_setting('app_name', 'Humas Hub UIN Ar-Raniry'); ?> | Command Center</title>
    <link rel="stylesheet" href="assets/themes/<?php echo $theme; ?>/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --primary: <?php echo $primary_color; ?>; }
        
        .cc-container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .cc-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: var(--bg-card); padding: 20px 30px; border-radius: var(--border-radius); border: 1px solid var(--border-color); box-shadow: var(--shadow); }
        .cc-brand { display: flex; align-items: center; gap: 15px; }
        .cc-brand img { height: 50px; }
        .cc-brand h1 { font-size: 24px; font-weight: 800; color: var(--navy); text-transform: uppercase; letter-spacing: 1px; }
        
        .cc-time { text-align: right; }
        .cc-time h2 { font-size: 28px; font-weight: 900; color: var(--primary); margin: 0; }
        .cc-time p { font-size: 14px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; }

        .hero-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .hero-stat-card { background: var(--bg-card); padding: 25px; border-radius: var(--border-radius); border: 1px solid var(--border-color); border-bottom: 4px solid var(--primary); text-align: center; box-shadow: var(--shadow); }
        .hero-stat-card i { font-size: 30px; color: var(--primary); margin-bottom: 15px; opacity: 0.8; }
        .hero-stat-card h3 { font-size: 36px; font-weight: 900; color: var(--text-main); line-height: 1; margin-bottom: 10px; }
        .hero-stat-card p { font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; }

        .main-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
        
        .widget-title { font-size: 16px; font-weight: 800; color: var(--navy); text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; border-bottom: 2px solid var(--primary); padding-bottom: 10px; width: fit-content; }
        
        .news-item { display: flex; gap: 20px; padding: 15px; background: var(--bg-card); border-radius: var(--border-radius); border: 1px solid var(--border-color); margin-bottom: 15px; transition: 0.2s; }
        .news-item:hover { transform: translateX(5px); border-color: var(--primary); }
        .news-date { min-width: 70px; text-align: center; padding: 10px; background: var(--bg-body); border-radius: var(--border-radius); display: flex; flex-direction: column; justify-content: center; height: fit-content; }
        .news-date span:first-child { font-size: 24px; font-weight: 900; color: var(--primary); line-height: 1; }
        .news-date span:last-child { font-size: 10px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; }
        .news-content h4 { font-size: 16px; font-weight: 700; color: var(--text-main); margin-bottom: 5px; }
        .news-content p { font-size: 13px; color: var(--text-muted); line-height: 1.4; }

        .weather-widget { background: linear-gradient(135deg, var(--navy) 0%, #1a252f 100%); color: white; padding: 25px; border-radius: var(--border-radius); margin-bottom: 30px; position: relative; overflow: hidden; }
        .weather-widget::before { content: "\f6c4"; font-family: "Font Awesome 6 Free"; font-weight: 900; position: absolute; right: -20px; bottom: -20px; font-size: 150px; opacity: 0.05; }
        .weather-temp { font-size: 48px; font-weight: 900; margin-bottom: 5px; }
        .weather-desc { font-size: 18px; font-weight: 600; text-transform: capitalize; margin-bottom: 15px; color: var(--secondary); }
        .weather-info { display: flex; gap: 20px; font-size: 13px; opacity: 0.8; }

        .prayer-widget { background: var(--bg-card); padding: 25px; border-radius: var(--border-radius); border: 1px solid var(--border-color); box-shadow: var(--shadow); margin-bottom: 30px; }
        .prayer-list { list-style: none; }
        .prayer-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px dashed var(--border-color); font-weight: 700; }
        .prayer-item:last-child { border: none; }
        .prayer-item span:first-child { color: var(--text-muted); text-transform: uppercase; font-size: 12px; }
        .prayer-item span:last-child { color: var(--primary); font-size: 15px; }
        .prayer-item.active { background: rgba(211, 47, 47, 0.05); border-left: 3px solid var(--primary); padding-left: 10px; margin-left: -10px; margin-right: -10px; padding-right: 10px; }

        @media (max-width: 992px) {
            .hero-stats { grid-template-columns: 1fr 1fr; }
            .main-grid { grid-template-columns: 1fr; }
            .cc-header { flex-direction: column; text-align: center; gap: 20px; }
            .cc-time { text-align: center; }
        }
    </style>
</head>
<body data-theme="<?php echo $mode; ?>">
    <div class="cc-container">
        <!-- Header Section -->
        <header class="cc-header">
            <div class="cc-brand">
                <i class="fas fa-university fa-3x" style="color: var(--primary);"></i>
                <div>
                    <h1><?php echo get_setting('app_name', 'HUMAS HUB UIN'); ?></h1>
                    <p style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Pusat Dokumentasi & Command Center Digital</p>
                </div>
            </div>
            <div class="cc-time">
                <p id="current-date"><?php echo date('l, d F Y'); ?></p>
                <h2 id="current-clock"><?php echo date('H:i:s'); ?></h2>
            </div>
        </header>

        <!-- Hero Metrics Row -->
        <div class="hero-stats">
            <div class="hero-stat-card">
                <i class="fas fa-user-graduate"></i>
                <h3><?php echo get_setting('stat_students', '0'); ?></h3>
                <p>Mahasiswa Aktif</p>
            </div>
            <div class="hero-stat-card">
                <i class="fas fa-chalkboard-teacher"></i>
                <h3><?php echo get_setting('stat_lecturers', '0'); ?></h3>
                <p>Dosen & Pengajar</p>
            </div>
            <div class="hero-stat-card">
                <i class="fas fa-building-columns"></i>
                <h3><?php echo get_setting('stat_faculties', '0'); ?></h3>
                <p>Fakultas</p>
            </div>
            <div class="hero-stat-card">
                <i class="fas fa-users"></i>
                <h3><?php echo get_setting('stat_alumni', '0'); ?></h3>
                <p>Total Alumni</p>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="main-grid">
            <!-- Left Column: News & Clippings -->
            <div>
                <h3 class="widget-title"><i class="fas fa-newspaper"></i> Berita Utama Terkini</h3>
                <div class="news-list">
                    <?php if (empty($news)): ?>
                        <div class="card" style="text-align: center; color: var(--text-muted);">Belum ada berita online terbaru.</div>
                    <?php else: ?>
                        <?php foreach ($news as $n): ?>
                        <div class="news-item">
                            <div class="news-date">
                                <span><?php echo date('d', strtotime($n['news_date'])); ?></span>
                                <span><?php echo date('M', strtotime($n['news_date'])); ?></span>
                            </div>
                            <div class="news-content">
                                <h4 style="margin-bottom: 8px;"><?php echo $n['title']; ?></h4>
                                <div style="display: flex; gap: 15px; font-size: 11px; font-weight: 700;">
                                    <span style="color: var(--primary);"><i class="fas fa-link"></i> <?php echo $n['media_name'] ?? 'UIN News'; ?></span>
                                    <a href="<?php echo $n['news_link']; ?>" target="_blank" style="color: var(--secondary); text-decoration: none;">BACA BERITA LENGKAP <i class="fas fa-external-link-alt"></i></a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <h3 class="widget-title" style="margin-top: 40px;"><i class="fas fa-copy"></i> Kliping Koran Digital</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <?php if (empty($clippings)): ?>
                        <div class="card" style="grid-column: span 2; text-align: center; color: var(--text-muted);">Belum ada arsip kliping terbaru.</div>
                    <?php else: ?>
                        <?php foreach ($clippings as $c): ?>
                        <div class="card" style="padding: 15px; margin-bottom: 0;">
                            <div style="font-size: 10px; font-weight: 800; color: var(--primary); text-transform: uppercase; margin-bottom: 10px;">
                                <?php echo $c['category_name']; ?> | <?php echo $c['media_name']; ?>
                            </div>
                            <h4 style="font-size: 14px; margin-bottom: 10px;"><?php echo $c['title']; ?></h4>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 11px; color: var(--text-muted);"><i class="far fa-calendar"></i> <?php echo date('d M Y', strtotime($c['clipping_date'])); ?></span>
                                <a href="uploads/clippings/<?php echo $c['file_path']; ?>" target="_blank" class="btn-circle" style="width: 30px; height: 30px;"><i class="fas fa-eye"></i></a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column: Widgets -->
            <aside>
                <!-- Weather Widget -->
                <div class="weather-widget">
                    <p style="font-size: 12px; font-weight: 800; text-transform: uppercase; opacity: 0.7; margin-bottom: 10px;">Iklim Kampus Saat Ini</p>
                    <div id="weather-box">
                        <div class="weather-temp">--&deg;C</div>
                        <div class="weather-desc">Memuat data cuaca...</div>
                    </div>
                    <div class="weather-info">
                        <span><i class="fas fa-location-dot"></i> <?php echo get_setting('api_prayer_city', 'Banda Aceh'); ?></span>
                        <span><i class="fas fa-wind"></i> <span id="wind-speed">0</span> km/h</span>
                    </div>
                </div>

                <!-- Prayer Widget -->
                <div class="prayer-widget">
                    <h3 class="widget-title"><i class="fas fa-clock"></i> Jadwal Shalat</h3>
                    <ul class="prayer-list">
                        <li class="prayer-item"><span>Subuh</span> <span>05:12</span></li>
                        <li class="prayer-item"><span>Dzuhur</span> <span>12:34</span></li>
                        <li class="prayer-item"><span>Ashar</span> <span>15:56</span></li>
                        <li class="prayer-item active"><span>Maghrib</span> <span>18:45</span></li>
                        <li class="prayer-item"><span>Isya</span> <span>19:58</span></li>
                    </ul>
                    <p style="font-size: 10px; color: var(--text-muted); text-align: center; margin-top: 15px; font-weight: 700; text-transform: uppercase;">Zona Waktu: WIB</p>
                </div>

                <!-- Quick Contacts -->
                <div class="card">
                    <h3 class="widget-title"><i class="fas fa-phone"></i> Kontak Penting</h3>
                    <div style="font-size: 13px; line-height: 1.6;">
                        <div style="margin-bottom: 10px; border-bottom: 1px solid var(--border-color); padding-bottom: 5px;">
                            <strong style="display: block; font-size: 11px; color: var(--primary);">Humas Rektorat</strong>
                            <span>0811-687-XXX</span>
                        </div>
                        <div style="margin-bottom: 10px; border-bottom: 1px solid var(--border-color); padding-bottom: 5px;">
                            <strong style="display: block; font-size: 11px; color: var(--primary);">Call Center Akademik</strong>
                            <span>0852-600-XXX</span>
                        </div>
                        <div>
                            <strong style="display: block; font-size: 11px; color: var(--primary);">Email Resmi</strong>
                            <span>humas@ar-raniry.ac.id</span>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        <footer style="margin-top: 50px; text-align: center; padding: 40px; border-top: 1px solid var(--border-color);">
            <p style="font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                <?php echo get_setting('app_footer', '&copy; 2026 UIN Ar-Raniry Banda Aceh'); ?>
            </p>
            <div style="margin-top: 15px; display: flex; justify-content: center; gap: 15px; color: var(--text-muted);">
                <a href="admin/login.php" style="color: inherit; text-decoration: none; font-size: 12px; font-weight: 700;">ADMIN LOGIN</a>
            </div>
        </footer>
    </div>

    <script>
        // Update Clock
        function updateClock() {
            const now = new Date();
            const clock = document.getElementById('current-clock');
            clock.textContent = now.toLocaleTimeString('id-ID', { hour12: false });
        }
        setInterval(updateClock, 1000);

        // Fetch Weather (Placeholder logic)
        async function fetchWeather() {
            const apiKey = '<?php echo get_setting('api_weather_key'); ?>';
            const city = '<?php echo get_setting('api_prayer_city', 'Banda Aceh'); ?>';
            
            if (!apiKey) {
                document.querySelector('.weather-temp').textContent = '29°C';
                document.querySelector('.weather-desc').textContent = 'Cerah Berawan';
                document.getElementById('wind-speed').textContent = '12';
                return;
            }

            try {
                const response = await fetch(`https://api.openweathermap.org/data/2.5/weather?q=${city}&appid=${apiKey}&units=metric`);
                const data = await response.json();
                document.querySelector('.weather-temp').textContent = Math.round(data.main.temp) + '°C';
                document.querySelector('.weather-desc').textContent = data.weather[0].description;
                document.getElementById('wind-speed').textContent = data.wind.speed;
            } catch (error) {
                console.error('Weather error:', error);
            }
        }
        fetchWeather();
    </script>
</body>
</html>
