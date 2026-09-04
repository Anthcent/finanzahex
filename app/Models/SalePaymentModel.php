<?php

namespace App\Models;

use CodeIgniter\Model;

class SalePaymentModel extends Model
{
    protected $table = 'sale_payments';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'sale_id', 'amount', 'amount_usd', 'rate', 
        'date', 'reference', 'created_at'
    ];
    protected $useTimestamps = false; // We use created_at manually or default

    public function getPaymentsBySale($saleId)
    {
        return $this->where('sale_id', $saleId)->orderBy('date', 'DESC')->findAll();
    }
}
