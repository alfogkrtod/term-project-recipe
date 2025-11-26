<?php
// Quick DB connection test script
require_once __DIR__ . '/../config/db.php';

echo "DB connection test\n";

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        echo "ERROR: getDBConnection() returned null. Check config/db.php and DB server.\n";
        exit(1);
    }

    // Simple probe
    $one = $pdo->query('SELECT 1')->fetchColumn();
    echo "SELECT 1 => " . var_export($one, true) . "\n";

    // Try a sample query on `recipes` table if it exists
    try {
        $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM recipes");
        $row = $stmt->fetch();
        if ($row && isset($row['cnt'])) {
            echo "recipes count => " . $row['cnt'] . "\n";
        } else {
            echo "recipes table exists but count not returned as expected.\n";
        }
    } catch (Exception $e) {
        echo "Note: could not query 'recipes' table: " . $e->getMessage() . "\n";
    }

    echo "DB test completed successfully.\n";
    exit(0);

} catch (Exception $e) {
    echo "Exception while testing DB: " . $e->getMessage() . "\n";
    exit(1);
}

?>
