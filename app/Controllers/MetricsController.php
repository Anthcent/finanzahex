<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\TransactionModel;

class MetricsController extends BaseController
{
    public function index()
    {
        return view('metrics/index');
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
        header("Content-Type: application/csv; "); 

        $file = fopen('php://output', 'w');
        
        // Add BOM for Excel UTF-8 compatibility
        fwrite($file, "\xEF\xBB\xBF");

        // Headers
        fputcsv($file, ['ID', 'Fecha', 'Descripcion', 'Categoria', 'Cuenta', 'Tipo', 'Monto (Bs)', 'Monto ($)', 'Tasa', 'Responsable']);

        foreach ($history as $row) {
            fputcsv($file, [
                $row['id'],
                $row['created_at'],
                $row['description'],
                $row['category_name'] ?? 'Sin Categoría',
                $row['account_name'] ?? 'Sin Cuenta',
                ucfirst($row['type']),
                number_format($row['amount'], 2, ',', '.'),
                number_format($row['amount_usd'], 2, ',', '.'),
                $row['exchange_rate'],
                $row['owner']
            ]);
        }
        fclose($file);
        exit;
    }
}
