<?php
$pdo = new PDO('mysql:host=localhost;dbname=finazapersonal;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== ESTADO ACTUAL DE CUENTAS ===\n\n";

$stmt = $pdo->query("SELECT id, name, type, balance, status, parent_account_id FROM accounts ORDER BY type, id");
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($accounts as $acc) {
    $parent = $acc['parent_account_id'] ? "Parent: {$acc['parent_account_id']}" : "Sin padre";
    echo "[{$acc['type']}] {$acc['name']} - Bs. {$acc['balance']} ({$acc['status']}) - {$parent}\n";
}

echo "\n=== CREANDO CUENTA TEMPORAL DE PRUEBA ===\n\n";

// Get first main account
$stmt = $pdo->query("SELECT * FROM accounts WHERE type != 'temporary' AND status = 'active' ORDER BY balance DESC LIMIT 1");
$parent = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$parent) {
    die("ERROR: No hay cuentas principales\n");
}

echo "Cuenta origen seleccionada:\n";
echo "  Nombre: {$parent['name']}\n";
echo "  Balance actual: Bs. {$parent['balance']}\n\n";

$testAmount = 1000;
$newBalance = $parent['balance'] - $testAmount;

// Start transaction
$pdo->beginTransaction();

try {
    // 1. Deduct from parent
    $stmt = $pdo->prepare("UPDATE accounts SET balance = ? WHERE id = ?");
    $stmt->execute([$newBalance, $parent['id']]);
    
    // 2. Create temp account
    $stmt = $pdo->prepare("INSERT INTO accounts (name, type, balance, status, parent_account_id) VALUES (?, 'temporary', ?, 'active', ?)");
    $stmt->execute(['Prueba Liquidación', $testAmount, $parent['id']]);
    $tempId = $pdo->lastInsertId();
    
    // 3. Record transactions
    $stmt = $pdo->prepare("INSERT INTO transactions (account_id, category_id, type, amount, amount_usd, exchange_rate, owner, description) VALUES (?, 1, ?, ?, 0, 0, 'System', ?)");
    
    // Debit from parent
    $stmt->execute([$parent['id'], 'expense', $testAmount, "Transferencia a Fondo: Prueba Liquidación"]);
    
    // Credit to temp
    $stmt->execute([$tempId, 'income', $testAmount, "Creación de Fondo Temporal"]);
    
    $pdo->commit();
    
    echo "✓ CUENTA TEMPORAL CREADA EXITOSAMENTE\n\n";
    echo "Detalles:\n";
    echo "  ID: {$tempId}\n";
    echo "  Nombre: Prueba Liquidación\n";
    echo "  Balance: Bs. {$testAmount}\n";
    echo "  Padre: {$parent['name']} (ID: {$parent['id']})\n\n";
    
    echo "INSTRUCCIONES:\n";
    echo "1. Refresca la página de cuentas (F5)\n";
    echo "2. Verás 'Prueba Liquidación' en FONDOS TEMPORALES (sección naranja)\n";
    echo "3. Presiona el botón NARANJA 'Liquidar' (NO el botón X)\n";
    echo "4. Los Bs. {$testAmount} volverán automáticamente a '{$parent['name']}'\n";
    echo "5. La cuenta quedará 'cerrada' (gris) en el historial\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    die("ERROR: " . $e->getMessage() . "\n");
}
