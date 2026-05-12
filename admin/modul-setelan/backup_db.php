<?php
// admin/modul-setelan/backup_db.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
checkAccess(['Super Admin']); // Hanya Super Admin yang bisa backup

// Config
$host = DB_HOST;
$user = DB_USER;
$pass = DB_PASS;
$name = DB_NAME;

// Filename
$filename = "backup_" . $name . "_" . date("Y-m-d_H-i-s") . ".sql";

header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . $filename . '"');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$name", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all tables
    $tables = [];
    $result = $pdo->query("SHOW TABLES");
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    
    $output = "-- Database Backup for Humas Hub\n";
    $output .= "-- Generated: " . date("Y-m-d H:i:s") . "\n\n";
    
    foreach ($tables as $table) {
        $result = $pdo->query("SELECT * FROM `$table`");
        $numColumns = $result->columnCount();
        
        $output .= "DROP TABLE IF EXISTS `$table`;\n";
        
        $row2 = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
        $output .= "\n\n" . $row2[1] . ";\n\n";
        
        for ($i = 0; $i < $numColumns; $i++) {
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $output .= "INSERT INTO `$table` VALUES(";
                for ($j = 0; $j < $numColumns; $j++) {
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = str_replace("\n", "\\n", $row[$j]);
                    if (isset($row[$j])) {
                        $output .= '"' . $row[$j] . '"';
                    } else {
                        $output .= '""';
                    }
                    if ($j < ($numColumns - 1)) {
                        $output .= ',';
                    }
                }
                $output .= ");\n";
            }
        }
        $output .= "\n\n\n";
    }
    
    echo $output;
    exit;

} catch (PDOException $e) {
    die("Error during backup: " . $e->getMessage());
}
?>
