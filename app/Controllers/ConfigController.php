<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\CategoryModel;
use App\Models\AccountModel;
use App\Models\AuditLogModel;

class ConfigController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        $accounts = $db->table('accounts')->whereIn('status', ['active', 'closed'])->get()->getResultArray();
        
        $settingsQuery = $db->table('settings')->get()->getResultArray();
        $settings = [];
        foreach($settingsQuery as $row) $settings[$row['key']] = $row['value'];

        return view('config/index', ['accounts' => $accounts, 'settings' => $settings]);
    }

    public function saveSetting() {
        $json = $this->request->getJSON();
        

        if (!$json || !isset($json->key) || !isset($json->value)) {
             return $this->response->setJSON(['status' => 'error', 'message' => 'Missing data']);
        }

        $db = \Config\Database::connect();
        
        try {
            $builder = $db->table('settings');
            if ($builder->where('key', $json->key)->countAllResults() > 0) {
                $builder->where('key', $json->key)->update(['value' => $json->value]);
            } else {
                $builder->insert(['key' => $json->key, 'value' => $json->value]);
            }

            return $this->response->setJSON(['status' => 'success']);
        } catch (\Exception $e) {
             return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function getData()
    {
        $catModel = new CategoryModel();
        $accModel = new AccountModel();

        return $this->response->setJSON([
            'categories' => $catModel->findAll(),
            'accounts' => $accModel->whereIn('status', ['active', 'closed'])->orderBy('type', 'ASC')->findAll()
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
        $name = trim((string) ($json->name ?? ''));
        if ($name === '') return $this->response->setJSON(['status' => 'error', 'message' => 'El nombre es obligatorio.']);
        $id = $model->insert([
            'name' => $name, 'balance' => 0, 'initial_balance' => 0,
            'type' => 'general', 'status' => 'active', 'currency' => 'Bs', 'tenure_type' => 'none',
        ]);
        return $this->response->setJSON($id ? ['status' => 'success'] : ['status' => 'error', 'message' => 'No se pudo crear la cuenta.']);
    }

    public function updateAccount($id)
    {
        $json = $this->request->getJSON();
        $model = new AccountModel();
        $account = $model->find((int) $id);
        if (!$account || ($account['status'] ?? '') === 'deleted') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Cuenta no encontrada.']);
        }

        $name = trim((string) ($json->name ?? ''));
        $currency = $account['type'] === 'temporary'
            ? ($account['currency'] ?? 'Bs')
            : (strtoupper((string) ($json->currency ?? 'BS')) === 'USD' ? 'USD' : 'Bs');
        $tenure = in_array(($json->tenure_type ?? 'none'), ['none', 'digital', 'physical'], true)
            ? $json->tenure_type : 'none';
        if ($account['type'] === 'temporary') $tenure = $account['tenure_type'] ?? 'none';
        if ($name === '') return $this->response->setJSON(['status' => 'error', 'message' => 'El nombre es obligatorio.']);

        $changes = ['name' => $name, 'currency' => $currency, 'tenure_type' => $tenure];
        if (!$model->update($id, $changes)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No se pudo actualizar la cuenta.']);
        }
        if ($account['type'] !== 'temporary' && $currency !== ($account['currency'] ?? 'Bs')) {
            $model->where('parent_account_id', $id)->where('status', 'active')->set([
                'currency' => $currency, 'tenure_type' => $tenure,
            ])->update();
        }
        AuditLogModel::log('accounts', 'update', $id, $account, $changes, null, "Edición de cuenta: {$name}");
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
