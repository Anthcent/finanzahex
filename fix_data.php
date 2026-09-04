<?php
// Try to connect to DB
$dsn = 'mysql:host=localhost;dbname=finazapersonal;charset=utf8mb4';
$user = 'root';
$pass = 'Wrapper!12345'; // Try this first

try {
    $pdo = new PDO($dsn, $user, $pass);
} catch (PDOException $e) {
    try {
        // Try empty password
        $pdo = new PDO($dsn, $user, '');
    } catch (PDOException $e2) {
        die("Connection failed: " . $e2->getMessage());
    }
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Connected to DB.\n";

// 1. Fix accounts that have a parent but wrong type
$sql = "UPDATE accounts SET type = 'temporary', status = 'active' WHERE parent_account_id IS NOT NULL AND parent_account_id > 0";
$stmt = $pdo->query($sql);
echo "Fixed accounts with parent_id: " . $stmt->rowCount() . "\n";

// 2. Specific fix for 'compras' if it was created without parent (dummy fix)
// We'll attach it to the first general account found if it has no parent
$sql = "SELECT id FROM accounts WHERE name LIKE '%compras%' AND (type != 'temporary' OR parent_account_id IS NULL)";
$stmt = $pdo->query($sql);
$badAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($badAccounts) > 0) {
    // Find a valid parent
    $parentStmt = $pdo->query("SELECT id FROM accounts WHERE type != 'temporary' AND status = 'active' LIMIT 1");
    $parent = $parentStmt->fetch(PDO::FETCH_ASSOC);
    $parentId = $parent ? $parent['id'] : 0;

    foreach ($badAccounts as $acc) {
        $id = $acc['id'];
        $update = "UPDATE accounts SET type = 'temporary', status = 'active', parent_account_id = $parentId WHERE id = $id";
        $pdo->exec($update);
        echo "Forced 'compras' ID $id to be temporary (Parent: $parentId)\n";
    }
}

echo "Done.\n";
