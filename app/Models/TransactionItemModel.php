<?php

namespace App\Models;

use CodeIgniter\Model;

class TransactionItemModel extends Model
{
    protected $table = 'transaction_items';
    protected $primaryKey = 'id';
    protected $allowedFields = ['transaction_id', 'name', 'description', 'quantity', 'price', 'price_usd']; // Total is generated
    public $timestamps = false;
}
