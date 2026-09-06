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

    public function getProducts()
    {
        $products = (new PrintProductModel())
            ->orderBy('category', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();

        return $this->response->setJSON(['status' => 'success', 'data' => $products]);
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
        $term = trim((string) $this->request->getGet('term'));

        $customerBuilder = $db->table('customers');
        if ($term !== '') {
            $customerBuilder->like('name', $term, 'both', null, true);
        }
        $savedCustomers = $customerBuilder
            ->orderBy('is_favorite', 'DESC')
            ->orderBy('updated_at', 'DESC')
            ->limit(30)
            ->get()->getResultArray();

        $historyBuilder = $db->table('print_orders')
            ->select("customer_name AS name, COUNT(*) AS order_count, SUM(CASE WHEN status <> 'paid' THEN 1 ELSE 0 END) AS open_orders, MAX(created_at) AS last_order_at", false)
            ->where('customer_name IS NOT NULL', null, false)
            ->where('customer_name !=', '')
            ->where('customer_name !=', 'Cliente');
        if ($term !== '') {
            $historyBuilder->like('customer_name', $term, 'both', null, true);
        }
        $historicalCustomers = $historyBuilder
            ->groupBy('customer_name')
            ->orderBy('last_order_at', 'DESC')
            ->limit(30)
            ->get()->getResultArray();

        $customersByName = [];
        foreach ($savedCustomers as $customer) {
            $key = mb_strtolower(trim($customer['name']), 'UTF-8');
            $customersByName[$key] = [
                'key' => $key,
                'id' => $customer['id'],
                'name' => $customer['name'],
                'is_favorite' => (int) $customer['is_favorite'],
                'order_count' => 0,
                'open_orders' => 0,
                'last_order_at' => null,
            ];
        }
        foreach ($historicalCustomers as $customer) {
            $key = mb_strtolower(trim($customer['name']), 'UTF-8');
            if (!isset($customersByName[$key])) {
                $customersByName[$key] = [
                    'key' => $key,
                    'id' => null,
                    'name' => $customer['name'],
                    'is_favorite' => 0,
                ];
            }
            $customersByName[$key]['order_count'] = (int) $customer['order_count'];
            $customersByName[$key]['open_orders'] = (int) $customer['open_orders'];
            $customersByName[$key]['last_order_at'] = $customer['last_order_at'];
        }

        $normalizedTerm = mb_strtolower($term, 'UTF-8');
        $customers = array_values($customersByName);
        usort($customers, static function ($left, $right) use ($normalizedTerm) {
            if ($normalizedTerm !== '') {
                $leftName = mb_strtolower($left['name'], 'UTF-8');
                $rightName = mb_strtolower($right['name'], 'UTF-8');
                $leftRank = $leftName === $normalizedTerm ? 0 : (strpos($leftName, $normalizedTerm) === 0 ? 1 : 2);
                $rightRank = $rightName === $normalizedTerm ? 0 : (strpos($rightName, $normalizedTerm) === 0 ? 1 : 2);
                if ($leftRank !== $rightRank) {
                    return $leftRank <=> $rightRank;
                }
            }
            if ($left['is_favorite'] !== $right['is_favorite']) {
                return $right['is_favorite'] <=> $left['is_favorite'];
            }
            if ($left['open_orders'] !== $right['open_orders']) {
                return $right['open_orders'] <=> $left['open_orders'];
            }
            return strcmp((string) $right['last_order_at'], (string) $left['last_order_at']);
        });

        return $this->response->setJSON([
            'status' => 'success',
            'data' => array_slice($customers, 0, 20),
        ]);
    }

    public function getCustomerOrders()
    {
        $name = trim((string) $this->request->getGet('name'));
        if ($name === '') {
            return $this->orderError('Selecciona un cliente para consultar sus órdenes.', 422);
        }

        $db = \Config\Database::connect();
        $columns = 'id, customer_name, details, total_bs, total_usd, paid_bs, paid_usd, status, created_at ';
        $openOrders = $db->query(
            'SELECT ' . $columns . 'FROM print_orders WHERE LOWER(customer_name) = LOWER(?) '
            . "AND status <> 'paid' ORDER BY created_at DESC",
            [mb_substr($name, 0, 255)]
        )->getResultArray();
        $recentPaidOrders = $db->query(
            'SELECT ' . $columns . 'FROM print_orders WHERE LOWER(customer_name) = LOWER(?) '
            . "AND status = 'paid' ORDER BY created_at DESC LIMIT 12",
            [mb_substr($name, 0, 255)]
        )->getResultArray();

        $ordersById = [];
        foreach (array_merge($openOrders, $recentPaidOrders) as $order) {
            $ordersById[(int) $order['id']] = $order;
        }
        $orders = array_values($ordersById);
        usort($orders, static fn ($left, $right) => strcmp($right['created_at'], $left['created_at']));

        $stats = $db->query(
            "SELECT COUNT(*) AS order_count, SUM(CASE WHEN status <> 'paid' THEN 1 ELSE 0 END) AS open_orders "
            . 'FROM print_orders WHERE LOWER(customer_name) = LOWER(?)',
            [mb_substr($name, 0, 255)]
        )->getRowArray();

        return $this->response->setJSON([
            'status' => 'success',
            'data' => $orders,
            'meta' => [
                'customer_name' => $name,
                'order_count' => (int) ($stats['order_count'] ?? 0),
                'open_orders' => (int) ($stats['open_orders'] ?? 0),
            ],
        ]);
    }

    public function toggleFavorite()
    {
        $payload = $this->request->getJSON(true);
        $name = mb_substr(trim((string) ($payload['name'] ?? '')), 0, 255);
        if ($name === '') {
            return $this->orderError('Escribe o selecciona un cliente.', 422);
        }
        $favorite = !empty($payload['favorite']) ? 1 : 0;

        $db = \Config\Database::connect();
        $exists = $this->findCustomerByName($db, $name);

        if ($exists) {
            $db->table('customers')->where('id', $exists['id'])->update([
                'is_favorite' => $favorite,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
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
        $payload = $this->request->getJSON(true);
        if (!is_array($payload) || empty($payload['items']) || !is_array($payload['items'])) {
            return $this->orderError('Agrega al menos un servicio a la orden.', 422);
        }

        $db = \Config\Database::connect();
        $transactionStarted = false;

        try {
            $rate = round((float) ($payload['exchange_rate'] ?? 0), 4);
            $paidBs = round((float) ($payload['paid_bs'] ?? 0), 2);
            $paidUsd = round((float) ($payload['paid_usd'] ?? 0), 2);

            if ($rate <= 0) {
                throw new \InvalidArgumentException('La tasa de cambio debe ser mayor que cero.');
            }
            if ($paidBs < 0 || $paidUsd < 0) {
                throw new \InvalidArgumentException('Los montos pagados no pueden ser negativos.');
            }

            [$items, $totalBs, $totalUsd, $details, $summary] = $this->prepareOrderItems(
                $db,
                $payload['items'],
                $rate
            );

            $paidAsBs = $paidBs + ($paidUsd * $rate);
            if ($paidAsBs > ($totalBs + 0.01)) {
                throw new \InvalidArgumentException('El monto pagado supera el total de la orden.');
            }

            $accountId = (int) ($payload['account_id'] ?? 0);
            $account = null;
            $accountModel = new AccountModel();
            if ($paidBs > 0 || $paidUsd > 0) {
                if ($accountId <= 0) {
                    throw new \InvalidArgumentException('Selecciona la cuenta donde ingresará el pago.');
                }
                $account = $accountModel->find($accountId);
                if (!$account) {
                    throw new \InvalidArgumentException('La cuenta seleccionada ya no está disponible.');
                }
            }

            $customerName = trim((string) ($payload['customer_name'] ?? ''));
            $customerName = $customerName !== '' ? mb_substr($customerName, 0, 255) : 'Cliente';
            $status = 'pending';
            if ($paidAsBs > 0 && $paidAsBs >= ($totalBs - 0.01)) {
                $status = 'paid';
            } elseif ($paidAsBs > 0) {
                $status = 'partial';
            }

            $db->transBegin();
            $transactionStarted = true;

            $orderData = [
                'customer_name' => $customerName,
                'details' => json_encode($details, JSON_UNESCAPED_UNICODE),
                'total_bs' => $totalBs,
                'total_usd' => $totalUsd,
                'paid_bs' => $paidBs,
                'paid_usd' => $paidUsd,
                'status' => $status,
                'created_at' => date('Y-m-d H:i:s'),
            ];
            if (!$db->table('print_orders')->insert($orderData)) {
                throw new \RuntimeException('No se pudo crear la orden.');
            }
            $orderId = (int) $db->insertID();
            if ($orderId <= 0) {
                throw new \RuntimeException('No se pudo obtener el número de la orden.');
            }

            $this->rememberCustomer($db, $customerName);
            $transactionId = null;
            if ($account) {
                $categoryId = $this->getPrintingCategoryId($db);
                $description = mb_substr(
                    'Impresiones #' . $orderId . ' - ' . $customerName . ' - ' . implode(', ', $summary),
                    0,
                    255
                );
                $transactionId = (new TransactionModel())->insert([
                    'account_id' => $accountId,
                    'category_id' => $categoryId,
                    'print_order_id' => $orderId,
                    'amount' => $paidBs,
                    'amount_usd' => $paidUsd,
                    'exchange_rate' => $rate,
                    'type' => 'income',
                    'owner' => 'Negocio',
                    'description' => $description,
                    'created_at' => date('Y-m-d H:i:s'),
                ], true);
                if (!$transactionId) {
                    throw new \RuntimeException('No se pudo registrar el ingreso de la orden.');
                }

                $db->table('print_orders')->where('id', $orderId)->update(['transaction_id' => $transactionId]);
                $amountToAdd = ($account['currency'] ?? 'Bs') === 'USD'
                    ? $paidUsd + ($paidBs / $rate)
                    : $paidBs + ($paidUsd * $rate);
                if (!$accountModel->update($accountId, [
                    'balance' => round((float) $account['balance'] + $amountToAdd, 2),
                ])) {
                    throw new \RuntimeException('No se pudo actualizar el saldo de la cuenta.');
                }
            }

            if ($db->transStatus() === false) {
                throw new \RuntimeException('La base de datos rechazó la operación.');
            }
            $db->transCommit();
            $transactionStarted = false;

            AuditLogModel::log('printing', 'create_order', $orderId, null, $orderData, [
                'transaction_id' => $transactionId,
                'items_count' => count($items),
            ], "Nueva orden de impresión #{$orderId}");

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Orden registrada correctamente.',
                'order_id' => $orderId,
            ]);
        } catch (\Throwable $e) {
            if ($transactionStarted) {
                $db->transRollback();
            }
            log_message('error', '[Printing::store] ' . $e->getMessage());
            return $this->orderError(
                $e->getMessage(),
                $e instanceof \InvalidArgumentException ? 422 : 500
            );
        }
    }

    private function prepareOrderItems($db, array $requestedItems, float $rate): array
    {
        $items = [];
        $productIds = [];
        foreach ($requestedItems as $item) {
            $productId = (int) ($item['id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);
            if ($productId <= 0 || $quantity <= 0 || $quantity > 999) {
                throw new \InvalidArgumentException('Hay un servicio o una cantidad no válida en la orden.');
            }
            $items[] = [
                'id' => $productId,
                'quantity' => $quantity,
                'note' => mb_substr(trim((string) ($item['note'] ?? '')), 0, 180),
            ];
            $productIds[] = $productId;
        }

        $products = $db->table('print_products')
            ->whereIn('id', array_values(array_unique($productIds)))
            ->get()->getResultArray();
        $productsById = [];
        foreach ($products as $product) {
            $productsById[(int) $product['id']] = $product;
        }

        $totalBs = 0.0;
        $totalUsd = 0.0;
        $details = [];
        $summary = [];
        foreach ($items as $item) {
            if (!isset($productsById[$item['id']])) {
                throw new \InvalidArgumentException('Uno de los servicios ya no existe. Actualiza la página e intenta nuevamente.');
            }
            $product = $productsById[$item['id']];
            $priceBs = (float) $product['price_bs'];
            $priceUsd = (float) $product['price_usd'];
            if ($priceBs <= 0 && $priceUsd <= 0) {
                throw new \InvalidArgumentException("El servicio {$product['name']} no tiene un precio válido.");
            }

            if ($priceBs > 0) {
                $lineBs = $priceBs * $item['quantity'];
                $lineUsd = $lineBs / $rate;
            } else {
                $lineUsd = $priceUsd * $item['quantity'];
                $lineBs = $lineUsd * $rate;
            }
            $totalBs += $lineBs;
            $totalUsd += $lineUsd;
            $note = $item['note'] !== '' ? " ({$item['note']})" : '';
            $details[] = "{$item['quantity']}x {$product['name']}{$note}";
            $summary[] = "{$item['quantity']}x {$product['name']}";
        }

        return [$items, round($totalBs, 2), round($totalUsd, 2), $details, $summary];
    }

    private function rememberCustomer($db, string $customerName): void
    {
        if ($customerName === 'Cliente') {
            return;
        }
        $customer = $this->findCustomerByName($db, $customerName);
        if ($customer) {
            $db->table('customers')->where('id', $customer['id'])->update(['updated_at' => date('Y-m-d H:i:s')]);
            return;
        }
        $db->table('customers')->insert([
            'name' => $customerName,
            'is_favorite' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function findCustomerByName($db, string $name): ?array
    {
        $customer = $db->query(
            'SELECT * FROM customers WHERE LOWER(name) = LOWER(?) ORDER BY id ASC LIMIT 1',
            [$name]
        )->getRowArray();

        return $customer ?: null;
    }

    private function getPrintingCategoryId($db): int
    {
        $setting = $db->table('settings')->where('key', 'default_print_category')->get()->getRowArray();
        $categoryId = (int) ($setting['value'] ?? 0);
        if ($categoryId > 0 && $db->table('categories')->where('id', $categoryId)->countAllResults() > 0) {
            return $categoryId;
        }
        $category = $db->table('categories')->select('id')->limit(1)->get()->getRowArray();
        $categoryId = (int) ($category['id'] ?? 0);
        if ($categoryId <= 0) {
            throw new \RuntimeException('Configura una categoría para los ingresos de impresiones.');
        }
        return $categoryId;
    }

    private function orderError(string $message, int $statusCode)
    {
        return $this->response->setStatusCode($statusCode)->setJSON([
            'status' => 'error',
            'message' => $message,
        ]);
    }

    public function getHistory() {
        $db = \Config\Database::connect();
        $openOrders = $db->table('print_orders')
            ->whereIn('status', ['pending', 'partial'])
            ->orderBy('created_at', 'DESC')
            ->get()->getResultArray();
        $recentPaidOrders = $db->table('print_orders')
            ->where('status', 'paid')
            ->orderBy('created_at', 'DESC')
            ->limit(200)
            ->get()->getResultArray();

        $ordersById = [];
        foreach (array_merge($openOrders, $recentPaidOrders) as $order) {
            $ordersById[(int) $order['id']] = $order;
        }
        $orders = array_values($ordersById);
        usort($orders, static fn ($left, $right) => strcmp($right['created_at'], $left['created_at']));

        return $this->response->setJSON([
            'status' => 'success',
            'data' => $orders,
            'meta' => ['paid_limit' => 200],
        ]);
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
