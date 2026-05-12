<?php
// admin/update_media_table.php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo->exec("ALTER TABLE media ADD COLUMN media_logo VARCHAR(255) AFTER media_type");
    echo "<h1>Berhasil! Kolom media_logo telah ditambahkan.</h1>";
    echo "<p><a href='kliping_media.php'>Kembali ke Manajemen Media</a></p>";
} catch (PDOException $e) {
    echo "<h1>Informasi</h1>";
    echo "<p>Kolom mungkin sudah ada atau terjadi error: " . $e->getMessage() . "</p>";
    echo "<p><a href='kliping_media.php'>Ke Manajemen Media</a></p>";
}
?>
