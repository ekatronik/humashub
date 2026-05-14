<?php
$p = new PDO('mysql:host=localhost;dbname=humashub', 'root', '');
$cols = $p->query('DESCRIBE clippings')->fetchAll(PDO::FETCH_COLUMN);
echo implode(', ', $cols);
