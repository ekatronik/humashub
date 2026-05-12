<?php
// admin/kliping_daftar.php
require_once __DIR__ . '/../../includes/auth.php';
checkAccess(['Super Admin', 'Pranata Humas', 'Operator Kliping']);

// Auto-repair database: Pastikan kolom media_scale ada
try {
    $pdo->query("SELECT media_scale FROM media LIMIT 1");
} catch (Exception $e) {
    $pdo->exec("ALTER TABLE media ADD COLUMN media_scale VARCHAR(50) AFTER media_logo");
}

// Hapus Kliping
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("SELECT file_path FROM clippings WHERE id = ?");
    $stmt->execute([$id]);
    $file = $stmt->fetch();
    if ($file && $file['file_path'] && file_exists("upload-kliping/" . $file['file_path'])) {
        unlink("upload-kliping/" . $file['file_path']);
    }
    // Log Aktivitas
    $stmt_title = $pdo->prepare("SELECT title FROM clippings WHERE id = ?");
    $stmt_title->execute([$id]);
    $c_title = $stmt_title->fetchColumn();
    write_log($pdo, "Menghapus kliping berita: $c_title", "Kliping", $id);

    $pdo->prepare("DELETE FROM clipping_category_rel WHERE clipping_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM clippings WHERE id = ?")->execute([$id]);
    header("Location: kliping_daftar.php?success=Deleted");
    exit();
}

// ---------------------------------------------------------
// PAGINATION & FILTER LOGIC
// ---------------------------------------------------------
$limit = isset($_GET['limit']) ? ($_GET['limit'] === 'all' ? 'all' : (int)$_GET['limit']) : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$filter_year = $_GET['year'] ?? '';
$filter_media = $_GET['media'] ?? '';
$filter_category = $_GET['category'] ?? '';

$where_clauses = [];
$params = [];
if ($filter_year) { $where_clauses[] = "YEAR(c.clipping_date) = ?"; $params[] = $filter_year; }
if ($filter_media) { $where_clauses[] = "c.media_id = ?"; $params[] = $filter_media; }
if ($filter_category) { 
    $where_clauses[] = "c.id IN (SELECT clipping_id FROM clipping_category_rel WHERE category_id = ?)"; 
    $params[] = $filter_category; 
}
$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Count Total for Pagination
$count_query = "SELECT COUNT(DISTINCT c.id) FROM clippings c $where_sql";
$stmt_count = $pdo->prepare($count_query);
$stmt_count->execute($params);
$total_records = $stmt_count->fetchColumn();

// Pagination Math
$total_pages = ($limit === 'all') ? 1 : ceil($total_records / $limit);
if ($page > $total_pages && $total_pages > 0) $page = $total_pages;
$offset = ($limit === 'all') ? 0 : ($page - 1) * $limit;

// Main Query
$limit_sql = ($limit === 'all') ? "" : "LIMIT $limit OFFSET $offset";
$query = "SELECT c.*, m.media_name, m.media_logo, m.media_scale, u.full_name as author, u.username,
          GROUP_CONCAT(cat.name SEPARATOR ', ') as categories_names
          FROM clippings c 
          LEFT JOIN media m ON c.media_id = m.id 
          LEFT JOIN users u ON c.created_by = u.id 
          LEFT JOIN clipping_category_rel rel ON c.id = rel.clipping_id
          LEFT JOIN categories cat ON rel.category_id = cat.id
          $where_sql
          GROUP BY c.id
          ORDER BY c.clipping_date DESC
          $limit_sql";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$clippings = $stmt->fetchAll();

// Data for Filters
$years = $pdo->query("SELECT DISTINCT YEAR(clipping_date) as yr FROM clippings ORDER BY yr DESC")->fetchAll();
$categories_list = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$media_list = $pdo->query("SELECT * FROM media WHERE media_type = 'cetak' ORDER BY media_name ASC")->fetchAll();

// Function to build pagination URL
function pgUrl($p) {
    $params = $_GET;
    $params['page'] = $p;
    return "?" . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Kliping | Humas Hub</title>
    <link rel="stylesheet" href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) == 'admin') ? '../' : '../../'; ?>assets/themes/<?php echo get_setting('app_theme', 'default'); ?>/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        // Set worker path
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    </script>
    <style>
        .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 30px; }
        .page-link { padding: 8px 16px; border-radius: 10px; background: white; border: 1px solid #e2e8f0; color: #64748b; text-decoration: none; font-weight: 600; font-size: 14px; transition: 0.2s; }
        .page-link:hover { border-color: var(--primary); color: var(--primary); }
        .page-link.active { background: var(--primary); color: white; border-color: var(--primary); }
        .page-link.disabled { opacity: 0.5; pointer-events: none; }
        .pagination-status { text-align: center; font-size: 13px; color: #94a3b8; margin-top: 15px; }
    </style>
</head>
<body data-theme="<?php echo get_setting('theme_mode', 'light'); ?>">
    <?php include '../common/sidebar.php'; ?>

    <div class="main-content">
        <header class="content-header">
            <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                <div>
                    <h1 class="main-title">Daftar Kliping Berita</h1>
                    <p class="sub-title">Kelola arsip berita media cetak dengan fitur navigasi halaman.</p>
                </div>
                <a href="kliping_tambah.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Tambah Kliping
                </a>
            </div>

            <!-- Filter Bar -->
            <form method="GET" class="filter-bar">
                <div class="filter-group">
                    <label>Tahun</label>
                    <select name="year" class="stitch-select">
                        <option value="">Semua</option>
                        <?php foreach ($years as $y): ?>
                            <option value="<?php echo $y['yr']; ?>" <?php echo $filter_year == $y['yr'] ? 'selected' : ''; ?>><?php echo $y['yr']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Media</label>
                    <select name="media" class="stitch-select">
                        <option value="">Semua</option>
                        <?php foreach ($media_list as $m): ?>
                            <option value="<?php echo $m['id']; ?>" <?php echo $filter_media == $m['id'] ? 'selected' : ''; ?>><?php echo $m['media_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Kategori</label>
                    <select name="category" class="stitch-select">
                        <option value="">Semua</option>
                        <?php foreach ($categories_list as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $filter_category == $cat['id'] ? 'selected' : ''; ?>><?php echo $cat['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Tampilkan</label>
                    <select name="limit" class="stitch-select" onchange="this.form.submit()">
                        <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                        <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25</option>
                        <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                        <option value="all" <?php echo $limit === 'all' ? 'selected' : ''; ?>>Semua</option>
                    </select>
                </div>
                <div style="display: flex; align-items: center; gap: 10px; margin-top: 18px;">
                    <button type="submit" class="btn btn-primary" style="height: 45px;">
                        <i class="fas fa-search"></i> Cari
                    </button>
                    <?php if ($filter_year || $filter_media || $filter_category || $limit != 10): ?>
                        <a href="kliping_daftar.php" class="btn" style="background:#eee; color:#666; height: 45px;">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </header>

        <div class="table-wrapper">
            <table class="stitch-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">No</th>
                        <th>Informasi Berita</th>
                        <th>Media & Skala</th>
                        <th>Operator</th>
                        <th style="width: 100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = ($limit === 'all') ? 1 : ($offset + 1);
                    foreach ($clippings as $c): 
                    ?>
                    <tr>
                        <td><span style="color: #ccc; font-weight: 700;"><?php echo str_pad($no++, 2, '0', STR_PAD_LEFT); ?></span></td>
                        <td>
                            <a href="kliping_edit.php?id=<?php echo $c['id']; ?>" class="col-judul" style="text-decoration: none; color: inherit; cursor: pointer; display: block; hover: text-decoration: underline;">
                                <?php echo $c['title']; ?>
                            </a>
                            <div class="col-meta">
                                <i class="fas fa-calendar-alt"></i> <?php echo tgl_indo($c['clipping_date']); ?>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: var(--navy); font-size: 13px;"><?php echo $c['media_name']; ?></div>
                            <div style="font-size: 11px; color: var(--primary); font-weight: 700; text-transform: uppercase; margin-top: 2px;">
                                <i class="fas fa-layer-group"></i> <?php echo $c['media_scale'] ?: 'Lokal'; ?>
                            </div>
                        </td>
                        <td>
                            <div style="font-size: 12px; font-weight: 700; color: #64748b;">
                                <i class="fas fa-user-circle" style="color: #e2e8f0; margin-right: 4px;"></i>
                                <?php echo $c['username'] ?? 'admin'; ?>
                            </div>
                        </td>
                        <td>
                            <div style="display: flex; gap: 10px;">
                                <?php if ($c['file_path']): ?>
                                    <button onclick='viewFile(<?php echo json_encode($c); ?>)' class="btn-circle btn-circle-view" title="Lihat">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php endif; ?>
                                <a href="?delete=<?php echo $c['id']; ?>" onclick="return confirm('Hapus kliping ini?')" class="btn-circle btn-circle-delete" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
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
                Menampilkan <?php echo $offset + 1; ?> - <?php echo min($offset + $limit, $total_records); ?> dari <?php echo $total_records; ?> data kliping.
            </div>
        <?php endif; ?>
        <?php if ($limit === 'all' && $total_records > 0): ?>
            <div class="pagination-status">
                Menampilkan seluruh <?php echo $total_records; ?> data kliping.
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Viewer -->
    <div id="fileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div style="flex: 1;">
                    <div id="modalDate" style="font-size: 15px; color: var(--primary); font-weight: 700; margin-bottom: 5px; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-calendar-day"></i>
                        <span id="modalDateText"></span>
                    </div>
                    <div id="modalTitle" style="font-size: 20px; font-weight: 800; color: var(--navy); line-height: 1.3;"></div>
                </div>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body-wrapper" style="display: flex; flex: 1; min-height: 0;">
                <div class="modal-info-col" style="width: 320px; padding: 25px; background: #f8fafc; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; gap: 20px; overflow-y: auto;">
                    <div id="modalLogoContainer" style="text-align: center; background: white; padding: 15px; border-radius: 16px; border: 1px solid #e2e8f0;"></div>
                    <div class="info-section-grid" style="display: grid; grid-template-columns: 1fr; gap: 15px;">
                        <div class="info-section">
                            <label style="display:block; font-size: 10px; color: #94a3b8; text-transform: uppercase; font-weight: 800; margin-bottom: 8px; letter-spacing: 1px;">Detail Media</label>
                            <div id="modalMediaName" style="font-weight: 700; color: var(--navy); font-size: 15px;"></div>
                            <div id="modalMediaScale" style="font-size: 12px; color: var(--primary); font-weight: 700; margin-top: 2px;"></div>
                        </div>
                        <div class="info-section">
                            <label style="display:block; font-size: 10px; color: #94a3b8; text-transform: uppercase; font-weight: 800; margin-bottom: 8px; letter-spacing: 1px;">Lokasi Arsip Fisik</label>
                            <div style="background: white; border-radius: 12px; padding: 12px; border: 1px solid #e2e8f0; display: flex; flex-direction: column; gap: 8px;">
                                <div style="font-size: 13px; color: var(--navy);"><i class="fas fa-building" style="width: 20px; color: #cbd5e0;"></i> <span id="modalBuilding">-</span></div>
                                <div style="font-size: 13px; color: var(--navy);"><i class="fas fa-door-open" style="width: 20px; color: #cbd5e0;"></i> <span id="modalRoom">-</span></div>
                                <div style="font-size: 13px; color: var(--navy);"><i class="fas fa-th-large" style="width: 20px; color: #cbd5e0;"></i> <span id="modalRack">-</span></div>
                                <div style="font-size: 13px; color: var(--navy);"><i class="fas fa-folder-open" style="width: 20px; color: #cbd5e0;"></i> <span id="modalFolder">-</span></div>
                            </div>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;" class="mobile-grid-1">
                        <div class="info-section">
                            <label style="display:block; font-size: 10px; color: #94a3b8; text-transform: uppercase; font-weight: 800; margin-bottom: 8px; letter-spacing: 1px;">Status Peminjaman</label>
                            <div id="modalBorrow" style="font-weight: 700; font-size: 13px;"></div>
                        </div>
                        <div class="info-section">
                            <label style="display:block; font-size: 10px; color: #94a3b8; text-transform: uppercase; font-weight: 800; margin-bottom: 8px; letter-spacing: 1px;">Kategori</label>
                            <div id="modalCategory" style="display: flex; flex-wrap: wrap; gap: 5px;"></div>
                        </div>
                    </div>
                    <div class="info-section" style="background: #fff; border-radius: 12px; padding: 12px; border: 1px solid #e2e8f0;">
                        <label style="display:block; font-size: 9px; color: #94a3b8; text-transform: uppercase; font-weight: 800; margin-bottom: 5px;">Metadata System</label>
                        <div style="font-size: 11px; color: #64748b; display: flex; flex-direction: column; gap: 5px;">
                            <div><i class="fas fa-user-edit" style="width: 15px;"></i> Operator: <span id="modalOperator" style="font-weight: 700;">-</span></div>
                            <div><i class="fas fa-clock" style="width: 15px;"></i> Upload: <span id="modalUploadDate">-</span></div>
                        </div>
                    </div>
                    <div style="margin-top: auto; padding-top: 20px;">
                        <a id="btnExportPdf" href="#" target="_blank" class="btn btn-primary" style="width:100%; justify-content:center; background: #e67e22; border:none; box-shadow: 0 4px 12px rgba(230, 126, 34, 0.2);">
                            <i class="fas fa-file-pdf"></i> Export ke PDF
                        </a>
                    </div>
                </div>
                <div class="modal-viewer-col" style="flex: 1; background: #475569; position: relative; overflow: auto; display: flex; flex-direction: column; align-items: center;">
                    <!-- Toolbar PDF.js Manual -->
                    <div class="pdf-toolbar" style="width: 100%; background: #1e293b; padding: 10px 20px; display: flex; align-items: center; gap: 15px; position: sticky; top: 0; z-index: 10; border-bottom: 1px solid #334155;">
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <button onclick="changeZoom(-0.2)" class="btn-tool" title="Zoom Out"><i class="fas fa-search-minus"></i></button>
                            <span id="zoomPercent" style="color: white; font-size: 12px; min-width: 45px; text-align: center;">100%</span>
                            <button onclick="changeZoom(0.2)" class="btn-tool" title="Zoom In"><i class="fas fa-search-plus"></i></button>
                        </div>
                        <div style="height: 20px; width: 1px; background: #475569;"></div>
                        <button onclick="downloadFile()" class="btn-tool" title="Download Original"><i class="fas fa-download"></i> Download</button>
                        <button onclick="window.print()" class="btn-tool" title="Print"><i class="fas fa-print"></i></button>
                    </div>

                    <div id="pdfLoading" style="display: none; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; text-align: center;">
                        <i class="fas fa-circle-notch fa-spin fa-3x"></i>
                        <p style="margin-top: 10px; font-weight: 600;">Memuat PDF...</p>
                    </div>
                    <div id="modalBody" style="width: 100%; height: auto; padding: 20px; display: flex; flex-direction: column; align-items: center;"></div>
                </div>
            </div>
        </div>
    </div>

    <style>
    .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(15, 23, 42, 0.8); backdrop-filter: blur(8px); }
    .modal-content { background-color: #fff; margin: 2vh auto; border-radius: 24px; width: 95%; max-width: 1300px; height: 96vh; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
    .modal-header { padding: 20px 30px; background: #fff; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
    .close { color: #64748b; font-size: 32px; font-weight: bold; cursor: pointer; transition: 0.2s; }
    .close:hover { color: #ef4444; }
    #modalBody iframe, #modalBody img { width: 100%; height: 100%; border: none; object-fit: contain; }
    .btn-tool { background: #334155; border: none; color: white; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 13px; display: flex; align-items: center; gap: 5px; transition: 0.2s; }
    .btn-tool:hover { background: var(--primary); }
    @media (max-width: 768px) {
        .modal-content { height: 100vh; margin: 0; width: 100%; border-radius: 0; }
        .modal-header { padding: 15px 20px; }
        .modal-body-wrapper { flex-direction: column !important; overflow-y: auto !important; height: auto; }
        .modal-info-col { width: 100% !important; border-right: none !important; padding: 20px !important; order: 1; height: auto !important; overflow: visible !important; }
        .modal-viewer-col { width: 100% !important; height: auto !important; min-height: 500px; padding: 0 !important; order: 2; overflow: visible !important; }
        #modalBody canvas, #modalBody img { max-width: 100%; height: auto !important; }
        .modal-info-col { background: #fff; }
        .mobile-grid-1 { grid-template-columns: 1fr !important; }
        .info-section-grid { gap: 10px !important; }
        .pdf-toolbar { position: sticky; top: 0; }
    }
    </style>

    <script>
    let currentPdf = null;
    let currentScale = 1.2;
    let currentUrl = "";

    function changeZoom(delta) {
        currentScale = Math.min(Math.max(0.5, currentScale + delta), 3.0);
        document.getElementById('zoomPercent').innerText = Math.round(currentScale * 100) + '%';
        renderPages();
    }

    function downloadFile() {
        if (currentUrl) {
            const link = document.createElement('a');
            link.href = currentUrl;
            link.download = currentUrl.split('/').pop();
            link.click();
        }
    }

    function tgl_indo_js(dateStr) {
        const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        const d = new Date(dateStr);
        return days[d.getDay()] + ', ' + d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
    }

    function renderPages() {
        if (!currentPdf) return;
        const body = document.getElementById('modalBody');
        body.innerHTML = "";
        
        for (let pageNum = 1; pageNum <= currentPdf.numPages; pageNum++) {
            currentPdf.getPage(pageNum).then(function(page) {
                const viewport = page.getViewport({scale: currentScale});
                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                canvas.style.maxWidth = "100%";
                canvas.style.height = "auto";
                canvas.style.marginBottom = "20px";
                canvas.style.boxShadow = "0 10px 25px rgba(0,0,0,0.3)";
                canvas.style.borderRadius = "8px";
                
                body.appendChild(canvas);

                const renderContext = {
                    canvasContext: context,
                    viewport: viewport
                };
                page.render(renderContext);
            });
        }
    }

    function viewFile(data) {
        const modal = document.getElementById('fileModal');
        document.getElementById('modalTitle').innerText = data.title;
        document.getElementById('modalDateText').innerText = tgl_indo_js(data.clipping_date);
        document.getElementById('modalMediaName').innerText = data.media_name;
        document.getElementById('modalMediaScale').innerText = data.media_scale || 'Media Lokal';
        document.getElementById('modalBuilding').innerText = data.storage_building || '-';
        document.getElementById('modalRoom').innerText = data.storage_room || '-';
        document.getElementById('modalRack').innerText = data.storage_rack || '-';
        document.getElementById('modalFolder').innerText = data.storage_folder || '-';
        document.getElementById('modalOperator').innerText = data.author || data.username || 'Admin';
        document.getElementById('modalUploadDate').innerText = data.created_at ? tgl_indo_js(data.created_at.split(' ')[0]) : '-';

        const borrow = document.getElementById('modalBorrow');
        if (data.is_borrowable == 1) { borrow.innerHTML = '<span style="color: #10b981;"><i class="fas fa-check-circle"></i> BOLEH DIPINJAM</span>'; } 
        else { borrow.innerHTML = '<span style="color: #ef4444;"><i class="fas fa-times-circle"></i> ARSIP TETAP</span>'; }
        const catContainer = document.getElementById('modalCategory');
        catContainer.innerHTML = '';
        if (data.categories_names) {
            data.categories_names.split(', ').forEach((c, i) => {
                catContainer.innerHTML += `<span class="badge badge-soft-${(i%6)+1}">${c}</span>`;
            });
        }
        document.getElementById('btnExportPdf').href = 'kliping_export.php?id=' + data.id;
        const logoContainer = document.getElementById('modalLogoContainer');
        if (data.media_logo) { logoContainer.innerHTML = `<img src="../modul-setelan/upload-media/${data.media_logo}" style="max-width: 100%; max-height: 100px; object-fit: contain;">`; } 
        else { logoContainer.innerHTML = `<div style="padding: 20px; color: #cbd5e0; font-size: 11px; font-weight: 700; text-transform: uppercase;"><i class="fas fa-image"></i> No Logo</div>`; }

        currentUrl = 'upload-kliping/' + data.file_path;
        const ext = data.file_path.split('.').pop().toLowerCase();
        const loading = document.getElementById('pdfLoading');
        const body = document.getElementById('modalBody');
        const toolbar = document.querySelector('.pdf-toolbar');
        
        body.innerHTML = "";
        loading.style.display = "none";
        toolbar.style.display = "none";
        currentPdf = null;
        currentScale = 1.2;
        document.getElementById('zoomPercent').innerText = '120%';

        if (ext === 'pdf') { 
            loading.style.display = "block";
            toolbar.style.display = "flex";
            
            const loadingTask = pdfjsLib.getDocument(currentUrl);
            loadingTask.promise.then(function(pdf) {
                loading.style.display = "none";
                currentPdf = pdf;
                renderPages();
            }, function (reason) {
                loading.style.display = "none";
                console.error(reason);
                body.innerHTML = `<div style="color:white; padding: 20px; text-align:center;">Gagal memuat PDF: ${reason.message}</div>`;
            });
        } else { 
            body.innerHTML = `<img src="${currentUrl}" alt="Preview" style="width:100%; height:auto; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.3);">`; 
        }
        modal.style.display = "block";
    }
    function closeModal() { document.getElementById('fileModal').style.display = "none"; document.getElementById('modalBody').innerHTML = ""; currentPdf = null; }
    window.onclick = function(event) { if (event.target == document.getElementById('fileModal')) closeModal(); }
    </script>
</body>
</html>
