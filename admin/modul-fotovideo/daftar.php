<?php
// admin/modul-fotovideo/daftar.php
require_once __DIR__ . '/../../includes/auth.php';
checkAccess(['Super Admin', 'Pranata Humas', 'Operator Foto/Video']);

// Hapus Dokumentasi
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Log Aktivitas
    $stmt_title = $pdo->prepare("SELECT event_name FROM documentation WHERE id = ?");
    $stmt_title->execute([$id]);
    $event_name = $stmt_title->fetchColumn();
    write_log($pdo, "Menghapus dokumentasi: $event_name", "Foto/Video", $id);

    // Cascading delete is set in DB, so just delete the main record
    $stmt = $pdo->prepare("DELETE FROM documentation WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: daftar.php?success=Deleted");
    exit();
}

$filter_year = $_GET['year'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$quarter = $_GET['quarter'] ?? '';
$keyword = $_GET['q'] ?? '';
$filter_category = $_GET['category'] ?? '';
$filter_location = $_GET['location'] ?? '';

// Handle Quarter logic
if ($quarter && $filter_year) {
    switch ($quarter) {
        case '1': $start_date = "$filter_year-01-01"; $end_date = "$filter_year-03-31"; break;
        case '2': $start_date = "$filter_year-04-01"; $end_date = "$filter_year-06-30"; break;
        case '3': $start_date = "$filter_year-07-01"; $end_date = "$filter_year-09-30"; break;
        case '4': $start_date = "$filter_year-10-01"; $end_date = "$filter_year-12-31"; break;
    }
}

$where_clauses = [];
$params = [];
if ($filter_year && !$start_date && !$end_date) { $where_clauses[] = "YEAR(d.event_date) = ?"; $params[] = $filter_year; }
if ($start_date) { $where_clauses[] = "d.event_date >= ?"; $params[] = $start_date; }
if ($end_date)   { $where_clauses[] = "d.event_date <= ?"; $params[] = $end_date; }
if ($keyword)    { $where_clauses[] = "d.event_name LIKE ?"; $params[] = "%$keyword%"; }
if ($filter_category) { 
    $where_clauses[] = "d.id IN (SELECT documentation_id FROM documentation_category_rel WHERE category_id = ?)"; 
    $params[] = $filter_category; 
}
if ($filter_location) { $where_clauses[] = "d.location_type = ?"; $params[] = $filter_location; }
$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

$query = "SELECT d.*, GROUP_CONCAT(cat.name SEPARATOR ', ') as category_names, u.full_name as author 
          FROM documentation d 
          LEFT JOIN documentation_category_rel rel ON d.id = rel.documentation_id
          LEFT JOIN categories cat ON rel.category_id = cat.id 
          LEFT JOIN users u ON d.created_by = u.id 
          $where_sql 
          GROUP BY d.id
          ORDER BY d.event_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$docs = $stmt->fetchAll();

// Data untuk filter
$years = $pdo->query("SELECT DISTINCT YEAR(event_date) as yr FROM documentation ORDER BY yr DESC")->fetchAll();
$categories_list = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

// Fungsi Helper Konversi GDrive URL untuk Thumbnail (img tag)
function getDirectImageUrl($url) {
    if (!$url) return '';
    if (preg_match('/drive\.google\.com\/file\/d\/([a-zA-Z0-9-_]+)/', $url, $matches)) {
        return "https://lh3.googleusercontent.com/d/" . $matches[1];
    }
    if (preg_match('/drive\.google\.com\/open\?id=([a-zA-Z0-9-_]+)/', $url, $matches)) {
        return "https://lh3.googleusercontent.com/d/" . $matches[1];
    }
    return $url;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Dokumentasi | Humas Hub</title>
    <link rel="stylesheet" href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) == 'admin') ? '../' : '../../'; ?>assets/themes/<?php echo get_setting('app_theme', 'default'); ?>/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body data-theme="<?php echo get_setting('theme_mode', 'light'); ?>">
    <?php include '../common/sidebar.php'; ?>

    <div class="main-content">
        <header class="content-header" style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px;">
            <div>
                <h1 class="main-title">Daftar Dokumentasi</h1>
                <p class="sub-title">Arsip foto dan video dari berbagai kegiatan UIN Ar-Raniry.</p>
            </div>
            <a href="tambah.php" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Dokumentasi</a>
        </header>

        <form method="GET" class="filter-bar" style="background: white; padding: 25px; border-radius: 15px; margin-bottom: 30px; display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; align-items: end; box-shadow: var(--shadow);">
            <div class="filter-group">
                <label style="display: block; font-size: 11px; font-weight: 800; color: var(--navy); margin-bottom: 5px; text-transform: uppercase;">Tahun</label>
                <select name="year" class="stitch-select">
                    <option value="">Pilih Tahun</option>
                    <?php foreach ($years as $y): ?>
                        <option value="<?php echo $y['yr']; ?>" <?php echo $filter_year == $y['yr'] ? 'selected' : ''; ?>><?php echo $y['yr']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label style="display: block; font-size: 11px; font-weight: 800; color: var(--navy); margin-bottom: 5px; text-transform: uppercase;">Triwulan (TW)</label>
                <select name="quarter" class="stitch-select">
                    <option value="">Semua</option>
                    <option value="1" <?php echo $quarter == '1' ? 'selected' : ''; ?>>Tw 1 (Jan-Mar)</option>
                    <option value="2" <?php echo $quarter == '2' ? 'selected' : ''; ?>>Tw 2 (Apr-Jun)</option>
                    <option value="3" <?php echo $quarter == '3' ? 'selected' : ''; ?>>Tw 3 (Jul-Sep)</option>
                    <option value="4" <?php echo $quarter == '4' ? 'selected' : ''; ?>>Tw 4 (Okt-Des)</option>
                </select>
            </div>
            <div class="filter-group">
                <label style="display: block; font-size: 11px; font-weight: 800; color: var(--navy); margin-bottom: 5px; text-transform: uppercase;">Dari Tanggal</label>
                <input type="date" name="start_date" class="stitch-select" value="<?php echo $start_date; ?>">
            </div>
            <div class="filter-group">
                <label style="display: block; font-size: 11px; font-weight: 800; color: var(--navy); margin-bottom: 5px; text-transform: uppercase;">Sampai Tanggal</label>
                <input type="date" name="end_date" class="stitch-select" value="<?php echo $end_date; ?>">
            </div>
            <div class="filter-group">
                <label style="display: block; font-size: 11px; font-weight: 800; color: var(--navy); margin-bottom: 5px; text-transform: uppercase;">Kata Kunci</label>
                <input type="text" name="q" class="stitch-select" placeholder="Cari kegiatan..." value="<?php echo htmlspecialchars($keyword); ?>">
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary" style="flex: 1; height: 45px;"><i class="fas fa-filter"></i></button>
                <a href="daftar.php" class="btn" style="background:#f1f5f9; color:#64748b; height: 45px; width: 45px; display: flex; align-items: center; justify-content: center;"><i class="fas fa-sync-alt"></i></a>
            </div>
        </form>

        <div class="table-wrapper">
            <table class="stitch-table">
                <thead>
                    <tr>
                        <th>Thumbnail</th>
                        <th>Info Kegiatan</th>
                        <th>Lokasi</th>
                        <th>Tautan G-Drive</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($docs as $d): ?>
                    <tr>
                        <td style="width: 100px;">
                            <?php if ($d['thumbnail_url']): ?>
                                <img src="<?php echo getDirectImageUrl($d['thumbnail_url']); ?>" style="width: 80px; height: 60px; object-fit: cover; border-radius: 8px;">
                            <?php else: ?>
                                <img src="https://placehold.co/200x150/e2e8f0/64748b?text=No+Image" style="width: 80px; height: 60px; object-fit: cover; border-radius: 8px; opacity: 0.6;">
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="edit.php?id=<?php echo $d['id']; ?>" class="col-judul" style="text-decoration: none; transition: 0.2s;"><?php echo htmlspecialchars($d['event_name']); ?></a>
                            <div class="col-meta" style="margin-top: 5px;">
                                <span class="badge badge-soft-1"><i class="fas fa-tag"></i> <?php echo $d['category_names']; ?></span>
                                &nbsp;
                                <i class="fas fa-calendar-alt text-muted"></i> <?php echo tgl_indo($d['event_date']); ?>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: var(--navy);"><?php echo htmlspecialchars($d['location_name']); ?></div>
                            <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;"><?php echo $d['location_type']; ?></div>
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <?php if ($d['photo_folder_link']): ?>
                                    <a href="#" onclick="openPopup('<?php echo $d['photo_folder_link']; ?>'); return false;" class="btn-circle btn-circle-view" title="Folder Foto" style="background: #E1F0FF; color: #3498DB; border: none;">
                                        <i class="fas fa-image"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if ($d['video_folder_link']): ?>
                                    <a href="#" onclick="openPopup('<?php echo $d['video_folder_link']; ?>'); return false;" class="btn-circle btn-circle-view" title="Folder Video" style="background: #F3E5F5; color: #9B59B6; border: none;">
                                        <i class="fas fa-video"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if ($d['news_link']): ?>
                                    <a href="<?php echo $d['news_link']; ?>" target="_blank" class="btn-circle btn-circle-view" title="Berita Terkait" style="background: #E1F5E3; color: #27AE60; border: none;">
                                        <i class="fas fa-link"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <a href="detail.php?id=<?php echo $d['id']; ?>" class="btn-circle btn-circle-view" title="Lihat Detail" style="background: #f1f5f9; color: #64748b; border: none;"><i class="fas fa-eye"></i></a>
                                <a href="?delete=<?php echo $d['id']; ?>" onclick="return confirm('Hapus dokumentasi ini?')" class="btn-circle btn-circle-delete" title="Hapus"><i class="fas fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Popup G-Drive -->
    <div id="gdriveModal" class="modal" style="display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(15, 23, 42, 0.8); backdrop-filter: blur(8px);">
        <div class="modal-content" style="background-color: #fff; margin: 2vh auto; border-radius: 24px; width: 90%; max-width: 1200px; height: 96vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
            <div class="modal-header" style="padding: 20px 30px; background: #fff; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 style="margin: 0; color: var(--navy);"><i class="fab fa-google-drive" style="color: #4285F4;"></i> Google Drive Viewer</h3>
                    <p style="margin: 0; font-size: 12px; color: var(--text-muted);">Pratinjau dan unduh aset dari direktori cloud.</p>
                </div>
                <div style="display: flex; gap: 15px; align-items: center;">
                    <a id="btnOpenExternal" href="#" target="_blank" class="btn btn-primary" style="padding: 8px 16px; font-size: 12px;"><i class="fas fa-external-link-alt"></i> Buka di Tab Baru</a>
                    <span class="close" onclick="closePopup()" style="color: #64748b; font-size: 32px; font-weight: bold; cursor: pointer; transition: 0.2s;">&times;</span>
                </div>
            </div>
            <div style="flex: 1; background: #f8fafc; position: relative; padding: 0;">
                <iframe id="gdriveFrame" src="" style="width: 100%; height: 100%; border: none;"></iframe>
            </div>
        </div>
    </div>

    <script>
        function openPopup(url) {
            let embedUrl = url;
            
            // Konversi otomatis link folder G-Drive standar menjadi link Embed
            const folderMatch = url.match(/\/folders\/([a-zA-Z0-9-_]+)/);
            if (folderMatch && folderMatch[1]) {
                embedUrl = "https://drive.google.com/embeddedfolderview?id=" + folderMatch[1] + "#grid";
            } 
            // Konversi link file tunggal (jika user tidak sengaja input file)
            else {
                const fileMatch = url.match(/\/file\/d\/([a-zA-Z0-9-_]+)/);
                if (fileMatch && fileMatch[1]) {
                    embedUrl = "https://drive.google.com/file/d/" + fileMatch[1] + "/preview";
                }
            }

            document.getElementById('gdriveFrame').src = embedUrl;
            document.getElementById('btnOpenExternal').href = url;
            document.getElementById('gdriveModal').style.display = "block";
        }
        function closePopup() {
            document.getElementById('gdriveModal').style.display = "none";
            document.getElementById('gdriveFrame').src = "";
        }
        window.onclick = function(event) {
            if (event.target == document.getElementById('gdriveModal')) {
                closePopup();
            }
        }
    </script>
</body>
</html>
