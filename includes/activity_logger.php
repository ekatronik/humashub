<?php
// activity_logger.php (Simpan di folder includes atau root)
// Fungsi sentral untuk mencatat log

function write_log($pdo, $activity, $module, $target_id = null) {
    if (!isset($_SESSION['user_id'])) return;
    
    $user_id = $_SESSION['user_id'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $agent = $_SERVER['HTTP_USER_AGENT'];

    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, activity, module, target_id, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $activity, $module, $target_id, $ip, $agent]);
}

// Inisialisasi tabel log jika belum ada
try {
    $pdo->query("SELECT id FROM activity_logs LIMIT 1");
} catch (Exception $e) {
    $pdo->exec("CREATE TABLE activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        activity VARCHAR(255),
        module VARCHAR(100),
        target_id INT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}

// Pastikan level user di tabel users sudah sesuai
try {
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('Super Admin', 'Pranata Humas', 'Operator Kliping', 'Operator Berita Online', 'Operator Foto/Video') NOT NULL");
} catch (Exception $e) {}
?>
