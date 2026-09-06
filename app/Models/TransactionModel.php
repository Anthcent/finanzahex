<?php

namespace App\Models;

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
        $month = date('Y-m');

        // Real Balance = Sum of all Account Balances
        $db = \Config\Database::connect();
        $accountBalance = $db->table('accounts')->selectSum('balance')->get()->getRow()->balance ?? 0;

        $todayExpense = $this->where('type', 'expense')
                             ->where('created_at >=', $today . ' 00:00:00')
                             ->where('created_at <', date('Y-m-d', strtotime($today . ' +1 day')) . ' 00:00:00')
                             ->selectSum('amount')->first()['amount'] ?? 0;

        $recent = $this->builder()
            ->select('transactions.*, accounts.name as account_name, categories.name as category_name, categories.icon as category_icon')
            ->join('accounts', 'accounts.id = transactions.account_id', 'left')
            ->join('categories', 'categories.id = transactions.category_id', 'left')
            ->orderBy('transactions.created_at', 'DESC')
            ->limit(5)
            ->get()->getResultArray();

        return [
            'balance' => (float)$accountBalance,
            'today_expense' => (float)$todayExpense,
            'recent' => $recent,
            'monthly_profit' => 0 // Removed for now as it's ambiguous
        ];
    }
    public function getFilteredRecords($filters = [])
    {
        $builder = $this->builder();
        $builder->select('transactions.*, accounts.name as account_name, categories.name as category_name');
        $builder->join('accounts', 'accounts.id = transactions.account_id', 'left');
        $builder->join('categories', 'categories.id = transactions.category_id', 'left');

        if (!empty($filters['date_start'])) {
            $builder->where('transactions.created_at >=', $filters['date_start'] . ' 00:00:00');
        }
        if (!empty($filters['date_end'])) {
            $builder->where('transactions.created_at <=', $filters['date_end'] . ' 23:59:59');
        }
        if (!empty($filters['type'])) {
            $builder->where('transactions.type', $filters['type']);
        }
        if (!empty($filters['owner'])) {
            $builder->where('transactions.owner', $filters['owner']);
        }
        if (!empty($filters['category_id'])) {
            $builder->where('transactions.category_id', $filters['category_id']);
        }
        if (!empty($filters['search'])) {
            $builder->groupStart();
            $builder->like('transactions.description', $filters['search']);
            $builder->orLike('categories.name', $filters['search']);
            $builder->groupEnd();
        }

        $builder->orderBy('transactions.created_at', 'DESC');
        return $builder->get()->getResultArray();
    }

    public function getMetricsData($startDate, $endDate)
    {
        $db = \Config\Database::connect();

        // 1. Totals in Bs and USD (including savings)
        $totals = $this->builder()
            ->select("
                COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as income,
                COALESCE(SUM(CASE WHEN type = 'income' THEN amount_usd ELSE 0 END), 0) as income_usd,
                COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as expense,
                COALESCE(SUM(CASE WHEN type = 'expense' THEN amount_usd ELSE 0 END), 0) as expense_usd,
                COALESCE(SUM(CASE WHEN type = 'savings' THEN amount ELSE 0 END), 0) as savings,
                COALESCE(SUM(CASE WHEN type = 'savings' THEN amount_usd ELSE 0 END), 0) as savings_usd,
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
            ->select("categories.id, COALESCE(categories.name, 'Sin Categoría') as name, COALESCE(categories.icon, 'category') as icon, SUM(transactions.amount) as total, SUM(transactions.amount_usd) as total_usd, COUNT(*) as count", false)
            ->join('categories', 'categories.id = transactions.category_id', 'left')
            ->where('transactions.type', 'expense')
            ->where('transactions.created_at >=', $startDate . ' 00:00:00')
            ->where('transactions.created_at <=', $endDate . ' 23:59:59')
            ->groupBy(['categories.id', 'categories.name', 'categories.icon'])
            ->orderBy('total', 'DESC')
            ->get()->getResultArray();

        // 3. Income by Category
        $incomeByCategory = $this->builder()
            ->select("categories.id, COALESCE(categories.name, 'Sin Categoría') as name, COALESCE(categories.icon, 'category') as icon, SUM(transactions.amount) as total, SUM(transactions.amount_usd) as total_usd, COUNT(*) as count", false)
            ->join('categories', 'categories.id = transactions.category_id', 'left')
            ->where('transactions.type', 'income')
            ->where('transactions.created_at >=', $startDate . ' 00:00:00')
            ->where('transactions.created_at <=', $endDate . ' 23:59:59')
            ->groupBy(['categories.id', 'categories.name', 'categories.icon'])
            ->orderBy('total', 'DESC')
            ->get()->getResultArray();

        // 4. Savings by Category
        $savingsByCategory = $this->builder()
            ->select("categories.id, COALESCE(categories.name, 'Ahorro / Fondo') as name, COALESCE(categories.icon, 'savings') as icon, SUM(transactions.amount) as total, SUM(transactions.amount_usd) as total_usd, COUNT(*) as count", false)
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
                      COALESCE(SUM(CASE WHEN transactions.type = 'income' THEN transactions.amount ELSE 0 END), 0) as income,
                      COALESCE(SUM(CASE WHEN transactions.type = 'income' THEN transactions.amount_usd ELSE 0 END), 0) as income_usd,
                      COALESCE(SUM(CASE WHEN transactions.type = 'expense' THEN transactions.amount ELSE 0 END), 0) as expense,
                      COALESCE(SUM(CASE WHEN transactions.type = 'expense' THEN transactions.amount_usd ELSE 0 END), 0) as expense_usd,
                      COALESCE(SUM(CASE WHEN transactions.type = 'savings' THEN transactions.amount ELSE 0 END), 0) as savings,
                      COALESCE(SUM(CASE WHEN transactions.type = 'savings' THEN transactions.amount_usd ELSE 0 END), 0) as savings_usd,
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
                      COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as income,
                      COALESCE(SUM(CASE WHEN type = 'income' THEN amount_usd ELSE 0 END), 0) as income_usd,
                      COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as expense,
                      COALESCE(SUM(CASE WHEN type = 'expense' THEN amount_usd ELSE 0 END), 0) as expense_usd,
                      COALESCE(SUM(CASE WHEN type = 'savings' THEN amount ELSE 0 END), 0) as savings,
                      COALESCE(SUM(CASE WHEN type = 'savings' THEN amount_usd ELSE 0 END), 0) as savings_usd", false)
            ->where('created_at >=', $startDate . ' 00:00:00')
            ->where('created_at <=', $endDate . ' 23:59:59')
            ->groupBy('DATE(created_at)')
            ->orderBy('date', 'ASC')
            ->get()->getResultArray();

        // 7. Breakdown by Owner (Negocio vs Personal)
        $byOwner = $this->builder()
            ->select("COALESCE(owner, 'General') as owner,
                      COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as income,
                      COALESCE(SUM(CASE WHEN type = 'income' THEN amount_usd ELSE 0 END), 0) as income_usd,
                      COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as expense,
                      COALESCE(SUM(CASE WHEN type = 'expense' THEN amount_usd ELSE 0 END), 0) as expense_usd,
                      COALESCE(SUM(CASE WHEN type = 'savings' THEN amount ELSE 0 END), 0) as savings,
                      COALESCE(SUM(CASE WHEN type = 'savings' THEN amount_usd ELSE 0 END), 0) as savings_usd,
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
        ];
    }

    public function getDetailedHistory($start, $end)
    {
        return $this->builder()
            ->select('transactions.*, accounts.name as account_name, categories.name as category_name, categories.icon as category_icon')
            ->join('accounts', 'accounts.id = transactions.account_id', 'left')
            ->join('categories', 'categories.id = transactions.category_id', 'left')
            ->where('transactions.created_at >=', $start . ' 00:00:00')
            ->where('transactions.created_at <=', $end . ' 23:59:59')
            ->orderBy('transactions.created_at', 'DESC')
            ->get()->getResultArray();
    }
}
