<?php
$pdo = new PDO('mysql:host=localhost;dbname=finazapersonal;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== VERIFICANDO DATOS DE CUENTAS ===\n\n";

$stmt = $pdo->query("SELECT id, name, type, balance, status, parent_account_id FROM accounts ORDER BY id");
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total cuentas: " . count($accounts) . "\n\n";

foreach ($accounts as $acc) {
    $typeLabel = $acc['type'] ?? 'NULL';
    $statusLabel = $acc['status'] ?? 'NULL';
    $parentLabel = $acc['parent_account_id'] ?? 'NULL';
    
    echo "ID {$acc['id']}: {$acc['name']}\n";
    echo "  type: '{$typeLabel}'\n";
    echo "  status: '{$statusLabel}'\n";
    echo "  parent_account_id: {$parentLabel}\n";
    echo "  balance: {$acc['balance']}\n";
    echo "\n";
}

echo "=== VERIFICANDO COLUMNAS DE LA TABLA ===\n\n";
$stmt = $pdo->query("DESCRIBE accounts");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($columns as $col) {
    echo "{$col['Field']} - {$col['Type']} - {$col['Null']} - {$col['Default']}\n";
}
