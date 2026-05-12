<?php
// admin/modul-setelan/settings.php
require_once __DIR__ . '/../../includes/auth.php';
checkAccess(['Super Admin']); // Hanya Super Admin yang bisa akses

$message = "";

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'save_settings') {
        try {
            // Loop through all POST data and update settings
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            foreach ($_POST as $key => $value) {
                if ($key != 'action') {
                    $stmt->execute([$value, $key]);
                }
            }
            
            // Reload global settings to reflect immediately
            load_settings($pdo);
            $message = "Pengaturan berhasil disimpan.";
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
        }
    }
}

// Fetch all settings and organize by group
$settings = [
    'identity' => [],
    'appearance' => [],
    'modules' => [],
    'system' => [],
    'api' => [],
    'stats' => []
];

$stmt = $pdo->query("SELECT * FROM settings");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $group = $row['setting_group'];
    if (isset($settings[$group])) {
        $settings[$group][$row['setting_key']] = $row['setting_value'];
    }
}

// Fetch available themes
$available_themes = get_available_themes();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setelan Sistem | Humas Hub</title>
    <link rel="stylesheet" href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) == 'admin') ? '../' : '../../'; ?>assets/themes/<?php echo get_setting('app_theme', 'default'); ?>/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #eee; }
        .tab-btn { padding: 10px 20px; background: none; border: none; font-size: 16px; font-weight: 600; color: var(--text-muted); cursor: pointer; border-bottom: 3px solid transparent; }
        .tab-btn.active { color: var(--primary); border-bottom-color: var(--primary); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .color-picker {
            width: 100%; height: 50px; padding: 0; border: none; border-radius: 8px; cursor: pointer;
        }
        
        .switch {
            position: relative; display: inline-block; width: 60px; height: 34px;
        }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
            background-color: #ccc; transition: .4s; border-radius: 34px;
        }
        .slider:before {
            position: absolute; content: ""; height: 26px; width: 26px; left: 4px; bottom: 4px;
            background-color: white; transition: .4s; border-radius: 50%;
        }
        input:checked + .slider { background-color: #ff4d4d; }
        input:checked + .slider:before { transform: translateX(26px); }
    </style>
</head>
<body data-theme="<?php echo get_setting('theme_mode', 'light'); ?>">
    <?php include '../common/sidebar.php'; ?>

    <div class="main-content">
        <header style="margin-bottom: 30px;">
            <h1 style="font-weight: 700; color: var(--dark);">Setelan Sistem</h1>
            <p style="color: var(--text-muted);">Konfigurasi global aplikasi Humas Hub UIN Ar-Raniry</p>
        </header>

        <?php if ($message): ?>
            <div class="alert" style="background: rgba(46, 204, 113, 0.2); color: #27ae60; padding: 15px; border-radius: 12px; margin-bottom: 25px; border: 1px solid #2ecc71;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="tabs">
                <button class="tab-btn active" onclick="openTab('identity')"><i class="fas fa-id-card"></i> Identitas & Tampilan</button>
                <button class="tab-btn" onclick="openTab('modules')"><i class="fas fa-cubes"></i> Modul</button>
                <button class="tab-btn" onclick="openTab('stats')"><i class="fas fa-chart-line"></i> Statistik Kampus</button>
                <button class="tab-btn" onclick="openTab('system')"><i class="fas fa-server"></i> Sistem & API</button>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save_settings">
                
                <!-- Tab Identity & Appearance -->
                <div id="identity" class="tab-content active">
                    <div class="grid-2">
                        <div>
                            <div class="field-group">
                                <label>Nama Aplikasi</label>
                                <input type="text" name="app_name" class="stitch-select" style="width: 100%;" value="<?php echo htmlspecialchars($settings['identity']['app_name'] ?? ''); ?>" required>
                            </div>
                            <div class="field-group">
                                <label>Deskripsi Singkat</label>
                                <textarea name="app_description" rows="3" class="stitch-select" style="width: 100%; resize: vertical;"><?php echo htmlspecialchars($settings['identity']['app_description'] ?? ''); ?></textarea>
                            </div>
                            <div class="field-group">
                                <label>Teks Footer (Copyright)</label>
                                <input type="text" name="app_footer" class="stitch-select" style="width: 100%;" value="<?php echo htmlspecialchars($settings['identity']['app_footer'] ?? ''); ?>">
                            </div>
                        </div>
                        <div>
                            <div class="field-group">
                                <label>Tema Warna Utama (Primary Color)</label>
                                <input type="color" name="theme_primary_color" class="color-picker" value="<?php echo htmlspecialchars($settings['appearance']['theme_primary_color'] ?? '#0984e3'); ?>">
                                <small style="color: var(--text-muted);">Pilih warna utama untuk antarmuka aplikasi.</small>
                            </div>
                            
                            <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #eee;">
                                <h4 style="margin-bottom: 15px; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--navy);">Sistem Desain (Theme Engine)</h4>
                                <div class="field-group">
                                    <label>Gaya Desain (Theme)</label>
                                    <select name="app_theme" class="stitch-select" style="width: 100%;">
                                        <?php 
                                        $cur_theme = $settings['appearance']['app_theme'] ?? 'default';
                                        foreach ($available_themes as $dir => $info): 
                                            $sel = ($dir == $cur_theme) ? 'selected' : '';
                                        ?>
                                            <option value="<?php echo htmlspecialchars($dir); ?>" <?php echo $sel; ?>>
                                                <?php echo htmlspecialchars($info['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small style="color: var(--text-muted); margin-top: 5px; display: block;">Sistem akan memuat CSS dari folder assets/themes/.</small>
                                </div>
                                <div class="field-group" style="margin-top: 15px;">
                                    <label>Mode Tampilan</label>
                                    <select name="theme_mode" class="stitch-select" style="width: 100%;">
                                        <option value="light" <?php echo ($settings['appearance']['theme_mode'] ?? 'light') == 'light' ? 'selected' : ''; ?>>Light Mode (Terang)</option>
                                        <option value="dark" <?php echo ($settings['appearance']['theme_mode'] ?? 'light') == 'dark' ? 'selected' : ''; ?>>Dark Mode (Gelap)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Modules -->
                <div id="modules" class="tab-content">
                    <div class="grid-2">
                        <div class="field-group">
                            <label>Batas Maksimal Upload (MB)</label>
                            <input type="number" name="max_upload_size" class="stitch-select" style="width: 100%;" value="<?php echo htmlspecialchars($settings['modules']['max_upload_size'] ?? '10'); ?>" min="1" max="100">
                        </div>
                        <div class="field-group">
                            <label>Default Pagination (Baris per Halaman)</label>
                            <select name="default_pagination" class="stitch-select" style="width: 100%;">
                                <?php
                                $opts = ['10', '25', '50', '100'];
                                $cur = $settings['modules']['default_pagination'] ?? '10';
                                foreach ($opts as $o) {
                                    $sel = ($o == $cur) ? 'selected' : '';
                                    echo "<option value=\"$o\" $sel>$o Baris</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <!-- Tab Stats -->
                <div id="stats" class="tab-content">
                    <div class="grid-2">
                        <div>
                            <div class="field-group">
                                <label>Jumlah Mahasiswa Aktif</label>
                                <input type="text" name="stat_students" class="stitch-select" style="width: 100%;" value="<?php echo htmlspecialchars($settings['stats']['stat_students'] ?? ''); ?>" placeholder="Contoh: 25.000+">
                            </div>
                            <div class="field-group">
                                <label>Jumlah Dosen / Pengajar</label>
                                <input type="text" name="stat_lecturers" class="stitch-select" style="width: 100%;" value="<?php echo htmlspecialchars($settings['stats']['stat_lecturers'] ?? ''); ?>" placeholder="Contoh: 1.200+">
                            </div>
                        </div>
                        <div>
                            <div class="field-group">
                                <label>Jumlah Fakultas</label>
                                <input type="text" name="stat_faculties" class="stitch-select" style="width: 100%;" value="<?php echo htmlspecialchars($settings['stats']['stat_faculties'] ?? ''); ?>" placeholder="Contoh: 9">
                            </div>
                            <div class="field-group">
                                <label>Jumlah Alumni</label>
                                <input type="text" name="stat_alumni" class="stitch-select" style="width: 100%;" value="<?php echo htmlspecialchars($settings['stats']['stat_alumni'] ?? ''); ?>" placeholder="Contoh: 45.000+">
                            </div>
                        </div>
                    </div>
                    <small style="color: var(--text-muted);"><i class="fas fa-info-circle"></i> Data ini akan ditampilkan sebagai angka statistik utama di halaman depan (Command Center).</small>
                </div>

                <!-- Tab System -->
                <div id="system" class="tab-content">
                    <div class="grid-2">
                        <div>
                            <h3 style="margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px;">Maintenance & Keamanan</h3>
                            <div class="field-group" style="display: flex; justify-content: space-between; align-items: center; background: #fafafa; padding: 15px; border-radius: 8px;">
                                <div>
                                    <label style="margin: 0; font-size: 16px;">Mode Pemeliharaan</label>
                                    <div style="font-size: 13px; color: var(--text-muted);">Hanya Super Admin yang bisa login.</div>
                                </div>
                                <label class="switch">
                                    <input type="hidden" name="maintenance_mode" value="0">
                                    <input type="checkbox" name="maintenance_mode" value="1" <?php echo ($settings['system']['maintenance_mode'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div style="margin-top: 25px; padding: 20px; border: 1px solid #ddd; border-radius: 12px; text-align: center;">
                                <i class="fas fa-database fa-3x" style="color: var(--primary); margin-bottom: 15px;"></i>
                                <h4 style="margin-bottom: 10px;">Backup Database</h4>
                                <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 15px;">Unduh salinan database terkini (.sql) untuk keperluan pencadangan.</p>
                                <a href="backup_db.php" class="btn" style="background: var(--dark); display: inline-block;">
                                    <i class="fas fa-download"></i> Unduh File Backup
                                </a>
                            </div>
                        </div>

                        <div>
                            <h3 style="margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px;">Integrasi API Command Center</h3>
                            <div class="field-group">
                                <label>OpenWeather API Key (Iklim Kampus)</label>
                                <input type="text" name="api_weather_key" class="stitch-select" style="width: 100%;" value="<?php echo htmlspecialchars($settings['api']['api_weather_key'] ?? ''); ?>" placeholder="Dapatkan di openweathermap.org">
                            </div>
                            <div class="field-group">
                                <label>Kota / Lokasi (Untuk Cuaca & Shalat)</label>
                                <input type="text" name="api_prayer_city" class="stitch-select" style="width: 100%;" value="<?php echo htmlspecialchars($settings['api']['api_prayer_city'] ?? 'Banda Aceh'); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 30px; text-align: right; padding-top: 20px; border-top: 1px solid #eee;">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Pengaturan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openTab(tabName) {
            var i;
            var x = document.getElementsByClassName("tab-content");
            var btns = document.getElementsByClassName("tab-btn");
            for (i = 0; i < x.length; i++) {
                x[i].style.display = "none";
                x[i].classList.remove("active");
                btns[i].classList.remove("active");
            }
            document.getElementById(tabName).style.display = "block";
            event.currentTarget.classList.add("active");
        }
    </script>
</body>
</html>
