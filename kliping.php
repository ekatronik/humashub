<?php
// kliping.php (Frontend Archive)
require_once __DIR__ . '/config/database.php';

$query = "SELECT c.*, cat.name as category_name, m.media_name 
          FROM clippings c 
          LEFT JOIN categories cat ON c.category_id = cat.id 
          LEFT JOIN media m ON c.media_id = m.id 
          ORDER BY c.clipping_date DESC";
$clippings = $pdo->query($query)->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arsip Kliping | Humas Hub</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        nav {
            padding: 20px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .container { max-width: 1200px; margin: 50px auto; padding: 0 20px; }
        .page-header { margin-bottom: 40px; }
        .page-header h1 { font-size: 36px; color: var(--dark); margin-bottom: 10px; }
        .filter-bar {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 40px;
            box-shadow: var(--shadow);
            display: flex;
            gap: 20px;
            align-items: center;
        }
    </style>
</head>
<body>
    <nav>
        <div class="logo" style="display:flex; align-items:center; gap:10px; color: var(--primary); font-weight: 800; font-size: 20px;">
            <i class="fas fa-university fa-lg"></i>
            <span>HUMAS HUB</span>
        </div>
        <ul style="display: flex; gap: 30px; list-style: none;">
            <li><a href="index.php" style="text-decoration:none; color:var(--text-main); font-weight:600;">Beranda</a></li>
            <li><a href="kliping.php" style="text-decoration:none; color:var(--primary); font-weight:600;">Kliping Koran</a></li>
            <li><a href="admin/login.php" class="btn btn-primary" style="padding: 8px 20px; color:white;">Login</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>Arsip Kliping Koran</h1>
            <p style="color: var(--text-muted);">Menampilkan seluruh dokumentasi kliping media cetak UIN Ar-Raniry.</p>
        </div>

        <div class="filter-bar">
            <div style="flex: 1;">
                <input type="text" class="btn" style="width: 100%; border: 1px solid #ddd; text-align: left;" placeholder="Cari judul berita...">
            </div>
            <select class="btn" style="border: 1px solid #ddd;">
                <option>Semua Kategori</option>
            </select>
            <select class="btn" style="border: 1px solid #ddd;">
                <option>Semua Media</option>
            </select>
            <button class="btn btn-primary">Filter</button>
        </div>

        <div class="clipping-grid">
            <?php foreach ($clippings as $c): ?>
            <div class="clipping-card">
                <?php if ($c['file_path']): ?>
                    <img src="uploads/clippings/<?php echo $c['file_path']; ?>" alt="Kliping">
                <?php else: ?>
                    <div style="height:200px; background:#eee; display:flex; align-items:center; justify-content:center; color:#ccc;">
                        <i class="fas fa-image fa-3x"></i>
                    </div>
                <?php endif; ?>
                <div class="content">
                    <div style="font-size: 12px; color: var(--primary); font-weight: 700; margin-bottom: 5px;">
                        <?php echo strtoupper($c['category_name']); ?> | <?php echo strtoupper($c['media_name']); ?>
                    </div>
                    <h3 style="font-size: 18px; margin-bottom: 10px; line-height: 1.4;"><?php echo $c['title']; ?></h3>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px;">
                        <span style="font-size: 13px; color: var(--text-muted);"><i class="far fa-calendar"></i> <?php echo date('d M Y', strtotime($c['clipping_date'])); ?></span>
                        <a href="uploads/clippings/<?php echo $c['file_path']; ?>" target="_blank" style="color: var(--primary); font-weight: 700; text-decoration:none; font-size: 14px;">BACA <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($clippings)): ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 100px 0; color: var(--text-muted);">
                <i class="fas fa-search fa-3x" style="margin-bottom: 20px; opacity: 0.2;"></i>
                <p>Belum ada arsip kliping yang ditemukan.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <footer style="background: var(--dark); color: white; padding: 40px 20px; text-align: center; margin-top: 100px;">
        <p style="font-size: 14px; opacity: 0.5;">&copy; 2026 UIN Ar-Raniry Banda Aceh. All Rights Reserved.</p>
    </footer>
</body>
</html>
