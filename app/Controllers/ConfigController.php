<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\CategoryModel;
use App\Models\AccountModel;

class ConfigController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        $accounts = $db->table('accounts')->get()->getResultArray();
        
        $settingsQuery = $db->table('settings')->get()->getResultArray();
        $settings = [];
        foreach($settingsQuery as $row) $settings[$row['key']] = $row['value'];

        return view('config/index', ['accounts' => $accounts, 'settings' => $settings]);
    }

    public function saveSetting() {
        $json = $this->request->getJSON();
        
        // DEBUG: Write to FCPATH (public folder) to be sure
        file_put_contents(FCPATH . 'debug_settings.txt', date('Y-m-d H:i:s') . " - Request: " . print_r($json, true) . "\n", FILE_APPEND);

        if (!$json || !isset($json->key) || !isset($json->value)) {
             file_put_contents(FCPATH . 'debug_settings.txt', "ERROR: Missing key/value\n", FILE_APPEND);
             return $this->response->setJSON(['status' => 'error', 'message' => 'Missing data']);
        }

        $db = \Config\Database::connect();
        
        try {
            // Use query binding to avoid reserved word issues completely
            $sql = "SELECT count(*) as count FROM settings WHERE `key` = ?";
            $query = $db->query($sql, [$json->key]);
            $row = $query->getRow();
            $exists = $row->count > 0;
            
            if ($exists) {
                $sql = "UPDATE settings SET `value` = ? WHERE `key` = ?";
                $db->query($sql, [$json->value, $json->key]);
            } else {
                $sql = "INSERT INTO settings (`key`, `value`) VALUES (?, ?)";
                $db->query($sql, [$json->key, $json->value]);
            }
            
            return $this->response->setJSON(['status' => 'success']);
        } catch (\Exception $e) {
             file_put_contents(FCPATH . 'debug_settings.txt', "ERROR SQL: " . $e->getMessage() . "\n", FILE_APPEND);
             return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function getData()
    {
        $catModel = new CategoryModel();
        $accModel = new AccountModel();

        return $this->response->setJSON([
            'categories' => $catModel->findAll(),
            'accounts' => $accModel->findAll()
        ]);
    }

    public function addCategory()
    {
        $json = $this->request->getJSON();
        $model = new CategoryModel();
        $model->insert(['name' => $json->name, 'type' => $json->type ?? 'expense']);
        return $this->response->setJSON(['status' => 'success']);
    }

    public function deleteCategory($id)
    {
        $model = new CategoryModel();
        $model->delete($id);
        return $this->response->setJSON(['status' => 'success']);
    }

    public function addAccount()
    {
        $json = $this->request->getJSON();
        $model = new AccountModel();
        $model->insert(['name' => $json->name, 'balance' => 0]);
        return $this->response->setJSON(['status' => 'success']);
    }

    public function deleteAccount($id)
    {
        $model = new AccountModel();
        $model->delete($id);
        return $this->response->setJSON(['status' => 'success']);
    }

    public function updateBalance()
    {
        $json = $this->request->getJSON();
        $model = new AccountModel();
        $model->update($json->id, ['balance' => $json->balance]);
        return $this->response->setJSON(['status' => 'success']);
    }

    public function export()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('transactions');
        $builder->select('transactions.created_at, transactions.amount, transactions.amount_usd, transactions.type, transactions.description, transactions.owner, accounts.name as account, categories.name as category');
        $builder->join('accounts', 'accounts.id = transactions.account_id', 'left');
        $builder->join('categories', 'categories.id = transactions.category_id', 'left');
        $builder->orderBy('transactions.created_at', 'DESC');
        
        $query = $builder->get();
        $results = $query->getResultArray();

        $filename = 'transactions_export_' . date('Y-m-d_H-i-s') . '.csv';

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $fp = fopen('php://output', 'w');
        
        // Header
        fputcsv($fp, ['Fecha', 'Monto (Bs)', 'Monto (USD)', 'Tipo', 'Descripción', 'Responsable', 'Cuenta', 'Categoría']);

        foreach ($results as $row) {
            fputcsv($fp, $row);
        }

        fclose($fp);
        exit;
    }
}
