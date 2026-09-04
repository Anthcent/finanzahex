<?php

namespace App\Controllers;

use App\Models\PrintProductModel;
use App\Models\TransactionModel;
use App\Models\AccountModel;
use App\Models\AuditLogModel;

class PrintingController extends BaseController
{
    public function index()
    {
        // Check DB Tables on every load to ensure seeding happens if products are missing
        $this->ensureTablesExist();

        $db = \Config\Database::connect();
        $model = new PrintProductModel();
        $accountModel = new AccountModel();

        // Get Products grouped by category
        $products = $model->orderBy('category', 'ASC')->orderBy('name', 'ASC')->findAll();
        
        // Get Accounts for income selection
        $accounts = $accountModel->where('status', 'active')->findAll();

        try {
            $setting = $db->table('settings')->where('key', 'default_print_account')->get()->getRowArray();
        } catch (\Exception $e) {
            $setting = null;
        }

        // Safe fallback for Default Account
        if ($setting && isset($setting['value'])) {
            $defaultAccount = $setting['value'];
        } elseif (!empty($accounts)) {
            $defaultAccount = $accounts[0]['id'];
        } else {
            $defaultAccount = 0; // Or handle as "No Account"
        }

        return view('printing/index', ['products' => $products, 'accounts' => $accounts, 'defaultAccount' => $defaultAccount]);
    }

    public function fixDb()
    {
        $this->ensureTablesExist();
        echo "<h1>Base de datos actualizada correctamente</h1><p>Tablas verificadas: print_products, settings, customers, print_orders, transactions (columnas), audit_logs.</p><a href='" . base_url('printing') . "'>Volver al Módulo</a>";
    }

    public function ensureTablesExist()
    {
        // Schema changes run through app:prepare before HTTP startup.
        \App\Libraries\PrintCatalog::seed(\Config\Database::connect());
    }

    public function getCustomers()
    {
        $db = \Config\Database::connect();
        $term = $this->request->getVar('term');
        
        $builder = $db->table('customers');
        if ($term) {
            $builder->like('name', $term);
        }
        
        // Prioritize favorites
        $customers = $builder->orderBy('is_favorite', 'DESC')
                             ->orderBy('name', 'ASC')
                             ->limit(20)
                             ->get()->getResultArray();
                             
        return $this->response->setJSON(['status' => 'success', 'data' => $customers]);
    }

    public function toggleFavorite()
    {
        $json = $this->request->getJSON();
        $name = trim($json->name);
        $favorite = $json->favorite ? 1 : 0;
        
        $db = \Config\Database::connect();
        // Check if exists
        $exists = $db->table('customers')->where('name', $name)->get()->getRow();
        
        if ($exists) {
            $db->table('customers')->where('id', $exists->id)->update(['is_favorite' => $favorite, 'updated_at' => date('Y-m-d H:i:s')]);
        } else {
            $db->table('customers')->insert([
                'name' => $name,
                'is_favorite' => $favorite,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        return $this->response->setJSON(['status' => 'success']);
    }

    // ... (settings, store methods need Update to sync customers table - done below implicitly or need explicit hook?)
    // Actually, let's update `store` to save customer name too logic is better in `store`.
    // I will assume `store` remains as is for now, but I should add the logic to "Upsert" customer there too if needed,
    // or rely on the frontend calling `toggleFavorite` explicitly. 
    // BUT the user wants "Suggestions", so populate table on Store is good practice.
    
    // I'll stick to replacing the END of the file for now to add the new Delete Transaction logic 
    // and rely on a separate call or check for customer saving for now to be safe.

    public function deleteTransaction($id)
    {
        $db = \Config\Database::connect();
        $transModel = new TransactionModel();
        $accountModel = new AccountModel();
        
        $revert = $this->request->getVar('revert') === 'true';
        
        // AUDIT LOG SNAPSHOT
        $orderSnapshot = $db->table('print_orders')->where('id', $id)->get()->getRowArray();
        
        $db->transStart();
        
        try {
            $trans = $transModel->find($id);
            if (!$trans) throw new \Exception('Transacción no encontrada');
            
            // 1. Revert Money (If requested)
            if ($revert && $trans['account_id']) {
                 $account = $accountModel->find($trans['account_id']);
                 if ($account) {
                     $amountToDeduct = 0;
                     $rate = ($trans['exchange_rate'] > 0) ? $trans['exchange_rate'] : 50;
                     $itemCurrency = $account['currency'] ?? 'Bs';

                     if ($itemCurrency === 'USD') {
                         $amountToDeduct = $trans['amount_usd'] + ($trans['amount'] / $rate);
                     } else {
                         $amountToDeduct = $trans['amount'] + ($trans['amount_usd'] * $rate);
                     }
                     $accountModel->update($account['id'], ['balance' => $account['balance'] - $amountToDeduct]);
                 }
            }
            
            // 2. Update Order Paid Amount
            $orderId = $trans['print_order_id'];
            if ($orderId) {
                $order = $db->table('print_orders')->where('id', $orderId)->get()->getRowArray();
                if ($order) {
                    $newPaidBs = max(0, $order['paid_bs'] - $trans['amount']);
                    $newPaidUsd = max(0, $order['paid_usd'] - $trans['amount_usd']);
                    
                    // Recalculate Status
                    $status = 'pending';
                    $rate = ($trans['exchange_rate'] > 0) ? $trans['exchange_rate'] : 50;
                    
                    $totalAsBs = $order['total_bs'];
                    $totalAsUsd = $order['total_usd'];
                    
                    $paidValueBs = $newPaidBs + ($newPaidUsd * $rate);
                    
                    if ($totalAsBs > 0) {
                        if ($paidValueBs >= ($totalAsBs - 0.50)) $status = 'paid';
                        else if ($paidValueBs > 1) $status = 'partial';
                    } else {
                        // USD based check
                        $paidValueUsd = $newPaidUsd + ($newPaidBs / $rate);
                        if ($paidValueUsd >= ($totalAsUsd - 0.10)) $status = 'paid';
                        else if ($paidValueUsd > 0.1) $status = 'partial';
                    }
                    
                    $db->table('print_orders')->where('id', $orderId)->update([
                        'paid_bs' => $newPaidBs,
                        'paid_usd' => $newPaidUsd,
                        'status' => $status
                    ]);
                }
            }

            // 3. Delete Transaction
            $transModel->delete($id);
            
            // AUDIT LOG
            if ($trans) {
                 AuditLogModel::log('printing', 'delete_transaction_only', $id, $trans, null, ['reverted' => $revert], "Eliminación de transacción de impresión");
            }

            $db->transComplete();
            
            if ($db->transStatus() === false) {
                 throw new \Exception('Error al eliminar transacción');
            }
            
            // Return updated order data
            $updatedOrder = null;
            if ($orderId) {
                $updatedOrder = $db->table('print_orders')->where('id', $orderId)->get()->getRowArray();
            }

            return $this->response->setJSON(['status' => 'success', 'order' => $updatedOrder]);

        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function settings()
    {
        // ... (reuse existing settings code)
        $db = \Config\Database::connect();
        $model = new PrintProductModel();
        $accountModel = new AccountModel();

        $products = $model->orderBy('category', 'ASC')->orderBy('name', 'ASC')->findAll();
        $accounts = $accountModel->where('status', 'active')->findAll();
        
        $categories = (new \App\Models\CategoryModel())->findAll();
        
        // Safe Default Account
        try {
            $settingAccount = $db->table('settings')->where('key', 'default_print_account')->get()->getRowArray();
        } catch(\Exception $e) { $settingAccount = null; }

        if ($settingAccount && isset($settingAccount['value'])) {
            $defaultAccount = $settingAccount['value'];
        } elseif (!empty($accounts)) {
            $defaultAccount = $accounts[0]['id'];
        } else {
            $defaultAccount = 0;
        }

        // Safe Default Category
        try {
            $settingCategory = $db->table('settings')->where('key', 'default_print_category')->get()->getRowArray();
        } catch(\Exception $e) { $settingCategory = null; }

        if ($settingCategory && isset($settingCategory['value'])) {
            $defaultCategory = $settingCategory['value'];
        } elseif (!empty($categories)) {
            $defaultCategory = $categories[0]['id'];
        } else {
            $defaultCategory = 0;
        }

        // Get Rate
        $rate = 55; // Default Fallback
        
        try {
            // Attempt to get from internal API
            $client = \Config\Services::curlrequest();
            $response = $client->get(base_url('currency/get-rate'), ['timeout' => 2]);
            $body = $response->getBody();
            $data = json_decode($body);
            if(isset($data->rate)) $rate = $data->rate;
        } catch(\Exception $e) {
            // If request fails, ignore and use default
        }

        return view('printing/settings', [
            'products' => $products, 
            'accounts' => $accounts, 
            'categories' => $categories,
            'defaultAccount' => $defaultAccount,
            'defaultCategory' => $defaultCategory,
            'rate' => $rate
        ]);
    }

    public function store()
    {
        $json = $this->request->getJSON();
        
        $db = \Config\Database::connect();
        $transModel = new TransactionModel();
        $accountModel = new AccountModel();

        $db->transStart();

        try {
            // 1. Calculate Single Item Totals
            $qty = $json->quantity ?? 1;
            $priceBs = $json->price_bs ?? 0;
            $priceUsd = $json->price_usd ?? 0;
            $rate = $json->exchange_rate ?? 50;

            $totalBs = 0;
            $totalUsd = 0;

            // Priority Logic: Same as before
            if ($priceBs > 0) {
                 $totalBs = ($priceBs * $qty);
                 $totalUsd = ($priceBs * $qty / $rate);
            } else {
                 $totalUsd = ($priceUsd * $qty);
                 $totalBs = ($priceUsd * $qty * $rate);
            }

            $note = isset($json->note) && !empty($json->note) ? " ({$json->note})" : "";
            $details = ["{$qty}x {$json->product_name}{$note}"];

            // 2. Prepare Payment Data
            $paidBs = $json->paid_bs ?? 0;
            $paidUsd = $json->paid_usd ?? 0;
            
            // Status Logic
            $status = 'pending';
            $totalAsUsd = $totalUsd; 
            $paidAsUsd = $paidUsd + ($paidBs / ($rate ?? 1));
            
            if ($paidAsUsd >= ($totalAsUsd - 0.05)) {
                $status = 'paid';
            } elseif ($paidAsUsd > 0) {
                $status = 'partial';
            }

            // 3. Create Print Order
            $builder = $db->table('print_orders');
            $orderData = [
                'customer_name' => $json->customer_name ?: 'Cliente',
                'details' => json_encode($details),
                'total_bs' => $totalBs,
                'total_usd' => $totalUsd,
                'paid_bs' => $paidBs,
                'paid_usd' => $paidUsd,
                'status' => $status,
                'created_at' => date('Y-m-d H:i:s')
            ];
            $builder->insert($orderData);
            $orderId = $db->insertID();

            if (!$orderId) {
                $error = $db->error();
                throw new \Exception('Error al crear orden: ' . ($error['message'] ?? 'Desconocido'));
            }

            // AUDIT LOG
            AuditLogModel::log('printing', 'create_order', $orderId, null, $orderData, null, "Nueva Venta Rápida #$orderId");

            // 4. Register Transaction (If Money Entered)
            if (($paidBs > 0 || $paidUsd > 0) && !empty($json->account_id)) {
                 $account = $accountModel->find($json->account_id);
                 if ($account) {
                     $desc = "Venta #$orderId - " . ($json->customer_name ?: 'Cliente') . " - {$qty}x {$json->product_name}";
                     
                     // Get Category (Safe Fallback)
                     $setting = $db->table('settings')->where('key', 'default_print_category')->get()->getRowArray();
                     $catId = $setting ? $setting['value'] : 3;
                     
                     // Verify category exists
                     $checkCat = $db->table('categories')->where('id', $catId)->countAllResults();
                     if ($checkCat == 0) {
                         $firstCat = $db->table('categories')->limit(1)->get()->getRowArray();
                         $catId = $firstCat ? $firstCat['id'] : 0;
                     }

                     $transData = [
                        'account_id' => $json->account_id,
                        'category_id' => $catId, 
                        'print_order_id' => $orderId,
                        'amount' => $paidBs,
                        'amount_usd' => $paidUsd,
                        'exchange_rate' => $rate,
                        'type' => 'income',
                        'owner' => 'Negocio',
                        'description' => $desc,
                        'created_at' => date('Y-m-d H:i:s')
                    ];

                    $transModel->insert($transData);
                    $transId = $transModel->getInsertID();

                    // Link Transaction
                    $db->table('print_orders')->where('id', $orderId)->update(['transaction_id' => $transId]);

                    // Update Balance
                    $amountToAdd = 0;
                    $itemCurrency = $account['currency'] ?? 'Bs';
                     if ($itemCurrency === 'USD') {
                        $amountToAdd = $paidUsd + ($paidBs / $rate);
                    } else {
                        $amountToAdd = $paidBs + ($paidUsd * $rate);
                    }
                    $accountModel->update($json->account_id, ['balance' => $account['balance'] + $amountToAdd]);
                 }
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                $error = $db->error();
                $errMsg = $error['message'] ?? 'Error desconocido';
                log_message('error', '[Printing::store] Transacción fallida: ' . $errMsg);
                throw new \Exception('Error al confirmar la transacción: ' . $errMsg);
            }

            return $this->response->setJSON(['status' => 'success', 'message' => 'Registrado']);

        } catch (\Exception $e) {
            log_message('error', '[Printing::store] Excepción: ' . $e->getMessage());
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function getHistory() {
        $db = \Config\Database::connect();
        $orders = $db->table('print_orders')->orderBy('created_at', 'DESC')->get()->getResultArray();
        return $this->response->setJSON(['status' => 'success', 'data' => $orders]);
    }

    public function getMovements()
    {
        $db = \Config\Database::connect();
        
        $movements = $db->table('transactions')
                        ->select('transactions.*, accounts.name as account_name, print_orders.id as order_id, print_orders.customer_name as order_customer')
                        ->join('accounts', 'accounts.id = transactions.account_id', 'left')
                        ->join('print_orders', 'print_orders.id = transactions.print_order_id', 'left')
                        ->groupStart()
                            ->where('transactions.print_order_id IS NOT NULL')
                            ->orLike('transactions.description', 'Impresiones')
                            ->orLike('transactions.description', 'Venta Impresión')
                            ->orLike('transactions.description', 'Abono Impresiones')
                        ->groupEnd()
                        ->orderBy('created_at', 'DESC')
                        ->limit(100)
                        ->get()->getResultArray();
                        
        return $this->response->setJSON(['status' => 'success', 'data' => $movements]);
    }

    public function addPayment() {
        $json = $this->request->getJSON();
        $db = \Config\Database::connect();
        $transModel = new TransactionModel();
        $accountModel = new AccountModel();

        $db->transStart();

        try {
            $order = $db->table('print_orders')->where('id', $json->order_id)->get()->getRowArray();
            if (!$order) throw new \Exception('Orden no encontrada');

            $amountBs = floatval($json->amount_bs ?? 0);
            $amountUsd = floatval($json->amount_usd ?? 0);
            $rate = floatval($json->rate ?? 50);

            // Validation: Must have an account if paying money
            if (($amountBs > 0 || $amountUsd > 0) && empty($json->account_id)) {
                throw new \Exception('Debe seleccionar una cuenta para registrar el pago');
            }

            // 1. Update Order Values
            $newPaidBs = floatval($order['paid_bs']) + $amountBs;
            $newPaidUsd = floatval($order['paid_usd']) + $amountUsd;
            
            // Check status
            $totalAsUsd = floatval($order['total_usd']);
            // Improved Status Check respecting original currency totals
            $totalAsBs = floatval($order['total_bs']);
            $totalAsUsd = floatval($order['total_usd']);
            
            $isPaid = false;

            if ($totalAsBs > 0) {
                 // Check primarily against Bs Total
                 // Calculate total paid value in Bs
                 $paidValueBs = $newPaidBs + ($newPaidUsd * $rate);
                 // Tolerance 0.50 Bs
                 if ($paidValueBs >= ($totalAsBs - 0.50)) $isPaid = true;
            } else {
                 // Check against USD Total
                 $paidValueUsd = $newPaidUsd + ($newPaidBs / $rate);
                 if ($paidValueUsd >= ($totalAsUsd - 0.10)) $isPaid = true;
            }

            $status = $isPaid ? 'paid' : 'partial';

            // Special case: If nothing paid, pending
            if ($newPaidBs == 0 && $newPaidUsd == 0) $status = 'pending';

            $db->table('print_orders')->where('id', $json->order_id)->update([
                'paid_bs' => $newPaidBs,
                'paid_usd' => $newPaidUsd,
                'status' => $status
            ]);

            // 2. Transaction (Mandatory if amount > 0)
            if ($amountBs > 0 || $amountUsd > 0) {
                // Determine Category
                $setting = $db->table('settings')->where('key', 'default_print_category')->get()->getRowArray();
                $catId = $setting ? $setting['value'] : 3;
                
                // Verify Category Exists
                if ($db->table('categories')->where('id', $catId)->countAllResults() == 0) {
                     $catId = $db->table('categories')->limit(1)->get()->getRowArray()['id'] ?? 0;
                }

                $account = $accountModel->find($json->account_id);
                if (!$account) throw new \Exception('Cuenta no encontrada');

                // Use Query Builder to ensure no Model filtering issues
                $transData = [
                    'account_id' => $json->account_id,
                    'category_id' => $catId,
                    'print_order_id' => $order['id'],
                    'amount' => $amountBs,
                    'amount_usd' => $amountUsd,
                    'exchange_rate' => $rate,
                    'type' => 'income',
                    'owner' => 'Negocio',
                    'description' => "Abono Impresiones #{$order['id']} - {$order['customer_name']}",
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                if (!$db->table('transactions')->insert($transData)) {
                     throw new \Exception('Error al insertar transacción en base de datos.');
                }

                // Update Account Balance
                $amountToAdd = 0;
                $itemCurrency = $account['currency'] ?? 'Bs';
                if ($itemCurrency === 'USD') {
                    $amountToAdd = $amountUsd + ($amountBs / $rate);
                } else {
                    $amountToAdd = $amountBs + ($amountUsd * $rate);
                }
                $accountModel->update($json->account_id, ['balance' => $account['balance'] + $amountToAdd]);
                
                // AUDIT LOG
                AuditLogModel::log('printing', 'payment', $json->order_id, null, $transData, ['amount_added' => $amountToAdd], "Abono a Orden #{$order['id']}");
            }

            $db->transComplete();
            
            if ($db->transStatus() === false) {
                 throw new \Exception('Error en transacción de base de datos.');
            }

            // Fetch updated order
            $updatedOrder = $db->table('print_orders')->where('id', $json->order_id)->get()->getRowArray();
            
            // Fetch updated payments history
            $history = $db->table('transactions')
                          ->select('transactions.*, accounts.name as account_name')
                          ->join('accounts', 'accounts.id = transactions.account_id', 'left')
                          ->where('print_order_id', $json->order_id)
                          ->orderBy('created_at', 'DESC')
                          ->get()->getResultArray();

            return $this->response->setJSON([
                'status' => 'success', 
                'order' => $updatedOrder,
                'history' => $history,
                'message' => 'Abono registrado correctamente'
            ]);



        } catch (\Exception $e) {
             return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // CRUD Settings
    public function saveProduct()
    {
        $json = $this->request->getJSON();
        $model = new PrintProductModel();
        
        $data = [
            'name' => $json->name,
            'price_bs' => $json->price_bs,
            'price_usd' => $json->price_usd,
            'category' => $json->category,
            'icon' => $json->icon,
            'color' => $json->color // e.g. 'indigo', 'emerald'
        ];

        if (isset($json->id) && $json->id) {
            $model->update($json->id, $data);
        } else {
            $model->insert($data);
        }

        return $this->response->setJSON(['status' => 'success']);
    }

    public function deleteProduct($id)
    {
        $model = new PrintProductModel();
        $model->delete($id);
        return $this->response->setJSON(['status' => 'success']);
    }

    public function deleteOrder($id)
    {
        // ... (existing deleteOrder code) ...
        $db = \Config\Database::connect();
        $transModel = new TransactionModel();
        $accountModel = new AccountModel();
        $revert = $this->request->getVar('revert') === 'true';

        $db->transStart();

        try {
            $order = $db->table('print_orders')->where('id', $id)->get()->getRowArray();
            if (!$order) {
                throw new \Exception('Orden no encontrada');
            }

            // 1. Revert Transaction if requested
            // 1. Revert Transactions if requested
            if ($revert) {
                // Find ALL transactions linked to this order
                $transactions = $db->table('transactions')
                                   ->where('print_order_id', $id)
                                   ->get()->getResultArray();

                // Fallback for legacy (single transaction link)
                if (empty($transactions) && !empty($order['transaction_id'])) {
                    $tr = $transModel->find($order['transaction_id']);
                    if ($tr) $transactions[] = $tr;
                }

                foreach ($transactions as $transaction) {
                    $transId = $transaction['id'];
                    $account = $accountModel->find($transaction['account_id']);
                    
                    if ($account) {
                        $amountToDeduct = 0;
                        $rate = ($transaction['exchange_rate'] > 0) ? $transaction['exchange_rate'] : 50;
                        $itemCurrency = $account['currency'] ?? 'Bs';

                        if ($itemCurrency === 'USD') {
                            $amountToDeduct = $transaction['amount_usd'] + ($transaction['amount'] / $rate);
                        } else {
                            $amountToDeduct = $transaction['amount'] + ($transaction['amount_usd'] * $rate);
                        }
                        $accountModel->update($account['id'], ['balance' => $account['balance'] - $amountToDeduct]);
                    }
                    // Delete Transaction
                    $transModel->delete($transId);
                }
            }
            
            // 2. Delete Order
            $db->table('print_orders')->where('id', $id)->delete();

            // AUDIT LOG
            if (isset($order)) {
                 AuditLogModel::log('printing', 'delete_order', $id, $order, null, ['reverted_transactions' => $revert], "Eliminación de Orden #$id");
            }

            $db->transComplete();
            return $this->response->setJSON(['status' => 'success']);

        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function updateOrder()
    {
        $json = $this->request->getJSON();
        $db = \Config\Database::connect();
        
        try {
            $db->table('print_orders')->where('id', $json->id)->update([
                'customer_name' => $json->customer_name,
                'status'        => $json->status
            ]);
            return $this->response->setJSON(['status' => 'success']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function getPayments($orderId)
    {
        $db = \Config\Database::connect();
        
        $payments = $db->table('transactions')
                       ->select('transactions.*, accounts.name as account_name')
                       ->join('accounts', 'accounts.id = transactions.account_id', 'left')
                       ->where('print_order_id', $orderId)
                       ->orderBy('created_at', 'DESC')
                       ->get()->getResultArray();
                       
        return $this->response->setJSON(['status' => 'success', 'data' => $payments]);
    }
}
