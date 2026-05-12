<?php
require_once __DIR__ . '/../config/database.php';

try {
    echo "Setting up settings table...\n";

    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT,
        setting_group VARCHAR(50) DEFAULT 'general',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Default settings to insert
    $defaults = [
        // App Identity
        ['app_name', 'Humas Hub UIN', 'identity'],
        ['app_description', 'Portal Informasi dan Dokumentasi UIN Ar-Raniry', 'identity'],
        ['app_footer', '© 2026 Humas Hub UIN Ar-Raniry. All Rights Reserved.', 'identity'],
        ['app_logo', '', 'identity'], // Logo filename
        ['app_favicon', '', 'identity'], // Favicon filename
        
        // Appearance
        ['theme_primary_color', '#0984e3', 'appearance'], // Default blue color
        
        // Module Config
        ['max_upload_size', '10', 'modules'], // In MB
        ['default_pagination', '10', 'modules'], 
        
        // System
        ['maintenance_mode', '0', 'system'], // 0 = false, 1 = true
        ['api_weather_key', '', 'api'],
        ['api_prayer_key', '', 'api']
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value, setting_group) VALUES (?, ?, ?)");
    foreach ($defaults as $setting) {
        $stmt->execute($setting);
    }

    echo "Settings table created and populated successfully.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
