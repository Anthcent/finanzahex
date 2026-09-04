<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AccountModel;
use App\Models\AuditLogModel;

class AccountController extends BaseController
{
    public function index()
    {
        return view('accounts/index');
    }

    public function fetch()
    {
        $model = new AccountModel();
        // Return both active and closed, but maybe sort active first
        return $this->response->setJSON(['status' => 'success', 'data' => $model->orderBy('status', 'ASC')->findAll()]);
    }

    public function createTemporary()
    {
        $json = $this->request->getJSON();
        $name = $json->name;
        $amount = $json->amount;
        $sourceId = $json->source_id;

        $accountModel = new AccountModel();
        $transModel = new \App\Models\TransactionModel();
        $db = \Config\Database::connect();
        
        $db->transStart();

        try {
            // Validate Source
            $source = $accountModel->find($sourceId);
            if (!$source) throw new \Exception("Cuenta origen no existe");
            if ($source['balance'] < $amount) throw new \Exception("Saldo insuficiente en cuenta origen");

            // Get a valid category (e.g. first one) for internal records
            $categoryModel = new \App\Models\CategoryModel();
            $defaultCat = $categoryModel->first();
            $catId = $defaultCat ? $defaultCat['id'] : 1; // Fallback to 1

            // 1. Deduct from Source
            if (!$accountModel->update($sourceId, ['balance' => $source['balance'] - $amount])) {
                throw new \Exception("Error actualizando origen: " . json_encode($accountModel->errors()));
            }

            // 2. Create Transfer Out Transaction
            if (!$transModel->insert([
                'account_id' => $sourceId,
                'category_id' => $catId,
                'amount' => $amount,
                'amount_usd' => 0,
                'exchange_rate' => 0,
                'type' => 'expense',
                'owner' => 'System',
                'description' => "Transferencia a Fondo: $name"
            ])) {
                throw new \Exception("Error registrando transferencia: " . json_encode($transModel->errors()));
            }

            // 3. Create Temp Account
            $newId = $accountModel->insert([
                'name' => $name,
                'balance' => $amount,
                'type' => 'temporary',
                'status' => 'active',
                'parent_account_id' => $sourceId
            ]);

            if (!$newId) {
                throw new \Exception("Error creando fondo: " . json_encode($accountModel->errors()));
            }

            // 4. Create Initial Deposit Transaction
            if (!$transModel->insert([
                'account_id' => $newId,
                'category_id' => $catId,
                'amount' => $amount,
                'amount_usd' => 0,
                'exchange_rate' => 0,
                'type' => 'income',
                'owner' => 'System',
                'description' => "Fondo Inicial desde: " . $source['name']
            ])) {
                 throw new \Exception("Error registrando depósito inicial: " . json_encode($transModel->errors()));
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception("Error en transacción de base de datos");
            }

            return $this->response->setJSON(['status' => 'success']);

        } catch (\Exception $e) {
            log_message('error', $e->getMessage()); // Log error for server admin
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function closeTemporary($id)
    {
        $accountModel = new AccountModel();
        $transModel = new \App\Models\TransactionModel();
        $categoryModel = new \App\Models\CategoryModel();
        $db = \Config\Database::connect();

        $db->transStart();

        try {
            $account = $accountModel->find($id);
            if (!$account) throw new \Exception("Cuenta no existe");
            if ($account['status'] !== 'active') throw new \Exception("Cuenta ya cerrada");
            
            $parentId = $account['parent_account_id'];
            $remaining = $account['balance'];

            // Get valid category
            $defaultCat = $categoryModel->first();
            $catId = $defaultCat ? $defaultCat['id'] : 1;

            // 1. Return Funds to Source (if any)
            if ($remaining > 0 && $parentId) {
                $parent = $accountModel->find($parentId);
                if ($parent) {
                    $accountModel->update($parentId, ['balance' => $parent['balance'] + $remaining]);
                    
                    $transModel->insert([
                        'account_id' => $parentId,
                        'category_id' => $catId,
                        'type' => 'income',
                        'amount' => $remaining,
                        'amount_usd' => 0,
                        'exchange_rate' => 0,
                        'owner' => 'System',
                        'description' => "Devolución de Fondo: " . $account['name']
                    ]);
                }
            }

            // 2. Zero out Temp Account Record (Closing Entry)
            if ($remaining > 0) {
                 $transModel->insert([
                    'account_id' => $id,
                    'category_id' => $catId,
                    'type' => 'expense', // Withdrawal
                    'amount' => $remaining,
                    'amount_usd' => 0,
                    'exchange_rate' => 0,
                    'owner' => 'System',
                    'description' => "Cierre de Cuenta (Devolución)"
                ]);
            }

            // 3. Close Account
            $accountModel->update($id, ['balance' => 0, 'status' => 'closed']);

            $db->transComplete();
            return $this->response->setJSON(['status' => 'success']);

        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function transfer()
    {
        $json = $this->request->getJSON();
        $sourceId = $json->source_id;
        $destId = $json->dest_id;
        $categoryId = $json->category_id ?? null;
        $amount = $json->amount;
        $note = $json->note ?? '';

        if (!$sourceId || !$destId || !$amount || $amount <= 0 || !$categoryId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Datos incompletos: Faltan cuentas, monto o categoría']);
        }

        if ($sourceId == $destId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Cuenta origen y destino deben ser diferentes']);
        }

        $accountModel = new AccountModel();
        $transModel = new \App\Models\TransactionModel();
        $db = \Config\Database::connect();

        $db->transStart();

        try {
            $source = $accountModel->find($sourceId);
            $dest = $accountModel->find($destId);

            if (!$source || !$dest) {
                throw new \Exception("Una de las cuentas no existe");
            }

            // Currency Check
            if (($source['currency'] ?? 'Bs') !== ($dest['currency'] ?? 'Bs')) {
                 throw new \Exception("Las cuentas deben tener la misma moneda");
            }

            if ($source['balance'] < $amount) {
                throw new \Exception("Saldo insuficiente en cuenta origen");
            }

            // 1. Deduct from Source
            $accountModel->update($sourceId, ['balance' => $source['balance'] - $amount]);

            // 2. Add to Dest
            $accountModel->update($destId, ['balance' => $dest['balance'] + $amount]);

            // 3. Record Out (Source)
            $transModel->insert([
                'account_id' => $sourceId,
                'category_id' => $categoryId,
                'amount' => $amount,
                'amount_usd' => 0, // Simplified for same-currency
                'exchange_rate' => 0,
                'type' => 'transfer_out',
                'owner' => 'System',
                'description' => "Transferencia a " . $dest['name'] . ($note ? ": $note" : "")
            ]);

            // 4. Record In (Dest)
            // 4. Update Dest Transaction (audit log injected below)
            $transModel->insert([
                'account_id' => $destId,
                'category_id' => $categoryId,
                'amount' => $amount,
                'amount_usd' => 0,
                'exchange_rate' => 0,
                'type' => 'transfer_in',
                'owner' => 'System',
                'description' => "Transferencia desde " . $source['name'] . ($note ? ": $note" : "")
            ]);

            // AUDIT LOG
            AuditLogModel::log('accounts', 'transfer', $sourceId, null, null, [
                'source' => ['id' => $sourceId, 'delta' => -$amount],
                'dest' => ['id' => $destId, 'delta' => +$amount]
            ], "Transferencia: {$source['name']} -> {$dest['name']}");

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception("Error en la transacción de base de datos");
            }

            return $this->response->setJSON(['status' => 'success']);

        } catch (\Throwable $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function add()
    {
        $json = $this->request->getJSON();
        $model = new AccountModel();
        
        try {
            // Validate and sanitize input
            $name = $json->name ?? '';
            if (empty($name)) {
                throw new \Exception("El nombre de la cuenta es obligatorio");
            }
            
            // Ensure balance is never null and is numeric
            $balance = $json->balance ?? 0;
            if (!is_numeric($balance) || $balance === '') {
                $balance = 0;
            }

            $model->insert([
                'name' => $name, 
                'balance' => $balance, 
                'type' => 'general', 
                'status' => 'active',
                'currency' => $json->currency ?? 'Bs',
                'tenure_type' => $json->tenure_type ?? 'none'
            ]);

            // AUDIT LOG
            $newId = $model->insertID();
            AuditLogModel::log('accounts', 'create', $newId, null, $json, ['initial_balance' => $balance], "Creación de cuenta: $name");

            if ($model->errors()) {
                throw new \Exception(json_encode($model->errors()));
            }

            return $this->response->setJSON(['status' => 'success']);
        } catch (\Throwable $e) {
            log_message('error', '[AccountCreate] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // ... keep delete and updateBalance
    public function delete($id)
    {
        $model = new AccountModel();
        $transModel = new \App\Models\TransactionModel();
        
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $account = $model->find($id);
            if (!$account) throw new \Exception("Cuenta no encontrada");

            // Snapshot for Audit
            $accountSnapshot = $account;

            // SAFEGUARD: If it's a temporary account with money, return it to source first
            if ($account['type'] === 'temporary' && $account['balance'] > 0 && !empty($account['parent_account_id'])) {
                $parentId = $account['parent_account_id'];
                $parent = $model->find($parentId);
                
                if ($parent) {
                    // Update parent balance
                    $model->update($parentId, ['balance' => $parent['balance'] + $account['balance']]);

                    // Record refund transaction
                    $transModel->insert([
                        'account_id' => $parentId,
                        'category_id' => 1, // Fallback category
                        'type' => 'income',
                        'amount' => $account['balance'],
                        'amount_usd' => 0,
                        'exchange_rate' => 0,
                        'owner' => 'System',
                        'description' => "Devolución por Eliminación: " . $account['name']
                    ]);
                }
            }

            // 1. Delete transactions where this account is the owner
            $transModel->where('account_id', $id)->delete();
            
            // 2. Check for child temporary accounts
            $children = $model->where('parent_account_id', $id)->findAll();
            foreach($children as $child) {
                // Delete child transactions
                $transModel->where('account_id', $child['id'])->delete();
                // Delete child account
                $model->delete($child['id']);
            }

            // 3. Delete the account itself
            if (!$model->delete($id)) {
                throw new \Exception("Error eliminando cuenta.");
            }
            
            // AUDIT LOG
            AuditLogModel::log('accounts', 'delete', $id, $accountSnapshot, null, ['deleted_balance' => $accountSnapshot['balance']], "Eliminación de cuenta: {$accountSnapshot['name']}");

            $db->transComplete();
            return $this->response->setJSON(['status' => 'success']);

        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function updateBalance()
    {
        $json = $this->request->getJSON();
        $model = new AccountModel();
        $model->update($json->id, ['balance' => $json->balance]);
        
        // AUDIT LOG
        AuditLogModel::log('accounts', 'update_balance', $json->id, null, ['balance' => $json->balance], null, "Ajuste manual de balance");

        return $this->response->setJSON(['status' => 'success']);
    }
}
