<?php
// admin/kategori.php
require_once __DIR__ . '/../../includes/auth.php';
checkAccess(['Super Admin', 'Pranata Humas']);

$message = "";
$edit_mode = false;
$edit_data = null;

if (isset($_POST['save_category'])) {
    $name = trim($_POST['name']);
    
    // Check for duplicate
    if (isset($_POST['id'])) {
        $check = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
        $check->execute([$name, $_POST['id']]);
    } else {
        $check = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
        $check->execute([$name]);
    }

    if ($check->fetch()) {
        $message = "Error: Nama kategori '$name' sudah ada dalam sistem!";
    } else {
        if (isset($_POST['id'])) {
            $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?");
            $stmt->execute([$name, $_POST['id']]);
            header("Location: kategori.php?success=Updated");
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->execute([$name]);
            header("Location: kategori.php?success=Added");
        }
        exit();
    }
}

if (isset($_GET['edit'])) {
    $edit_mode = true;
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_data = $stmt->fetch();
}

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: kategori.php?success=Deleted");
    exit();
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori | Humas Hub</title>
    <link rel="stylesheet" href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) == 'admin') ? '../' : '../../'; ?>assets/themes/<?php echo get_setting('app_theme', 'default'); ?>/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .input-group { margin-bottom: 20px; }
        .input-group label { display: block; font-size: 12px; font-weight: 700; color: var(--navy); text-transform: uppercase; margin-bottom: 8px; }
        .stitch-input { width: 100%; background: #F9FAFB; border: 1px solid #EAECF0; border-radius: 12px; padding: 12px 16px; font-size: 14px; outline: none; transition: 0.3s; }
        .stitch-input:focus { border-color: var(--secondary); background: white; }
    </style>
</head>
<body data-theme="<?php echo get_setting('theme_mode', 'light'); ?>">
    <?php include '../common/sidebar.php'; ?>

    <div class="main-content">
        <header class="content-header">
            <h1 class="main-title">Manajemen Kategori</h1>
            <p class="sub-title">Kelola taksonomi kategori untuk semua modul berita dan arsip.</p>
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
                    <?php echo $edit_mode ? 'Edit Kategori' : 'Tambah Kategori Baru'; ?>
                </h3>
                <form method="POST">
                    <?php if ($edit_mode): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                    <?php endif; ?>
                    <div class="input-group">
                        <label>Nama Kategori</label>
                        <input type="text" name="name" class="stitch-input" placeholder="Contoh: Akademik, Infrastruktur..." value="<?php echo $edit_mode ? $edit_data['name'] : ''; ?>" required>
                    </div>
                    <button type="submit" name="save_category" class="btn btn-primary" style="width: 100%; justify-content: center;">
                        <i class="fas fa-save"></i> <?php echo $edit_mode ? 'Simpan Perubahan' : 'Simpan Kategori'; ?>
                    </button>
                    <?php if ($edit_mode): ?>
                        <a href="kategori.php" class="btn" style="width: 100%; justify-content: center; margin-top: 10px; background: #eee; color: #666;">Batal</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Tabel Kolom Kanan -->
            <div class="card" style="padding: 0; overflow: hidden;">
                <div style="padding: 25px; border-bottom: 1px solid #eee;">
                    <h3 style="font-size: 18px; color: var(--navy);">Daftar Kategori Terdaftar</h3>
                </div>
                <div class="table-wrapper" style="margin-top: 0; border: none; box-shadow: none; border-radius: 0;">
                    <table class="stitch-table">
                        <thead>
                            <tr>
                                <th style="width: 60px;">No</th>
                                <th>Nama Kategori</th>
                                <th style="width: 120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $index => $cat): ?>
                            <tr>
                                <td><span style="color: #ccc; font-weight: 700;"><?php echo str_pad($index + 1, 2, '0', STR_PAD_LEFT); ?></span></td>
                                <td style="font-weight: 600; color: var(--navy);"><?php echo $cat['name']; ?></td>
                                <td>
                                    <div style="display: flex; gap: 10px;">
                                        <a href="?edit=<?php echo $cat['id']; ?>" class="btn-circle btn-circle-edit" title="Edit">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <a href="?delete=<?php echo $cat['id']; ?>" onclick="return confirm('Hapus kategori ini?')" class="btn-circle btn-circle-delete" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </a>
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
