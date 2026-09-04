<?php
// Direct repair using CodeIgniter
require __DIR__ . '/vendor/autoload.php';

// Bootstrap CodeIgniter
$pathsConfig = new Config\Paths();
$bootstrap = rtrim($pathsConfig->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';
require $bootstrap;

$app = Config\Services::codeigniter();
$app->initialize();

// Now use models
$accountModel = new \App\Models\AccountModel();

echo "=== REPARANDO CUENTA COMPRAS ===\n\n";

// Find compras
$compras = $accountModel->where('name', 'compras')->first();

if (!$compras) {
    die("ERROR: Cuenta 'compras' no encontrada\n");
}

echo "Cuenta encontrada:\n";
echo "  ID: {$compras['id']}\n";
echo "  Nombre: {$compras['name']}\n";
echo "  Tipo: {$compras['type']}\n";
echo "  Balance: Bs. {$compras['balance']}\n";
echo "  Parent ID actual: " . ($compras['parent_account_id'] ?? 'NULL') . "\n\n";

// Find parent
$parent = $accountModel->where('type !=', 'temporary')
                       ->where('status', 'active')
                       ->first();

if (!$parent) {
    die("ERROR: No hay cuentas principales\n");
}

echo "Asignando como padre:\n";
echo "  ID: {$parent['id']}\n";
echo "  Nombre: {$parent['name']}\n";
echo "  Balance actual: Bs. {$parent['balance']}\n\n";

// Update
$accountModel->update($compras['id'], [
    'parent_account_id' => $parent['id'],
    'type' => 'temporary',
    'status' => 'active'
]);

echo "✓ REPARACIÓN EXITOSA\n\n";
echo "Ahora puedes:\n";
echo "1. Refrescar la página de cuentas\n";
echo "2. Presionar el botón NARANJA 'Liquidar' en la cuenta 'compras'\n";
echo "3. Los Bs. {$compras['balance']} serán devueltos a '{$parent['name']}'\n";
