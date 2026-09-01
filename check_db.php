<?php
require_once __DIR__ . '/config/database.php';
try {
    $db = get_db_connection();
    $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in database (" . count($tables) . "): " . implode(', ', $tables) . "\n";
} catch (Exception $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}
