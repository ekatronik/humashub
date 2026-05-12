<?php
// admin/modul-fotovideo/foto_video.php (Dashboard Modul Foto/Video)
require_once __DIR__ . '/../../includes/auth.php';
checkAccess(['Super Admin', 'Pranata Humas', 'Operator Foto/Video']);

// Statistik
$total_docs = $pdo->query("SELECT COUNT(*) FROM documentation")->fetchColumn();
$total_rektorat = $pdo->query("SELECT COUNT(DISTINCT documentation_id) FROM documentation_attendance WHERE level = 'Rektorat'")->fetchColumn();
$total_fakultas = $pdo->query("SELECT COUNT(DISTINCT documentation_id) FROM documentation_attendance WHERE level = 'Fakultas'")->fetchColumn();

// 5 Kegiatan Terakhir
$recent_docs = $pdo->query("SELECT d.*, GROUP_CONCAT(cat.name SEPARATOR ', ') as category_names 
                            FROM documentation d 
                            LEFT JOIN documentation_category_rel rel ON d.id = rel.documentation_id
                            LEFT JOIN categories cat ON rel.category_id = cat.id 
                            GROUP BY d.id
                            ORDER BY d.event_date DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Foto/Video | Humas Hub</title>
    <link rel="stylesheet" href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) == 'admin') ? '../' : '../../'; ?>assets/themes/<?php echo get_setting('app_theme', 'default'); ?>/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body data-theme="<?php echo get_setting('theme_mode', 'light'); ?>">
    <?php include '../common/sidebar.php'; ?>

    <div class="main-content">
        <header style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1 style="font-weight: 700; color: var(--dark);">Dashboard Dokumentasi</h1>
                <p style="color: var(--text-muted);">Ringkasan arsip foto dan video kegiatan UIN Ar-Raniry.</p>
            </div>
            <a href="tambah.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Tambah Dokumentasi
            </a>
        </header>

        <div class="stats-grid">
            <div class="stat-card green">
                <div class="stat-icon"><i class="fas fa-camera-retro"></i></div>
                <div class="stat-info">
                    <p>Total Kegiatan</p>
                    <h3><?php echo number_format($total_docs); ?></h3>
                </div>
            </div>
            <div class="stat-card blue">
                <div class="stat-icon"><i class="fas fa-user-tie"></i></div>
                <div class="stat-info">
                    <p>Dihadiri Rektorat</p>
                    <h3><?php echo number_format($total_rektorat); ?></h3>
                </div>
            </div>
            <div class="stat-card orange">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <p>Dihadiri Fakultas</p>
                    <h3><?php echo number_format($total_fakultas); ?></h3>
                </div>
            </div>
        </div>

        <div class="dashboard-layout" style="display: grid; grid-template-columns: 1fr; gap: 30px; margin-top: 30px;">
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <h3 style="font-size: 18px; color: var(--navy);">Kegiatan Terkini</h3>
                    <a href="daftar.php" class="btn btn-primary" style="padding: 8px 16px; font-size: 12px;">SEMUA DATA</a>
                </div>
                <div class="table-wrapper" style="margin-top: 0; border: none; box-shadow: none; border-radius: 0;">
                    <table class="stitch-table">
                        <thead>
                            <tr>
                                <th>Informasi Kegiatan</th>
                                <th>Lokasi</th>
                                <th>Media Folder</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_docs as $rn): ?>
                            <tr>
                                <td>
                                    <div class="col-judul" style="font-size: 14px;"><?php echo $rn['event_name']; ?></div>
                                    <div class="col-meta" style="font-size: 11px;">
                                        <i class="fas fa-calendar-alt"></i> <?php echo tgl_indo($rn['event_date']); ?> | <i class="fas fa-tag"></i> <?php echo $rn['category_names']; ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: var(--navy); font-size: 13px;"><?php echo $rn['location_name']; ?></div>
                                    <span class="badge badge-soft-2" style="font-size: 10px;"><?php echo $rn['location_type']; ?></span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <?php if ($rn['photo_folder_link']): ?>
                                            <a href="<?php echo $rn['photo_folder_link']; ?>" target="_blank" class="badge badge-soft-1"><i class="fab fa-google-drive"></i> Foto</a>
                                        <?php endif; ?>
                                        <?php if ($rn['video_folder_link']): ?>
                                            <a href="<?php echo $rn['video_folder_link']; ?>" target="_blank" class="badge badge-soft-3"><i class="fab fa-google-drive"></i> Video</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
