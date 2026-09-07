<?php

namespace App\Models;

use CodeIgniter\Model;

class OcrInvoiceModel extends Model
{
    protected $table = 'ocr_invoices';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'transaction_id',
        'account_id',
        'category_id',
        'merchant',
        'rif',
        'invoice_number',
        'model_type',
        'model_label',
        'invoice_date',
        'invoice_time',
        'total_bs',
        'total_usd',
        'exchange_rate',
        'subtotal',
        'exento',
        'base_imponible',
        'iva_amount',
        'iva_rate',
        'igtf_amount',
        'payment_method',
        'cashea_amount',
        'owner',
        'items_json',
        'raw_text',
        'status', // 'pending_review', 'approved', 'cancelled'
        'quick_scan',
        'reviewed_at',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get pending review invoices with elapsed hours and 72-hour alert calculation.
     */
    public function getPendingWithAlerts(): array
    {
        $rows = $this->builder()
            ->select('ocr_invoices.*, accounts.name as account_name, accounts.currency as account_currency, categories.name as category_name')
            ->join('accounts', 'accounts.id = ocr_invoices.account_id', 'left')
            ->join('categories', 'categories.id = ocr_invoices.category_id', 'left')
            ->where('ocr_invoices.status', 'pending_review')
            ->orderBy('ocr_invoices.created_at', 'DESC')
            ->get()
            ->getResultArray();

        $now = time();
        foreach ($rows as &$row) {
            $createdTs = !empty($row['created_at']) ? strtotime($row['created_at']) : $now;
            $diffHours = max(0, round(($now - $createdTs) / 3600, 1));
            $row['elapsed_hours'] = $diffHours;
            $row['is_overdue_72h'] = ($diffHours >= 72.0);
            $row['hours_remaining'] = max(0, round(72.0 - $diffHours, 1));
            $row['items'] = !empty($row['items_json']) ? json_decode($row['items_json'], true) : [];
        }
        unset($row);

        return $rows;
    }

    /**
     * Count how many pending invoices have exceeded the 72h limit.
     */
    public function countOverdue72h(): int
    {
        $limitDate = date('Y-m-d H:i:s', strtotime('-72 hours'));
        return $this->where('status', 'pending_review')
            ->where('created_at <=', $limitDate)
            ->countAllResults();
    }

    /**
     * Get scanned invoices history with optional filtering.
     */
    public function getHistory(array $filters = []): array
    {
        $builder = $this->builder()
            ->select('ocr_invoices.*, accounts.name as account_name, accounts.currency as account_currency, categories.name as category_name')
            ->join('accounts', 'accounts.id = ocr_invoices.account_id', 'left')
            ->join('categories', 'categories.id = ocr_invoices.category_id', 'left');

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $builder->where('ocr_invoices.status', $filters['status']);
        }

        if (!empty($filters['merchant'])) {
            $builder->like('ocr_invoices.merchant', $filters['merchant']);
        }

        if (!empty($filters['date_from'])) {
            $builder->where('ocr_invoices.invoice_date >=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $builder->where('ocr_invoices.invoice_date <=', $filters['date_to']);
        }

        $rows = $builder->orderBy('ocr_invoices.created_at', 'DESC')
            ->limit(100)
            ->get()
            ->getResultArray();

        foreach ($rows as &$row) {
            $row['items'] = !empty($row['items_json']) ? json_decode($row['items_json'], true) : [];
        }
        unset($row);

        return $rows;
    }
}
