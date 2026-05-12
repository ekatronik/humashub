<?php
// admin/logs.php
require_once __DIR__ . '/../../includes/auth.php';
checkAccess(['Super Admin', 'Pranata Humas']);

$query = "SELECT l.*, u.full_name, r.role_name 
          FROM activity_logs l 
          JOIN users u ON l.user_id = u.id 
          LEFT JOIN roles r ON u.role_id = r.id 
          ORDER BY l.created_at DESC";
$logs = $pdo->query($query)->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Aktivitas | Humas Hub</title>
    <link rel="stylesheet" href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) == 'admin') ? '../' : '../../'; ?>assets/themes/<?php echo get_setting('app_theme', 'default'); ?>/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .log-timeline { position: relative; padding: 20px 0; }
        .log-item { position: relative; padding-left: 60px; margin-bottom: 30px; }
        .log-item::before { content: ''; position: absolute; left: 24px; top: 0; bottom: -30px; width: 2px; background: #e2e8f0; }
        .log-item:last-child::before { display: none; }
        .log-icon { position: absolute; left: 0; top: 0; width: 50px; height: 50px; border-radius: 50%; background: white; border: 2px solid #e2e8f0; display: flex; align-items: center; justify-content: center; z-index: 1; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .log-content { background: white; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0; transition: 0.3s; }
        .log-content:hover { transform: translateX(5px); border-color: var(--primary); }
        .log-time { font-size: 12px; color: #94a3b8; margin-bottom: 5px; font-weight: 600; }
        .log-user { font-weight: 700; color: var(--navy); }
        .log-role { font-size: 11px; background: #f1f5f9; padding: 2px 8px; border-radius: 4px; color: #64748b; margin-left: 5px; }
        .log-action { margin: 8px 0; color: #475569; line-height: 1.5; }
        .log-module { font-size: 11px; font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 1px; }
    </style>
</head>
<body data-theme="<?php echo get_setting('theme_mode', 'light'); ?>">
    <?php include '../common/sidebar.php'; ?>

    <div class="main-content">
        <header class="content-header">
            <h1 class="main-title">Audit Trail & Log Aktivitas</h1>
            <p class="sub-title">Pantau seluruh aktivitas pengguna untuk keamanan dan akuntabilitas data.</p>
        </header>

        <div class="log-timeline">
            <?php foreach ($logs as $log): ?>
            <?php 
                $icon = "fa-bolt";
                if (strpos($log['activity'], 'Menambah') !== false) $icon = "fa-plus-circle";
                if (strpos($log['activity'], 'Memperbarui') !== false) $icon = "fa-edit";
                if (strpos($log['activity'], 'Menghapus') !== false) $icon = "fa-trash-alt";
            ?>
            <div class="log-item">
                <div class="log-icon">
                    <i class="fas <?php echo $icon; ?>" style="color: var(--primary);"></i>
                </div>
                <div class="log-content">
                    <div class="log-time">
                        <i class="far fa-clock"></i> <?php echo date('d M Y, H:i:s', strtotime($log['created_at'])); ?>
                    </div>
                    <div>
                        <span class="log-user"><?php echo $log['full_name']; ?></span>
                        <span class="log-role"><?php echo $log['role_name']; ?></span>
                    </div>
                    <div class="log-action">
                        <?php echo $log['activity']; ?>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                        <span class="log-module"><i class="fas fa-tag"></i> <?php echo $log['module']; ?></span>
                        <span style="font-size: 11px; color: #94a3b8;"><i class="fas fa-network-wired"></i> IP: <?php echo $log['ip_address']; ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($logs)): ?>
                <div class="card" style="text-align: center; padding: 50px;">
                    <i class="fas fa-history fa-3x" style="color: #e2e8f0; margin-bottom: 20px;"></i>
                    <p style="color: #94a3b8;">Belum ada aktivitas yang tercatat.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
