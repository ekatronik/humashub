<?php
// config/database.php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'humashub');

// Set Timezone Indonesia
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../includes/functions.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Load system settings globally
    require_once __DIR__ . '/../includes/settings_helper.php';
    load_settings($pdo);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
