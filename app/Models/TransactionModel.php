<?php

namespace App\Models;

use App\Libraries\TransactionValue;
use CodeIgniter\Model;

class TransactionModel extends Model
{
    protected $table = 'transactions';
    protected $primaryKey = 'id';
    protected $allowedFields = ['account_id', 'category_id', 'print_order_id', 'amount', 'amount_usd', 'exchange_rate', 'type', 'owner', 'description', 'created_at', 'updated_at'];
    protected $useTimestamps = true;

    public function getStats()
    {
        $today = date('Y-m-d');
        $db = \Config\Database::connect();
        $exchangeRate = $this->currentExchangeRate($db);

        // Account balances are stored in each account's own currency.
        $accountBalance = 0.0;
        foreach ($db->table('accounts')->select('balance, currency')->get()->getResultArray() as $account) {
            $balance = (float) ($account['balance'] ?? 0);
            $accountBalance += strtoupper((string) ($account['currency'] ?? 'BS')) === 'USD'
                ? $balance * $exchangeRate
                : $balance;
        }

        // Today's expenses expressed in Bs, including USD-only payments.
        $todayExpense = 0.0;
        $todayExpenses = $db->table('transactions')
            ->select('amount, amount_usd, exchange_rate')
            ->where('type', 'expense')
            ->where('created_at >=', $today . ' 00:00:00')
            ->where('created_at <', date('Y-m-d', strtotime($today . ' +1 day')) . ' 00:00:00')
            ->get()->getResultArray();
        foreach ($todayExpenses as $expense) {
            $todayExpense += TransactionValue::inBolivars($expense, $exchangeRate);
        }

        // Recent transactions: join accounts, categories, and OCR invoices if available
        $hasOcr = $db->tableExists('ocr_invoices');

        $selectCols = 'transactions.id, transactions.amount, transactions.amount_usd, transactions.exchange_rate, transactions.type,
                       transactions.description, transactions.created_at, transactions.owner,
                       accounts.name as account_name, accounts.currency as account_currency,
                       categories.name as category_name, categories.icon as category_icon';

        if ($hasOcr) {
            $selectCols .= ', ocr_invoices.merchant as ocr_merchant, ocr_invoices.invoice_number as ocr_invoice_number';
        }

        $builder = $db->table('transactions')
            ->select($selectCols)
            ->join('accounts', 'accounts.id = transactions.account_id', 'left')
            ->join('categories', 'categories.id = transactions.category_id', 'left');

        if ($hasOcr) {
            $builder->join('ocr_invoices', 'ocr_invoices.transaction_id = transactions.id', 'left');
        }

        $recent = $builder
            ->orderBy('transactions.created_at', 'DESC')
            ->limit(8)
            ->get()->getResultArray();

        // Enrich description: if OCR merchant available and description is empty, use merchant
        foreach ($recent as &$row) {
            $row = TransactionValue::enrich($row, $exchangeRate);
            if (empty($row['description']) && !empty($row['ocr_merchant'])) {
                $row['description'] = $row['ocr_merchant'];
            }
        }
        unset($row);

        return [
            'balance'       => (float)$accountBalance,
            'today_expense' => (float)$todayExpense,
            'recent'        => $recent,
            'monthly_profit'=> 0,
        ];
    }

    public function getFilteredRecords($filters = [])
    {
        $db = \Config\Database::connect();
        $builder = $this->builder();

        $hasOcrTable = $db->tableExists('ocr_invoices');
        $hasItemsTable = $db->tableExists('transaction_items');

        $selectCols = 'transactions.*, accounts.name as account_name, accounts.currency as account_currency, categories.name as category_name, categories.icon as category_icon';

        if ($hasOcrTable) {
            $selectCols .= ',
                ocr_invoices.id as ocr_invoice_id,
                ocr_invoices.merchant as invoice_merchant,
                ocr_invoices.rif as invoice_rif,
                ocr_invoices.invoice_number as invoice_number,
                ocr_invoices.model_type as invoice_model_type,
                ocr_invoices.model_label as invoice_model_label,
                ocr_invoices.invoice_date as invoice_date,
                ocr_invoices.invoice_time as invoice_time,
                ocr_invoices.subtotal as invoice_subtotal,
                ocr_invoices.exento as invoice_exento,
                ocr_invoices.base_imponible as invoice_base_imponible,
                ocr_invoices.iva_amount as invoice_iva_amount,
                ocr_invoices.iva_rate as invoice_iva_rate,
                ocr_invoices.igtf_amount as invoice_igtf_amount,
                ocr_invoices.payment_method as invoice_payment_method,
                ocr_invoices.cashea_amount as invoice_cashea_amount,
                ocr_invoices.items_json as invoice_items_json,
                ocr_invoices.raw_text as invoice_raw_text,
                ocr_invoices.status as invoice_status,
                ocr_invoices.quick_scan as invoice_quick_scan,
                CASE WHEN ocr_invoices.id IS NOT NULL THEN 1 ELSE 0 END as has_invoice';
        } else {
            $selectCols .= ', 0 as has_invoice, NULL as ocr_invoice_id';
        }

        if ($hasItemsTable) {
            $selectCols .= ', (SELECT COUNT(*) FROM transaction_items WHERE transaction_items.transaction_id = transactions.id) as items_count';
        } else {
            $selectCols .= ', 0 as items_count';
        }

        $builder->select($selectCols, false);
        $builder->join('accounts', 'accounts.id = transactions.account_id', 'left');
        $builder->join('categories', 'categories.id = transactions.category_id', 'left');

        if ($hasOcrTable) {
            $builder->join('ocr_invoices', 'ocr_invoices.transaction_id = transactions.id', 'left');
        }

        if (!empty($filters['date_start'])) {
            $builder->where('transactions.created_at >=', $filters['date_start'] . ' 00:00:00');
        }
        if (!empty($filters['date_end'])) {
            $builder->where('transactions.created_at <=', $filters['date_end'] . ' 23:59:59');
        }

        // Type filter: supports 'income', 'expense', 'savings', or 'invoice' (only invoice expenses)
        if (!empty($filters['type'])) {
            if ($filters['type'] === 'invoice' || !empty($filters['only_invoices'])) {
                if ($hasOcrTable) {
                    $builder->groupStart();
                    $builder->where('ocr_invoices.id IS NOT NULL');
                    $builder->orLike('transactions.description', 'Gasto Rápido');
                    $builder->orLike('transactions.description', 'Gasto Factura');
                    $builder->groupEnd();
                } else {
                    $builder->like('transactions.description', 'Factura');
                }
            } else {
                $builder->where('transactions.type', $filters['type']);
            }
        } elseif (!empty($filters['only_invoices'])) {
            if ($hasOcrTable) {
                $builder->where('ocr_invoices.id IS NOT NULL');
            } else {
                $builder->like('transactions.description', 'Factura');
            }
        }

        if (!empty($filters['account_id'])) {
            $builder->where('transactions.account_id', (int) $filters['account_id']);
        }

        if (!empty($filters['owner'])) {
            $builder->where('transactions.owner', $filters['owner']);
        }

        if (!empty($filters['category_id'])) {
            $builder->where('transactions.category_id', (int) $filters['category_id']);
        }

        if (!empty($filters['search'])) {
            $term = trim($filters['search']);
            $builder->groupStart();
            $builder->like('transactions.description', $term);
            $builder->orLike('categories.name', $term);
            $builder->orLike('accounts.name', $term);
            if ($hasOcrTable) {
                $builder->orLike('ocr_invoices.merchant', $term);
                $builder->orLike('ocr_invoices.rif', $term);
                $builder->orLike('ocr_invoices.invoice_number', $term);
            }
            $builder->groupEnd();
        }

        // Sorting
        $sort = $filters['sort'] ?? 'date_desc';
        switch ($sort) {
            case 'date_asc':
                $builder->orderBy('transactions.created_at', 'ASC');
                break;
            case 'amount_desc':
                $builder->orderBy('transactions.amount', 'DESC')->orderBy('transactions.created_at', 'DESC');
                break;
            case 'amount_asc':
                $builder->orderBy('transactions.amount', 'ASC')->orderBy('transactions.created_at', 'DESC');
                break;
            case 'date_desc':
            default:
                $builder->orderBy('transactions.created_at', 'DESC');
                break;
        }

        $records = $builder->get()->getResultArray();
        $exchangeRate = $this->currentExchangeRate($db);

        // Add formatted date helpers for grouping
        foreach ($records as &$rec) {
            $rec = TransactionValue::enrich($rec, $exchangeRate);
            $ts = !empty($rec['created_at']) ? strtotime($rec['created_at']) : time();
            $rec['date_ymd'] = date('Y-m-d', $ts);
            $rec['time_hi'] = date('H:i', $ts);
            $rec['has_invoice'] = !empty($rec['ocr_invoice_id']) || (!empty($rec['has_invoice']) && $rec['has_invoice'] == 1);
            $rec['items_count'] = (int) ($rec['items_count'] ?? 0);
        }
        unset($rec);

        if (in_array($sort, ['amount_desc', 'amount_asc'], true)) {
            usort($records, static function (array $left, array $right) use ($sort): int {
                $amountComparison = ((float) $left['display_amount_bs']) <=> ((float) $right['display_amount_bs']);
                if ($amountComparison === 0) {
                    return strcmp((string) $right['created_at'], (string) $left['created_at']);
                }

                return $sort === 'amount_desc' ? -$amountComparison : $amountComparison;
            });
        }

        return $records;
    }

    public function getMetricsData($startDate, $endDate)
    {
        $db = \Config\Database::connect();
        $exchangeRate = $this->currentExchangeRate($db);
        $amountBs = $this->normalizedAmountExpression('transactions.', $exchangeRate, 'bs');
        $amountUsd = $this->normalizedAmountExpression('transactions.', $exchangeRate, 'usd');

        // 1. Totals in Bs and USD (including savings)
        $totals = $this->builder()
            ->select("
                COALESCE(SUM(CASE WHEN type = 'income' THEN {$amountBs} ELSE 0 END), 0) as income,
                COALESCE(SUM(CASE WHEN type = 'income' THEN {$amountUsd} ELSE 0 END), 0) as income_usd,
                COALESCE(SUM(CASE WHEN type = 'expense' THEN {$amountBs} ELSE 0 END), 0) as expense,
                COALESCE(SUM(CASE WHEN type = 'expense' THEN {$amountUsd} ELSE 0 END), 0) as expense_usd,
                COALESCE(SUM(CASE WHEN type = 'savings' THEN {$amountBs} ELSE 0 END), 0) as savings,
                COALESCE(SUM(CASE WHEN type = 'savings' THEN {$amountUsd} ELSE 0 END), 0) as savings_usd,
                COUNT(*) as total_transactions,
                COALESCE(SUM(CASE WHEN type = 'income' THEN 1 ELSE 0 END), 0) as count_income,
                COALESCE(SUM(CASE WHEN type = 'expense' THEN 1 ELSE 0 END), 0) as count_expense,
                COALESCE(SUM(CASE WHEN type = 'savings' THEN 1 ELSE 0 END), 0) as count_savings
            ", false)
            ->where('created_at >=', $startDate . ' 00:00:00')
            ->where('created_at <=', $endDate . ' 23:59:59')
            ->get()->getRowArray();

        // 2. Expenses by Category
        $byCategory = $this->builder()
            ->select("categories.id, COALESCE(categories.name, 'Sin Categoría') as name, COALESCE(categories.icon, 'category') as icon, SUM({$amountBs}) as total, SUM({$amountUsd}) as total_usd, COUNT(*) as count", false)
            ->join('categories', 'categories.id = transactions.category_id', 'left')
            ->where('transactions.type', 'expense')
            ->where('transactions.created_at >=', $startDate . ' 00:00:00')
            ->where('transactions.created_at <=', $endDate . ' 23:59:59')
            ->groupBy(['categories.id', 'categories.name', 'categories.icon'])
            ->orderBy('total', 'DESC')
            ->get()->getResultArray();

        // 3. Income by Category
        $incomeByCategory = $this->builder()
            ->select("categories.id, COALESCE(categories.name, 'Sin Categoría') as name, COALESCE(categories.icon, 'category') as icon, SUM({$amountBs}) as total, SUM({$amountUsd}) as total_usd, COUNT(*) as count", false)
            ->join('categories', 'categories.id = transactions.category_id', 'left')
            ->where('transactions.type', 'income')
            ->where('transactions.created_at >=', $startDate . ' 00:00:00')
            ->where('transactions.created_at <=', $endDate . ' 23:59:59')
            ->groupBy(['categories.id', 'categories.name', 'categories.icon'])
            ->orderBy('total', 'DESC')
            ->get()->getResultArray();

        // 4. Savings by Category
        $savingsByCategory = $this->builder()
            ->select("categories.id, COALESCE(categories.name, 'Ahorro / Fondo') as name, COALESCE(categories.icon, 'savings') as icon, SUM({$amountBs}) as total, SUM({$amountUsd}) as total_usd, COUNT(*) as count", false)
            ->join('categories', 'categories.id = transactions.category_id', 'left')
            ->where('transactions.type', 'savings')
            ->where('transactions.created_at >=', $startDate . ' 00:00:00')
            ->where('transactions.created_at <=', $endDate . ' 23:59:59')
            ->groupBy(['categories.id', 'categories.name', 'categories.icon'])
            ->orderBy('total', 'DESC')
            ->get()->getResultArray();

        // 5. Movements by Account
        $byAccount = $this->builder()
            ->select("accounts.id, COALESCE(accounts.name, 'Sin Cuenta') as name, accounts.currency, accounts.balance as current_balance,
                      COALESCE(SUM(CASE WHEN transactions.type = 'income' THEN {$amountBs} ELSE 0 END), 0) as income,
                      COALESCE(SUM(CASE WHEN transactions.type = 'income' THEN {$amountUsd} ELSE 0 END), 0) as income_usd,
                      COALESCE(SUM(CASE WHEN transactions.type = 'expense' THEN {$amountBs} ELSE 0 END), 0) as expense,
                      COALESCE(SUM(CASE WHEN transactions.type = 'expense' THEN {$amountUsd} ELSE 0 END), 0) as expense_usd,
                      COALESCE(SUM(CASE WHEN transactions.type = 'savings' THEN {$amountBs} ELSE 0 END), 0) as savings,
                      COALESCE(SUM(CASE WHEN transactions.type = 'savings' THEN {$amountUsd} ELSE 0 END), 0) as savings_usd,
                      COUNT(*) as count", false)
            ->join('accounts', 'accounts.id = transactions.account_id', 'left')
            ->where('transactions.created_at >=', $startDate . ' 00:00:00')
            ->where('transactions.created_at <=', $endDate . ' 23:59:59')
            ->groupBy(['accounts.id', 'accounts.name', 'accounts.currency', 'accounts.balance'])
            ->orderBy('accounts.name', 'ASC')
            ->get()->getResultArray();

        // 6. Trends (Daily) with Income, Expense and Savings
        $dailyTrend = $this->builder()
            ->select("DATE(created_at) as date, 
                      COALESCE(SUM(CASE WHEN type = 'income' THEN {$amountBs} ELSE 0 END), 0) as income,
                      COALESCE(SUM(CASE WHEN type = 'income' THEN {$amountUsd} ELSE 0 END), 0) as income_usd,
                      COALESCE(SUM(CASE WHEN type = 'expense' THEN {$amountBs} ELSE 0 END), 0) as expense,
                      COALESCE(SUM(CASE WHEN type = 'expense' THEN {$amountUsd} ELSE 0 END), 0) as expense_usd,
                      COALESCE(SUM(CASE WHEN type = 'savings' THEN {$amountBs} ELSE 0 END), 0) as savings,
                      COALESCE(SUM(CASE WHEN type = 'savings' THEN {$amountUsd} ELSE 0 END), 0) as savings_usd", false)
            ->where('created_at >=', $startDate . ' 00:00:00')
            ->where('created_at <=', $endDate . ' 23:59:59')
            ->groupBy('DATE(created_at)')
            ->orderBy('date', 'ASC')
            ->get()->getResultArray();

        // 7. Breakdown by Owner (Negocio vs Personal)
        $byOwner = $this->builder()
            ->select("COALESCE(owner, 'General') as owner,
                      COALESCE(SUM(CASE WHEN type = 'income' THEN {$amountBs} ELSE 0 END), 0) as income,
                      COALESCE(SUM(CASE WHEN type = 'income' THEN {$amountUsd} ELSE 0 END), 0) as income_usd,
                      COALESCE(SUM(CASE WHEN type = 'expense' THEN {$amountBs} ELSE 0 END), 0) as expense,
                      COALESCE(SUM(CASE WHEN type = 'expense' THEN {$amountUsd} ELSE 0 END), 0) as expense_usd,
                      COALESCE(SUM(CASE WHEN type = 'savings' THEN {$amountBs} ELSE 0 END), 0) as savings,
                      COALESCE(SUM(CASE WHEN type = 'savings' THEN {$amountUsd} ELSE 0 END), 0) as savings_usd,
                      COUNT(*) as count", false)
            ->where('created_at >=', $startDate . ' 00:00:00')
            ->where('created_at <=', $endDate . ' 23:59:59')
            ->groupBy('owner')
            ->get()->getResultArray();

        // 8. Printing stats in period
        $printStats = [];
        if ($db->tableExists('print_orders')) {
            $printStats = $db->table('print_orders')
                ->select("COUNT(*) as order_count,
                          COALESCE(SUM(total_bs), 0) as total_bs,
                          COALESCE(SUM(total_usd), 0) as total_usd,
                          COALESCE(SUM(paid_bs), 0) as paid_bs,
                          COALESCE(SUM(paid_usd), 0) as paid_usd,
                          COALESCE(SUM(CASE WHEN status <> 'paid' THEN 1 ELSE 0 END), 0) as open_count", false)
                ->where('created_at >=', $startDate . ' 00:00:00')
                ->where('created_at <=', $endDate . ' 23:59:59')
                ->get()->getRowArray();
        }

        // 9. All active accounts snapshot
        $accountsSnapshot = [];
        if ($db->tableExists('accounts')) {
            $accountsSnapshot = $db->table('accounts')
                ->where('status', 'active')
                ->orderBy('name', 'ASC')
                ->get()->getResultArray();
        }

        // 10. Invoice Expense Stats
        $invoiceStats = [
            'total_bs' => 0.0,
            'total_usd' => 0.0,
            'count' => 0,
            'iva_bs' => 0.0
        ];
        if ($db->tableExists('ocr_invoices')) {
            $invRow = $db->table('ocr_invoices')
                ->select("
                    COALESCE(SUM(total_bs), 0) as total_bs,
                    COALESCE(SUM(total_usd), 0) as total_usd,
                    COALESCE(SUM(iva_amount), 0) as iva_bs,
                    COUNT(*) as count
                ", false)
                ->where('created_at >=', $startDate . ' 00:00:00')
                ->where('created_at <=', $endDate . ' 23:59:59')
                ->where('status !=', 'cancelled')
                ->get()->getRowArray();
            if ($invRow) {
                $invoiceStats = [
                    'total_bs' => (float)$invRow['total_bs'],
                    'total_usd' => (float)$invRow['total_usd'],
                    'count' => (int)$invRow['count'],
                    'iva_bs' => (float)$invRow['iva_bs']
                ];
            }
        }

        return [
            'totals' => $totals,
            'by_category' => $byCategory,
            'income_by_category' => $incomeByCategory,
            'savings_by_category' => $savingsByCategory,
            'by_account' => $byAccount,
            'by_owner' => $byOwner,
            'trends' => $dailyTrend,
            'print_stats' => $printStats,
            'accounts_snapshot' => $accountsSnapshot,
            'invoice_stats' => $invoiceStats,
        ];
    }

    public function getDetailedHistory($start, $end)
    {
        $db = \Config\Database::connect();
        $builder = $this->builder();
        $hasOcr = $db->tableExists('ocr_invoices');

        $selectCols = 'transactions.*, accounts.name as account_name, categories.name as category_name, categories.icon as category_icon';
        if ($hasOcr) {
            $selectCols .= ', ocr_invoices.id as ocr_invoice_id, ocr_invoices.merchant as invoice_merchant, ocr_invoices.invoice_number, ocr_invoices.rif as invoice_rif, ocr_invoices.iva_amount as invoice_iva';
        }

        $builder->select($selectCols, false)
            ->join('accounts', 'accounts.id = transactions.account_id', 'left')
            ->join('categories', 'categories.id = transactions.category_id', 'left');

        if ($hasOcr) {
            $builder->join('ocr_invoices', 'ocr_invoices.transaction_id = transactions.id', 'left');
        }

        $records = $builder->where('transactions.created_at >=', $start . ' 00:00:00')
            ->where('transactions.created_at <=', $end . ' 23:59:59')
            ->orderBy('transactions.created_at', 'DESC')
            ->get()->getResultArray();

        $exchangeRate = $this->currentExchangeRate($db);

        return array_map(
            static fn (array $record): array => TransactionValue::enrich($record, $exchangeRate),
            $records
        );
    }

    private function currentExchangeRate($db): float
    {
        if (!$db->tableExists('settings')) {
            return 50.0;
        }

        $row = $db->table('settings')->select('value')->where('key', 'bcv_usd_rate')->get()->getRowArray();
        $rate = (float) ($row['value'] ?? 0);

        return $rate > 0 ? $rate : 50.0;
    }

    private function normalizedAmountExpression(string $prefix, float $fallbackRate, string $currency): string
    {
        $rate = number_format($fallbackRate > 0 ? $fallbackRate : 50.0, 6, '.', '');
        $storedRate = "(CASE WHEN COALESCE({$prefix}exchange_rate, 0) > 0 THEN {$prefix}exchange_rate ELSE {$rate} END)";
        $amountBs = "COALESCE({$prefix}amount, 0)";
        $amountUsd = "COALESCE({$prefix}amount_usd, 0)";
        $tolerance = "(CASE WHEN ({$storedRate} * 0.01) > 0.05 THEN ({$storedRate} * 0.01) ELSE 0.05 END)";
        $equivalent = "({$amountBs} > 0 AND {$amountUsd} > 0 AND ABS({$amountBs} - ({$amountUsd} * {$storedRate})) <= {$tolerance})";

        if ($currency === 'usd') {
            return "(CASE WHEN {$equivalent} THEN {$amountUsd} ELSE {$amountUsd} + ({$amountBs} / {$storedRate}) END)";
        }

        return "(CASE WHEN {$equivalent} THEN {$amountBs} ELSE {$amountBs} + ({$amountUsd} * {$storedRate}) END)";
    }
}
