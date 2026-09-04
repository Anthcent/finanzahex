<?php
// Simple PDO repair
$pdo = new PDO('mysql:host=localhost;dbname=finazapersonal;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== REPARANDO CUENTA COMPRAS ===\n\n";

// 1. Get compras account
$stmt = $pdo->query("SELECT * FROM accounts WHERE name = 'compras'");
$compras = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$compras) {
    die("ERROR: Cuenta 'compras' no encontrada\n");
}

echo "Cuenta encontrada:\n";
echo "  ID: {$compras['id']}\n";
echo "  Nombre: {$compras['name']}\n";
echo "  Tipo: {$compras['type']}\n";
echo "  Balance: Bs. {$compras['balance']}\n";
echo "  Parent ID: " . ($compras['parent_account_id'] ?? 'NULL') . "\n\n";

// 2. Get first main account
$stmt = $pdo->query("SELECT * FROM accounts WHERE type != 'temporary' AND status = 'active' ORDER BY id LIMIT 1");
$parent = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$parent) {
    die("ERROR: No hay cuentas principales\n");
}

echo "Asignando como padre:\n";
echo "  ID: {$parent['id']}\n";
echo "  Nombre: {$parent['name']}\n";
echo "  Balance: Bs. {$parent['balance']}\n\n";

// 3. Update compras
$stmt = $pdo->prepare("UPDATE accounts SET parent_account_id = ?, type = 'temporary', status = 'active' WHERE id = ?");
$stmt->execute([$parent['id'], $compras['id']]);

echo "✓ REPARACIÓN EXITOSA\n\n";
echo "Cambios aplicados:\n";
echo "  - parent_account_id: {$parent['id']} ({$parent['name']})\n";
echo "  - type: temporary\n";
echo "  - status: active\n\n";

echo "SIGUIENTE PASO:\n";
echo "1. Refresca la página de cuentas (F5)\n";
echo "2. Busca la cuenta 'compras' en la sección FONDOS TEMPORALES (naranja)\n";
echo "3. Presiona el botón NARANJA 'Liquidar'\n";
echo "4. Los Bs. {$compras['balance']} volverán a '{$parent['name']}'\n";
