<?php
// admin/news_tambah.php
require_once __DIR__ . '/../../includes/auth.php';
checkAccess(['Super Admin', 'Pranata Humas', 'Operator Berita Online']);

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $date = $_POST['news_date'];
    $media_id = $_POST['media_id'];
    $category_id = $_POST['category_id'];
    $source_type = $_POST['source_type'];
    $news_link = $_POST['news_link'];
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("INSERT INTO news_online (title, news_date, media_id, category_id, source_type, news_link, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    try {
        $stmt->execute([$title, $date, $media_id, $category_id, $source_type, $news_link, $user_id]);
        
        // Log Aktivitas
        $news_id = $pdo->lastInsertId();
        write_log($pdo, "Menambahkan berita online: $title", "Berita Online", $news_id);
        
        header("Location: news_daftar.php?success=1");
        exit();
    } catch (PDOException $e) {
        $message = "Gagal: " . $e->getMessage();
    }
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$media_list = $pdo->query("SELECT * FROM media WHERE media_type = 'online' ORDER BY media_name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Berita Online | Humas Hub</title>
    <link rel="stylesheet" href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) == 'admin') ? '../' : '../../'; ?>assets/themes/<?php echo get_setting('app_theme', 'default'); ?>/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .form-split { display: grid; grid-template-columns: 1fr 350px; gap: 30px; }
        .meta-sidebar { background: #F0F4F8; padding: 30px; border-radius: 24px; display: flex; flex-direction: column; gap: 20px; height: fit-content; position: sticky; top: 30px; }
        .field-group { display: flex; flex-direction: column; gap: 8px; margin-bottom: 15px; }
        .field-group label { font-size: 11px; font-weight: 700; color: var(--navy); text-transform: uppercase; letter-spacing: 0.5px; }
        @media (max-width: 1100px) { .form-split { grid-template-columns: 1fr; } .meta-sidebar { position: static; } }
    </style>
</head>
<body data-theme="<?php echo get_setting('theme_mode', 'light'); ?>">
    <?php include '../common/sidebar.php'; ?>

    <div class="main-content">
        <header class="content-header">
            <h1 class="main-title">Tambah Berita Online</h1>
            <p class="sub-title">Input data publikasi berita media online terbaru.</p>
        </header>

        <?php if ($message): ?>
            <div class="alert" style="background: #ff4d4d; color: white; padding: 15px; border-radius: 12px; margin-bottom: 25px;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-split">
                <!-- Kolom Utama -->
                <div style="display: flex; flex-direction: column; gap: 25px;">
                    <div class="card">
                        <div class="field-group" style="margin-bottom: 25px;">
                            <label>Judul Berita</label>
                            <input type="text" name="title" class="stitch-select" style="font-size: 18px; font-weight: 700; height: 60px;" placeholder="Masukkan judul berita online..." required>
                        </div>
                        
                        <div class="field-group">
                            <label>Tautan / Link Berita</label>
                            <div style="position: relative;">
                                <i class="fas fa-link" style="position: absolute; left: 15px; top: 18px; color: #94a3b8;"></i>
                                <input type="url" name="news_link" class="stitch-select" style="padding-left: 45px; height: 55px;" placeholder="https://example.com/berita-anda" required>
                            </div>
                            <p style="font-size: 11px; color: #94a3b8; margin-top: 5px;">Pastikan tautan diawali dengan http:// atau https://</p>
                        </div>
                    </div>

                    <div class="card" style="background: var(--bg-soft-green); border: 1px dashed var(--primary);">
                        <div style="display: flex; align-items: flex-start; gap: 15px;">
                            <i class="fas fa-info-circle" style="color: var(--primary); font-size: 20px; margin-top: 5px;"></i>
                            <div>
                                <h4 style="color: var(--navy); margin-bottom: 5px;">Tips Penginputan</h4>
                                <p style="font-size: 13px; color: #64748b; line-height: 1.5;">Gunakan judul asli yang terbit di media untuk memudahkan pencarian di masa mendatang. Pastikan kategori yang dipilih sesuai dengan topik utama berita.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Metadata -->
                <div class="meta-sidebar">
                    <h3 style="font-size: 16px; color: var(--navy); border-bottom: 1px solid #E2E8F0; padding-bottom: 15px; margin-bottom: 5px;">Metadata</h3>
                    
                    <div class="field-group">
                        <label>Tanggal Berita</label>
                        <input type="date" name="news_date" class="stitch-select" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="field-group">
                        <label>Sumber Berita</label>
                        <select name="source_type" class="stitch-select" required>
                            <option value="Rilis Humas">Rilis Humas</option>
                            <option value="Liputan Wartawan">Liputan Wartawan</option>
                        </select>
                    </div>

                    <div class="field-group">
                        <label>Nama Media Online</label>
                        <select name="media_id" class="stitch-select" required>
                            <option value="">-- Pilih Media --</option>
                            <?php foreach ($media_list as $m): ?>
                                <option value="<?php echo $m['id']; ?>"><?php echo $m['media_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field-group">
                        <label>Kategori Utama</label>
                        <select name="category_id" class="stitch-select" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="margin-top: 15px; display: flex; flex-direction: column; gap: 10px;">
                        <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; height: 50px;">
                            <i class="fas fa-save"></i> SIMPAN BERITA
                        </button>
                        <a href="news_daftar.php" class="btn" style="width: 100%; justify-content: center; background: #CBD5E0; color: #4A5568;">BATAL</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</body>
</html>
