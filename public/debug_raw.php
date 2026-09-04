<?php
// public/debug_raw.php

$host = '127.0.0.1';
$db   = 'finanza'; // Guessing local DB name, user path was 'finazapersonal', DB might be 'finanza' or 'finanzapersonal' or copied from prod. 
// Let's try 'finanzapersonal' based on folder name or 'educbogv_finanza' if they imported it.
// Actually, earlier the user said: c:\xampp\htdocs\finazapersonal\
// And .env said database.default.database = educbogv_finanza
// Often people import the same DB name locally.
$db = 'educbogv_finanza'; 
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

$stmt = $pdo->query("SELECT id, customer_name, total_bs, total_usd, paid_bs, paid_usd, status FROM print_orders ORDER BY id DESC LIMIT 5");
echo "ID | Customer | Total Bs | Total USD | Paid Bs | Paid USD | Status\n";
while ($row = $stmt->fetch()) {
    echo "{$row['id']} | {$row['customer_name']} | {$row['total_bs']} | {$row['total_usd']} | {$row['paid_bs']} | {$row['paid_usd']} | {$row['status']}\n";
}
