<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\PrintProductModel;

class TestDb extends Controller
{
    public function index()
    {
        $db = \Config\Database::connect();
        
        // 1. Check if table exists
        if (!$db->tableExists('print_products')) {
            echo "Table 'print_products' does NOT exist.<br>";
        } else {
            echo "Table 'print_products' exists.<br>";
            
            // 2. Check count
            $count = $db->table('print_products')->countAll();
            echo "Total products in DB: " . $count . "<br>";
            
            // 3. Show sample
            $query = $db->table('print_products')->get();
            $results = $query->getResultArray();
            
            if (empty($results)) {
                echo "No products found.<br>";
            } else {
                echo "<pre>";
                print_r($results);
                echo "</pre>";
            }
        }
        
        // 4. Check Model
        try {
            $model = new PrintProductModel();
            $items = $model->findAll();
            echo "Model found " . count($items) . " items.<br>";
        } catch (\Exception $e) {
            echo "Model Error: " . $e->getMessage();
        }
    }
}
