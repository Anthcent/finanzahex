<?php
// Test the API endpoint directly
$url = 'http://localhost/finazapersonal/public/accounts/fetch';
$response = file_get_contents($url);
$data = json_decode($response, true);

echo "=== RESPUESTA DEL API /accounts/fetch ===\n\n";
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\n";

if ($data['status'] === 'success') {
    echo "=== ANÁLISIS DE CUENTAS ===\n\n";
    foreach ($data['data'] as $acc) {
        $type = $acc['type'] ?? 'UNDEFINED';
        echo "{$acc['name']}:\n";
        echo "  type: '{$type}'\n";
        echo "  status: {$acc['status']}\n";
        echo "  parent_account_id: " . ($acc['parent_account_id'] ?? 'NULL') . "\n";
        echo "\n";
    }
}
