<?php
// setup.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$user = 'root';
$pass = '';

echo "<body style='font-family:sans-serif; line-height:1.6; padding:20px;'>";
echo "<h2>🔧 Sistem Inisialisasi Humas Hub</h2>";

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Baca file SQL
    $sqlContent = file_get_contents('database.sql');
    if ($sqlContent === false) {
        throw new Exception("Gagal membaca file database.sql");
    }

    // Split SQL by semicolon, tapi hati-hati dengan semicolon di dalam string
    // Kita gunakan pendekatan regex sederhana untuk memisahkan query
    $queries = preg_split("/;+(?=(?:[^']*'[^']*')*[^']*$)/", $sqlContent);

    echo "<ul>";
    foreach ($queries as $query) {
        $query = trim($query);
        if ($query != "") {
            try {
                $pdo->exec($query);
                // Hanya tampilkan baris yang bukan komentar/pendek
                if (strlen($query) > 10) {
                    echo "<li style='color:green;'>Berhasil: " . substr(htmlspecialchars($query), 0, 50) . "...</li>";
                }
            } catch (PDOException $e) {
                echo "<li style='color:red;'>Gagal: " . substr(htmlspecialchars($query), 0, 50) . "... <br>Error: " . $e->getMessage() . "</li>";
            }
        }
    }
    echo "</ul>";

    echo "<div style='background:#d4edda; color:#155724; padding:20px; border-radius:10px; margin-top:20px;'>";
    echo "<h3>✅ Inisialisasi Selesai!</h3>";
    echo "<p>Database telah di-reset dan data dummy telah dimasukkan.</p>";
    echo "<p>Silakan gunakan akun berikut:</p>";
    echo "<ul>
            <li><strong>Admin:</strong> admin / admin123</li>
            <li><strong>Petugas Kliping:</strong> kliping_user / admin123</li>
          </ul>";
    echo "<a href='admin/login.php' style='display:inline-block; background:#006837; color:white; padding:12px 25px; text-decoration:none; border-radius:8px; font-weight:bold; margin-top:10px;'>Buka Dashboard Admin</a>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='background:#f8d7da; color:#721c24; padding:20px; border-radius:10px; margin-top:20px;'>";
    echo "<h3>❌ Terjadi Kesalahan Fatal</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
echo "</body>";
?>
