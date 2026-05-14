<?php
require_once 'config/database.php';
$clips = $pdo->query('SELECT id, title, clipping_date, category_id FROM clippings WHERE YEAR(clipping_date) = 2026')->fetchAll();
print_r($clips);
