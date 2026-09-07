<?php

namespace App\Models;

use CodeIgniter\Model;

class CurrencyOperationModel extends Model
{
    protected $table = 'currency_operations';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'request_id', 'operation_type', 'source_account_id', 'destination_account_id',
        'subtotal_bs', 'commission_percent', 'commission_bs', 'total_bs', 'amount_usd',
        'quoted_rate', 'effective_rate', 'reference', 'notes', 'owner', 'status',
        'operation_date', 'reversed_at',
    ];
}
