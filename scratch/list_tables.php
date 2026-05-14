<?php
require_once 'config/database.php';
print_r($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN));
