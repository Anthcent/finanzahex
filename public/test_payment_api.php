<?php
// public/test_payment_api.php

echo "<h1>Testing Payment Logic via API</h1>";

$baseUrl = "http://localhost/finazapersonal/public"; // Adjust port if needed, e.g. :8080

// 1. Create a dummy order manually via direct DB or just assume one exists? 
// Without DB access (due to CI boostrap issues), I can't easily create a test order AND clean it up.
// So I will try to use the 'store' endpoint to create an order first!

function sendRequest($url, $data = [], $method = 'POST') {
    $ch = curl_init($url);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_POST, 1);
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'body' => json_decode($result, true), 'raw' => $result];
}

// Check if server is reachable
$res = sendRequest("$baseUrl/printing/settings", [], 'GET');
if ($res['code'] !== 200) {
    echo "Server check failed. Code: {$res['code']}. URL: $baseUrl/printing/settings<br>";
    // Try https if http failed?
    $baseUrl = "https://educags-os.online/finanza/public"; // Remote fallback? No, safer local.
    // Try adding port 80 or 8080?
    // Let's assume user is running XAMPP as per paths involving xampp/htdocs
    echo "Is XAMPP Apache running?<br>";
    exit;
} else {
    echo "Server reachable.<br>";
}


// 2. Create Order
echo "<h3>1. Creating Test Order</h3>";
$orderData = [
    'items' => [
        ['name' => 'Test Item', 'price_bs' => 10, 'price_usd' => 0, 'quantity' => 10] // Total 100 Bs
    ],
    'account_id' => 1, // Assoc with account 1
    'exchange_rate' => 50,
    'customer_name' => 'API Test Customer',
    'paid_bs' => 0, // DEBT
    'paid_usd' => 0
];

$resOrder = sendRequest("$baseUrl/printing/store", $orderData);
echo "Create Response: <pre>" . print_r($resOrder['body'], true) . "</pre>";

if (($resOrder['body']['status'] ?? '') !== 'success') {
    echo "Failed to create order. Exiting.";
    exit;
}

// Check ID? 
// The store response doesn't return the ID explicitly in the JSON based on my read of PrintingController.store()
// Wait, looking at store() code: return $this->response->setJSON(['status' => 'success', 'message' => 'Registrado']);
// It does NOT return the order ID. That makes it hard to test the payment on *this* specific order without querying DB.
// I'll have to rely on manual verification or assume the last created order is this one.

echo "<h3>Cannot proceed with automated payment test because 'store' endpoint does not return Order ID.</h3>";
echo "Please manually verify in the UI.";
