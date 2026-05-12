<?php
// admin/kliping_tambah.php
require_once __DIR__ . '/../../includes/auth.php';
checkAccess(['Super Admin', 'Pranata Humas', 'Operator Kliping']);

// Auto-repair database: Tambah kolom lokasi fisik jika belum ada
$new_columns = [
    'storage_building' => "VARCHAR(100) AFTER file_path",
    'storage_room' => "VARCHAR(100) AFTER storage_building",
    'storage_rack' => "VARCHAR(100) AFTER storage_room",
    'storage_folder' => "VARCHAR(100) AFTER storage_rack",
    'is_borrowable' => "TINYINT(1) DEFAULT 1 AFTER storage_folder"
];

foreach ($new_columns as $col => $def) {
    try {
        $pdo->query("SELECT $col FROM clippings LIMIT 1");
    } catch (Exception $e) {
        $pdo->exec("ALTER TABLE clippings ADD COLUMN $col $def");
    }
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$media_list = $pdo->query("SELECT * FROM media WHERE media_type = 'cetak' ORDER BY media_name ASC")->fetchAll();

$message = "";
$error = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $summary = $_POST['summary'];
    $date = $_POST['clipping_date'];
    $media_id = $_POST['media_id'];
    $selected_categories = $_POST['categories'] ?? [];
    $user_id = $_SESSION['user_id'];
    
    // Lokasi Fisik
    $building = $_POST['storage_building'];
    $room = $_POST['storage_room'];
    $rack = $_POST['storage_rack'];
    $folder = $_POST['storage_folder'];
    $borrowable = $_POST['is_borrowable'];

    // Validasi File Size 5MB
    if (isset($_FILES['clipping_file']) && $_FILES['clipping_file']['error'] == 0) {
        if ($_FILES['clipping_file']['size'] > 5 * 1024 * 1024) {
            $message = "Gagal! Ukuran file melebihi batas 5 MB.";
            $error = true;
        }
    }

    if (!$error) {
        $file_path = "";
        if (isset($_FILES['clipping_file']) && $_FILES['clipping_file']['error'] == 0) {
            $upload_year = date('Y', strtotime($date));
            $upload_month = date('m', strtotime($date));
            $target_dir = "upload-kliping/$upload_year/$upload_month/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

            $stmt_media = $pdo->prepare("SELECT media_name FROM media WHERE id = ?");
            $stmt_media->execute([$media_id]);
            $m_info = $stmt_media->fetch();
            $m_name = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $m_info['media_name']));
            $f_date = date('dmY', strtotime($date));
            $f_ext = strtolower(pathinfo($_FILES['clipping_file']['name'], PATHINFO_EXTENSION));
            
            // Generate filename (selalu .pdf jika targetnya konversi)
            $is_image = in_array($f_ext, ['jpg', 'jpeg', 'png']);
            $final_ext = $is_image ? 'pdf' : $f_ext;
            $new_name = $f_date . "_" . $m_name . "_" . uniqid() . "." . $final_ext;
            
            // Path relatif untuk DB, path absolut untuk filesystem
            $rel_path = "$upload_year/$upload_month/$new_name";
            $abs_upload_path = __DIR__ . "/" . $target_dir . $new_name;

            if ($is_image) {
                // Konversi Image ke PDF
                try {
                    if (!class_exists('FPDF')) {
                        require_once __DIR__ . '/../../includes/fpdf.php';
                    }
                    $tmp_img = $_FILES['clipping_file']['tmp_name'];
                    
                    if (!file_exists($tmp_img)) {
                        throw new Exception("File temp tidak ditemukan.");
                    }

                    // Ambil ukuran gambar
                    $img_info = getimagesize($tmp_img);
                    if (!$img_info) {
                        throw new Exception("File yang diunggah bukan gambar yang valid.");
                    }
                    list($img_w, $img_h) = $img_info;
                    
                    // Tentukan orientasi (Portrait/Landscape)
                    $orientation = ($img_w > $img_h) ? 'L' : 'P';
                    
                    $pdf = new FPDF($orientation, 'mm', 'A4');
                    $pdf->AddPage();
                    
                    // Hitung dimensi agar pas di A4 (210x297mm)
                    $pw = $pdf->GetPageWidth() - 20; 
                    $ph = $pdf->GetPageHeight() - 20; 
                    
                    $ratio = min($pw/$img_w, $ph/$img_h);
                    $new_w = $img_w * $ratio;
                    $new_h = $img_h * $ratio;
                    
                    $off_x = ($pdf->GetPageWidth() - $new_w) / 2;
                    $off_y = ($pdf->GetPageHeight() - $new_h) / 2;
                    
                    $pdf->Image($tmp_img, $off_x, $off_y, $new_w, $new_h, $f_ext);
                    $pdf->Output('F', $abs_upload_path);
                    $file_path = $rel_path;
                } catch (Exception $e) {
                    $message = "Gagal konversi PDF: " . $e->getMessage();
                    $error = true;
                    error_log("FPDF Error: " . $e->getMessage());
                }
            } else {
                // Upload normal (untuk PDF)
                if (move_uploaded_file($_FILES['clipping_file']['tmp_name'], $abs_upload_path)) {
                    $file_path = $rel_path;
                } else {
                    $message = "Gagal mengunggah file. Pastikan folder upload memiliki izin tulis.";
                    $error = true;
                }
            }
        }

        try {
            $pdo->beginTransaction();
            $sql = "INSERT INTO clippings (title, summary, clipping_date, media_id, file_path, storage_building, storage_room, storage_rack, storage_folder, is_borrowable, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$title, $summary, $date, $media_id, $file_path, $building, $room, $rack, $folder, $borrowable, $user_id]);
            $clipping_id = $pdo->lastInsertId();

            // Log Aktivitas
            write_log($pdo, "Menambahkan kliping berita: $title", "Kliping", $clipping_id);

            if (!empty($selected_categories)) {
                $stmt_rel = $pdo->prepare("INSERT INTO clipping_category_rel (clipping_id, category_id) VALUES (?, ?)");
                foreach ($selected_categories as $cat_id) $stmt_rel->execute([$clipping_id, $cat_id]);
            }

            $pdo->commit();
            header("Location: kliping_daftar.php?success=1");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Gagal menyimpan: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Kliping Baru | Humas Hub</title>
    <link rel="stylesheet" href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) == 'admin') ? '../' : '../../'; ?>assets/themes/<?php echo get_setting('app_theme', 'default'); ?>/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .form-split { display: grid; grid-template-columns: 1fr 350px; gap: 30px; }
        .meta-sidebar { background: #F0F4F8; padding: 30px; border-radius: 24px; display: flex; flex-direction: column; gap: 20px; height: fit-content; position: sticky; top: 30px; }
        .field-group { display: flex; flex-direction: column; gap: 8px; margin-bottom: 15px; }
        .field-group label { font-size: 11px; font-weight: 700; color: var(--navy); text-transform: uppercase; letter-spacing: 0.5px; }
        .category-list { display: grid; grid-template-columns: 1fr; gap: 10px; max-height: 200px; overflow-y: auto; }
        .cat-item { display: flex; align-items: center; gap: 10px; background: white; padding: 10px 15px; border-radius: 12px; cursor: pointer; border: 1px solid transparent; transition: 0.2s; font-size: 13px; }
        .cat-item:hover { border-color: var(--primary); }
        .upload-zone { border: 2px dashed #CBD5E0; border-radius: 18px; padding: 30px; text-align: center; background: white; cursor: pointer; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        @media (max-width: 1100px) { .form-split { grid-template-columns: 1fr; } .meta-sidebar { position: static; } }
    </style>
</head>
<body data-theme="<?php echo get_setting('theme_mode', 'light'); ?>">
    <?php include '../common/sidebar.php'; ?>

    <div class="main-content">
        <header class="content-header">
            <h1 class="main-title">Tambah Kliping Baru</h1>
            <p class="sub-title">Kelola arsip digital dan catat lokasi penyimpanan fisiknya.</p>
        </header>

        <?php if ($message): ?>
            <div style="background: <?php echo $error ? '#fee2e2' : '#dcfce7'; ?>; color: <?php echo $error ? '#b91c1c' : '#15803d'; ?>; padding: 15px; border-radius: 12px; margin-bottom: 25px; font-weight: 600;">
                <i class="fas <?php echo $error ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="clippingForm">
            <div class="form-split">
                <!-- Kolom Utama -->
                <div style="display: flex; flex-direction: column; gap: 25px;">
                    <div class="card">
                        <div class="field-group" style="margin-bottom: 25px;">
                            <label>Judul Utama Berita</label>
                            <input type="text" name="title" class="stitch-select" style="font-size: 18px; font-weight: 700; height: 60px;" placeholder="Masukkan judul kliping..." required>
                        </div>
                        <div class="field-group">
                            <label>Ringkasan Isi Berita</label>
                            <textarea name="summary" rows="8" class="stitch-select" style="resize: vertical; line-height: 1.6;" placeholder="Tuliskan ringkasan..."></textarea>
                        </div>
                    </div>

                    <!-- Keterangan Tambahan: Lokasi Fisik -->
                    <div class="card">
                        <h3 style="font-size: 16px; color: var(--navy); margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-archive" style="color: var(--primary);"></i> Lokasi Arsip Fisik
                        </h3>
                        <div class="grid-2">
                            <div class="field-group">
                                <label>Gedung Penyimpanan</label>
                                <input type="text" name="storage_building" class="stitch-select" placeholder="Contoh: Gedung Rektorat">
                            </div>
                            <div class="field-group">
                                <label>Ruang Simpan</label>
                                <input type="text" name="storage_room" class="stitch-select" placeholder="Contoh: Lantai 2 / Ruang Humas">
                            </div>
                        </div>
                        <div class="grid-3">
                            <div class="field-group">
                                <label>Kode Rak/Lemari</label>
                                <input type="text" name="storage_rack" class="stitch-select" placeholder="Contoh: R-01">
                            </div>
                            <div class="field-group">
                                <label>Kode Map/Folder</label>
                                <input type="text" name="storage_folder" class="stitch-select" placeholder="Contoh: F-2024-A">
                            </div>
                            <div class="field-group">
                                <label>Boleh Pinjam?</label>
                                <select name="is_borrowable" class="stitch-select">
                                    <option value="1">BOLEH (Ya)</option>
                                    <option value="0">TIDAK (Arsip Tetap)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="field-group">
                            <label>Upload Scan Kliping (Max 5MB)</label>
                            <input type="file" name="clipping_file" id="clipping_file" style="display: none;" accept="image/*,application/pdf">
                            <div class="upload-zone" onclick="document.getElementById('clipping_file').click()" id="uploadZone">
                                <i class="fas fa-cloud-upload-alt fa-2x" style="color: var(--primary); margin-bottom: 10px; display: block;"></i>
                                <div id="file-name-display" style="font-weight: 700; color: var(--navy);">Klik untuk pilih file lampiran</div>
                                <p style="font-size: 11px; color: #94a3b8; margin-top: 5px;">Format: PDF, JPG, PNG (Max 5MB)</p>
                            </div>

                            <div id="new-file-info" style="display: none; margin-top: 10px; background: #ecfdf5; border: 1px solid #10b981; border-radius: 12px; padding: 15px; align-items: center; justify-content: space-between;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 40px; height: 40px; background: white; border-radius: 8px; display: flex; align-items: center; justify-content: center; border: 1px solid #e2e8f0;">
                                        <i class="fas fa-file-alt text-success fa-lg"></i>
                                    </div>
                                    <div>
                                        <div id="new-file-name" style="font-size: 13px; font-weight: 700; color: #065f46;"></div>
                                        <div style="font-size: 11px; color: #059669;">Siap untuk diarsipkan</div>
                                    </div>
                                </div>
                                <button type="button" onclick="document.getElementById('clipping_file').click()" class="btn" style="padding: 6px 12px; font-size: 12px; background: white; border: 1px solid #10b981; color: #059669;">
                                    <i class="fas fa-sync-alt"></i> Ganti
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Metadata -->
                <div class="meta-sidebar">
                    <h3 style="font-size: 16px; color: var(--navy);">Metadata</h3>
                    <div class="field-group">
                        <label>Tanggal Berita</label>
                        <input type="date" name="clipping_date" class="stitch-select" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="field-group">
                        <label>Nama Media</label>
                        <select name="media_id" class="stitch-select" required>
                            <option value="">-- Pilih Media --</option>
                            <?php foreach ($media_list as $m): ?>
                                <option value="<?php echo $m['id']; ?>"><?php echo $m['media_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field-group">
                        <label>Kategori Berita</label>
                        <div class="category-list">
                            <?php foreach ($categories as $cat): ?>
                                <label class="cat-item">
                                    <input type="checkbox" name="categories[]" value="<?php echo $cat['id']; ?>" style="accent-color: var(--primary);">
                                    <span><?php echo $cat['name']; ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div style="margin-top: 10px; display: flex; flex-direction: column; gap: 10px;">
                        <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; height: 50px;">
                            <i class="fas fa-save"></i> SIMPAN ARSIP
                        </button>
                        <a href="kliping_daftar.php" class="btn" style="width: 100%; justify-content: center; background: #CBD5E0; color: #4A5568;">BATAL</a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('clipping_file').onchange = function() {
            const file = this.files[0];
            const display = document.getElementById('new-file-name');
            const info = document.getElementById('new-file-info');
            const zone = document.getElementById('uploadZone');
            
            if (file) {
                if (file.size > 5242880) { 
                    alert("File terlalu besar! Maksimal 5MB."); 
                    this.value = ""; 
                    info.style.display = "none";
                    zone.style.display = "block";
                } else { 
                    display.innerHTML = file.name; 
                    info.style.display = "flex";
                    zone.style.display = "none";
                }
            }
        };
    </script>
</body>
</html>
