<?php
// admin/index.php
require_once __DIR__ . '/../includes/auth.php';
checkAccess();

$role = $_SESSION['role'] ?? 'User';
$full_name = $_SESSION['full_name'] ?? 'Guest';

// Counts
$total_kliping = $pdo->query("SELECT COUNT(*) FROM clippings")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Humas Hub</title>
    <link rel="stylesheet" href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) == 'admin') ? '../' : '../../'; ?>assets/themes/<?php echo get_setting('app_theme', 'default'); ?>/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body data-theme="<?php echo get_setting('theme_mode', 'light'); ?>">
    <?php include 'common/sidebar.php'; ?>

    <div class="main-content">
        <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <div>
                <h1 style="font-weight: 700; color: var(--dark);">Selamat Datang, <?php echo $full_name; ?></h1>
                <p style="color: var(--text-muted);">Dashboard Sistem Informasi Humas UIN Ar-Raniry</p>
            </div>
            <div style="display: flex; align-items: center; gap: 15px;">
                <div style="text-align: right;">
                    <div style="font-weight: 600;"><?php echo $full_name; ?></div>
                    <div style="font-size: 12px; color: var(--primary); font-weight: 700;"><?php echo strtoupper($role); ?></div>
                </div>
                <div style="width: 45px; height: 45px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                    <i class="fas fa-user"></i>
                </div>
            </div>
        </header>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div class="card" style="border-left: 5px solid var(--primary);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="color: var(--text-muted); font-size: 14px; margin-bottom: 5px;">Total Kliping</div>
                        <h2 style="font-size: 28px; font-weight: 800;"><?php echo $total_kliping; ?></h2>
                    </div>
                    <i class="fas fa-newspaper fa-2x" style="color: rgba(0, 104, 55, 0.2);"></i>
                </div>
            </div>
            <div class="card" style="border-left: 5px solid var(--secondary);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="color: var(--text-muted); font-size: 14px; margin-bottom: 5px;">Link Berita</div>
                        <h2 style="font-size: 28px; font-weight: 800;">0</h2>
                    </div>
                    <i class="fas fa-link fa-2x" style="color: rgba(251, 176, 59, 0.2);"></i>
                </div>
            </div>
            <div class="card" style="border-left: 5px solid #3498db;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="color: var(--text-muted); font-size: 14px; margin-bottom: 5px;">Foto/Video</div>
                        <h2 style="font-size: 28px; font-weight: 800;">0</h2>
                    </div>
                    <i class="fas fa-camera fa-2x" style="color: rgba(52, 152, 219, 0.2);"></i>
                </div>
            </div>
        </div>

        <div class="card">
            <h3 style="margin-bottom: 20px;">Aktivitas Terkini</h3>
            <div style="padding: 40px; text-align: center; color: var(--text-muted);">
                <i class="fas fa-info-circle fa-2x" style="margin-bottom: 15px; opacity: 0.3;"></i>
                <p>Belum ada aktivitas tercatat hari ini.</p>
            </div>
        </div>
    </div>
</body>
</html>
