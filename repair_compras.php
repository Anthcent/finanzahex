<?php
// Emergency repair script for "compras" account
$dsn = 'mysql:host=localhost;dbname=finazapersonal;charset=utf8mb4';
$user = 'root';
$pass = ''; // Try empty password

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== REPAIR SCRIPT FOR COMPRAS ACCOUNT ===\n\n";
    
    // 1. Find "compras" account
    $stmt = $pdo->query("SELECT id, name, type, balance, status, parent_account_id FROM accounts WHERE name LIKE '%compras%'");
    $compras = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$compras) {
        die("ERROR: No se encontró cuenta 'compras'\n");
    }
    
    echo "Cuenta encontrada:\n";
    echo "  ID: {$compras['id']}\n";
    echo "  Nombre: {$compras['name']}\n";
    echo "  Tipo: {$compras['type']}\n";
    echo "  Balance: {$compras['balance']}\n";
    echo "  Status: {$compras['status']}\n";
    echo "  Parent ID: " . ($compras['parent_account_id'] ?? 'NULL') . "\n\n";
    
    // 2. Find a valid parent (first main account)
    $stmt = $pdo->query("SELECT id, name FROM accounts WHERE type != 'temporary' AND status = 'active' ORDER BY id LIMIT 1");
    $parent = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$parent) {
        die("ERROR: No hay cuentas principales disponibles\n");
    }
    
    echo "Cuenta padre seleccionada:\n";
    echo "  ID: {$parent['id']}\n";
    echo "  Nombre: {$parent['name']}\n\n";
    
    // 3. Update compras to have correct parent
    $pdo->exec("UPDATE accounts SET parent_account_id = {$parent['id']}, type = 'temporary', status = 'active' WHERE id = {$compras['id']}");
    
    echo "✓ Cuenta 'compras' reparada exitosamente\n";
    echo "  - parent_account_id: {$parent['id']} ({$parent['name']})\n";
    echo "  - type: temporary\n";
    echo "  - status: active\n\n";
    
    echo "Ahora puedes LIQUIDAR la cuenta desde la interfaz.\n";
    echo "El dinero (Bs. {$compras['balance']}) será devuelto a '{$parent['name']}'.\n";
    
} catch (PDOException $e) {
    die("ERROR DE CONEXIÓN: " . $e->getMessage() . "\n");
}
