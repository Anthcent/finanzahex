<?php
// Complete end-to-end test of temporary funds system
$pdo = new PDO('mysql:host=localhost;dbname=finazapersonal;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== TEST COMPLETO DEL SISTEMA DE FONDOS TEMPORALES ===\n\n";

// Step 1: Verify and fix "Prueba Liquidación"
echo "PASO 1: Verificando 'Prueba Liquidación'...\n";
$stmt = $pdo->query("SELECT id, name, type, balance, status, parent_account_id FROM accounts WHERE name = 'Prueba Liquidación'");
$prueba = $stmt->fetch(PDO::FETCH_ASSOC);

if ($prueba) {
    echo "  Encontrada - ID: {$prueba['id']}\n";
    echo "  Type actual: '{$prueba['type']}'\n";
    echo "  Parent: {$prueba['parent_account_id']}\n";
    
    if ($prueba['type'] !== 'temporary') {
        echo "  ⚠ Corrigiendo type...\n";
        $pdo->exec("UPDATE accounts SET type = 'temporary' WHERE id = {$prueba['id']}");
        echo "  ✓ Type actualizado a 'temporary'\n";
    } else {
        echo "  ✓ Type correcto\n";
    }
} else {
    echo "  ⚠ No existe, creando nueva...\n";
    
    // Get parent account
    $stmt = $pdo->query("SELECT id, name, balance FROM accounts WHERE type != 'temporary' AND status = 'active' ORDER BY balance DESC LIMIT 1");
    $parent = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$parent) {
        die("ERROR: No hay cuentas principales\n");
    }
    
    $amount = 1000;
    $newBalance = $parent['balance'] - $amount;
    
    $pdo->beginTransaction();
    
    // Deduct from parent
    $pdo->exec("UPDATE accounts SET balance = {$newBalance} WHERE id = {$parent['id']}");
    
    // Create temp account
    $stmt = $pdo->prepare("INSERT INTO accounts (name, type, balance, status, parent_account_id) VALUES ('Prueba Liquidación', 'temporary', ?, 'active', ?)");
    $stmt->execute([$amount, $parent['id']]);
    $tempId = $pdo->lastInsertId();
    
    // Record transactions
    $stmt = $pdo->prepare("INSERT INTO transactions (account_id, category_id, type, amount, amount_usd, exchange_rate, owner, description) VALUES (?, 1, ?, ?, 0, 0, 'System', ?)");
    $stmt->execute([$parent['id'], 'expense', $amount, "Transferencia a Fondo: Prueba Liquidación"]);
    $stmt->execute([$tempId, 'income', $amount, "Creación de Fondo Temporal"]);
    
    $pdo->commit();
    echo "  ✓ Cuenta creada (ID: {$tempId}, Parent: {$parent['id']})\n";
}

echo "\n";

// Step 2: Verify API response
echo "PASO 2: Verificando respuesta del API...\n";
$response = file_get_contents('http://localhost/finazapersonal/public/accounts/fetch');
$data = json_decode($response, true);

$found = false;
foreach ($data['data'] as $acc) {
    if ($acc['name'] === 'Prueba Liquidación') {
        $found = true;
        echo "  Encontrada en API:\n";
        echo "    type: '{$acc['type']}'\n";
        echo "    status: '{$acc['status']}'\n";
        echo "    parent_account_id: " . ($acc['parent_account_id'] ?? 'NULL') . "\n";
        
        if ($acc['type'] === 'temporary') {
            echo "  ✓ API devuelve type='temporary' correctamente\n";
        } else {
            echo "  ✗ ERROR: API devuelve type='{$acc['type']}'\n";
        }
        break;
    }
}

if (!$found) {
    echo "  ✗ ERROR: No encontrada en respuesta del API\n";
}

echo "\n";

// Step 3: Test liquidation endpoint
echo "PASO 3: Probando endpoint de liquidación...\n";
$stmt = $pdo->query("SELECT id, balance, parent_account_id FROM accounts WHERE name = 'Prueba Liquidación' AND type = 'temporary'");
$temp = $stmt->fetch(PDO::FETCH_ASSOC);

if ($temp && $temp['parent_account_id']) {
    $stmt = $pdo->query("SELECT balance FROM accounts WHERE id = {$temp['parent_account_id']}");
    $parentBefore = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "  Balance antes:\n";
    echo "    Prueba Liquidación: Bs. {$temp['balance']}\n";
    echo "    Cuenta Padre: Bs. {$parentBefore['balance']}\n";
    
    // Call liquidation endpoint
    $url = "http://localhost/finazapersonal/public/accounts/close-temp/{$temp['id']}";
    $result = file_get_contents($url);
    $resultData = json_decode($result, true);
    
    echo "\n  Resultado de liquidación:\n";
    echo "    " . json_encode($resultData, JSON_UNESCAPED_UNICODE) . "\n";
    
    if ($resultData['status'] === 'success') {
        // Verify balances
        $stmt = $pdo->query("SELECT balance, status FROM accounts WHERE id = {$temp['id']}");
        $tempAfter = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->query("SELECT balance FROM accounts WHERE id = {$temp['parent_account_id']}");
        $parentAfter = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "\n  Balance después:\n";
        echo "    Prueba Liquidación: Bs. {$tempAfter['balance']} (status: {$tempAfter['status']})\n";
        echo "    Cuenta Padre: Bs. {$parentAfter['balance']}\n";
        
        $expectedParent = $parentBefore['balance'] + $temp['balance'];
        if (abs($parentAfter['balance'] - $expectedParent) < 0.01) {
            echo "\n  ✓ LIQUIDACIÓN EXITOSA - Dinero devuelto correctamente\n";
        } else {
            echo "\n  ✗ ERROR: Balance incorrecto (esperado: {$expectedParent})\n";
        }
    } else {
        echo "\n  ✗ ERROR: " . ($resultData['message'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "  ⚠ No se puede probar liquidación (cuenta no encontrada o sin padre)\n";
}

echo "\n=== FIN DEL TEST ===\n";
