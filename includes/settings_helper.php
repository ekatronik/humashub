<?php
// includes/settings_helper.php

$global_settings = [];

function load_settings($pdo) {
    global $global_settings;
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $global_settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (PDOException $e) {
        // Table might not exist yet, ignore
    }
}

function get_setting($key, $default = '') {
    global $global_settings;
    return isset($global_settings[$key]) ? $global_settings[$key] : $default;
}

function get_available_themes() {
    $themes_dir = __DIR__ . '/../assets/themes/';
    $themes = [];
    if (is_dir($themes_dir)) {
        $dirs = array_diff(scandir($themes_dir), ['..', '.']);
        foreach ($dirs as $dir) {
            if (is_dir($themes_dir . $dir) && file_exists($themes_dir . $dir . '/info.json')) {
                $info = json_decode(file_get_contents($themes_dir . $dir . '/info.json'), true);
                if ($info) {
                    $themes[$dir] = $info;
                }
            }
        }
    }
    return $themes;
}
