<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\TransactionModel;
use App\Models\AccountModel;
use App\Models\CategoryModel;

class MetricsController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        $accountModel = new AccountModel();
        $categoryModel = new CategoryModel();

        $accounts = $accountModel->where('status', 'active')->orderBy('name', 'ASC')->findAll();
        $categories = $categoryModel->orderBy('name', 'ASC')->findAll();

        $rateRow = $db->table('settings')->where('key', 'bcv_usd_rate')->get()->getRowArray();
        $exchangeRate = (float) ($rateRow['value'] ?? 50.0);

        return view('metrics/index', [
            'accounts' => $accounts,
            'categories' => $categories,
            'exchangeRate' => $exchangeRate,
        ]);
    }

    public function fetch()
    {
        $json = $this->request->getJSON();
        $start = $json->start ?? date('Y-m-01');
        $end = $json->end ?? date('Y-m-t');

        $model = new TransactionModel();
        $metrics = $model->getMetricsData($start, $end);
        $history = $model->getDetailedHistory($start, $end);

        return $this->response->setJSON([
            'status' => 'success', 
            'data' => $metrics,
            'history' => $history
        ]);
    }

    public function export()
    {
        $start = $this->request->getGet('start') ?? date('Y-m-01');
        $end = $this->request->getGet('end') ?? date('Y-m-t');

        $model = new TransactionModel();
        $history = $model->getDetailedHistory($start, $end);

        $filename = 'reporte_financiero_' . $start . '_al_' . $end . '.csv';
        
        header("Content-Description: File Transfer");
        header("Content-Disposition: attachment; filename=$filename");
        header("Content-Type: application/csv; charset=UTF-8"); 

        $file = fopen('php://output', 'w');
        
        // Add BOM for Excel UTF-8 compatibility
        fwrite($file, "\xEF\xBB\xBF");

        // Headers
        fputcsv($file, ['ID', 'Fecha', 'Descripcion', 'Categoria', 'Cuenta', 'Tipo', 'Monto (Bs)', 'Monto ($ USD)', 'Tasa', 'Responsable']);

        $typeLabels = [
            'income' => 'Ingreso',
            'expense' => 'Gasto',
            'savings' => 'Ahorro',
        ];

        foreach ($history as $row) {
            $type = $row['type'] ?? 'expense';
            $typeLabel = $typeLabels[$type] ?? ucfirst($type);

            fputcsv($file, [
                $row['id'],
                $row['created_at'],
                $row['description'],
                $row['category_name'] ?? 'Sin Categoría',
                $row['account_name'] ?? 'Sin Cuenta',
                $typeLabel,
                number_format((float) ($row['display_amount_bs'] ?? $row['amount'] ?? 0), 2, ',', '.'),
                number_format((float) ($row['display_amount_usd'] ?? $row['amount_usd'] ?? 0), 2, '.', ''),
                number_format((float) ($row['exchange_rate'] ?? 0), 2, ',', '.'),
                $row['owner'] ?? 'General'
            ]);
        }
        fclose($file);
        exit;
    }
}
