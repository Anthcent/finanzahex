<?php
$pdo = new PDO('mysql:host=localhost;dbname=finazapersonal;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== AGREGANDO COLUMNA 'type' A LA TABLA accounts ===\n\n";

try {
    // Add type column
    $pdo->exec("ALTER TABLE accounts ADD COLUMN type VARCHAR(50) DEFAULT 'general' AFTER name");
    echo "✓ Columna 'type' agregada exitosamente\n\n";
    
    // Update existing accounts to have type='general'
    $pdo->exec("UPDATE accounts SET type = 'general' WHERE type IS NULL OR type = ''");
    echo "✓ Cuentas existentes marcadas como 'general'\n\n";
    
    // Update "Prueba Liquidación" to be temporary
    $stmt = $pdo->prepare("UPDATE accounts SET type = 'temporary' WHERE name = 'Prueba Liquidación'");
    $stmt->execute();
    echo "✓ 'Prueba Liquidación' marcada como 'temporary'\n\n";
    
    echo "=== MIGRACIÓN COMPLETADA ===\n\n";
    echo "Ahora refresca la página y verás:\n";
    echo "1. 'Prueba Liquidación' en la sección FONDOS TEMPORALES (naranja)\n";
    echo "2. Un botón naranja 'Liquidar' en esa cuenta\n";
    echo "3. Al presionarlo, los Bs. 1000 volverán a 'Banco Principal'\n";
    
} catch (PDOException $e) {
    die("ERROR: " . $e->getMessage() . "\n");
}
