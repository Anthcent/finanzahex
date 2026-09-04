<?php

namespace App\Models;

use CodeIgniter\Model;

class InventoryItemModel extends Model
{
    protected $table = 'inventory_items';
    protected $primaryKey = 'id';
    protected $allowedFields = ['category_id', 'name', 'description', 'price', 'cost', 'stock', 'unit', 'characteristics', 'created_at', 'updated_at'];
    protected $useTimestamps = true;
}
