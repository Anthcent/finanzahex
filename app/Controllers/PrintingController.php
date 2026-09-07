<?php

namespace App\Controllers;

use App\Libraries\PrintingPaymentCalculator;
use App\Libraries\PrintProductConfigurator;
use App\Libraries\TransactionValue;
use App\Models\PrintProductModel;
use App\Models\PrintProductCategoryModel;
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
        $products = $model->where('is_active', 1)->orderBy('category', 'ASC')->orderBy('name', 'ASC')->findAll();
        $products = array_map([$this, 'hydrateCatalogProduct'], $products);
        $catalogCategories = (new PrintProductCategoryModel())->orderBy('sort_order', 'ASC')->orderBy('name', 'ASC')->findAll();
        $products = $this->filterProductsByActiveCategories($products, $catalogCategories);
        
        // Get Accounts for income selection
        $accounts = $accountModel->where('status', 'active')->findAll();

        $settingsQuery = $db->table('settings')->get()->getResultArray();
        $settings = [];
        foreach ($settingsQuery as $row) {
            $settings[$row['key']] = $row['value'];
        }

        $defaultAccount = $settings['default_print_account'] ?? (!empty($accounts) ? $accounts[0]['id'] : 0);

        return view('printing/index', [
            'products' => $products,
            'catalogCategories' => $catalogCategories,
            'accounts' => $accounts,
            'defaultAccount' => $defaultAccount,
            'settings' => $settings,
            'initialTab' => $this->request->getGet('tab') ?: 'pos',
        ]);
    }

    public function debts()
    {
        $this->ensureTablesExist();

        $db = \Config\Database::connect();
        $model = new PrintProductModel();
        $accountModel = new AccountModel();

        $products = $model->where('is_active', 1)->orderBy('category', 'ASC')->orderBy('name', 'ASC')->findAll();
        $products = array_map([$this, 'hydrateCatalogProduct'], $products);
        $catalogCategories = (new PrintProductCategoryModel())->orderBy('sort_order', 'ASC')->orderBy('name', 'ASC')->findAll();
        $products = $this->filterProductsByActiveCategories($products, $catalogCategories);
        $accounts = $accountModel->where('status', 'active')->findAll();

        $settingsQuery = $db->table('settings')->get()->getResultArray();
        $settings = [];
        foreach ($settingsQuery as $row) {
            $settings[$row['key']] = $row['value'];
        }

        $defaultAccount = $settings['default_print_account'] ?? (!empty($accounts) ? $accounts[0]['id'] : 0);

        return view('printing/index', [
            'products' => $products,
            'catalogCategories' => $catalogCategories,
            'accounts' => $accounts,
            'defaultAccount' => $defaultAccount,
            'settings' => $settings,
            'initialTab' => 'debts',
        ]);
    }

    public function getProducts()
    {
        $products = (new PrintProductModel())
            ->where('is_active', 1)
            ->orderBy('category', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();

        return $this->response->setJSON([
            'status' => 'success',
            'data' => array_map([$this, 'hydrateCatalogProduct'], $products),
        ]);
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
        $all = !empty($this->request->getGet('all'));
        $queryLimit = $all ? (int) ($this->request->getGet('limit') ?: 350) : 30;

        $customerBuilder = $db->table('customers');
        if ($term !== '') {
            $customerBuilder->like('name', $term, 'both', null, true);
        }
        $savedCustomers = $customerBuilder
            ->orderBy('is_favorite', 'DESC')
            ->orderBy('updated_at', 'DESC')
            ->limit($queryLimit)
            ->get()->getResultArray();

        $historyBuilder = $db->table('print_orders')
            ->select("customer_name AS name, 
                      COUNT(*) AS order_count, 
                      SUM(CASE WHEN status <> 'paid' THEN 1 ELSE 0 END) AS open_orders, 
                      MAX(customer_phone) AS phone, 
                      COALESCE(SUM(total_bs), 0) AS total_bs,
                      COALESCE(SUM(total_usd), 0) AS total_usd,
                      COALESCE(SUM(paid_bs), 0) AS paid_bs,
                      COALESCE(SUM(paid_usd), 0) AS paid_usd,
                      MAX(created_at) AS last_order_at", false)
            ->where('customer_name IS NOT NULL', null, false)
            ->where('customer_name !=', '')
            ->where('customer_name !=', 'Cliente');
        if ($term !== '') {
            $historyBuilder->like('customer_name', $term, 'both', null, true);
        }
        $historicalCustomers = $historyBuilder
            ->groupBy('customer_name')
            ->orderBy('last_order_at', 'DESC')
            ->limit($queryLimit)
            ->get()->getResultArray();

        $customersByName = [];
        foreach ($savedCustomers as $customer) {
            $key = mb_strtolower(trim($customer['name']), 'UTF-8');
            $customersByName[$key] = [
                'key' => $key,
                'id' => $customer['id'],
                'name' => $customer['name'],
                'phone' => '',
                'is_favorite' => (int) $customer['is_favorite'],
                'order_count' => 0,
                'open_orders' => 0,
                'total_bs' => 0.0,
                'total_usd' => 0.0,
                'paid_bs' => 0.0,
                'paid_usd' => 0.0,
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
                    'phone' => !empty($customer['phone']) ? $customer['phone'] : '',
                    'is_favorite' => 0,
                ];
            }
            $customersByName[$key]['order_count'] = (int) $customer['order_count'];
            $customersByName[$key]['open_orders'] = (int) $customer['open_orders'];
            if (!empty($customer['phone'])) {
                $customersByName[$key]['phone'] = $customer['phone'];
            }
            $customersByName[$key]['total_bs'] = (float) ($customer['total_bs'] ?? 0);
            $customersByName[$key]['total_usd'] = (float) ($customer['total_usd'] ?? 0);
            $customersByName[$key]['paid_bs'] = (float) ($customer['paid_bs'] ?? 0);
            $customersByName[$key]['paid_usd'] = (float) ($customer['paid_usd'] ?? 0);
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

        $returnLimit = $all ? $queryLimit : 20;

        return $this->response->setJSON([
            'status' => 'success',
            'data' => array_slice($customers, 0, $returnLimit),
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
        $this->ensureTablesExist();
        $db = \Config\Database::connect();
        $model = new PrintProductModel();
        $accountModel = new AccountModel();

        $products = $model->orderBy('category', 'ASC')->orderBy('name', 'ASC')->findAll();
        $products = array_map([$this, 'hydrateCatalogProduct'], $products);
        $accounts = $accountModel->where('status', 'active')->findAll();
        $catalogCategories = (new PrintProductCategoryModel())
            ->orderBy('sort_order', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();
        
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
            'catalogCategories' => $catalogCategories,
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

            $phone = trim((string) ($payload['customer_phone'] ?? ''));
            $dueDate = trim((string) ($payload['due_date'] ?? ''));
            $notes = trim((string) ($payload['collection_notes'] ?? ''));

            $orderData = [
                'customer_name' => $customerName,
                'details' => json_encode($details, JSON_UNESCAPED_UNICODE),
                'total_bs' => $totalBs,
                'total_usd' => $totalUsd,
                'paid_bs' => $paidBs,
                'paid_usd' => $paidUsd,
                'status' => $status,
                'customer_phone' => $phone !== '' ? $phone : null,
                'due_date' => $dueDate !== '' ? $dueDate : null,
                'collection_notes' => $notes !== '' ? $notes : null,
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
                'selections' => is_array($item['selections'] ?? null) ? $item['selections'] : [],
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
        foreach ($items as $itemIndex => $item) {
            if (!isset($productsById[$item['id']])) {
                throw new \InvalidArgumentException('Uno de los servicios ya no existe. Actualiza la página e intenta nuevamente.');
            }
            $product = $productsById[$item['id']];
            $priceBs = (float) $product['price_bs'];
            $priceUsd = (float) $product['price_usd'];
            if ($priceBs <= 0 && $priceUsd <= 0) {
                throw new \InvalidArgumentException("El servicio {$product['name']} no tiene un precio válido.");
            }

            $configuration = PrintProductConfigurator::calculate(
                $product,
                $item['selections'],
                $rate
            );
            $selectionLabels = $configuration['labels'];
            $adjustmentBs = $configuration['adjustment_bs'];
            $adjustmentUsd = $configuration['adjustment_usd'];
            $items[$itemIndex]['selections'] = $configuration['selections'];

            if ($priceBs > 0) {
                $unitBs = $priceBs + $adjustmentBs;
                $unitUsd = $unitBs / $rate;
            } else {
                $unitUsd = $priceUsd + $adjustmentUsd;
                $unitBs = $unitUsd * $rate;
            }
            $lineBs = $unitBs * $item['quantity'];
            $lineUsd = $unitUsd * $item['quantity'];
            $totalBs += $lineBs;
            $totalUsd += $lineUsd;
            $note = $item['note'] !== '' ? " ({$item['note']})" : '';
            $features = $selectionLabels !== [] ? ' · ' . implode(', ', $selectionLabels) : '';
            $details[] = "{$item['quantity']}x {$product['name']}{$features}{$note}";
            $summary[] = "{$item['quantity']}x {$product['name']}{$features}";
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
        $limit = (int) ($this->request->getGet('limit') ?: 350);
        $openOrders = $db->table('print_orders')
            ->whereIn('status', ['pending', 'partial'])
            ->orderBy('created_at', 'DESC')
            ->get()->getResultArray();
        $recentPaidOrders = $db->table('print_orders')
            ->where('status', 'paid')
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
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

        $rateRow = $db->table('settings')->select('value')->where('key', 'bcv_usd_rate')->get()->getRowArray();
        $exchangeRate = (float) ($rateRow['value'] ?? 50.0);
        if ($exchangeRate <= 0) {
            $exchangeRate = 50.0;
        }
        $movements = array_map(
            static fn (array $movement): array => TransactionValue::enrich($movement, $exchangeRate),
            $movements
        );
                        
        return $this->response->setJSON(['status' => 'success', 'data' => $movements]);
    }

    public function addPayment()
    {
        $payload = $this->request->getJSON(true) ?? [];
        $db = \Config\Database::connect();
        $accountModel = new AccountModel();
        $transactionStarted = false;
        $orderId = (int) ($payload['order_id'] ?? 0);
        $requestId = trim((string) ($payload['payment_request_id'] ?? ''));

        try {
            if ($orderId <= 0) {
                throw new \InvalidArgumentException('La orden indicada no es válida.');
            }
            if ($requestId === '') {
                // Keep older cached clients functional; current clients always send a stable retry token.
                $requestId = bin2hex(random_bytes(16));
            } elseif (!preg_match('/^[A-Za-z0-9_-]{16,64}$/', $requestId)) {
                throw new \InvalidArgumentException('No se pudo identificar de forma segura este abono. Recarga e intenta nuevamente.');
            }

            $accountId = (int) ($payload['account_id'] ?? 0);
            if ($accountId <= 0) {
                throw new \InvalidArgumentException('Debe seleccionar una cuenta para registrar el pago.');
            }
            $rate = (float) ($payload['rate'] ?? 0);
            $payment = PrintingPaymentCalculator::normalize(
                (float) ($payload['amount_bs'] ?? 0),
                (float) ($payload['amount_usd'] ?? 0),
                $rate,
                isset($payload['payment_currency']) ? (string) $payload['payment_currency'] : null
            );

            $existing = $db->table('transactions')->where('payment_request_id', $requestId)->get()->getRowArray();
            if ($existing) {
                if ((int) $existing['print_order_id'] !== $orderId) {
                    throw new \InvalidArgumentException('El identificador del abono ya fue utilizado.');
                }
                return $this->printingPaymentResponse($db, $orderId, true);
            }

            $account = $accountModel->find($accountId);
            if (!$account) {
                throw new \InvalidArgumentException('La cuenta seleccionada ya no está disponible.');
            }

            $db->transBegin();
            $transactionStarted = true;

            $orderSql = 'SELECT * FROM ' . $db->protectIdentifiers('print_orders')
                . ' WHERE ' . $db->protectIdentifiers('id') . ' = ?';
            if ($db->DBDriver !== 'SQLite3') {
                $orderSql .= ' FOR UPDATE';
            }
            $order = $db->query($orderSql, [$orderId])->getRowArray();
            if (!$order) {
                throw new \InvalidArgumentException('Orden no encontrada.');
            }

            // Recheck after locking because another request may have completed while this one waited.
            $existing = $db->table('transactions')->where('payment_request_id', $requestId)->get()->getRowArray();
            if ($existing) {
                $db->transRollback();
                $transactionStarted = false;
                return $this->printingPaymentResponse($db, $orderId, true);
            }
            if (($order['status'] ?? '') === 'paid') {
                throw new \InvalidArgumentException('La deuda ya fue pagada.');
            }

            $updatedAmounts = PrintingPaymentCalculator::apply($order, $payment, $rate);
            $setting = $db->table('settings')->where('key', 'default_print_category')->get()->getRowArray();
            $categoryId = (int) ($setting['value'] ?? 3);
            if ($categoryId <= 0 || $db->table('categories')->where('id', $categoryId)->countAllResults() === 0) {
                $categoryId = (int) ($db->table('categories')->select('id')->orderBy('id', 'ASC')->get()->getRowArray()['id'] ?? 0);
            }
            if ($categoryId <= 0) {
                throw new \RuntimeException('No existe una categoría válida para registrar el abono.');
            }

            $now = date('Y-m-d H:i:s');
            $amountToAdd = ($account['currency'] ?? 'Bs') === 'USD'
                ? $payment['amount_usd'] + ($payment['amount_bs'] / $rate)
                : $payment['amount_bs'] + ($payment['amount_usd'] * $rate);
            $transData = [
                'account_id' => $accountId,
                'category_id' => $categoryId,
                'print_order_id' => $orderId,
                'payment_request_id' => $requestId,
                'amount' => $payment['amount_bs'],
                'amount_usd' => $payment['amount_usd'],
                'exchange_rate' => $rate,
                'type' => 'income',
                'owner' => 'Negocio',
                'description' => "Abono Impresiones #{$orderId} - {$order['customer_name']}",
                'balance_before' => (float) $account['balance'],
                'balance_after' => (float) $account['balance'] + $amountToAdd,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if (!$db->table('transactions')->insert($transData)) {
                throw new \RuntimeException('No se pudo registrar el movimiento del abono.');
            }

            if (!$db->table('print_orders')->where('id', $orderId)->update([
                'paid_bs' => $updatedAmounts['paid_bs'],
                'paid_usd' => $updatedAmounts['paid_usd'],
                'status' => $updatedAmounts['status'],
            ])) {
                throw new \RuntimeException('No se pudo actualizar el saldo pendiente.');
            }

            if (!$db->table('accounts')->where('id', $accountId)
                ->set('balance', 'balance + ' . $db->escape($amountToAdd), false)
                ->update()) {
                throw new \RuntimeException('No se pudo actualizar el saldo de la cuenta.');
            }

            if ($db->transStatus() === false) {
                throw new \RuntimeException('La base de datos rechazó el abono.');
            }
            $db->transCommit();
            $transactionStarted = false;

            AuditLogModel::log('printing', 'payment', $orderId, null, $transData, [
                'amount_added' => $amountToAdd,
                'remaining_bs' => $updatedAmounts['remaining_bs'],
            ], "Abono a Orden #{$orderId}");

            return $this->printingPaymentResponse($db, $orderId, false);
        } catch (\Throwable $e) {
            if ($transactionStarted) {
                $db->transRollback();
            }

            // A concurrent request with the same token may have committed first.
            if ($requestId !== '' && $db->fieldExists('payment_request_id', 'transactions')) {
                $existing = $db->table('transactions')->where('payment_request_id', $requestId)->get()->getRowArray();
                if ($existing && (int) $existing['print_order_id'] === $orderId) {
                    return $this->printingPaymentResponse($db, $orderId, true);
                }
            }

            log_message('error', '[Printing::addPayment] ' . $e->getMessage());
            return $this->response->setStatusCode($e instanceof \InvalidArgumentException ? 422 : 500)->setJSON([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function printingPaymentResponse($db, int $orderId, bool $duplicate)
    {
        $order = $db->table('print_orders')->where('id', $orderId)->get()->getRowArray();
        $history = $db->table('transactions')
            ->select('transactions.*, accounts.name as account_name')
            ->join('accounts', 'accounts.id = transactions.account_id', 'left')
            ->where('print_order_id', $orderId)
            ->orderBy('created_at', 'DESC')
            ->get()->getResultArray();

        return $this->response->setJSON([
            'status' => 'success',
            'order' => $order,
            'history' => $history,
            'duplicate' => $duplicate,
            'message' => $duplicate ? 'El abono ya había sido registrado.' : 'Abono registrado correctamente.',
        ]);
    }

    // CRUD Settings
    public function saveProduct()
    {
        $json = $this->request->getJSON(true) ?? [];
        $model = new PrintProductModel();

        try {
            $name = mb_substr(trim((string) ($json['name'] ?? '')), 0, 255);
            $categoryId = (int) ($json['category_id'] ?? 0);
            $category = (new PrintProductCategoryModel())->find($categoryId);
            $priceBs = round((float) ($json['price_bs'] ?? 0), 2);
            $priceUsd = round((float) ($json['price_usd'] ?? 0), 2);
            if ($name === '') {
                throw new \InvalidArgumentException('Escribe el nombre del producto o servicio.');
            }
            if (!$category) {
                throw new \InvalidArgumentException('Selecciona una categoría válida.');
            }
            if ($priceBs < 0 || $priceUsd < 0 || ($priceBs <= 0 && $priceUsd <= 0)) {
                throw new \InvalidArgumentException('Indica un precio mayor que cero.');
            }

            $type = strtolower(trim((string) ($json['product_type'] ?? 'service')));
            if (!in_array($type, ['service', 'product', 'custom'], true)) {
                $type = 'service';
            }
            $data = [
                'name' => $name,
                'price_bs' => $priceBs,
                'price_usd' => $priceUsd,
                'category_id' => $categoryId,
                'category' => $category['name'],
                'product_type' => $type,
                'sku' => ($sku = mb_substr(trim((string) ($json['sku'] ?? '')), 0, 80)) !== '' ? $sku : null,
                'description' => ($description = trim((string) ($json['description'] ?? ''))) !== '' ? mb_substr($description, 0, 1000) : null,
                'unit' => mb_substr(trim((string) ($json['unit'] ?? 'unidad')) ?: 'unidad', 0, 30),
                'characteristics_json' => json_encode($this->normalizeCharacteristics($json['characteristics'] ?? []), JSON_UNESCAPED_UNICODE),
                'is_active' => array_key_exists('is_active', $json) ? (!empty($json['is_active']) ? 1 : 0) : 1,
                'icon' => mb_substr(trim((string) ($json['icon'] ?? 'inventory_2')), 0, 50),
                'color' => mb_substr(trim((string) ($json['color'] ?? $category['color'] ?? 'emerald')), 0, 20),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            $id = (int) ($json['id'] ?? 0);
            if ($id <= 0) {
                $data['created_at'] = date('Y-m-d H:i:s');
            }
            $saved = $id > 0 ? $model->update($id, $data) : $model->insert($data);
            if (!$saved) {
                throw new \RuntimeException('No se pudo guardar el producto.');
            }

            return $this->response->setJSON(['status' => 'success']);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode($e instanceof \InvalidArgumentException ? 422 : 500)
                ->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function saveCatalogCategory()
    {
        $json = $this->request->getJSON(true) ?? [];
        try {
            $name = mb_substr(trim((string) ($json['name'] ?? '')), 0, 100);
            if ($name === '') {
                throw new \InvalidArgumentException('Escribe el nombre de la categoría.');
            }
            $model = new PrintProductCategoryModel();
            $id = (int) ($json['id'] ?? 0);
            $duplicate = $model->where('name', $name)->first();
            if ($duplicate && (int) $duplicate['id'] !== $id) {
                throw new \InvalidArgumentException('Ya existe una categoría con ese nombre.');
            }
            $data = [
                'name' => $name,
                'icon' => mb_substr(trim((string) ($json['icon'] ?? 'category')), 0, 50),
                'color' => mb_substr(trim((string) ($json['color'] ?? 'emerald')), 0, 20),
                'sort_order' => (int) ($json['sort_order'] ?? 0),
                'is_active' => array_key_exists('is_active', $json) ? (!empty($json['is_active']) ? 1 : 0) : 1,
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            if ($id <= 0) {
                $data['created_at'] = date('Y-m-d H:i:s');
            }
            $saved = $id > 0 ? $model->update($id, $data) : $model->insert($data);
            if (!$saved) {
                throw new \RuntimeException('No se pudo guardar la categoría.');
            }
            if ($id > 0) {
                \Config\Database::connect()->table('print_products')
                    ->where('category_id', $id)
                    ->update(['category' => $name, 'updated_at' => date('Y-m-d H:i:s')]);
            }
            return $this->response->setJSON(['status' => 'success']);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode($e instanceof \InvalidArgumentException ? 422 : 500)
                ->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function deleteCatalogCategory($id)
    {
        if ((new PrintProductModel())->where('category_id', (int) $id)->countAllResults() > 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'message' => 'Mueve o elimina los productos de esta categoría antes de borrarla.',
            ]);
        }
        (new PrintProductCategoryModel())->delete((int) $id);
        return $this->response->setJSON(['status' => 'success']);
    }

    public function deleteProduct($id)
    {
        $model = new PrintProductModel();
        $model->delete($id);
        return $this->response->setJSON(['status' => 'success']);
    }

    private function hydrateCatalogProduct(array $product): array
    {
        $characteristics = json_decode((string) ($product['characteristics_json'] ?? '[]'), true);
        $product['characteristics'] = is_array($characteristics) ? $characteristics : [];
        $product['is_active'] = (int) ($product['is_active'] ?? 1);

        return $product;
    }

    private function filterProductsByActiveCategories(array $products, array $categories): array
    {
        $activeIds = [];
        $activeNames = [];
        foreach ($categories as $category) {
            if (empty($category['is_active'])) {
                continue;
            }
            $activeIds[(int) $category['id']] = true;
            $activeNames[(string) $category['name']] = true;
        }

        return array_values(array_filter($products, static function (array $product) use ($activeIds, $activeNames): bool {
            $categoryId = (int) ($product['category_id'] ?? 0);
            if ($categoryId > 0) {
                return isset($activeIds[$categoryId]);
            }

            return isset($activeNames[(string) ($product['category'] ?? '')]);
        }));
    }

    private function normalizeCharacteristics($characteristics): array
    {
        if (!is_array($characteristics)) {
            return [];
        }
        $normalized = [];
        $seen = [];
        foreach (array_slice($characteristics, 0, 12) as $characteristic) {
            if (!is_array($characteristic)) {
                continue;
            }
            $name = mb_substr(trim((string) ($characteristic['name'] ?? '')), 0, 60);
            $key = mb_strtolower($name);
            if ($name === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $type = ($characteristic['type'] ?? 'select') === 'text' ? 'text' : 'select';
            $options = [];
            if ($type === 'select' && is_array($characteristic['options'] ?? null)) {
                foreach (array_slice($characteristic['options'], 0, 40) as $option) {
                    $label = mb_substr(trim((string) ($option['label'] ?? '')), 0, 60);
                    if ($label === '') {
                        continue;
                    }
                    $options[] = [
                        'label' => $label,
                        'price_bs' => round(max(0, (float) ($option['price_bs'] ?? 0)), 2),
                        'price_usd' => round(max(0, (float) ($option['price_usd'] ?? 0)), 2),
                    ];
                }
            }
            if ($type === 'select' && $options === []) {
                continue;
            }
            $normalized[] = [
                'name' => $name,
                'type' => $type,
                'required' => !empty($characteristic['required']),
                'options' => $options,
            ];
        }

        return $normalized;
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

    public function updateDebt()
    {
        $payload = $this->request->getJSON(true) ?? [];
        $orderId = (int) ($payload['order_id'] ?? 0);
        $db = \Config\Database::connect();
        $order = $db->table('print_orders')->where('id', $orderId)->get()->getRowArray();
        if (!$order || $order['status'] === 'paid') {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 'error',
                'message' => 'La orden no existe o ya fue pagada.',
            ]);
        }

        $dueDate = trim((string) ($payload['due_date'] ?? ''));
        if ($dueDate !== '') {
            $parsedDate = \DateTime::createFromFormat('Y-m-d', $dueDate);
            if (!$parsedDate || $parsedDate->format('Y-m-d') !== $dueDate) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'La fecha límite no es válida.',
                ]);
            }
        }

        $phone = trim((string) ($payload['customer_phone'] ?? ''));
        if ($phone !== '' && !preg_match('/^[+0-9()\-\s]{7,32}$/', $phone)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'message' => 'El teléfono sólo puede contener números, espacios, +, guiones y paréntesis.',
            ]);
        }

        $notes = trim((string) ($payload['collection_notes'] ?? ''));
        if (mb_strlen($notes) > 2000) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'message' => 'Las notas no pueden superar 2.000 caracteres.',
            ]);
        }

        $before = [
            'customer_phone' => $order['customer_phone'] ?? null,
            'due_date' => $order['due_date'] ?? null,
            'collection_notes' => $order['collection_notes'] ?? null,
        ];
        $changes = [
            'customer_phone' => $phone !== '' ? $phone : null,
            'due_date' => $dueDate !== '' ? $dueDate : null,
            'collection_notes' => $notes !== '' ? $notes : null,
        ];

        $db->table('print_orders')->where('id', $orderId)->update($changes);
        AuditLogModel::log('printing', 'update_debt', $orderId, $before, $changes, null, "Gestión de cobranza orden #{$orderId}");

        $updatedOrder = $db->table('print_orders')->where('id', $orderId)->get()->getRowArray();

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Datos de cobranza actualizados',
            'order' => $updatedOrder,
        ]);
    }

    public function recordDebtReminder()
    {
        $payload = $this->request->getJSON(true) ?? [];
        $orderIds = [];
        if (!empty($payload['order_ids']) && is_array($payload['order_ids'])) {
            $orderIds = array_map('intval', $payload['order_ids']);
        } elseif (!empty($payload['order_id'])) {
            $orderIds = [(int) $payload['order_id']];
        }

        if (empty($orderIds)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 'error',
                'message' => 'No se especificó ninguna orden.',
            ]);
        }

        $db = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');
        $updatedOrders = [];

        foreach ($orderIds as $orderId) {
            $order = $db->table('print_orders')->where('id', $orderId)->get()->getRowArray();
            if (!$order || $order['status'] === 'paid') {
                continue;
            }
            $changes = [
                'last_reminder_at' => $now,
                'reminder_count' => (int) ($order['reminder_count'] ?? 0) + 1,
            ];
            $db->table('print_orders')->where('id', $orderId)->update($changes);
            AuditLogModel::log('printing', 'debt_reminder', $orderId, null, $changes, null, "Recordatorio de cobranza orden #{$orderId}");
            $updatedOrders[$orderId] = $changes;
        }

        if (empty($updatedOrders)) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 'error',
                'message' => 'Órdenes no encontradas o ya pagadas.',
            ]);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data' => count($orderIds) === 1 && isset($updatedOrders[$orderIds[0]]) ? $updatedOrders[$orderIds[0]] : null,
            'updated' => $updatedOrders,
        ]);
    }

    public function saveDebtSettings()
    {
        $payload = $this->request->getJSON(true) ?? [];
        $settings = $payload['settings'] ?? $payload;
        if (!is_array($settings)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 'error',
                'message' => 'Datos inválidos',
            ]);
        }

        $allowedKeys = [
            'print_ticket_business_name',
            'print_ticket_subtitle',
            'print_ticket_rif',
            'print_ticket_phone',
            'print_ticket_address',
            'print_ticket_payment_info',
            'print_ticket_footer',
            'print_wa_friendly',
            'print_wa_detailed',
            'print_wa_urgent',
        ];

        $db = \Config\Database::connect();
        $builder = $db->table('settings');
        $updated = [];

        foreach ($settings as $key => $value) {
            if (!in_array($key, $allowedKeys, true)) {
                continue;
            }
            $val = trim((string) $value);
            $exists = $builder->where('key', $key)->countAllResults() > 0;
            if ($exists) {
                $builder->where('key', $key)->update(['value' => $val]);
            } else {
                $builder->insert(['key' => $key, 'value' => $val]);
            }
            $updated[$key] = $val;
        }

        AuditLogModel::log('printing', 'save_debt_settings', 0, null, $updated, null, 'Actualización de configuración de tickets y WhatsApp de deudas');

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Configuración de cobranzas guardada correctamente',
            'settings' => $updated,
        ]);
    }

}
