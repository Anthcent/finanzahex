<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\TransactionModel;
use App\Models\TransactionItemModel;
use App\Models\AccountModel;
use App\Models\CategoryModel;
use App\Models\AuditLogModel;

class TransactionController extends BaseController
{
    public function index()
    {
        $accountModel = new AccountModel();
        $categoryModel = new CategoryModel();

        $data = [
            'accounts' => $accountModel->where('status', 'active')->findAll(),
            'categories' => $categoryModel->findAll(),
            'inventory_items' => (new \App\Models\InventoryItemModel())->findAll(), // Load items for "Negocio" mode
        ];

        return view('tracker/index', $data);
    }

    public function save()
    {
        $json = $this->request->getJSON();
        
        if (!$json) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No data']);
        }

        $transModel = new TransactionModel();
        $itemModel = new TransactionItemModel();
        $accountModel = new AccountModel();

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $amount = $json->amount ?? 0;
            $amountUsd = $json->amount_usd ?? 0;
            $exchangeRate = $json->exchange_rate ?? 1;
            $type = $json->type;
            $accountId = $json->account_id; // Source Account

            if (empty($accountId)) {
                throw new \Exception('Debe seleccionar una cuenta origen');
            }

            $sourceAccount = $accountModel->find($accountId);
            if (!$sourceAccount) {
                 throw new \Exception('La cuenta origen no existe');
            }

            // --- LOGIC FOR CURRENCY EXCHANGE (Bs -> USD) ---
            if ($type === 'exchange') {
                $destAccountId = $json->destination_account_id ?? null;
                if (!$destAccountId) throw new \Exception('Debe seleccionar una cuenta destino para las divisas');
                
                $destAccount = $accountModel->find($destAccountId);
                if (!$destAccount) throw new \Exception('La cuenta destino no existe');
                
                // 1. Debit Source (Bs)
                if ($sourceAccount['balance'] < $amount) throw new \Exception('Saldo insuficiente en cuenta origen (Bs)');
                $accountModel->update($accountId, ['balance' => $sourceAccount['balance'] - $amount]);

                // 2. Credit Destination (USD)
                // Note: Destination account balance represents USD if it's a USD account. 
                // We assume amount_usd is the calculated amount to add.
                $accountModel->update($destAccountId, ['balance' => $destAccount['balance'] + $amountUsd]);

                // 3. Record Transactions
                // Outgoing from Source
                $transId = $transModel->insert([
                    'account_id' => $accountId,
                    'category_id' => $json->category_id ?? $this->getValidCategoryId(),
                    'amount' => $amount,
                    'amount_usd' => $amountUsd,
                    'exchange_rate' => $exchangeRate,
                    'type' => 'exchange_out', // New internal type
                    'owner' => $json->owner ?? 'Business',
                    'description' => "Compra de Divisas: $amountUsd USD -> " . $destAccount['name'],
                ]);

                // Incoming to Dest
                $transModel->insert([
                    'account_id' => $destAccountId,
                    'category_id' => $json->category_id ?? $this->getValidCategoryId(),
                    'amount' => 0, // It's USD balance update effectively, but we record 0 Bs impact or maybe implicit Bs value?
                    // Better to record 0 Bs to avoid double counting expenses/income if we agg by Bs. 
                    // But we should track the value. Let's stick to simple ledger for now.
                    // Actually, for stats, we might want to track this.
                    'amount_usd' => $amountUsd,
                    'exchange_rate' => $exchangeRate,
                    'type' => 'exchange_in', 
                    'owner' => $json->owner ?? 'Business',
                    'description' => "Recepción de Divisas ($amountUsd USD) desde " . $sourceAccount['name'],
                ]);
            }
            // --- LOGIC FOR MOVEMENT (USD -> USD) ---
            elseif ($type === 'movement') {
                $destAccountId = $json->destination_account_id ?? null;
                if (!$destAccountId) throw new \Exception('Debe seleccionar una cuenta destino');

                $destAccount = $accountModel->find($destAccountId);
                
                // 1. Debit Source (USD)
                if ($sourceAccount['balance'] < $amountUsd) throw new \Exception('Saldo insuficiente en cuenta origen (USD)');
                $accountModel->update($accountId, ['balance' => $sourceAccount['balance'] - $amountUsd]);

                // 2. Credit Dest (USD)
                $accountModel->update($destAccountId, ['balance' => $destAccount['balance'] + $amountUsd]);

                // 3. Record Transaction
                 $transId = $transModel->insert([
                    'account_id' => $accountId,
                    'category_id' => $json->category_id ?? $this->getValidCategoryId(),
                    'amount' => 0,
                    'amount_usd' => $amountUsd,
                    'exchange_rate' => $exchangeRate,
                    'type' => 'transfer_out',
                    'owner' => $json->owner ?? 'Business',
                    'description' => "Retiro/Movimiento: $amountUsd USD -> " . $destAccount['name'],
                ]);

                 $transModel->insert([
                    'account_id' => $destAccountId,
                    'category_id' => $json->category_id ?? $this->getValidCategoryId(),
                    'amount' => 0,
                    'amount_usd' => $amountUsd,
                    'exchange_rate' => $exchangeRate,
                    'type' => 'transfer_in',
                    'owner' => $json->owner ?? 'Business',
                    'description' => "Depósito/Movimiento: $amountUsd USD desde " . $sourceAccount['name'],
                ]);
            }
            // --- STANDARD TRANSACTION ---
            else {
                // Check if it's an Inventory Purchase (Business Mode)
                $inventoryItemId = $json->inventory_item_id ?? null;
                $quantity = $json->quantity ?? 1;

                if ($inventoryItemId && $json->owner === 'Negocio' && $type === 'expense') {
                    $invItemModel = new \App\Models\InventoryItemModel();
                    $invMovModel = new \App\Models\InventoryMovementModel();
                    
                    $item = $invItemModel->find($inventoryItemId);
                    if ($item) {
                        // 1. Update Stock
                        $invItemModel->update($inventoryItemId, [
                            'stock' => $item['stock'] + $quantity,
                            'cost' => $amount / $quantity // Update cost average or last cost? Let's simply update 'cost' for now as "last cost"
                        ]);

                        // 2. Log Movement
                        $invMovModel->insert([
                            'item_id' => $inventoryItemId,
                            'type' => 'purchase',
                            'quantity' => $quantity,
                            'date' => date('Y-m-d H:i:s'),
                            'reference' => 'Compra Rápida'
                        ]);

                        // 3. Auto-Description
                        if (empty($json->description)) {
                            $json->description = "Compra Stock: {$item['name']} x{$quantity}";
                        }
                    }
                }

                // Save Main Transaction
                $transId = $transModel->insert([
                    'account_id' => $accountId,
                    'category_id' => $json->category_id ?? $this->getValidCategoryId(),
                    'amount' => $amount,
                    'amount_usd' => $amountUsd,
                    'exchange_rate' => $exchangeRate,
                    'type' => $type,
                    'owner' => $json->owner ?? 'Business',
                    'description' => $json->description ?? '',
                    'created_at' => !empty($json->created_at) ? $json->created_at : date('Y-m-d H:i:s'), // Venezuela timezone (set in App.php)
                ]);

                if (!$transId) {
                    throw new \Exception('Error al guardar la transacción');
                }

                // Save Items (if valid)
                if (!empty($json->items) && is_array($json->items)) {
                    foreach ($json->items as $item) {
                        $itemModel->insert([
                            'transaction_id' => $transId,
                            'name' => $item->name,
                            'description' => $item->description ?? null,
                            'quantity' => $item->quantity ?? 1,
                            'price' => $item->price,
                            'price_usd' => $item->price_usd ?? 0,
                        ]);
                    }
                }

                // Update Balance
                $newBalance = $sourceAccount['balance'];
                if ($type === 'income' || $type === 'return') {
                    $newBalance += $amount; 
                } elseif ($type === 'expense' || $type === 'savings') {
                    $newBalance -= $amount;
                }
                $accountModel->update($accountId, ['balance' => $newBalance]);

                // AUDIT LOG
                $impact = [
                    'account_id' => $accountId,
                    'balance_before' => $sourceAccount['balance'],
                    'balance_after' => $newBalance,
                    'delta' => ($newBalance - $sourceAccount['balance'])
                ];
                AuditLogModel::log('transactions', 'create', $transId, null, $json, $impact, "Creación de transacción: $type");
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                 throw new \Exception('Error al confirmar la transacción');
            }

            return $this->response->setJSON(['status' => 'success', 'id' => $transId ?? 0]);

        } catch (\Exception $e) {
            $db->transRollback();
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function update($id)
    {
        $json = $this->request->getJSON();
        $transModel = new TransactionModel();
        $accModel = new AccountModel();
        
        $transaction = $transModel->find($id);
        if (!$transaction) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Transacción no encontrada']);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Restrictions: Can only edit amount for simple types
            $isComplex = in_array($transaction['type'], ['exchange_out', 'exchange_in', 'transfer_out', 'transfer_in']);
            
            // 1. Update Balance if Amount Changed (and allowed)
            if (isset($json->amount) && !$isComplex) {
                $oldAmount = $transaction['amount'];
                $newAmount = $json->amount;

                if ($oldAmount != $newAmount) {
                    $account = $accModel->find($transaction['account_id']);
                    
                    // Revert old effect
                    $revertedBalance = $account['balance'];
                    if ($transaction['type'] === 'income') $revertedBalance -= $oldAmount;
                    elseif ($transaction['type'] === 'expense' || $transaction['type'] === 'savings') $revertedBalance += $oldAmount;
                    
                    // Apply new effect
                    $newBalance = $revertedBalance;
                    if ($transaction['type'] === 'income') $newBalance += $newAmount;
                    elseif ($transaction['type'] === 'expense' || $transaction['type'] === 'savings') $newBalance -= $newAmount;
                    
                    $accModel->update($transaction['account_id'], ['balance' => $newBalance]);
                }
            }

            // 2. Prepare Data
            // Normalize created_at format (datetime-local sends T separator)
            $createdAt = $json->created_at ?? $transaction['created_at'];
            if ($createdAt) {
                $createdAt = str_replace('T', ' ', $createdAt);
                // Ensure seconds are included
                if (strlen($createdAt) === 16) $createdAt .= ':00';
            } else {
                $createdAt = date('Y-m-d H:i:s'); // Fallback to current Venezuela time
            }

            $updateData = [
                'description' => $json->description ?? $transaction['description'],
                'created_at'  => $createdAt,
                'updated_at'  => date('Y-m-d H:i:s'),
                'category_id' => $json->category_id ?? $transaction['category_id']
            ];

            if (!$isComplex && isset($json->amount)) {
                $updateData['amount'] = $json->amount;
                
                // Recalculate USD amount based on stored exchange_rate
                if ($transaction['exchange_rate'] > 0) {
                    $updateData['amount_usd'] = $json->amount / $transaction['exchange_rate'];
                }
            }

            $transModel->update($id, $updateData);
            
            // AUDIT LOG
            $updatedTrans = $transModel->find($id);
            $auditImpact = [];
            if (isset($newBalance)) {
                $auditImpact = [
                    'account_id' => $transaction['account_id'],
                    'balance_before' => $account['balance'],
                    'balance_after' => $newBalance,
                    'reason' => 'Monto actualizado'
                ];
            }
            AuditLogModel::log('transactions', 'update', $id, $transaction, $updatedTrans, $auditImpact, 'Actualización de transacción');
            
            $db->transComplete();

            if ($db->transStatus() === false) {
                 throw new \Exception('Error al actualizar');
            }

            return $this->response->setJSON(['status' => 'success']);

        } catch (\Exception $e) {
            $db->transRollback();
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function stats()
    {
        $model = new TransactionModel();
        return $this->response->setJSON($model->getStats());
    }
    private function getValidCategoryId()
    {
        $catModel = new CategoryModel();
        $cat = $catModel->find(1);
        if ($cat) return 1;
        
        $first = $catModel->first();
        if ($first) return $first['id'];
        
        return $catModel->insert(['name' => 'General', 'type' => 'expense']);
    }
}
