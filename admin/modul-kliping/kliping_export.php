<?php
// admin/kliping_export.php
require_once __DIR__ . '/../../includes/auth.php';
checkAccess(['Super Admin', 'Pranata Humas', 'Operator Kliping']);

if (!isset($_GET['id'])) {
    die("ID tidak ditemukan.");
}

$id = $_GET['id'];
$query = "SELECT c.*, cat.name as category_name, m.media_name, m.media_logo 
          FROM clippings c 
          LEFT JOIN categories cat ON c.category_id = cat.id 
          LEFT JOIN media m ON c.media_id = m.id 
          WHERE c.id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$id]);
$c = $stmt->fetch();

if (!$c) {
    die("Data tidak ditemukan.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Export Kliping - <?php echo htmlspecialchars($c['title']); ?></title>
    <style>
        body { font-family: 'Arial', sans-serif; padding: 40px; color: #333; line-height: 1.6; }
        .export-header { border-bottom: 2px solid #006837; padding-bottom: 20px; margin-bottom: 30px; display: flex; align-items: center; justify-content: space-between; }
        .logo-box { width: 150px; text-align: center; }
        .logo-box img { max-width: 100%; max-height: 80px; object-fit: contain; }
        .info-box { flex: 1; margin-left: 30px; }
        .date { font-size: 12px; color: #666; margin-bottom: 5px; }
        .title { font-size: 20px; font-weight: bold; color: #000; margin-bottom: 10px; }
        .meta { font-size: 13px; color: #444; }
        .meta span { font-weight: bold; color: #006837; }
        
        .clipping-content { text-align: center; margin-top: 20px; }
        .clipping-content img { max-width: 100%; border: 1px solid #ddd; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .clipping-content iframe { width: 100%; height: 1000px; border: none; }
        
        .footer { margin-top: 50px; font-size: 10px; text-align: center; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
        
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
            button { display: none; }
        }
    </style>
</head>
<body data-theme="<?php echo get_setting('theme_mode', 'light'); ?>">
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #006837; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">
            💾 Simpan ke PDF / Cetak
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #eee; color: #333; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
            Tutup
        </button>
    </div>

    <div class="export-header">
        <div class="logo-box">
            <?php if ($c['media_logo']): ?>
                <img src="../modul-setelan/upload-media/<?php echo $c['media_logo']; ?>" alt="Logo Media">
            <?php else: ?>
                <div style="font-weight: bold; color: #ccc;">[LOGO MEDIA]</div>
            <?php endif; ?>
        </div>
        <div class="info-box">
            <div class="date"><?php echo tgl_indo($c['clipping_date']); ?></div>
            <div class="title"><?php echo htmlspecialchars($c['title']); ?></div>
            <div class="meta">
                Media: <span><?php echo htmlspecialchars($c['media_name']); ?></span> | 
                Kategori: <span><?php echo htmlspecialchars($c['category_name']); ?></span>
            </div>
        </div>
    </div>

    <div class="clipping-content">
        <?php 
        $ext = strtolower(pathinfo($c['file_path'], PATHINFO_EXTENSION));
        if ($ext === 'pdf'): 
        ?>
            <p style="color: #666; font-size: 12px; margin-bottom: 10px;">(Halaman Lampiran PDF)</p>
            <iframe src="upload-kliping/<?php echo $c['file_path']; ?>#toolbar=0"></iframe>
        <?php else: ?>
            <img src="upload-kliping/<?php echo $c['file_path']; ?>" alt="Scan Kliping">
        <?php endif; ?>
    </div>

    <div class="footer">
        Dicetak dari Sistem Humas Hub UIN Ar-Raniry pada <?php echo date('d/m/Y H:i'); ?> WIB
    </div>

    <script>
        // Otomatis buka dialog print saat halaman dimuat
        window.onload = function() {
            // setTimeout(function() { window.print(); }, 500);
        };
    </script>
</body>
</html>
