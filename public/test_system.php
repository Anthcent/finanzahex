<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Sistema Fondos Temporales</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #0f0; }
        .section { background: #000; border: 1px solid #0f0; padding: 15px; margin: 10px 0; }
        .error { color: #f00; }
        .success { color: #0f0; }
        .warning { color: #ff0; }
        h2 { color: #0ff; }
        pre { background: #222; padding: 10px; overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #0f0; padding: 8px; text-align: left; }
        th { background: #003300; }
    </style>
</head>
<body>
    <h1>🔍 TEST COMPLETO - SISTEMA DE FONDOS TEMPORALES</h1>
    
    <?php
    $pdo = new PDO('mysql:host=localhost;dbname=finazapersonal;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // TEST 1: Verificar esquema de base de datos
    echo '<div class="section">';
    echo '<h2>TEST 1: Esquema de Base de Datos</h2>';
    $stmt = $pdo->query("SHOW COLUMNS FROM accounts");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasType = false;
    echo '<table><tr><th>Campo</th><th>Tipo</th><th>Default</th></tr>';
    foreach ($columns as $col) {
        if ($col['Field'] === 'type') $hasType = true;
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>" . ($col['Default'] ?? 'NULL') . "</td></tr>";
    }
    echo '</table>';
    
    if ($hasType) {
        echo '<p class="success">✓ Columna "type" existe</p>';
    } else {
        echo '<p class="error">✗ Columna "type" NO existe</p>';
    }
    echo '</div>';
    
    // TEST 2: Datos en la base de datos
    echo '<div class="section">';
    echo '<h2>TEST 2: Datos en Base de Datos</h2>';
    $stmt = $pdo->query("SELECT id, name, type, balance, status, parent_account_id FROM accounts ORDER BY id");
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo '<table>';
    echo '<tr><th>ID</th><th>Nombre</th><th>Type</th><th>Balance</th><th>Status</th><th>Parent</th></tr>';
    foreach ($accounts as $acc) {
        $typeClass = ($acc['type'] === 'temporary') ? 'success' : (empty($acc['type']) ? 'error' : '');
        echo "<tr>";
        echo "<td>{$acc['id']}</td>";
        echo "<td>{$acc['name']}</td>";
        echo "<td class='{$typeClass}'>" . ($acc['type'] ?: '(VACÍO)') . "</td>";
        echo "<td>Bs. {$acc['balance']}</td>";
        echo "<td>{$acc['status']}</td>";
        echo "<td>" . ($acc['parent_account_id'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo '</table>';
    echo '</div>';
    
    // TEST 3: Respuesta del API
    echo '<div class="section">';
    echo '<h2>TEST 3: Respuesta del API /accounts/fetch</h2>';
    $response = @file_get_contents('http://localhost/finazapersonal/public/accounts/fetch');
    
    if ($response) {
        $data = json_decode($response, true);
        echo '<pre>' . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
        
        if ($data['status'] === 'success') {
            $tempCount = 0;
            foreach ($data['data'] as $acc) {
                if ($acc['type'] === 'temporary') $tempCount++;
            }
            
            if ($tempCount > 0) {
                echo "<p class='success'>✓ API devuelve {$tempCount} cuenta(s) temporal(es)</p>";
            } else {
                echo "<p class='error'>✗ API NO devuelve cuentas temporales</p>";
            }
        }
    } else {
        echo '<p class="error">✗ No se pudo conectar al API</p>';
    }
    echo '</div>';
    
    // TEST 4: Verificar AccountModel
    echo '<div class="section">';
    echo '<h2>TEST 4: Configuración de AccountModel</h2>';
    $modelPath = __DIR__ . '/app/Models/AccountModel.php';
    if (file_exists($modelPath)) {
        $modelContent = file_get_contents($modelPath);
        echo '<pre>' . htmlspecialchars($modelContent) . '</pre>';
        
        if (strpos($modelContent, "'type'") !== false) {
            echo '<p class="success">✓ AccountModel incluye "type" en allowedFields</p>';
        } else {
            echo '<p class="error">✗ AccountModel NO incluye "type" en allowedFields</p>';
        }
    } else {
        echo '<p class="error">✗ No se encontró AccountModel.php</p>';
    }
    echo '</div>';
    
    // TEST 5: Verificar filtro en frontend
    echo '<div class="section">';
    echo '<h2>TEST 5: Filtro en Frontend (accounts/index.php)</h2>';
    $viewPath = __DIR__ . '/app/Views/accounts/index.php';
    if (file_exists($viewPath)) {
        $viewContent = file_get_contents($viewPath);
        
        // Buscar el filtro de cuentas principales
        if (preg_match("/accounts\.filter\(a => a\.type !== 'temporary'\)/", $viewContent)) {
            echo '<p class="success">✓ Filtro de cuentas principales encontrado</p>';
        } else {
            echo '<p class="warning">⚠ Filtro de cuentas principales no encontrado o diferente</p>';
        }
        
        // Buscar el filtro de fondos temporales
        if (preg_match("/accounts\.filter\(a => a\.type === 'temporary'/", $viewContent)) {
            echo '<p class="success">✓ Filtro de fondos temporales encontrado</p>';
        } else {
            echo '<p class="error">✗ Filtro de fondos temporales NO encontrado</p>';
        }
    }
    echo '</div>';
    
    // TEST 6: Diagnóstico final
    echo '<div class="section">';
    echo '<h2>TEST 6: Diagnóstico y Recomendaciones</h2>';
    
    $issues = [];
    
    // Check if Prueba Liquidación exists and has correct type
    $prueba = null;
    foreach ($accounts as $acc) {
        if ($acc['name'] === 'Prueba Liquidación') {
            $prueba = $acc;
            break;
        }
    }
    
    if (!$prueba) {
        $issues[] = "No existe cuenta 'Prueba Liquidación'";
    } else {
        if ($prueba['type'] !== 'temporary') {
            $issues[] = "Cuenta 'Prueba Liquidación' tiene type='" . ($prueba['type'] ?: 'VACÍO') . "' en lugar de 'temporary'";
        }
        if (!$prueba['parent_account_id']) {
            $issues[] = "Cuenta 'Prueba Liquidación' no tiene parent_account_id";
        }
    }
    
    if (count($issues) > 0) {
        echo '<p class="error"><strong>PROBLEMAS ENCONTRADOS:</strong></p><ul>';
        foreach ($issues as $issue) {
            echo "<li class='error'>✗ {$issue}</li>";
        }
        echo '</ul>';
        
        echo '<p class="warning"><strong>SOLUCIÓN:</strong></p>';
        echo '<p>Ejecuta este SQL para reparar:</p>';
        echo '<pre>';
        echo "UPDATE accounts SET type = 'temporary' WHERE name = 'Prueba Liquidación';\n";
        echo "UPDATE accounts SET type = 'general' WHERE type IS NULL OR type = '';";
        echo '</pre>';
    } else {
        echo '<p class="success">✓ No se encontraron problemas obvios</p>';
    }
    
    echo '</div>';
    ?>
    
    <div class="section">
        <h2>Acciones de Prueba</h2>
        <p>Después de revisar este reporte:</p>
        <ol>
            <li>Refresca la página de cuentas: <a href="/finazapersonal/public/accounts" style="color: #0ff;">http://localhost/finazapersonal/public/accounts</a></li>
            <li>Verifica que "Prueba Liquidación" aparezca en FONDOS TEMPORALES (sección naranja)</li>
            <li>Verifica que tenga un botón naranja "Liquidar"</li>
        </ol>
    </div>
</body>
</html>
