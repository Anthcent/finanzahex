<?php
$pdo = new PDO('mysql:host=localhost;dbname=finazapersonal;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== REPARACIÓN FINAL Y TEST COMPLETO ===\n\n";

// Step 1: Fix all accounts with empty type
echo "PASO 1: Reparando tipos de cuenta...\n";
$pdo->exec("UPDATE accounts SET type = 'general' WHERE type IS NULL OR type = ''");
echo "  ✓ Cuentas sin tipo marcadas como 'general'\n";

// Step 2: Mark "Prueba Liquidación" as temporary
$stmt = $pdo->prepare("UPDATE accounts SET type = 'temporary' WHERE name = 'Prueba Liquidación'");
$stmt->execute();
echo "  ✓ 'Prueba Liquidación' marcada como 'temporary'\n\n";

// Step 3: Verify data
echo "PASO 2: Verificando datos...\n";
$stmt = $pdo->query("SELECT id, name, type, balance, parent_account_id FROM accounts");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  [{$row['type']}] {$row['name']} - Bs. {$row['balance']}\n";
}
echo "\n";

// Step 4: Test API
echo "PASO 3: Probando API /accounts/fetch...\n";
$response = file_get_contents('http://localhost/finazapersonal/public/accounts/fetch');
$data = json_decode($response, true);

$tempCount = 0;
foreach ($data['data'] as $acc) {
    if ($acc['type'] === 'temporary') {
        $tempCount++;
        echo "  ✓ Encontrado en API: {$acc['name']} (type='temporary')\n";
    }
}

if ($tempCount === 0) {
    echo "  ✗ ERROR: No se encontraron cuentas temporales en el API\n";
} else {
    echo "  ✓ API devuelve {$tempCount} cuenta(s) temporal(es)\n";
}
echo "\n";

// Step 5: Test liquidation
echo "PASO 4: Probando liquidación...\n";
$stmt = $pdo->query("SELECT id, balance, parent_account_id FROM accounts WHERE name = 'Prueba Liquidación'");
$temp = $stmt->fetch(PDO::FETCH_ASSOC);

if ($temp && $temp['parent_account_id']) {
    $stmt = $pdo->query("SELECT name, balance FROM accounts WHERE id = {$temp['parent_account_id']}");
    $parent = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $balanceBefore = $parent['balance'];
    $expectedAfter = $balanceBefore + $temp['balance'];
    
    echo "  Cuenta padre: {$parent['name']}\n";
    echo "  Balance antes: Bs. {$balanceBefore}\n";
    echo "  A devolver: Bs. {$temp['balance']}\n";
    echo "  Esperado después: Bs. {$expectedAfter}\n\n";
    
    echo "  Ejecutando liquidación...\n";
    $url = "http://localhost/finazapersonal/public/accounts/close-temp/{$temp['id']}";
    $result = file_get_contents($url);
    $resultData = json_decode($result, true);
    
    if ($resultData['status'] === 'success') {
        $stmt = $pdo->query("SELECT balance FROM accounts WHERE id = {$temp['parent_account_id']}");
        $parentAfter = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->query("SELECT status FROM accounts WHERE id = {$temp['id']}");
        $tempAfter = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "  ✓ Liquidación exitosa\n";
        echo "  Balance final: Bs. {$parentAfter['balance']}\n";
        echo "  Estado cuenta temporal: {$tempAfter['status']}\n\n";
        
        if (abs($parentAfter['balance'] - $expectedAfter) < 0.01) {
            echo "  ✅ TEST EXITOSO - El dinero fue devuelto correctamente\n";
        } else {
            echo "  ✗ ERROR: Balance incorrecto\n";
        }
    } else {
        echo "  ✗ ERROR: " . ($resultData['message'] ?? 'Unknown') . "\n";
    }
} else {
    echo "  ⚠ No se puede probar (cuenta no encontrada o sin padre)\n";
}

echo "\n=== FIN DEL TEST ===\n";
