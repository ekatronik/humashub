<?php
require_once 'config/database.php';
echo "DESCRIBE clipping_category_rel:\n";
print_r($pdo->query('DESCRIBE clipping_category_rel')->fetchAll());

echo "\nSAMPLE DATA FROM clipping_category_rel:\n";
print_r($pdo->query('SELECT * FROM clipping_category_rel LIMIT 10')->fetchAll());
