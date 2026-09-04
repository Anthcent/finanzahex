<?php
// Load CodeIgniter
require 'start_ci.php';

use App\Models\TransactionModel;
use Config\Database;

$db = Database::connect();

// 1. Get the most recent print orders
$orders = $db->table('print_orders')
             ->orderBy('id', 'DESC')
             ->limit(5)
             ->get()->getResultArray();

echo "--- Recent Orders ---\n";
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
    // This helps identify if we are failing to save the ID column but saving the description OK.
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
