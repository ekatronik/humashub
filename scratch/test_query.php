<?php
require_once 'config/database.php';
$filter_year = '2026';
$where_year_join = ($filter_year === 'all') ? "" : "AND YEAR(c.clipping_date) = '$filter_year'";
$query = "SELECT cat.name, COUNT(c.id) as total 
          FROM categories cat 
          LEFT JOIN clippings c ON cat.id = c.category_id $where_year_join
          GROUP BY cat.id 
          ORDER BY total DESC, cat.name ASC";
echo "QUERY: $query\n";
try {
    $cat_stats = $pdo->query($query)->fetchAll();
    print_r($cat_stats);
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
