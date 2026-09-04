<?php
$pdo = new PDO('mysql:host=localhost;dbname=finazapersonal;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== ESTADO ACTUAL DE TODAS LAS CUENTAS ===\n\n";

$stmt = $pdo->query("SELECT id, name, type, balance, status, parent_account_id FROM accounts ORDER BY type, id");
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($accounts as $acc) {
    $type = $acc['type'] ?? 'NULL';
    $status = $acc['status'] ?? 'NULL';
    $parent = $acc['parent_account_id'] ?? 'NULL';
    
    echo "[{$type}] {$acc['name']}\n";
    echo "  ID: {$acc['id']}\n";
    echo "  Balance: Bs. {$acc['balance']}\n";
    echo "  Status: {$status}\n";
    echo "  Parent: {$parent}\n";
    echo "\n";
}

echo "=== ACTUALIZANDO 'Prueba Liquidación' ===\n\n";

$stmt = $pdo->prepare("UPDATE accounts SET type = 'temporary' WHERE name = 'Prueba Liquidación'");
$stmt->execute();
$affected = $stmt->rowCount();

echo "Filas actualizadas: {$affected}\n\n";

if ($affected > 0) {
    echo "✓ 'Prueba Liquidación' ahora es type='temporary'\n\n";
    echo "REFRESCA LA PÁGINA (F5) y verás:\n";
    echo "1. 'Prueba Liquidación' en FONDOS TEMPORALES (sección naranja)\n";
    echo "2. Un botón naranja 'Liquidar'\n";
    echo "3. Al presionarlo, Bs. 1000 volverán a 'Banco Principal'\n";
} else {
    echo "⚠ No se encontró 'Prueba Liquidación' o ya tenía type='temporary'\n";
}
