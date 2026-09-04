<?php

// Config
$baseUrl = 'http://localhost/finazapersonal/transaction/save';
// Database config for verification
$dbHost = 'localhost';
$dbName = 'finazapersonal';
$dbUser = 'root';
$dbPass = '';

echo "1. Preparing Payload...\n";

// 1. Prepare Payload
$payload = [
    'amount' => 60.50, // Total
    'amount_usd' => 1.21,
    'exchange_rate' => 50,
    'type' => 'expense',
    'account_id' => 1, // Assuming account 1 exists
    'category_id' => 1, // Assuming category 1 exists
    'owner' => 'TestBot',
    'description' => 'Cart Test ' . date('Y-m-d H:i:s'),
    'items' => [
        [
            'name' => 'Item A (Cheap)',
            'price' => 10.00,
            'price_usd' => 0.20,
            'quantity' => 1
        ],
        [
            'name' => 'Item B (Medium)',
            'price' => 20.00, // CHECKPOINT: This should NOT be 10.00
            'price_usd' => 0.40,
            'quantity' => 1
        ],
        [
            'name' => 'Item C (Expensive)',
            'price' => 30.50, // CHECKPOINT: This should NOT be 10.00 or 20.00
            'price_usd' => 0.61,
            'quantity' => 1
        ]
    ]
];

echo "   Items Prices Sent: 10.00, 20.00, 30.50\n";
echo "   Endpoint: $baseUrl\n";

// 2. Send Request
$ch = curl_init($baseUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "2. Response Received (HTTP $httpCode): " . substr($response, 0, 100) . "...\n";

$jsonResp = json_decode($response, true);

if (!$jsonResp || !isset($jsonResp['status']) || $jsonResp['status'] !== 'success') {
    echo "❌ FAIL: API Request Rejected\n";
    echo "Response: $response\n";
    exit(1);
}

// Check if ID exists
if (!isset($jsonResp['id'])) {
    echo "❌ FAIL: No Transaction ID returned\n";
    exit(1);
}

$transId = $jsonResp['id'];
echo "   Transaction ID: $transId\n";

// 3. Verify Database
echo "3. Verifying Database...\n";

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT * FROM transaction_items WHERE transaction_id = ?");
    $stmt->execute([$transId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "   Found " . count($items) . " items in DB.\n";

    $pass = true;
    foreach ($items as $item) {
        echo "   - Item: {$item['name']} | Saved Price: {$item['price']}\n";
        
        $price = floatval($item['price']);
        
        // Basic matching logic
        if (strpos($item['name'], 'Item A') !== false && abs($price - 10.00) > 0.01) {
            echo "     ❌ ERROR: Expected 10.00, got $price\n";
            $pass = false;
        }
        elseif (strpos($item['name'], 'Item B') !== false && abs($price - 20.00) > 0.01) {
            echo "     ❌ ERROR: Expected 20.00, got $price\n";
            $pass = false;
        }
        elseif (strpos($item['name'], 'Item C') !== false && abs($price - 30.50) > 0.01) {
            echo "     ❌ ERROR: Expected 30.50, got $price\n";
            $pass = false;
        } else {
            echo "     ✅ OK\n";
        }
    }

    if ($pass) {
        echo "\n✅ RESULT: PASS - Logic is correct. Prices are saved individually.\n";
    } else {
        echo "\n❌ RESULT: FAIL - Prices are incorrect.\n";
    }

} catch (PDOException $e) {
    echo "❌ DB Error: " . $e->getMessage() . "\n";
}
