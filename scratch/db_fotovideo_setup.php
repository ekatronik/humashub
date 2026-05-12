<?php
require_once __DIR__ . '/../config/database.php';

try {
    echo "Setting up Foto/Video Database...\n";

    // 1. Create documentation table
    $pdo->exec("CREATE TABLE IF NOT EXISTS documentation (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_name VARCHAR(255) NOT NULL,
        description TEXT,
        news_link VARCHAR(255),
        event_date DATE NOT NULL,
        category_id INT,
        location_name VARCHAR(255),
        location_type ENUM('Internal Kampus', 'Lokal Daerah', 'Nasional', 'Internasional') DEFAULT 'Internal Kampus',
        thumbnail_url VARCHAR(255),
        photo_folder_link VARCHAR(255),
        video_folder_link VARCHAR(255),
        creator_name VARCHAR(100),
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    )");
    echo "Table 'documentation' created.\n";

    // 2. Create documentation_attendance table
    $pdo->exec("CREATE TABLE IF NOT EXISTS documentation_attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        documentation_id INT NOT NULL,
        level ENUM('Rektorat', 'Fakultas') NOT NULL,
        position VARCHAR(100) NOT NULL,
        person_name VARCHAR(255) NOT NULL,
        FOREIGN KEY (documentation_id) REFERENCES documentation(id) ON DELETE CASCADE
    )");
    echo "Table 'documentation_attendance' created.\n";

    // 3. Clear existing data and insert Dummy Data
    $pdo->exec("DELETE FROM documentation_attendance");
    $pdo->exec("DELETE FROM documentation");

    $user_id = $pdo->query("SELECT id FROM users LIMIT 1")->fetchColumn();
    
    // Get a category
    $cat_id = $pdo->query("SELECT id FROM categories LIMIT 1")->fetchColumn();
    if (!$cat_id) {
        $pdo->exec("INSERT INTO categories (name) VALUES ('Kegiatan Akademik')");
        $cat_id = $pdo->lastInsertId();
    }

    $docs = [
        [
            'Wisuda Gelombang 1 Tahun 2026', 
            'Pelaksanaan wisuda sarjana dan pascasarjana UIN Ar-Raniry.', 
            'https://uin.ar-raniry.ac.id/wisuda-2026', 
            '2026-05-01', 
            $cat_id, 
            'Auditorium Ali Hasjmy', 
            'Internal Kampus', 
            'https://via.placeholder.com/600x400.png?text=Wisuda+2026', 
            'https://drive.google.com/drive/folders/1A2B3C', 
            'https://drive.google.com/drive/folders/4D5E6F', 
            'Tim Humas UIN', 
            $user_id
        ],
        [
            'Seminar Internasional Studi Islam', 
            'Seminar yang dihadiri narasumber dari berbagai negara.', 
            'https://uin.ar-raniry.ac.id/seminar-internasional', 
            '2026-04-15', 
            $cat_id, 
            'Aula Pascasarjana', 
            'Internasional', 
            'https://via.placeholder.com/600x400.png?text=Seminar+Intl', 
            'https://drive.google.com/drive/folders/7G8H9I', 
            '', 
            'Budi Fotografer', 
            $user_id
        ],
        [
            'Kunjungan Kerja Kementerian Agama RI', 
            'Menteri Agama berkunjung ke kampus UIN Ar-Raniry.', 
            'https://uin.ar-raniry.ac.id/kunker-menag', 
            '2026-03-20', 
            $cat_id, 
            'Gedung Rektorat', 
            'Nasional', 
            'https://via.placeholder.com/600x400.png?text=Kunker+Menag', 
            'https://drive.google.com/drive/folders/JKLMN', 
            'https://drive.google.com/drive/folders/OPQRS', 
            'Andi Video', 
            $user_id
        ],
        [
            'Rapat Senat Terbuka Dies Natalis', 
            'Perayaan ulang tahun kampus tercinta.', 
            '', 
            '2025-10-05', 
            $cat_id, 
            'Auditorium Ali Hasjmy', 
            'Internal Kampus', 
            'https://via.placeholder.com/600x400.png?text=Dies+Natalis', 
            'https://drive.google.com/drive/folders/TUVWX', 
            '', 
            'Tim Humas UIN', 
            $user_id
        ],
        [
            'Penandatanganan MoU dengan Pemerintah Aceh', 
            'Kerjasama di bidang pendidikan dan pengabdian.', 
            'https://uin.ar-raniry.ac.id/mou-pemprov', 
            '2026-02-10', 
            $cat_id, 
            'Kantor Gubernur Aceh', 
            'Lokal Daerah', 
            'https://via.placeholder.com/600x400.png?text=MoU+Pemprov', 
            'https://drive.google.com/drive/folders/YZ123', 
            'https://drive.google.com/drive/folders/45678', 
            'Fulan', 
            $user_id
        ]
    ];

    $stmt = $pdo->prepare("INSERT INTO documentation (event_name, description, news_link, event_date, category_id, location_name, location_type, thumbnail_url, photo_folder_link, video_folder_link, creator_name, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt_att = $pdo->prepare("INSERT INTO documentation_attendance (documentation_id, level, position, person_name) VALUES (?, ?, ?, ?)");

    foreach ($docs as $doc) {
        $stmt->execute($doc);
        $doc_id = $pdo->lastInsertId();

        // Add dummy attendance for each
        $stmt_att->execute([$doc_id, 'Rektorat', 'Rektor', 'Prof. Dr. Mujiburrahman, M.Ag']);
        $stmt_att->execute([$doc_id, 'Rektorat', 'Wakil Rektor 1', 'Prof. Dr. H. Khairuddin, M.Ag']);
        $stmt_att->execute([$doc_id, 'Fakultas', 'Dekan', 'Prof. Dr. Syabuddin, M.Ag']);
    }

    echo "Dummy data inserted successfully.\n";

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}
