<?php

namespace App\Controllers;

use App\Models\SaleModel;
use App\Models\SalePaymentModel;
use CodeIgniter\API\ResponseTrait;
use App\Models\AuditLogModel;

class SalesController extends BaseController
{
    use ResponseTrait;

    protected $saleModel;
    protected $paymentModel;

    public function __construct()
    {
        $this->saleModel = new SaleModel();
        $this->paymentModel = new SalePaymentModel();
    }

    public function index()
    {
        $db = \Config\Database::connect();
        
        // 1. Month Summary
        $currentMonthStart = date('Y-m-01');
        $lastMonthStart = date('Y-m-01', strtotime('-1 month'));
        $lastMonthEnd = date('Y-m-t', strtotime('-1 month'));
        
        // Current Month
        $currentMonthStats = $db->table('sales')
            ->select('COALESCE(SUM(amount_usd), 0) as total, COUNT(id) as count')
            ->where('date >=', $currentMonthStart)
            ->where('order_status_id !=', 5) // Exclude Cancelled (ID 5 usually)
            ->get()->getRowArray();
            
        // Last Month
        $lastMonthStats = $db->table('sales')
            ->select('COALESCE(SUM(amount_usd), 0) as total')
            ->where('date >=', $lastMonthStart)
            ->where('date <=', $lastMonthEnd)
             ->where('order_status_id !=', 5)
            ->get()->getRowArray();

        $monthTotal = $currentMonthStats['total'];
        $monthCount = $currentMonthStats['count'];
        $lastMonthTotal = $lastMonthStats['total'];

        // Growth Calculation
        $growth = 0;
        if ($lastMonthTotal > 0) {
            $growth = (($monthTotal - $lastMonthTotal) / $lastMonthTotal) * 100;
        } elseif ($monthTotal > 0) {
            $growth = 100;
        }

        // 2. Status Stats (Existing)
        $stats = $db->table('sale_statuses ss')
             ->select('ss.id, ss.name, ss.color, COUNT(s.id) as count, COALESCE(SUM(s.amount_usd), 0) as total_usd')
             ->join('sales s', 's.order_status_id = ss.id', 'left')
             ->groupBy('ss.id')
             ->get()->getResultArray();

        return view('sales/index', [
            'statusStats' => $stats,
            'monthTotal' => $monthTotal,
            'monthCount' => $monthCount,
            'growth' => $growth
        ]);
    }

    public function create()
    {
        return view('sales/create');
    }

    public function debts()
    {
        $debts = $this->saleModel->getDebts();
        return view('sales/debts', ['debts' => $debts]);
    }



    public function store()
    {
        $json = $this->request->getJSON();
        
        // Basic Validation
        if (!$json->customer || empty($json->items)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Faltan datos']);
        }

        $saleModel = new SaleModel();
        $salePaymentModel = new SalePaymentModel();
        $saleDetailModel = new \App\Models\SaleDetailModel();
        $inventoryItemModel = new \App\Models\InventoryItemModel();
        $inventoryMovementModel = new \App\Models\InventoryMovementModel();

        $db = \Config\Database::connect();
        $db->transStart();

        // Calculate Totals from Items
        $totalAmountUsd = 0;
        $totalAmountBs = 0;
        
        foreach ($json->items as $item) {
            $totalAmountUsd += ($item->price_usd * $item->quantity);
            // If item has specific BS price use it, otherwise calc
            $bsPrice = isset($item->price_bs) ? $item->price_bs : ($item->price_usd * $json->exchange_rate);
            $totalAmountBs += ($bsPrice * $item->quantity);
        }

        // Prepare Sale Data
        $data = [
            'date' => $json->date,
            'customer' => $json->customer,
            'amount' => $totalAmountBs,
            'amount_usd' => $totalAmountUsd,
            'amount_usd' => $totalAmountUsd,
            'exchange_rate' => $json->exchange_rate,
            'status' => $json->status, // Payment Status
            'order_status_id' => $json->order_status_id ?? 1, // Default 'Pendiente'
            'description' => 'Venta multiproducto',
            'product' => count($json->items) . ' Productos', // Summary for legacy view
            'reference' => $json->reference ?? '',
            'paid_amount' => 0,
            'paid_amount_usd' => 0
        ];

        // Handle Payments
        if ($json->status === 'paid') {
            $data['paid_amount'] = $totalAmountBs;
            $data['paid_amount_usd'] = $totalAmountUsd;
        } elseif ($json->status === 'partial') {
            $data['paid_amount'] = $json->paid_amount ?? 0;
            $data['paid_amount_usd'] = $json->paid_amount_usd ?? 0;
        }

        $saleId = $saleModel->insert($data);

        // Record Initial Payment if partial and amount > 0
        if ($json->status === 'partial' && ($data['paid_amount'] > 0 || $data['paid_amount_usd'] > 0)) {
            $salePaymentModel->insert([
                'sale_id' => $saleId,
                'amount' => $data['paid_amount'],
                'amount_usd' => $data['paid_amount_usd'],
                'rate' => $json->exchange_rate,
                'date' => $json->date,
                'reference' => 'Inicial'
            ]);
        }

        // --- INTEGRATION: Record Transaction (Income) ---
        // Only if there is a payment (full or partial with initial)
        $paymentAmountUsd = 0;
        $paymentAmountBs = 0;
        
        if ($json->status === 'paid') {
            $paymentAmountUsd = $totalAmountUsd;
            $paymentAmountBs = $totalAmountBs;
        } elseif ($json->status === 'partial') {
            $paymentAmountUsd = $json->paid_amount_usd ?? 0;
            $paymentAmountBs = $json->paid_amount ?? 0;
        }

        if (($paymentAmountUsd > 0 || $paymentAmountBs > 0) && !empty($json->account_id)) {
            $transModel = new \App\Models\TransactionModel();
            $accountModel = new \App\Models\AccountModel();
            
            // Get Account to check currency/logic if needed, but for now direct update
            $targetAccount = $accountModel->find($json->account_id);
            
            if ($targetAccount) {
                // 1. Record Transaction
                $transModel->insert([
                    'account_id' => $json->account_id,
                    'category_id' => $json->category_id ?? 2, // Use selected category or default to 2
                    'amount' => $paymentAmountBs,
                    'amount_usd' => $paymentAmountUsd,
                    'exchange_rate' => $json->exchange_rate,
                    'type' => 'income',
                    'owner' => $json->owner ?? 'Business',
                    'description' => "Venta #$saleId" . ($json->customer ? " - " . $json->customer : ""),
                    'created_at' => $json->date . ' ' . date('H:i:s')
                ]);

                // 2. Update Balance
                // Determine if account is USD or BS?
                // The system seems to track 'balance' generic, but let's assume 'balance' is in primary currency (Bs likely, or Mixed).
                // However, the TransactionController logic suggests:
                // if type='income' newBalance += amount.
                // But wait, if account is USD, we should add USD.
                // Let's assume the `balance` field stores the value in the account's currency.
                
                $amountToAdd = $paymentAmountBs;
                if (isset($targetAccount['currency']) && $targetAccount['currency'] === 'USD') {
                     $amountToAdd = $paymentAmountUsd;
                }
                
                $accountModel->update($json->account_id, [
                    'balance' => $targetAccount['balance'] + $amountToAdd
                ]);
            }
        }
        
        // AUDIT LOG
        AuditLogModel::log('sales', 'create', $saleId, null, $data, ['total_bs' => $totalAmountBs, 'total_usd' => $totalAmountUsd], "Venta #$saleId");
        // ------------------------------------------------

        // Process Items
        foreach ($json->items as $item) {
            // 1. Save Detail
            $saleDetailModel->insert([
                'sale_id' => $saleId,
                'item_id' => $item->id ?? null, // Null if manual item (not implemented yet but robust)
                'quantity' => $item->quantity,
                'price' => $item->price_usd,
                'subtotal' => $item->price_usd * $item->quantity
            ]);

            // 2. Inventory Deduction (Only if it's a registered item)
            if (isset($item->id) && $item->id) {
                // Deduct Stock
                // CI4 4.4's Postgre decrement() casts numeric stock through to_number().
                // A bound arithmetic update preserves the atomic stock deduction on both engines.
                $db->query('UPDATE inventory_items SET stock = stock - ? WHERE id = ?', [$item->quantity, $item->id]);
                
                // Record Movement
                $inventoryMovementModel->insert([
                    'item_id' => $item->id,
                    'type' => 'sale',
                    'quantity' => -abs($item->quantity),
                    'date' => $json->date . ' ' . date('H:i:s'),
                    'reference' => 'Venta #' . $saleId
                ]);
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Error en transacción']);
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Venta registrada']);
    }

    public function addPayment()
    {
        $json = $this->request->getJSON();
        $saleId = $json->sale_id;
        
        // 1. Record Payment
        $this->paymentModel->save([
            'sale_id' => $saleId,
            'amount' => $json->amount,
            'amount_usd' => $json->amount_usd,
            'rate' => $json->rate,
            'date' => $json->date,
            'reference' => $json->reference
        ]);

        // 2. Update Sale Totals
        $sale = $this->saleModel->find($saleId);
        $newPaidBs = $sale['paid_amount'] + $json->amount;
        $newPaidUsd = $sale['paid_amount_usd'] + $json->amount_usd;
        
        // Check if fully paid (allow small margin of error for float precision)
        // Logic: If Paid USD >= Total USD OR Paid BS >= Total BS (depending on main currency... simplified check)
        // Let's check against Total USD for simplicity as base
        $isPaid = false;
        if ($newPaidUsd >= ($sale['amount_usd'] - 0.01)) {
            $isPaid = true;
        }

        $this->saleModel->update($saleId, [
            'paid_amount' => $newPaidBs,
            'paid_amount_usd' => $newPaidUsd,
            'status' => $isPaid ? 'paid' : 'partial'
        ]);

        // AUDIT LOG
        AuditLogModel::log('sales', 'payment', $saleId, null, $json, ['amount' => $json->amount, 'amount_usd' => $json->amount_usd], "Pago a Venta #$saleId");

        return $this->respond(['status' => 'success', 'message' => 'Pago registrado']);
    }
    public function getSaleDetails($id)
    {
        $saleModel = new SaleModel();
        $paymentModel = new SalePaymentModel();
        $db = \Config\Database::connect();

        // Get Sale with Status Info
        $sale = $db->table('sales s')
            ->select('s.*, ss.name as status_name, ss.color as status_color')
            ->join('sale_statuses ss', 'ss.id = s.order_status_id', 'left')
            ->where('s.id', $id)
            ->get()->getRowArray();

        if (!$sale) return $this->response->setJSON(['status' => 'error']);

        // Get Items
        $items = $db->table('sale_details d')
            ->select('d.*, i.name as item_name, i.unit')
            ->join('inventory_items i', 'i.id = d.item_id', 'left')
            ->where('d.sale_id', $id)
            ->get()->getResultArray();

        // Get Payments
        $payments = $paymentModel->where('sale_id', $id)->orderBy('date', 'ASC')->findAll();

        return $this->response->setJSON([
            'status' => 'success',
            'sale' => $sale,
            'items' => $items,
            'payments' => $payments
        ]);
    }

    public function getStatuses()
    {
        $db = \Config\Database::connect();
        $statuses = $db->table('sale_statuses')->get()->getResultArray();
        return $this->response->setJSON(['status' => 'success', 'data' => $statuses]);
    }

    public function updateStatus()
    {
        $json = $this->request->getJSON();
        $saleId = $json->sale_id;
        $statusId = $json->status_id;

        $this->saleModel->update($saleId, ['order_status_id' => $statusId]);
        
        return $this->response->setJSON(['status' => 'success']);
    }

    public function history()
    {
        $db = \Config\Database::connect();
        
        // Fetch Sales with Status
        $builder = $db->table('sales s');
        $builder->select('s.*, ss.name as status_name, ss.color as status_color');
        $builder->join('sale_statuses ss', 'ss.id = s.order_status_id', 'left');
        $builder->orderBy('s.date', 'DESC');
        
        $sales = $builder->get()->getResultArray();
        
        $statuses = $db->table('sale_statuses')->get()->getResultArray();
        
        return view('sales/history', ['sales' => $sales, 'statuses' => $statuses]);
    }

    public function getActiveOrders()
    {
        $db = \Config\Database::connect();
        
        // Fetch statuses to find IDs for 'Entregado' and 'Cancelado' to exclude
        // Using names is safer than hardcoding IDs 4,5 in case they change
        $builder = $db->table('sales s');
        $builder->select('s.*, ss.name as status_name, ss.color as status_color');
        $builder->join('sale_statuses ss', 'ss.id = s.order_status_id', 'left');
        $builder->groupStart();
            $builder->whereNotIn('ss.name', ['Entregado', 'Cancelado']);
            $builder->orWhere('s.order_status_id', 0); // Include potentially unassigned
            $builder->orWhere('s.order_status_id IS NULL');
        $builder->groupEnd();
        $builder->orderBy('s.date', 'ASC'); // Oldest first
        
        $sales = $builder->get()->getResultArray();

        return $this->response->setJSON(['status' => 'success', 'data' => $sales]);
    }

    public function getAccounts()
    {
        $db = \Config\Database::connect();
        $accounts = $db->table('accounts')->where('status', 'active')->get()->getResultArray();
        return $this->response->setJSON(['status' => 'success', 'data' => $accounts]);
    }

    public function getCategories()
    {
        $db = \Config\Database::connect();
        $categories = $db->table('categories')->get()->getResultArray();
        return $this->response->setJSON(['status' => 'success', 'data' => $categories]);
    }
}
