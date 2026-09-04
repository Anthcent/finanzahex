<?php
// Load CodeIgniter framework
$minPath = __DIR__ . '/public/index.php';
chdir(__DIR__);
require 'app/Config/Paths.php';
$paths = new Config\Paths();
require 'system/bootstrap.php';

use App\Models\AccountModel;

$model = new AccountModel();
$accounts = $model->findAll();

echo "ID | Name | Type | Status | ParentID\n";
echo "--------------------------------------\n";
foreach ($accounts as $acc) {
    echo "{$acc['id']} | {$acc['name']} | {$acc['type']} | {$acc['status']} | {$acc['parent_account_id']}\n";
}
