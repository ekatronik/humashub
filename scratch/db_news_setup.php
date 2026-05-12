<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo->exec("INSERT INTO news_online (title, news_date, media_id, category_id, source_type, news_link, created_by) VALUES 
    ('UIN Ar-Raniry Perkuat Kerjasama Internasional dengan Universitas Malaysia', '2026-05-10', 4, 1, 'Rilis Humas', 'https://www.detik.com/edu/perguruan-tinggi/d-123456/uin-ar-raniry-kerjasama-malaysia', 1),
    ('Mahasiswa Ar-Raniry Ciptakan Aplikasi Pendeteksi Berita Hoax', '2026-05-09', 5, 2, 'Liputan Wartawan', 'https://acehtrend.com/news/mahasiswa-ar-raniry-ciptakan-aplikasi-hoax', 2),
    ('Rektor UIN Ar-Raniry Tinjau Kesiapan UTBK SNBT 2026', '2026-05-08', 4, 2, 'Rilis Humas', 'https://www.detik.com/edu/uin-ar-raniry-tinjau-utbk', 1),
    ('Gedung Sport Center UIN Ar-Raniry Mulai Digunakan untuk Latihan Atlet POPDA', '2026-05-07', 5, 3, 'Liputan Wartawan', 'https://acehtrend.com/news/sport-center-uin-popda', 2),
    ('Dosen UIN Ar-Raniry Terbitkan Buku Tentang Sejarah Aceh di Era Digital', '2026-05-06', 4, 1, 'Rilis Humas', 'https://www.detik.com/edu/buku-sejarah-aceh-digital', 1)");
    echo "Dummy data for news_online created successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
