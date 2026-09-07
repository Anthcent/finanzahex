<?php

namespace App\Models;

use CodeIgniter\Model;

class PrintProductCategoryModel extends Model
{
    protected $table = 'print_product_categories';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name', 'icon', 'color', 'sort_order', 'is_active', 'created_at', 'updated_at'];
    protected $useTimestamps = false;
}
