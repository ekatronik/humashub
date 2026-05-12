<?php
require_once __DIR__ . '/../config/database.php';

$default_settings = [
    ['stat_students', '25.000+', 'stats'],
    ['stat_lecturers', '1.200+', 'stats'],
    ['stat_faculties', '9', 'stats'],
    ['stat_alumni', '45.000+', 'stats'],
    ['api_weather_key', '', 'api'],
    ['api_prayer_city', 'Banda Aceh', 'api'],
    ['app_name', 'Humas Hub UIN Ar-Raniry', 'general'],
];

foreach ($default_settings as $s) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value, setting_group) VALUES (?, ?, ?)");
    $stmt->execute($s);
}

echo "Command Center settings initialized.";
