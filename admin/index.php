<?php
// Admin Dashboard - Testing Edit
require_once __DIR__ . '/../includes/auth.php';
checkAccess();

$role = $_SESSION['role'] ?? 'User';
$full_name = $_SESSION['full_name'] ?? 'Guest';

// Counts
$total_kliping = $pdo->query("SELECT COUNT(*) FROM clippings")->fetchColumn();
$total_news = $pdo->query("SELECT COUNT(*) FROM news_online")->fetchColumn();
$total_docs = $pdo->query("SELECT COUNT(*) FROM documentation")->fetchColumn();

// Recent Activity (Today)
$today = date('Y-m-d');
$stmt_logs = $pdo->prepare("SELECT l.*, u.full_name, r.role_name 
                           FROM activity_logs l 
                           JOIN users u ON l.user_id = u.id 
                           LEFT JOIN roles r ON u.role_id = r.id 
                           WHERE DATE(l.created_at) = ?
                           ORDER BY l.created_at DESC 
                           LIMIT 5");
$stmt_logs->execute([$today]);
$recent_logs = $stmt_logs->fetchAll();
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
                        <h2 style="font-size: 28px; font-weight: 800;"><?php echo $total_news; ?></h2>
                    </div>
                    <i class="fas fa-link fa-2x" style="color: rgba(251, 176, 59, 0.2);"></i>
                </div>
            </div>
            <div class="card" style="border-left: 5px solid #3498db;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="color: var(--text-muted); font-size: 14px; margin-bottom: 5px;">Foto/Video</div>
                        <h2 style="font-size: 28px; font-weight: 800;"><?php echo $total_docs; ?></h2>
                    </div>
                    <i class="fas fa-camera fa-2x" style="color: rgba(52, 152, 219, 0.2);"></i>
                </div>
            </div>
        </div>

        <div class="card">
            <h3 style="margin-bottom: 20px;">Aktivitas Terkini (Hari Ini)</h3>
            <?php if (!empty($recent_logs)): ?>
                <div class="activity-list">
                    <?php foreach ($recent_logs as $log): ?>
                        <div style="display: flex; gap: 15px; padding: 12px 0; border-bottom: 1px solid #f1f5f9; align-items: center;">
                            <div style="width: 35px; height: 35px; border-radius: 8px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: var(--primary);">
                                <i class="fas fa-history"></i>
                            </div>
                            <div style="flex: 1;">
                                <div style="font-size: 14px; font-weight: 600;"><?php echo htmlspecialchars($log['activity']); ?></div>
                                <div style="font-size: 11px; color: var(--text-muted);">
                                    <span style="font-weight: 700; color: var(--navy);"><?php echo $log['full_name']; ?></span> &bull; 
                                    <?php echo date('H:i', strtotime($log['created_at'])); ?> &bull; 
                                    <?php echo $log['module']; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div style="margin-top: 15px; text-align: center;">
                    <a href="modul-admin/logs.php" style="font-size: 12px; color: var(--primary); font-weight: 600; text-decoration: none;">Lihat Semua Log <i class="fas fa-arrow-right"></i></a>
                </div>
            <?php else: ?>
                <div style="padding: 40px; text-align: center; color: var(--text-muted);">
                    <i class="fas fa-info-circle fa-2x" style="margin-bottom: 15px; opacity: 0.3;"></i>
                    <p>Belum ada aktivitas tercatat hari ini.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
