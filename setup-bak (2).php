<?php
// setup.php
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Buat Database
    $pdo->exec("DROP DATABASE IF EXISTS humashub"); // Reset database agar bersih
    $pdo->exec("CREATE DATABASE humashub");
    $pdo->exec("USE humashub");

    // Baca SQL Schema
    $sql = file_get_contents('database.sql');
    if ($sql === false) {
        throw new Exception("File database.sql tidak ditemukan.");
    }
    
    // Jalankan SQL secara manual per statement jika perlu, 
    // tapi kita coba exec dengan multi_query support
    $pdo->exec($sql);

    echo "<div style='font-family:sans-serif; text-align:center; padding: 50px;'>";
    echo "<h1 style='color:#006837;'>Setup Berhasil!</h1>";
    echo "<p>Database telah di-reset dan di-install ulang dengan benar.</p>";
    echo "<p>Username Admin: <strong>admin</strong><br>Password: <strong>admin123</strong></p>";
    echo "<div style='margin: 30px 0;'>";
    echo "<a href='admin/login.php' style='background:#006837; color:white; padding: 12px 25px; text-decoration:none; border-radius:8px; font-weight:bold;'>Coba Login Sekarang</a>";
    echo "</div>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='font-family:sans-serif; color:red; padding: 50px;'>";
    echo "<h1>Setup Gagal</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
