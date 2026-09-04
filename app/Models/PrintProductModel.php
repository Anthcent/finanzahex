<?php

namespace App\Models;

use CodeIgniter\Model;

class PrintProductModel extends Model
{
    protected $table = 'print_products';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name', 'price_bs', 'price_usd', 'category', 'icon', 'color'];
    protected $useTimestamps = false; // Simple table
}
