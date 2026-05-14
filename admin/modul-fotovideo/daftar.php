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

// ---------------------------------------------------------
// PAGINATION & FILTER LOGIC
// ---------------------------------------------------------
$limit = isset($_GET['limit']) ? ($_GET['limit'] === 'all' ? 'all' : (int)$_GET['limit']) : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$filter_year = $_GET['year'] ?? date('Y'); // Default to current year for TW logic
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$quarter = $_GET['quarter'] ?? '';
$keyword = $_GET['q'] ?? '';
$filter_category = $_GET['category'] ?? '';
$filter_location = $_GET['location'] ?? '';

// Handle Quarter logic
if ($quarter) {
    switch ($quarter) {
        case '1': $start_date = "$filter_year-01-01"; $end_date = "$filter_year-03-31"; break;
        case '2': $start_date = "$filter_year-04-01"; $end_date = "$filter_year-06-30"; break;
        case '3': $start_date = "$filter_year-07-01"; $end_date = "$filter_year-09-30"; break;
        case '4': $start_date = "$filter_year-10-01"; $end_date = "$filter_year-12-31"; break;
    }
}

$where_clauses = [];
$params = [];
if ($start_date) { $where_clauses[] = "d.event_date >= ?"; $params[] = $start_date; }
if ($end_date)   { $where_clauses[] = "d.event_date <= ?"; $params[] = $end_date; }
if ($keyword)    { $where_clauses[] = "d.event_name LIKE ?"; $params[] = "%$keyword%"; }
if ($filter_category) { 
    $where_clauses[] = "d.id IN (SELECT documentation_id FROM documentation_category_rel WHERE category_id = ?)"; 
    $params[] = $filter_category; 
}
if ($filter_location) { $where_clauses[] = "d.location_type = ?"; $params[] = $filter_location; }
$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Count Total for Pagination
$count_query = "SELECT COUNT(DISTINCT d.id) FROM documentation d $where_sql";
$stmt_count = $pdo->prepare($count_query);
$stmt_count->execute($params);
$total_records = $stmt_count->fetchColumn();

// Pagination Math
$total_pages = ($limit === 'all') ? 1 : ceil($total_records / $limit);
if ($page > $total_pages && $total_pages > 0) $page = $total_pages;
$offset = ($limit === 'all') ? 0 : ($page - 1) * $limit;

// Main Query
$limit_sql = ($limit === 'all') ? "" : "LIMIT $limit OFFSET $offset";
$query = "SELECT d.*, GROUP_CONCAT(cat.name SEPARATOR ', ') as category_names, u.full_name as author 
          FROM documentation d 
          LEFT JOIN documentation_category_rel rel ON d.id = rel.documentation_id
          LEFT JOIN categories cat ON rel.category_id = cat.id 
          LEFT JOIN users u ON d.created_by = u.id 
          $where_sql 
          GROUP BY d.id
          ORDER BY d.event_date DESC
          $limit_sql";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$docs = $stmt->fetchAll();

// Data for Filters
$years = $pdo->query("SELECT DISTINCT YEAR(event_date) as yr FROM documentation ORDER BY yr DESC")->fetchAll();
$categories_list = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

// Function to build pagination URL
function pgUrl($p) {
    $params = $_GET;
    $params['page'] = $p;
    return "?" . http_build_query($params);
}

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
    <!-- Flatpickr for Compact Range Picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <style>
        .filter-card { background: white; padding: 25px; border-radius: 7px; box-shadow: var(--shadow); margin-bottom: 20px; }
        
        /* Compact Date Range Picker Style */
        .date-range-compact {
            display: flex;
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 7px;
            padding: 8px 18px;
            gap: 15px;
            transition: 0.3s;
            cursor: pointer;
            height: 47px;
        }
        .date-range-compact:hover { border-color: var(--primary); background: #f0fdf4; }
        .dr-section { flex: 1; display: flex; flex-direction: column; min-width: 0; }
        .dr-label { font-size: 8px; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 2px; letter-spacing: 0.5px; }
        .dr-value { font-size: 13px; font-weight: 700; color: var(--navy); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .dr-separator { color: #cbd5e0; font-size: 12px; }
        
        #range_trigger { border: none; background: transparent; padding: 0; margin: 0; width: 0; height: 0; opacity: 0; position: absolute; }

        /* Flatpickr Design System Overrides */
        .flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange, 
        .flatpickr-day.selected.inRange, .flatpickr-day.startRange.inRange, .flatpickr-day.endRange.inRange, 
        .flatpickr-day.selected:focus, .flatpickr-day.startRange:focus, .flatpickr-day.endRange:focus, 
        .flatpickr-day.selected:hover, .flatpickr-day.startRange:hover, .flatpickr-day.endRange:hover, 
        .flatpickr-day.selected.prevMonthDay, .flatpickr-day.startRange.prevMonthDay, .flatpickr-day.endRange.prevMonthDay, 
        .flatpickr-day.selected.nextMonthDay, .flatpickr-day.startRange.nextMonthDay, .flatpickr-day.endRange.nextMonthDay {
            background: var(--primary) !important;
            border-color: var(--primary) !important;
        }
        .flatpickr-day.inRange {
            box-shadow: -5px 0 0 #f0fdf4, 5px 0 0 #f0fdf4 !important;
            background: #f0fdf4 !important;
            border-color: #f0fdf4 !important;
        }
        .flatpickr-months .flatpickr-month, .flatpickr-current-month .flatpickr-month { color: var(--navy) !important; fill: var(--navy) !important; }
        .flatpickr-calendar { border-radius: 7px !important; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1) !important; border: none !important; }

        /* Mobile Optimization */
        @media (max-width: 768px) {
            .filter-card { padding: 15px; }
            .filter-group[style*="grid-column: span 2"] { grid-column: span 1 !important; min-width: 0 !important; }
            .filter-group { min-width: 0 !important; }
            .date-range-compact { gap: 10px; padding: 5px 12px; }
            .dr-value { font-size: 11px; }
            .dr-label { font-size: 7px; }
            .main-title { font-size: 24px; }
        }

        .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 30px; }
        .page-link { padding: 8px 16px; border-radius: 7px; background: white; border: 1px solid #e2e8f0; color: #64748b; text-decoration: none; font-weight: 600; font-size: 14px; transition: 0.2s; }
        .page-link:hover { border-color: var(--primary); color: var(--primary); }
        .page-link.active { background: var(--primary); color: white; border-color: var(--primary); }
        .page-link.disabled { opacity: 0.5; pointer-events: none; }
        .pagination-status { text-align: center; font-size: 13px; color: #94a3b8; margin-top: 15px; }
    </style>
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

        <form method="GET" id="filterForm">
            <div class="filter-card">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; align-items: end;">
                    <!-- Pos 1 & 2: Compact Date Range -->
                    <div class="filter-group" style="grid-column: span 2; min-width: 350px;">
                        <label style="display: block; font-size: 11px; font-weight: 800; color: var(--navy); margin-bottom: 8px; text-transform: uppercase;">Rentang Waktu Kegiatan</label>
                        <div class="date-range-compact" onclick="document.getElementById('range_trigger')._flatpickr.open()">
                            <div class="dr-section">
                                <span class="dr-label">Mulai</span>
                                <span class="dr-value" id="display_start"><?php echo $start_date ? tgl_indo($start_date) : 'Pilih Tanggal'; ?></span>
                            </div>
                            <div class="dr-separator"><i class="fas fa-arrow-right"></i></div>
                            <div class="dr-section">
                                <span class="dr-label">Selesai</span>
                                <span class="dr-value" id="display_end"><?php echo $end_date ? tgl_indo($end_date) : 'Pilih Tanggal'; ?></span>
                            </div>
                            <input type="text" id="range_trigger" placeholder="Select Range">
                            <input type="hidden" name="start_date" id="start_date_val" value="<?php echo $start_date; ?>">
                            <input type="hidden" name="end_date" id="end_date_val" value="<?php echo $end_date; ?>">
                        </div>
                    </div>

                    <!-- Pos 3 & 4: Jenis Lokasi & Kategori -->
                    <div class="filter-group">
                        <label style="display: block; font-size: 11px; font-weight: 800; color: var(--navy); margin-bottom: 8px; text-transform: uppercase;">Jenis Lokasi</label>
                        <select name="location" class="stitch-select">
                            <option value="">Semua Lokasi</option>
                            <option value="Internal Kampus" <?php echo $filter_location == 'Internal Kampus' ? 'selected' : ''; ?>>Internal Kampus</option>
                            <option value="Lokal Daerah" <?php echo $filter_location == 'Lokal Daerah' ? 'selected' : ''; ?>>Lokal Daerah</option>
                            <option value="Nasional" <?php echo $filter_location == 'Nasional' ? 'selected' : ''; ?>>Nasional</option>
                            <option value="Internasional" <?php echo $filter_location == 'Internasional' ? 'selected' : ''; ?>>Internasional</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label style="display: block; font-size: 11px; font-weight: 800; color: var(--navy); margin-bottom: 8px; text-transform: uppercase;">Kategori</label>
                        <select name="category" class="stitch-select">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($categories_list as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $filter_category == $cat['id'] ? 'selected' : ''; ?>><?php echo $cat['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Pos 5: Triwulan (TW) -->
                    <div class="filter-group">
                        <label style="display: block; font-size: 11px; font-weight: 800; color: var(--navy); margin-bottom: 8px; text-transform: uppercase;">Triwulan (TW)</label>
                        <select name="quarter" class="stitch-select">
                            <option value="">Pilih Triwulan</option>
                            <option value="1" <?php echo $quarter == '1' ? 'selected' : ''; ?>>Tw 1 (Jan-Mar)</option>
                            <option value="2" <?php echo $quarter == '2' ? 'selected' : ''; ?>>Tw 2 (Apr-Jun)</option>
                            <option value="3" <?php echo $quarter == '3' ? 'selected' : ''; ?>>Tw 3 (Jul-Sep)</option>
                            <option value="4" <?php echo $quarter == '4' ? 'selected' : ''; ?>>Tw 4 (Okt-Des)</option>
                        </select>
                    </div>

                    <!-- Pos 6: Keyword Search & Action Buttons -->
                    <div class="filter-group" style="grid-column: span 1; min-width: 300px;">
                        <label style="display: block; font-size: 11px; font-weight: 800; color: var(--navy); margin-bottom: 8px; text-transform: uppercase;">Cari Kata Kunci</label>
                        <div style="display: flex; gap: 8px;">
                            <div style="position: relative; flex: 1;">
                                <input type="text" name="q" class="stitch-select" placeholder="Cari kegiatan..." value="<?php echo htmlspecialchars($keyword); ?>" style="padding-left: 35px; height: 45px; border-radius: 10px;">
                                <i class="fas fa-search" style="position: absolute; left: 12px; top: 15px; color: #94a3b8; font-size: 12px;"></i>
                            </div>
                            <button type="submit" class="btn btn-primary" style="height: 45px; width: 45px; justify-content: center; border-radius: 7px; flex-shrink: 0;" title="Terapkan Filter">
                                <i class="fas fa-filter"></i>
                            </button>
                            <a href="daftar.php" class="btn" style="background:#f1f5f9; color:#64748b; height: 45px; width: 45px; display: flex; align-items: center; justify-content: center; border-radius: 7px; flex-shrink: 0;" title="Reset Filter">
                                <i class="fas fa-sync-alt"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="card" style="padding: 0; overflow: hidden; border-radius: 20px; box-shadow: var(--shadow); margin-bottom: 30px;">
            <div style="padding: 20px 25px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #fafafa;">
                <div style="font-weight: 700; color: var(--navy); font-size: 14px;">Data Dokumentasi Foto/Video</div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <label style="font-size: 12px; color: #64748b; font-weight: 600;">Tampilkan:</label>
                    <select name="limit" class="stitch-select" onchange="this.form.submit()" style="width: 80px; height: 35px; padding: 0 10px; font-size: 12px; border-radius: 7px;">
                        <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                        <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25</option>
                        <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                        <option value="all" <?php echo $limit === 'all' ? 'selected' : ''; ?>>Semua</option>
                    </select>
                </div>
            </div>

            <div class="table-wrapper" style="padding: 0 10px;">
                <table class="stitch-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">No</th>
                            <th>Thumbnail</th>
                            <th>Info Kegiatan</th>
                            <th>Lokasi</th>
                            <th>Tautan G-Drive</th>
                            <th style="width: 100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = ($limit === 'all') ? 1 : ($offset + 1);
                        foreach ($docs as $d): 
                        ?>
                        <tr>
                            <td><span style="color: #ccc; font-weight: 700;"><?php echo str_pad($no++, 2, '0', STR_PAD_LEFT); ?></span></td>
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

            <!-- Pagination Controls -->
            <div style="padding: 20px;">
                <?php if ($limit !== 'all' && $total_pages > 1): ?>
                    <div class="pagination">
                        <a href="<?php echo pgUrl(1); ?>" class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>" title="First"><i class="fas fa-angle-double-left"></i></a>
                        <a href="<?php echo pgUrl($page - 1); ?>" class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>"><i class="fas fa-chevron-left"></i></a>
                        
                        <?php 
                        $start = max(1, $page - 2);
                        $end = min($total_pages, $page + 2);
                        for($i = $start; $i <= $end; $i++): 
                        ?>
                            <a href="<?php echo pgUrl($i); ?>" class="page-link <?php echo $page == $i ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>

                        <a href="<?php echo pgUrl($page + 1); ?>" class="page-link <?php echo $page >= $total_pages ? 'disabled' : ''; ?>"><i class="fas fa-chevron-right"></i></a>
                        <a href="<?php echo pgUrl($total_pages); ?>" class="page-link <?php echo $page >= $total_pages ? 'disabled' : ''; ?>" title="Last"><i class="fas fa-angle-double-right"></i></a>
                    </div>
                    <div class="pagination-status">
                        Menampilkan <?php echo $offset + 1; ?> - <?php echo min($offset + $limit, $total_records); ?> dari <?php echo $total_records; ?> data dokumentasi.
                    </div>
                <?php endif; ?>
                <?php if ($limit === 'all' && $total_records > 0): ?>
                    <div class="pagination-status">
                        Menampilkan seluruh <?php echo $total_records; ?> data dokumentasi.
                    </div>
                <?php endif; ?>
            </div>
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
        // Flatpickr Initialization for Compact Range Picker
        const startVal = document.getElementById('start_date_val').value;
        const endVal = document.getElementById('end_date_val').value;
        
        const fp = flatpickr("#range_trigger", {
            mode: "range",
            dateFormat: "Y-m-d",
            defaultDate: (startVal && endVal) ? [startVal, endVal] : null,
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates.length === 2) {
                    const start = selectedDates[0];
                    const end = selectedDates[1];
                    
                    // Update Hidden Inputs
                    document.getElementById('start_date_val').value = instance.formatDate(start, "Y-m-d");
                    document.getElementById('end_date_val').value = instance.formatDate(end, "Y-m-d");
                    
                    // Update Display Text
                    document.getElementById('display_start').innerText = formatDateIndo(start);
                    document.getElementById('display_end').innerText = formatDateIndo(end);
                }
            }
        });

        function formatDateIndo(date) {
            const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            const d = new Date(date);
            return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
        }

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
