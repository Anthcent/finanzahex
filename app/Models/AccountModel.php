<?php

namespace App\Models;

use CodeIgniter\Model;

class AccountModel extends Model
{
    protected $table = 'accounts';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name', 'type', 'balance', 'status', 'parent_account_id', 'currency', 'tenure_type'];
    public $timestamps = true;
}

// ------ Separator for multi-file (simulated logic, actually separate calls needed or I make 2 calls)
// I will just make it one file? No, PHP requires one class per file usually for autoloading.
// I will create AccountModel here.
