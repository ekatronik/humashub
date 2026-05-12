<?php
require_once __DIR__ . '/../config/database.php';
$stmt = $pdo->query("SELECT * FROM media");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}
