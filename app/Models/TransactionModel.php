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
                             ->like('created_at', $today)
                             ->selectSum('amount')->first()['amount'] ?? 0;

        return [
            'balance' => $accountBalance,
            'today_expense' => $todayExpense,
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
        $builder = $this->builder();
        
        // Totals
        $totals = $this->builder()
            ->select("SUM(IF(type='income', amount, 0)) as income, SUM(IF(type='expense', amount, 0)) as expense, SUM(IF(type='savings', amount, 0)) as savings")
            ->where('created_at >=', $startDate . ' 00:00:00')
            ->where('created_at <=', $endDate . ' 23:59:59')
            ->get()->getRowArray();

        // By Category (Expenses)
        $byCategory = $this->builder()
            ->select('categories.name, SUM(transactions.amount) as total')
            ->join('categories', 'categories.id = transactions.category_id', 'left')
            ->where('transactions.type', 'expense')
            ->where('transactions.created_at >=', $startDate . ' 00:00:00')
            ->where('transactions.created_at <=', $endDate . ' 23:59:59')
            ->groupBy('transactions.category_id')
            ->orderBy('total', 'DESC')
            ->get()->getResultArray();

        // By Owner (Expenses)
        $byOwner = $this->builder()
            ->select('owner, SUM(amount) as total')
            ->where('type', 'expense')
            ->where('created_at >=', $startDate . ' 00:00:00')
            ->where('created_at <=', $endDate . ' 23:59:59')
            ->groupBy('owner')
            ->get()->getResultArray();

        // Trends (Daily)
        $dailyTrend = $this->builder()
            ->select("DATE(created_at) as date, SUM(IF(type='income', amount, 0)) as income, SUM(IF(type='expense', amount, 0)) as expense")
            ->where('created_at >=', $startDate . ' 00:00:00')
            ->where('created_at <=', $endDate . ' 23:59:59')
            ->groupBy('DATE(created_at)')
            ->orderBy('date', 'ASC')
            ->get()->getResultArray();

        return [
            'totals' => $totals,
            'by_category' => $byCategory,
            'by_owner' => $byOwner,
            'trends' => $dailyTrend,
            'income_by_owner' => $this->builder()->select('owner, SUM(amount) as total')->where('type', 'income')->where('created_at >=', $startDate . ' 00:00:00')->where('created_at <=', $endDate . ' 23:59:59')->groupBy('owner')->get()->getResultArray()
        ];
    }
    public function getDetailedHistory($start, $end)
    {
        return $this->builder()
            ->select('transactions.*, accounts.name as account_name, categories.name as category_name')
            ->join('accounts', 'accounts.id = transactions.account_id', 'left')
            ->join('categories', 'categories.id = transactions.category_id', 'left')
            ->where('transactions.created_at >=', $start . ' 00:00:00')
            ->where('transactions.created_at <=', $end . ' 23:59:59')
            ->orderBy('transactions.created_at', 'DESC')
            ->get()->getResultArray();
    }
}
