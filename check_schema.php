<?php
$pdo = new PDO('mysql:host=localhost;dbname=finazapersonal;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== COLUMNAS DE LA TABLA accounts ===\n\n";
$stmt = $pdo->query("SHOW COLUMNS FROM accounts");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "{$row['Field']} | {$row['Type']} | Default: " . ($row['Default'] ?? 'NULL') . "\n";
}

echo "\n=== DATOS ACTUALES ===\n\n";
$stmt = $pdo->query("SELECT id, name, type, balance FROM accounts LIMIT 5");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $type = $row['type'] ?? 'NULL';
    echo "ID {$row['id']}: {$row['name']} | type='{$type}' | balance={$row['balance']}\n";
}
