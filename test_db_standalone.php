<?php
$host = '127.0.0.1';
$db   = 'educbogv_finanza';
$user = 'educbogv_finanza';
$pass = ';Rfo_HuAwtOf';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "Connected to DB.\n";
    
    // Check table
    $stmt = $pdo->query("SHOW TABLES LIKE 'print_products'");
    if ($stmt->rowCount() > 0) {
        echo "Table print_products EXISTS.\n";
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM print_products");
        $row = $stmt->fetch();
        echo "Count: " . $row['count'] . "\n";
        
        if ($row['count'] > 0) {
            $stmt = $pdo->query("SELECT * FROM print_products LIMIT 5");
            print_r($stmt->fetchAll());
        }
    } else {
        echo "Table print_products DOES NOT EXIST.\n";
    }
    
} catch (\PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
