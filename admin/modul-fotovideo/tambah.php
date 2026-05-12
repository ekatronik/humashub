<?php
// admin/modul-fotovideo/tambah.php
require_once __DIR__ . '/../../includes/auth.php';
checkAccess(['Super Admin', 'Pranata Humas', 'Operator Foto/Video']);

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_name = $_POST['event_name'];
    $description = $_POST['description'];
    $news_link = $_POST['news_link'];
    $event_date = $_POST['event_date'];
    $selected_categories = $_POST['categories'] ?? [];
    $location_name = $_POST['location_name'];
    $location_type = $_POST['location_type'];
    
    $thumbnail_url = $_POST['thumbnail_url'];
    $photo_link = $_POST['photo_link'];
    $video_link = $_POST['video_link'];
    $creator_name = $_POST['creator_name'];
    
    $user_id = $_SESSION['user_id'];

    try {
        $pdo->beginTransaction();
        
        $sql = "INSERT INTO documentation (event_name, description, news_link, event_date, location_name, location_type, thumbnail_url, photo_folder_link, video_folder_link, creator_name, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$event_name, $description, $news_link, $event_date, $location_name, $location_type, $thumbnail_url, $photo_link, $video_link, $creator_name, $user_id]);
        $doc_id = $pdo->lastInsertId();

        if (!empty($selected_categories)) {
            $stmt_cat = $pdo->prepare("INSERT INTO documentation_category_rel (documentation_id, category_id) VALUES (?, ?)");
            foreach ($selected_categories as $cat_id) {
                $stmt_cat->execute([$doc_id, $cat_id]);
            }
        }

        $stmt_att = $pdo->prepare("INSERT INTO documentation_attendance (documentation_id, level, position, person_name) VALUES (?, ?, ?, ?)");
        
        // Rektorat Attendance
        if (!empty($_POST['rek_position'])) {
            foreach ($_POST['rek_position'] as $index => $pos) {
                if (!empty($pos) && !empty($_POST['rek_name'][$index])) {
                    $stmt_att->execute([$doc_id, 'Rektorat', $pos, $_POST['rek_name'][$index]]);
                }
            }
        }
        
        // Fakultas Attendance
        if (!empty($_POST['fak_position'])) {
            foreach ($_POST['fak_position'] as $index => $pos) {
                if (!empty($pos) && !empty($_POST['fak_name'][$index])) {
                    $stmt_att->execute([$doc_id, 'Fakultas', $pos, $_POST['fak_name'][$index]]);
                }
            }
        }

        // Tokoh Lainnya Attendance
        if (!empty($_POST['other_position'])) {
            foreach ($_POST['other_position'] as $index => $pos) {
                if (!empty($pos) && !empty($_POST['other_name'][$index])) {
                    $stmt_att->execute([$doc_id, 'Lainnya', $pos, $_POST['other_name'][$index]]);
                }
            }
        }

        // Log Aktivitas
        write_log($pdo, "Menambahkan dokumentasi: $event_name", "Foto/Video", $doc_id);

        $pdo->commit();
        header("Location: daftar.php?success=1");
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $message = "Gagal menyimpan: " . $e->getMessage();
    }
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Dokumentasi | Humas Hub</title>
    <link rel="stylesheet" href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) == 'admin') ? '../' : '../../'; ?>assets/themes/<?php echo get_setting('app_theme', 'default'); ?>/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .form-split { display: grid; grid-template-columns: 1fr 350px; gap: 30px; }
        .meta-sidebar { background: #F0F4F8; padding: 30px; border-radius: 24px; display: flex; flex-direction: column; gap: 20px; height: fit-content; position: sticky; top: 30px; }
        .field-group { display: flex; flex-direction: column; gap: 8px; margin-bottom: 15px; }
        .field-group label { font-size: 11px; font-weight: 700; color: var(--navy); text-transform: uppercase; letter-spacing: 0.5px; }
        .dynamic-row { display: grid; grid-template-columns: 1fr 1fr 40px; gap: 10px; align-items: end; margin-bottom: 10px; }
        .btn-remove-row { height: 45px; width: 40px; border: none; border-radius: 12px; background: #fee2e2; color: #ef4444; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
        .btn-remove-row:hover { background: #fecaca; }
        .btn-add-row { background: #f1f5f9; color: var(--navy); border: 1px dashed #cbd5e0; padding: 10px; width: 100%; border-radius: 12px; cursor: pointer; font-weight: 600; font-size: 13px; transition: 0.2s; }
        .btn-add-row:hover { border-color: var(--primary); color: var(--primary); }
        .card-title { font-size: 16px; color: var(--navy); border-bottom: 1px solid #E2E8F0; padding-bottom: 15px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        @media (max-width: 1100px) { .form-split { grid-template-columns: 1fr; } .meta-sidebar { position: static; } }
    </style>
</head>
<body data-theme="<?php echo get_setting('theme_mode', 'light'); ?>">
    <?php include '../common/sidebar.php'; ?>

    <div class="main-content">
        <header class="content-header">
            <h1 class="main-title">Tambah Dokumentasi Baru</h1>
            <p class="sub-title">Input arsip foto dan video kegiatan dari Google Drive.</p>
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
                    
                    <!-- Card A: Info Kegiatan -->
                    <div class="card">
                        <h3 class="card-title"><i class="fas fa-info-circle text-primary"></i> Info Kegiatan</h3>
                        <div class="field-group" style="margin-bottom: 25px;">
                            <label>Nama Kegiatan</label>
                            <input type="text" name="event_name" class="stitch-select" style="font-size: 18px; font-weight: 700; height: 60px;" placeholder="Masukkan judul kegiatan..." required>
                        </div>
                        <div class="field-group">
                            <label>Deskripsi Singkat</label>
                            <textarea name="description" rows="4" class="stitch-select" style="resize: vertical; line-height: 1.6;" placeholder="Tuliskan deskripsi singkat acara..."></textarea>
                        </div>
                        <div class="field-group">
                            <label>Link Berita di Web Resmi UIN</label>
                            <div style="position: relative;">
                                <i class="fas fa-globe" style="position: absolute; left: 15px; top: 18px; color: #94a3b8;"></i>
                                <input type="url" name="news_link" class="stitch-select" style="padding-left: 45px;" placeholder="https://uin.ar-raniry.ac.id/berita/...">
                            </div>
                        </div>
                    </div>

                    <!-- Card C: Kehadiran Pimpinan -->
                    <div class="card">
                        <h3 class="card-title"><i class="fas fa-users text-primary"></i> Kehadiran Pimpinan</h3>
                        
                        <!-- Rektorat -->
                        <div style="background: #f8fafc; padding: 20px; border-radius: 16px; margin-bottom: 20px; border: 1px solid #e2e8f0;">
                            <h4 style="font-size: 14px; margin-bottom: 15px; color: var(--navy);">Pimpinan Tk. Rektorat</h4>
                            <div id="rektorat-container">
                                <div class="dynamic-row">
                                    <div class="field-group" style="margin-bottom:0;">
                                        <label>Jabatan</label>
                                        <select name="rek_position[]" class="stitch-select">
                                            <option value="">-- Pilih --</option>
                                            <option value="Rektor">Rektor</option>
                                            <option value="Wakil Rektor 1">Wakil Rektor 1</option>
                                            <option value="Wakil Rektor 2">Wakil Rektor 2</option>
                                            <option value="Wakil Rektor 3">Wakil Rektor 3</option>
                                            <option value="Ka. Biro AUPK">Ka. Biro AUPK</option>
                                            <option value="Ka. Biro AAKK">Ka. Biro AAKK</option>
                                        </select>
                                    </div>
                                    <div class="field-group" style="margin-bottom:0;">
                                        <label>Nama Pejabat</label>
                                        <input type="text" name="rek_name[]" class="stitch-select" placeholder="Nama lengkap & gelar">
                                    </div>
                                    <button type="button" class="btn-remove-row" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                                </div>
                            </div>
                            <button type="button" class="btn-add-row" onclick="addRektoratRow()"><i class="fas fa-plus"></i> Tambah Pimpinan Rektorat</button>
                        </div>

                        <!-- Fakultas -->
                        <div style="background: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0;">
                            <h4 style="font-size: 14px; margin-bottom: 15px; color: var(--navy);">Pimpinan Tk. Fakultas/Pascasarjana</h4>
                            <div id="fakultas-container">
                                <div class="dynamic-row">
                                    <div class="field-group" style="margin-bottom:0;">
                                        <label>Jabatan</label>
                                        <select name="fak_position[]" class="stitch-select">
                                            <option value="">-- Pilih --</option>
                                            <option value="Dekan">Dekan</option>
                                            <option value="Wakil Dekan 1">Wakil Dekan 1</option>
                                            <option value="Wakil Dekan 2">Wakil Dekan 2</option>
                                            <option value="Wakil Dekan 3">Wakil Dekan 3</option>
                                            <option value="Dir. Pascasarjana">Dir. Pascasarjana</option>
                                            <option value="Wadir Pascasarjana">Wadir Pascasarjana</option>
                                            <option value="Ka. Prodi">Ka. Prodi</option>
                                            <option value="Lainnya">Lainnya</option>
                                        </select>
                                    </div>
                                    <div class="field-group" style="margin-bottom:0;">
                                        <label>Nama Pejabat</label>
                                        <input type="text" name="fak_name[]" class="stitch-select" placeholder="Nama lengkap & gelar">
                                    </div>
                                    <button type="button" class="btn-remove-row" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                                </div>
                            </div>
                            <button type="button" class="btn-add-row" onclick="addFakultasRow()"><i class="fas fa-plus"></i> Tambah Pimpinan Fakultas</button>
                        </div>

                        <!-- Tokoh Lainnya -->
                        <div style="background: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0; margin-top: 20px;">
                            <h4 style="font-size: 14px; margin-bottom: 15px; color: var(--navy);">Tokoh/Pejabat Lainnya</h4>
                            <div id="other-container">
                                <div class="dynamic-row">
                                    <div class="field-group" style="margin-bottom:0;">
                                        <label>Jabatan</label>
                                        <input type="text" name="other_position[]" class="stitch-select" placeholder="Contoh: Bupati Aceh Besar">
                                    </div>
                                    <div class="field-group" style="margin-bottom:0;">
                                        <label>Nama Tokoh</label>
                                        <input type="text" name="other_name[]" class="stitch-select" placeholder="Nama lengkap & gelar">
                                    </div>
                                    <button type="button" class="btn-remove-row" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                                </div>
                            </div>
                            <button type="button" class="btn-add-row" onclick="addOtherRow()"><i class="fas fa-plus"></i> Tambah Tokoh Lainnya</button>
                        </div>
                    </div>

                    <!-- Card D: Media Links -->
                    <div class="card">
                        <h3 class="card-title"><i class="fab fa-google-drive text-primary"></i> Tautan Media & Kredit</h3>
                        <div class="field-group">
                            <label>Link Foto Thumbnail Kegiatan</label>
                            <input type="url" name="thumbnail_url" class="stitch-select" placeholder="URL gambar untuk thumbnail..." required>
                        </div>
                        <div class="grid-2">
                            <div class="field-group">
                                <label>Link Folder Foto (G-Drive)</label>
                                <input type="url" name="photo_link" class="stitch-select" placeholder="https://drive.google.com/...">
                            </div>
                            <div class="field-group">
                                <label>Link Folder Video (G-Drive)</label>
                                <input type="url" name="video_link" class="stitch-select" placeholder="https://drive.google.com/...">
                            </div>
                        </div>
                        <div class="field-group">
                            <label>Nama Fotografer / Videografer</label>
                            <input type="text" name="creator_name" class="stitch-select" placeholder="Nama tim atau personil...">
                        </div>
                    </div>
                </div>

                <!-- Sidebar Metadata (Card B) -->
                <div class="meta-sidebar">
                    <h3 class="card-title" style="margin-bottom: 5px;"><i class="fas fa-map-marker-alt text-primary"></i> Metadata</h3>
                    
                    <div class="field-group">
                        <label>Tanggal Kegiatan</label>
                        <input type="date" name="event_date" class="stitch-select" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="field-group">
                        <label>Kategori Kegiatan</label>
                        <div class="category-list" style="display: grid; grid-template-columns: 1fr; gap: 10px; max-height: 200px; overflow-y: auto;">
                            <?php foreach ($categories as $cat): ?>
                                <label class="cat-item" style="display: flex; align-items: center; gap: 10px; background: white; padding: 10px 15px; border-radius: 12px; cursor: pointer; border: 1px solid #e2e8f0; font-size: 13px;">
                                    <input type="checkbox" name="categories[]" value="<?php echo $cat['id']; ?>" style="accent-color: var(--primary);">
                                    <span><?php echo $cat['name']; ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="field-group">
                        <label>Nama Lokasi</label>
                        <input type="text" name="location_name" class="stitch-select" placeholder="Contoh: Auditorium Ali Hasjmy" required>
                    </div>

                    <div class="field-group">
                        <label>Jenis Lokasi</label>
                        <select name="location_type" class="stitch-select" required>
                            <option value="Internal Kampus">Internal Kampus</option>
                            <option value="Lokal Daerah">Lokal Daerah</option>
                            <option value="Nasional">Nasional</option>
                            <option value="Internasional">Internasional</option>
                        </select>
                    </div>

                    <div style="margin-top: 15px; display: flex; flex-direction: column; gap: 10px;">
                        <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; height: 50px;">
                            <i class="fas fa-save"></i> SIMPAN DOKUMENTASI
                        </button>
                        <a href="daftar.php" class="btn" style="width: 100%; justify-content: center; background: #CBD5E0; color: #4A5568;">BATAL</a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        function addRektoratRow() {
            const container = document.getElementById('rektorat-container');
            const row = document.createElement('div');
            row.className = 'dynamic-row';
            row.innerHTML = `
                <div class="field-group" style="margin-bottom:0;">
                    <select name="rek_position[]" class="stitch-select">
                        <option value="">-- Pilih --</option>
                        <option value="Rektor">Rektor</option>
                        <option value="Wakil Rektor 1">Wakil Rektor 1</option>
                        <option value="Wakil Rektor 2">Wakil Rektor 2</option>
                        <option value="Wakil Rektor 3">Wakil Rektor 3</option>
                        <option value="Ka. Biro AUPK">Ka. Biro AUPK</option>
                        <option value="Ka. Biro AAKK">Ka. Biro AAKK</option>
                    </select>
                </div>
                <div class="field-group" style="margin-bottom:0;">
                    <input type="text" name="rek_name[]" class="stitch-select" placeholder="Nama lengkap & gelar">
                </div>
                <button type="button" class="btn-remove-row" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
            `;
            container.appendChild(row);
        }

        function addFakultasRow() {
            const container = document.getElementById('fakultas-container');
            const row = document.createElement('div');
            row.className = 'dynamic-row';
            row.innerHTML = `
                <div class="field-group" style="margin-bottom:0;">
                    <select name="fak_position[]" class="stitch-select">
                        <option value="">-- Pilih --</option>
                        <option value="Dekan">Dekan</option>
                        <option value="Wakil Dekan 1">Wakil Dekan 1</option>
                        <option value="Wakil Dekan 2">Wakil Dekan 2</option>
                        <option value="Wakil Dekan 3">Wakil Dekan 3</option>
                        <option value="Dir. Pascasarjana">Dir. Pascasarjana</option>
                        <option value="Wadir Pascasarjana">Wadir Pascasarjana</option>
                        <option value="Ka. Prodi">Ka. Prodi</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>
                <div class="field-group" style="margin-bottom:0;">
                    <input type="text" name="fak_name[]" class="stitch-select" placeholder="Nama lengkap & gelar">
                </div>
                <button type="button" class="btn-remove-row" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
            `;
            container.appendChild(row);
        }

        function addOtherRow() {
            const container = document.getElementById('other-container');
            const row = document.createElement('div');
            row.className = 'dynamic-row';
            row.innerHTML = `
                <div class="field-group" style="margin-bottom:0;">
                    <input type="text" name="other_position[]" class="stitch-select" placeholder="Jabatan/Instansi">
                </div>
                <div class="field-group" style="margin-bottom:0;">
                    <input type="text" name="other_name[]" class="stitch-select" placeholder="Nama lengkap & gelar">
                </div>
                <button type="button" class="btn-remove-row" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
            `;
            container.appendChild(row);
        }
    </script>
</body>
</html>
