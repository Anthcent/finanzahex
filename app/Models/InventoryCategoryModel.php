<?php

namespace App\Models;

use CodeIgniter\Model;

class InventoryCategoryModel extends Model
{
    protected $table = 'inventory_categories';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name', 'type', 'created_at'];
    protected $useTimestamps = true;
    protected $updatedField  = ''; // Disable updated_at
}
