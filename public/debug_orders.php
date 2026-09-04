<?php
// public/debug_orders.php

define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
$pathsPath = FCPATH . '../app/Config/Paths.php';
chdir(FCPATH . '../');
require $pathsPath;
$paths = new Config\Paths();
require $paths->systemDirectory . '/bootstrap.php';

use Config\Database;

$db = Database::connect();
echo "<h1>Debug Recent Orders</h1>";

$orders = $db->table('print_orders')
             ->orderBy('updated_at', 'DESC') // Assuming updated_at exists? No, created_at. Handled by update? 
             ->orderBy('id', 'DESC')
             ->limit(5)
             ->get()->getResultArray();

echo "<table border='1'><tr><th>ID</th><th>Customer</th><th>Total Bs</th><th>Total USD</th><th>Paid Bs</th><th>Paid USD</th><th>Status</th></tr>";
foreach ($orders as $o) {
    echo "<tr>";
    echo "<td>{$o['id']}</td>";
    echo "<td>{$o['customer_name']}</td>";
    echo "<td>{$o['total_bs']}</td>";
    echo "<td>{$o['total_usd']}</td>";
    echo "<td>{$o['paid_bs']}</td>";
    echo "<td>{$o['paid_usd']}</td>";
    echo "<td>{$o['status']}</td>";
    echo "</tr>";
}
echo "</table>";
