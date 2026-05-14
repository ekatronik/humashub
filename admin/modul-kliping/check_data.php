<?php
$p = new PDO('mysql:host=localhost;dbname=humashub', 'root', '');
$count = $p->query("SELECT COUNT(*) FROM clippings")->fetchColumn();
echo "Total clippings: $count\n";

$count_joined = $p->query("SELECT COUNT(*) FROM clippings c 
                           JOIN media m ON c.media_id = m.id 
                           JOIN categories cat ON c.category_id = cat.id")->fetchColumn();
echo "Total clippings with inner join: $count_joined\n";

$count_left = $p->query("SELECT COUNT(*) FROM clippings c 
                         LEFT JOIN media m ON c.media_id = m.id 
                         LEFT JOIN categories cat ON c.category_id = cat.id")->fetchColumn();
echo "Total clippings with left join: $count_left\n";
