<?php

namespace App\Models;

use CodeIgniter\Model;

class SaleModel extends Model
{
    protected $table = 'sales';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'date', 'product', 'amount', 'amount_usd', 'exchange_rate', 
        'customer', 'status', 'reference', 'description', 
        'paid_amount', 'paid_amount_usd', 'created_at', 'updated_at',
        'order_status_id'
    ];
    protected $useTimestamps = true;

    // Get all sales (history)
    public function getSales()
    {
        return $this->orderBy('date', 'DESC')->findAll();
    }

    // Get pending debts (partial status)
    public function getDebts()
    {
        return $this->where('status', 'partial')->orderBy('date', 'ASC')->findAll();
    }
}
