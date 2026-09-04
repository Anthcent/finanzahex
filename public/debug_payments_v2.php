<?php
// Path to the front controller (this file)
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

// Ensure the current directory is pointing to the front controller's directory
chdir(__DIR__);

// Load our paths config file
// This is the line that might need to be changed, depending on your folder structure.
$pathsConfig = FCPATH . '../app/Config/Paths.php';
// ^^^ Change this if you move your application folder

require $pathsConfig;
$paths = new Config\Paths();

// Location of the framework bootstrap file.
define('ENVIRONMENT', 'development');
require rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';

// Load the framework constants
if (file_exists(APPPATH . 'Config/Constants.php')) {
    require_once APPPATH . 'Config/Constants.php';
}

// User helper
use App\Models\TransactionModel;
use Config\Database;

$db = Database::connect();

echo "--- Recent Orders ---\n";
// 1. Get the most recent print orders
$orders = $db->table('print_orders')
             ->orderBy('id', 'DESC')
             ->limit(5)
             ->get()->getResultArray();

foreach ($orders as $o) {
    echo "ID: {$o['id']} | Customer: {$o['customer_name']} | Total: {$o['total_bs']} | Paid: {$o['paid_bs']} | Status: {$o['status']}\n";
    
    // 2. Check for transactions with this print_order_id
    $trans = $db->table('transactions')
                ->where('print_order_id', $o['id'])
                ->get()->getResultArray();
    
    echo "  -> Linked Transactions (" . count($trans) . "):\n";
    foreach ($trans as $t) {
        echo "     T-ID: {$t['id']} | Amount: {$t['amount']} | Desc: {$t['description']}\n";
    }

    // 3. Search for potentially unlinked transactions (orphaned but containing order ID in description?)
    $orphans = $db->table('transactions')
                  ->like('description', '#' . $o['id'])
                   ->where('print_order_id', null)
                   ->orWhere('print_order_id', 0)
                   ->get()->getResultArray();
                   
    if (!empty($orphans)) {
         echo "  -> POTENTIAL ORPHANS (Description match but no ID link):\n";
         foreach ($orphans as $ot) {
             // Filter by basic proximity if needed, but description match #ID is usually good enough for debug
             if (strpos($ot['description'], "#{$o['id']}") !== false) {
                  echo "     [ORPHAN] T-ID: {$ot['id']} | Amount: {$ot['amount']} | Desc: {$ot['description']}\n";
             }
         }
    }
    echo "\n";
}
