<?php

namespace App\Models;

use CodeIgniter\Model;

class InventoryMovementModel extends Model
{
    protected $table = 'inventory_movements';
    protected $primaryKey = 'id';
    protected $allowedFields = ['item_id', 'type', 'quantity', 'date', 'reference', 'created_at'];
    protected $useTimestamps = true;
    protected $updatedField  = '';
}
