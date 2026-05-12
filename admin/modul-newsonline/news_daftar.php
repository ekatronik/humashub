<?php
// admin/news_daftar.php
require_once __DIR__ . '/../../includes/auth.php';
checkAccess(['Super Admin', 'Pranata Humas', 'Operator Berita Online']);

// Hapus Berita
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM news_online WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: news_daftar.php?success=Deleted");
    exit();
}

$filter_year = $_GET['year'] ?? '';
$filter_media = $_GET['media'] ?? '';
$filter_category = $_GET['category'] ?? '';
$filter_source = $_GET['source'] ?? '';

$where_clauses = [];
$params = [];
if ($filter_year) { $where_clauses[] = "YEAR(news_date) = ?"; $params[] = $filter_year; }
if ($filter_media) { $where_clauses[] = "media_id = ?"; $params[] = $filter_media; }
if ($filter_category) { $where_clauses[] = "category_id = ?"; $params[] = $filter_category; }
if ($filter_source) { $where_clauses[] = "source_type = ?"; $params[] = $filter_source; }
$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

$query = "SELECT n.*, m.media_name, cat.name as category_name, u.full_name as author 
          FROM news_online n 
          LEFT JOIN media m ON n.media_id = m.id 
          LEFT JOIN categories cat ON n.category_id = cat.id 
          LEFT JOIN users u ON n.created_by = u.id 
          $where_sql 
          ORDER BY n.news_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$news = $stmt->fetchAll();

// Filter Data
$years = $pdo->query("SELECT DISTINCT YEAR(news_date) as yr FROM news_online ORDER BY yr DESC")->fetchAll();
$categories_list = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$media_list = $pdo->query("SELECT * FROM media WHERE media_type = 'online' ORDER BY media_name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Berita Online | Humas Hub</title>
    <link rel="stylesheet" href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) == 'admin') ? '../' : '../../'; ?>assets/themes/<?php echo get_setting('app_theme', 'default'); ?>/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body data-theme="<?php echo get_setting('theme_mode', 'light'); ?>">
    <?php include '../common/sidebar.php'; ?>

    <div class="main-content">
        <header class="content-header" style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px;">
            <div>
                <h1 class="main-title">Daftar Berita Online</h1>
                <p class="sub-title">Arsip publikasi berita dari berbagai media daring.</p>
            </div>
            <a href="news_tambah.php" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Berita</a>
        </header>

        <form method="GET" class="filter-bar" style="background: white; padding: 20px; border-radius: 15px; margin-bottom: 30px; display: flex; gap: 15px; align-items: flex-end; box-shadow: var(--shadow);">
            <div class="filter-group">
                <label style="display: block; font-size: 12px; margin-bottom: 5px;">Tahun</label>
                <select name="year" class="stitch-select">
                    <option value="">Semua</option>
                    <?php foreach ($years as $y): ?>
                        <option value="<?php echo $y['yr']; ?>" <?php echo $filter_year == $y['yr'] ? 'selected' : ''; ?>><?php echo $y['yr']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label style="display: block; font-size: 12px; margin-bottom: 5px;">Media</label>
                <select name="media" class="stitch-select">
                    <option value="">Semua</option>
                    <?php foreach ($media_list as $m): ?>
                        <option value="<?php echo $m['id']; ?>" <?php echo $filter_media == $m['id'] ? 'selected' : ''; ?>><?php echo $m['media_name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label style="display: block; font-size: 12px; margin-bottom: 5px;">Kategori</label>
                <select name="category" class="stitch-select">
                    <option value="">Semua</option>
                    <?php foreach ($categories_list as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $filter_category == $cat['id'] ? 'selected' : ''; ?>><?php echo $cat['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label style="display: block; font-size: 12px; margin-bottom: 5px;">Sumber</label>
                <select name="source" class="stitch-select">
                    <option value="">Semua</option>
                    <option value="Rilis Humas" <?php echo $filter_source == 'Rilis Humas' ? 'selected' : ''; ?>>Rilis Humas</option>
                    <option value="Liputan Wartawan" <?php echo $filter_source == 'Liputan Wartawan' ? 'selected' : ''; ?>>Liputan Wartawan</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
        </form>

        <div class="table-wrapper">
            <table class="stitch-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Judul Berita</th>
                        <th>Media & Kategori</th>
                        <th>Sumber</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($news as $n): ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td>
                            <div class="col-judul"><?php echo $n['title']; ?></div>
                            <div class="col-meta"><i class="fas fa-calendar"></i> <?php echo tgl_indo($n['news_date']); ?></div>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: var(--navy);"><?php echo $n['media_name']; ?></div>
                            <div style="font-size: 11px; color: var(--text-muted);"><?php echo $n['category_name']; ?></div>
                        </td>
                        <td><span class="badge <?php echo $n['source_type'] == 'Rilis Humas' ? 'badge-soft-1' : 'badge-soft-3'; ?>"><?php echo $n['source_type']; ?></span></td>
                        <td>
                            <div style="display: flex; gap: 10px;">
                                <a href="<?php echo $n['news_link']; ?>" target="_blank" class="btn-circle btn-circle-view" title="Buka Link"><i class="fas fa-external-link-alt"></i></a>
                                <a href="news_edit.php?id=<?php echo $n['id']; ?>" class="btn-circle btn-circle-edit"><i class="fas fa-pen"></i></a>
                                <a href="?delete=<?php echo $n['id']; ?>" onclick="return confirm('Hapus berita ini?')" class="btn-circle btn-circle-delete"><i class="fas fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
