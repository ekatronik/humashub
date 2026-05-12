<?php
// setup.php
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Buat Database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS humashub");
    $pdo->exec("USE humashub");

    // Baca dan Jalankan SQL Schema
    $sql = file_get_contents('database.sql');
    if ($sql === false) {
        throw new Exception("File database.sql tidak ditemukan.");
    }
    
    // Jalankan multiple queries
    $pdo->exec($sql);

    echo "<div style='font-family:sans-serif; text-align:center; padding: 50px;'>";
    echo "<h1 style='color:#006837;'>Setup Berhasil!</h1>";
    echo "<p>Database dan tabel Humas Hub telah siap digunakan.</p>";
    echo "<div style='margin: 30px 0;'>";
    echo "<a href='admin/login.php' style='background:#006837; color:white; padding: 12px 25px; text-decoration:none; border-radius:8px; font-weight:bold;'>Buka Dashboard Admin</a>";
    echo "</div>";
    echo "<p style='color:red;'><strong>Peringatan:</strong> Segera hapus file <code>setup.php</code> ini untuk alasan keamanan.</p>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='font-family:sans-serif; color:red; padding: 50px;'>";
    echo "<h1>Setup Gagal</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
