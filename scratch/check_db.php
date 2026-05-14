<?php
require_once 'config/database.php';

echo "CATEGORIES:\n";
$cats = $pdo->query("SELECT * FROM categories")->fetchAll();
print_r($cats);

echo "\nCLIPPINGS:\n";
$clips = $pdo->query("SELECT id, title, clipping_date, category_id FROM clippings")->fetchAll();
print_r($clips);

echo "\nYEARS IN CLIPPINGS:\n";
$years = $pdo->query("SELECT DISTINCT YEAR(clipping_date) as yr FROM clippings")->fetchAll();
print_r($years);
