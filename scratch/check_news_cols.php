<?php
require_once __DIR__ . '/../config/database.php';
$stmt = $pdo->query("DESCRIBE news_online");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}
