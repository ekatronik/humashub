<?php
require_once 'config/database.php';
print_r($pdo->query('SELECT * FROM categories')->fetchAll());
