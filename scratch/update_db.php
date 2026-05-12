<?php
require_once __DIR__ . '/../config/database.php';
$pdo->exec("INSERT IGNORE INTO settings (setting_key, setting_value, setting_group) VALUES ('app_theme', 'default', 'appearance'), ('theme_mode', 'light', 'appearance')");
echo "DB updated";
