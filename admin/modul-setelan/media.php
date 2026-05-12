<?php
// admin/media.php
require_once __DIR__ . '/../../includes/auth.php';
checkAccess(['Super Admin', 'Pranata Humas']);

$message = "";
$edit_mode = false;
$edit_data = null;

// Auto-repair database: Tambah kolom media_logo dan media_scale jika belum ada
try {
    $pdo->query("SELECT media_logo FROM media LIMIT 1");
} catch (Exception $e) {
    $pdo->exec("ALTER TABLE media ADD COLUMN media_logo VARCHAR(255) AFTER media_type");
}

try {
    $pdo->query("SELECT media_scale FROM media LIMIT 1");
} catch (Exception $e) {
    $pdo->exec("ALTER TABLE media ADD COLUMN media_scale VARCHAR(50) AFTER media_logo");
}

if (isset($_POST['save_media'])) {
    $name = trim($_POST['name']);
    $type = $_POST['type'];
    $scale = $_POST['scale'];

    // Check for duplicate
    if (isset($_POST['id'])) {
        $check = $pdo->prepare("SELECT id FROM media WHERE media_name = ? AND id != ?");
        $check->execute([$name, $_POST['id']]);
    } else {
        $check = $pdo->prepare("SELECT id FROM media WHERE media_name = ?");
        $check->execute([$name]);
    }

    if ($check->fetch()) {
        $message = "Error: Media '$name' sudah ada dalam sistem!";
    } else {
        // Handle Upload Logo
        $logo_name = $_POST['old_logo'] ?? '';
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
            $target_dir = "upload-media/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            
            $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $new_name = "logo_" . time() . "." . $ext;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_dir . $new_name)) {
                if ($logo_name && file_exists($target_dir . $logo_name)) unlink($target_dir . $logo_name);
                $logo_name = $new_name;
            }
        }

        if (isset($_POST['id'])) {
            $stmt = $pdo->prepare("UPDATE media SET media_name = ?, media_type = ?, media_logo = ?, media_scale = ? WHERE id = ?");
            $stmt->execute([$name, $type, $logo_name, $scale, $_POST['id']]);
            header("Location: media.php?success=Updated");
        } else {
            $stmt = $pdo->prepare("INSERT INTO media (media_name, media_type, media_logo, media_scale) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $type, $logo_name, $scale]);
            header("Location: media.php?success=Added");
        }
        exit();
    }
}

if (isset($_GET['edit'])) {
    $edit_mode = true;
    $stmt = $pdo->prepare("SELECT * FROM media WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_data = $stmt->fetch();
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("SELECT media_logo FROM media WHERE id = ?");
    $stmt->execute([$id]);
    $media = $stmt->fetch();
    if ($media && $media['media_logo'] && file_exists("upload-media/" . $media['media_logo'])) {
        unlink("upload-media/" . $media['media_logo']);
    }
    $stmt = $pdo->prepare("DELETE FROM media WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: media.php?success=Deleted");
    exit();
}

$media_list = $pdo->query("SELECT * FROM media ORDER BY media_name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Media | Humas Hub</title>
    <link rel="stylesheet" href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) == 'admin') ? '../' : '../../'; ?>assets/themes/<?php echo get_setting('app_theme', 'default'); ?>/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .input-group { margin-bottom: 20px; }
        .input-group label { display: block; font-size: 12px; font-weight: 700; color: var(--navy); text-transform: uppercase; margin-bottom: 8px; }
        .stitch-input, .stitch-select { width: 100%; background: #F9FAFB; border: 1px solid #EAECF0; border-radius: 12px; padding: 12px 16px; font-size: 14px; outline: none; transition: 0.3s; }
        .stitch-input:focus, .stitch-select:focus { border-color: var(--secondary); background: white; }
    </style>
</head>
<body data-theme="<?php echo get_setting('theme_mode', 'light'); ?>">
    <?php include '../common/sidebar.php'; ?>

    <div class="main-content">
        <header class="content-header">
            <h1 class="main-title">Manajemen Media</h1>
            <p class="sub-title">Kelola media cetak maupun daring untuk semua modul.</p>
        </header>

        <?php if ($message): ?>
            <div style="background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 12px; margin-bottom: 25px; font-weight: 600; border: 1px solid #fecaca;">
                <i class="fas fa-exclamation-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div style="background: #dcfce7; color: #15803d; padding: 15px; border-radius: 12px; margin-bottom: 25px; font-weight: 600; border: 1px solid #bbf7d0;">
                <i class="fas fa-check-circle"></i> Data berhasil <?php echo htmlspecialchars($_GET['success']); ?>!
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;" class="dashboard-layout">
            <!-- Form Kolom Kiri -->
            <div class="card">
                <h3 style="margin-bottom: 25px; font-size: 18px; color: var(--navy);">
                    <?php echo $edit_mode ? 'Edit Media' : 'Tambah Media Baru'; ?>
                </h3>
                <form method="POST" enctype="multipart/form-data">
                    <?php if ($edit_mode): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                        <input type="hidden" name="old_logo" value="<?php echo $edit_data['media_logo'] ?? ''; ?>">
                    <?php endif; ?>
                    
                    <div class="input-group">
                        <label>Nama Media</label>
                        <input type="text" name="name" class="stitch-input" placeholder="Contoh: Serambi Indonesia" value="<?php echo $edit_mode ? $edit_data['media_name'] : ''; ?>" required>
                    </div>

                    <div class="input-group">
                        <label>Jenis Media</label>
                        <select name="type" class="stitch-select">
                            <option value="cetak" <?php echo ($edit_mode && $edit_data['media_type'] == 'cetak') ? 'selected' : ''; ?>>Media Cetak (Koran)</option>
                            <option value="online" <?php echo ($edit_mode && $edit_data['media_type'] == 'online') ? 'selected' : ''; ?>>Media Online</option>
                        </select>
                    </div>

                    <div class="input-group">
                        <label>Skala Jangkauan Media</label>
                        <select name="scale" class="stitch-select">
                            <option value="Media Lokal" <?php echo ($edit_mode && $edit_data['media_scale'] == 'Media Lokal') ? 'selected' : ''; ?>>Media Lokal</option>
                            <option value="Media Nasional" <?php echo ($edit_mode && $edit_data['media_scale'] == 'Media Nasional') ? 'selected' : ''; ?>>Media Nasional</option>
                            <option value="Media Internasional" <?php echo ($edit_mode && $edit_data['media_scale'] == 'Media Internasional') ? 'selected' : ''; ?>>Media Internasional</option>
                        </select>
                    </div>

                    <div class="input-group">
                        <label>Logo Media (200x200)</label>
                        <input type="file" name="logo" class="stitch-input" accept="image/*" style="padding: 8px;">
                    </div>

                    <button type="submit" name="save_media" class="btn btn-primary" style="width: 100%; justify-content: center;">
                        <i class="fas fa-save"></i> SIMPAN MEDIA
                    </button>
                </form>
            </div>

            <!-- Tabel Kolom Kanan -->
            <div class="card" style="padding: 0; overflow: hidden;">
                <div class="table-wrapper" style="margin-top: 0; border: none; box-shadow: none; border-radius: 0;">
                    <table class="stitch-table">
                        <thead>
                            <tr>
                                <th style="width: 60px;">No</th>
                                <th>Logo</th>
                                <th>Nama Media</th>
                                <th>Skala</th>
                                <th style="width: 100px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($media_list as $index => $m): ?>
                            <tr>
                                <td><span style="color: #ccc; font-weight: 700;"><?php echo str_pad($index + 1, 2, '0', STR_PAD_LEFT); ?></span></td>
                                <td>
                                    <?php if ($m['media_logo']): ?>
                                        <img src="upload-media/<?php echo $m['media_logo']; ?>" style="width: 40px; height: 40px; object-fit: contain; border: 1px solid #eee; border-radius: 8px; padding: 2px;">
                                    <?php endif; ?>
                                </td>
                                <td style="font-weight: 600; color: var(--navy);">
                                    <?php echo $m['media_name']; ?>
                                    <div style="font-size: 10px; color: var(--text-muted); text-transform: uppercase;"><?php echo $m['media_type']; ?></div>
                                </td>
                                <td>
                                    <span class="badge badge-soft-2"><?php echo $m['media_scale'] ?: 'Lokal'; ?></span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 8px;">
                                        <a href="?edit=<?php echo $m['id']; ?>" class="btn-circle btn-circle-edit"><i class="fas fa-pen"></i></a>
                                        <a href="?delete=<?php echo $m['id']; ?>" class="btn-circle btn-circle-delete" onclick="return confirm('Hapus media?')"><i class="fas fa-trash"></i></a>
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
