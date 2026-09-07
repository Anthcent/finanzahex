<?php

namespace App\Models;

use CodeIgniter\Model;

class AccountTransferModel extends Model
{
    protected $table = 'account_transfers';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'request_id', 'transfer_type', 'source_account_id', 'destination_account_id',
        'amount', 'currency', 'category_id', 'note', 'source_balance_before',
        'source_balance_after', 'destination_balance_before', 'destination_balance_after', 'status',
    ];
}
