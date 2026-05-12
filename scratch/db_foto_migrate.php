<?php
require_once __DIR__ . '/../config/database.php';

try {
    echo "Updating schema for multiple categories...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS documentation_category_rel (
        documentation_id INT NOT NULL,
        category_id INT NOT NULL,
        PRIMARY KEY (documentation_id, category_id),
        FOREIGN KEY (documentation_id) REFERENCES documentation(id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
    )");
    echo "Table documentation_category_rel created.\n";

    // Migrate existing data
    $docs = $pdo->query("SELECT id, category_id FROM documentation WHERE category_id IS NOT NULL")->fetchAll();
    $stmt = $pdo->prepare("INSERT IGNORE INTO documentation_category_rel (documentation_id, category_id) VALUES (?, ?)");
    foreach ($docs as $d) {
        $stmt->execute([$d['id'], $d['category_id']]);
    }
    echo "Data migrated.\n";
    echo "Schema updated successfully.\n";

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}
