<?php
require_once 'config/database.php';
$media_check = $pdo->query("SELECT COUNT(*) FROM clippings WHERE media_id IS NOT NULL")->fetchColumn();
$total_check = $pdo->query("SELECT COUNT(*) FROM clippings")->fetchColumn();
echo "Total clippings: $total_check\nClippings with media_id: $media_check\n";
