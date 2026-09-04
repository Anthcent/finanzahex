<?php

namespace App\Models;

use CodeIgniter\Model;

class SaleDetailModel extends Model
{
    protected $table = 'sale_details';
    protected $primaryKey = 'id';
    protected $allowedFields = ['sale_id', 'item_id', 'quantity', 'price', 'subtotal'];
    protected $useTimestamps = false;
}
