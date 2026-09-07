<?php

namespace App\Models;

use CodeIgniter\Model;

class PrintProductModel extends Model
{
    protected $table = 'print_products';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'name', 'price_bs', 'price_usd', 'category', 'category_id', 'product_type',
        'sku', 'description', 'unit', 'characteristics_json', 'is_active', 'icon', 'color',
        'created_at', 'updated_at',
    ];
    protected $useTimestamps = false;
}
