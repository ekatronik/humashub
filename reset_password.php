<?php
// reset_password.php
require_once 'config/database.php';

$username = 'admin';
$password = 'admin123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    // Pastikan user admin ada
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
        $stmt->execute([$hashed_password, $username]);
        echo "<h1>Kata sandi admin berhasil disetel ulang!</h1>";
        echo "<p>Username: <strong>admin</strong></p>";
        echo "<p>Password: <strong>admin123</strong></p>";
        echo "<p><a href='admin/login.php'>Klik di sini untuk login</a></p>";
    } else {
        echo "<h1>User admin tidak ditemukan. Apakah Anda sudah menjalankan setup.php?</h1>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
