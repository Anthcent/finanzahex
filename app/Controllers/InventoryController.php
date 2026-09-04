<?php

namespace App\Controllers;

use App\Models\InventoryItemModel;
use App\Models\InventoryCategoryModel;
use App\Models\InventoryMovementModel;

class InventoryController extends BaseController
{
    public function index()
    {
        $itemModel = new InventoryItemModel();
        
        $data = [
            'total_items' => $itemModel->countAllResults(),
            'low_stock' => $itemModel->where('stock <', 5)->countAllResults(),
            'total_value' => 0 // Can implement sum query later
        ];
        
        return view('inventory/index', $data);
    }

    public function items()
    {
        return view('inventory/items');
    }

    public function movements()
    {
        $movModel = new InventoryMovementModel();
        
        // Fetch movements with Item Name
        $movements = $movModel->select('inventory_movements.*, inventory_items.name as item_name')
            ->join('inventory_items', 'inventory_items.id = inventory_movements.item_id')
            ->orderBy('date', 'DESC')
            ->findAll();
            
        return view('inventory/movements', ['movements' => $movements]);
    }

    public function getItems() 
    {
        $itemModel = new InventoryItemModel();
        $db = \Config\Database::connect();
        
        $items = $db->table('inventory_items i')
            ->select('i.*, c.name as category_name')
            ->join('inventory_categories c', 'c.id = i.category_id', 'left')
            ->orderBy('i.name', 'ASC')
            ->get()
            ->getResultArray();
            
        return $this->response->setJSON(['status' => 'success', 'data' => $items]);
    }
    
    public function saveItem()
    {
        $itemModel = new InventoryItemModel();
        $json = $this->request->getJSON();
        
        $data = [
            'name' => $json->name,
            'category_id' => $json->category_id ?: null,
            'price' => $json->price,
            'cost' => $json->cost,
            'unit' => $json->unit,
            'description' => $json->description ?? '',
            'characteristics' => isset($json->characteristics) ? json_encode($json->characteristics) : null
        ];
        
        // Initial Stock handled separately optionally, but for simplicity we assume stock is managed via movements
        // However, user might want to set initial stock.
        if (isset($json->stock) && !isset($json->id)) {
            $data['stock'] = $json->stock;
        }

        if (isset($json->id) && $json->id) {
            $itemModel->update($json->id, $data);
            $id = $json->id;
        } else {
            $id = $itemModel->insert($data);
            
            // Record Initial Stock Movement if provided
            if (isset($json->stock) && $json->stock > 0) {
                $moveModel = new InventoryMovementModel();
                $moveModel->insert([
                    'item_id' => $id,
                    'type' => 'in',
                    'quantity' => $json->stock,
                    'date' => date('Y-m-d H:i:s'),
                    'reference' => 'Stock Inicial'
                ]);
            }
        }
        
        return $this->response->setJSON(['status' => 'success', 'message' => 'Guardado']);
    }

    public function deleteItem($id)
    {
        $itemModel = new InventoryItemModel();
        $itemModel->delete($id);
        return $this->response->setJSON(['status' => 'success']);
    }

    public function getCategories()
    {
        $catModel = new InventoryCategoryModel();
        return $this->response->setJSON([
            'status' => 'success', 
            'data' => $catModel->findAll()
        ]);
    }

    public function saveCategory()
    {
        $catModel = new InventoryCategoryModel();
        $json = $this->request->getJSON();
        $catModel->save([
            'id' => $json->id ?? null,
            'name' => $json->name,
            'type' => $json->type
        ]);
        return $this->response->setJSON(['status' => 'success']);
    }
    
    // API for Sales Form Search
    public function search()
    {
        $term = $this->request->getGet('q');
        $itemModel = new InventoryItemModel();
        
        $items = $itemModel->like('name', $term)
            ->orLike('description', $term)
            ->orderBy('name', 'ASC') // Better sorting
            ->limit(10)
            ->find();
            
        return $this->response->setJSON(['status' => 'success', 'data' => $items]);
    }

    public function quickCreate()
    {
        $json = $this->request->getJSON();
        $name = $json->name;
        
        if(empty($name)) return $this->response->setJSON(['status' => 'error', 'message' => 'Nombre requerido']);
        
        $itemModel = new InventoryItemModel();
        
        // Check if exists
        $existing = $itemModel->where('name', $name)->first();
        if($existing) return $this->response->setJSON(['status' => 'success', 'data' => $existing]);
        
        $id = $itemModel->insert([
            'name' => $name,
            'category_id' => $this->getValidCategoryId(),
            'price' => 0,
            'cost' => 0,
            'stock' => 0,
            'unit' => 'unid'
        ]);
        
        $newItem = $itemModel->find($id);
        
        return $this->response->setJSON(['status' => 'success', 'data' => $newItem]);
    }
    private function getValidCategoryId()
    {
        $catModel = new InventoryCategoryModel();
        $cat = $catModel->find(1);
        if ($cat) return 1;
        
        $first = $catModel->first();
        if ($first) return $first['id'];
        
        return $catModel->insert(['name' => 'General', 'type' => 'product']);
    }
}
