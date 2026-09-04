<?php
// public/test_payment.php

define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
$pathsPath = FCPATH . '../app/Config/Paths.php';
chdir(FCPATH . '../');
require $pathsPath;
$paths = new Config\Paths();
require $paths->systemDirectory . '/bootstrap.php';

use Config\Database;
use App\Models\AccountModel;

$db = Database::connect();
echo "<h1>Testing Payment Logic</h1>";

// 1. Create a dummy order
$db->table('print_orders')->insert([
    'customer_name' => 'Test Customer',
    'details' => json_encode(['Test Item']),
    'total_bs' => 100,
    'total_usd' => 2,
    'paid_bs' => 0,
    'paid_usd' => 0,
    'status' => 'pending',
    'created_at' => date('Y-m-d H:i:s')
]);
$orderId = $db->insertID();
echo "Created Order #$orderId<br>";

// 2. Try to pay WITHOUT account (Should Fail)
echo "<h3>Test 1: Payment WITHOUT Account</h3>";
$url = 'http://localhost/finazapersonal/public/printing/add-payment';

// Simulate POST request via internal call logic or just instantiate controller?
// Let's use Curl to hit the actual endpoint if possible, or just instantiate the controller.
// Instantiating controller is faster/easier for script.

$request = \Config\Services::request();
$response = \Config\Services::response();

// Mock Request? Hard in standalone script without full CI boot.
// Let's just use CURL to the local server.

function sendPost($url, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result, true);
}

// NOTE: Ensure your server is running on localhost/finazapersonal/public or adapt URL
// Since we don't know the exact port/url efficiently, let's try to infer from .env or just assume standard XAMPP
// .env said: app.baseURL = 'https://educags-os.online/finanza/public/'
// BUT user local path is c:\xampp\htdocs\finazapersonal
// So local URL should be http://localhost/finazapersonal/public/

$localUrl = "http://localhost/finazapersonal/public/printing/add-payment";

$res1 = sendPost($localUrl, [
    'order_id' => $orderId,
    'amount_bs' => 10,
    'account_id' => '' // Empty
]);

echo "Response 1 (Should be error): <pre>" . print_r($res1, true) . "</pre>";


// 3. Try to pay WITH account (Should Success)
echo "<h3>Test 2: Payment WITH Account</h3>";
// Get first account
$acc = $db->table('accounts')->limit(1)->get()->getRowArray();
$accId = $acc ? $acc['id'] : 1;

$res2 = sendPost($localUrl, [
    'order_id' => $orderId,
    'amount_bs' => 10,
    'account_id' => $accId
]);

echo "Response 2 (Should be success): <pre>" . print_r($res2, true) . "</pre>";

// 4. Verify Transaction
$trans = $db->table('transactions')->where('print_order_id', $orderId)->get()->getResultArray();
echo "<h3>Transactions for Order #$orderId</h3>";
echo "<pre>" . print_r($trans, true) . "</pre>";

// Cleanup
$db->table('print_orders')->where('id', $orderId)->delete();
$db->table('transactions')->where('print_order_id', $orderId)->delete();
echo "<br>Cleaned up test data.";
