<?php
require_once __DIR__ . '/../config/database.php';

try {
    echo "Refining database...\n";
    
    // Ensure news_online exists (it might have been partially created or not)
    $pdo->exec("CREATE TABLE IF NOT EXISTS news_online (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        news_date DATE NOT NULL,
        media_id INT,
        category_id INT,
        source_type ENUM('Rilis Humas', 'Liputan Wartawan') NOT NULL,
        news_link TEXT NOT NULL,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (media_id) REFERENCES media(id),
        FOREIGN KEY (category_id) REFERENCES categories(id),
        FOREIGN KEY (created_by) REFERENCES users(id)
    )");

    // Get a valid category and media
    $cat_id = $pdo->query("SELECT id FROM categories LIMIT 1")->fetchColumn();
    $media_id = $pdo->query("SELECT id FROM media WHERE media_type = 'online' LIMIT 1")->fetchColumn();
    
    if (!$media_id) {
        // Create an online media if none exists
        $pdo->exec("INSERT INTO media (media_name, media_type) VALUES ('Detik.com', 'online')");
        $media_id = $pdo->lastInsertId();
    }
    
    if (!$cat_id) {
        $pdo->exec("INSERT INTO categories (name) VALUES ('Umum')");
        $cat_id = $pdo->lastInsertId();
    }

    $user_id = $pdo->query("SELECT id FROM users LIMIT 1")->fetchColumn();

    // Clear and Insert Dummy Data
    $pdo->exec("DELETE FROM news_online");
    $stmt = $pdo->prepare("INSERT INTO news_online (title, news_date, media_id, category_id, source_type, news_link, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $data = [
        ['UIN Ar-Raniry Perkuat Kerjasama Internasional dengan Universitas Malaysia', '2026-05-10', $media_id, $cat_id, 'Rilis Humas', 'https://www.detik.com/edu/perguruan-tinggi/d-123456/uin-ar-raniry-kerjasama-malaysia', $user_id],
        ['Mahasiswa Ar-Raniry Ciptakan Aplikasi Pendeteksi Berita Hoax', '2026-05-09', $media_id, $cat_id, 'Liputan Wartawan', 'https://acehtrend.com/news/mahasiswa-ar-raniry-ciptakan-aplikasi-hoax', $user_id],
        ['Rektor UIN Ar-Raniry Tinjau Kesiapan UTBK SNBT 2026', '2026-05-08', $media_id, $cat_id, 'Rilis Humas', 'https://www.detik.com/edu/uin-ar-raniry-tinjau-utbk', $user_id],
        ['Gedung Sport Center UIN Ar-Raniry Mulai Digunakan untuk Latihan Atlet POPDA', '2026-05-07', $media_id, $cat_id, 'Liputan Wartawan', 'https://acehtrend.com/news/sport-center-uin-popda', $user_id],
        ['Dosen UIN Ar-Raniry Terbitkan Buku Tentang Sejarah Aceh di Era Digital', '2026-05-06', $media_id, $cat_id, 'Rilis Humas', 'https://www.detik.com/edu/buku-sejarah-aceh-digital', $user_id]
    ];

    foreach ($data as $row) {
        $stmt->execute($row);
    }

    echo "Refactor and Dummy data completed successfully!\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
