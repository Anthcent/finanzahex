<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\TransactionModel;
use App\Models\AccountModel;
use App\Models\CategoryModel;
use App\Models\AuditLogModel;

class HistoryController extends BaseController
{
    public function index()
    {
        $accountModel = new AccountModel();
        $categoryModel = new CategoryModel();
        
        $data = [
            'accounts'   => $accountModel->orderBy('name', 'ASC')->findAll(),
            'categories' => $categoryModel->orderBy('name', 'ASC')->findAll(),
            'owners'     => ['Arianny', 'Anthony', 'Negocio']
        ];

        return view('history/index', $data);
    }

    public function fetch()
    {
        $request = $this->request->getJSON();
        $filters = (array)$request;

        $model = new TransactionModel();
        $records = $model->getFilteredRecords($filters);

        // Compute executive summary for the filtered result set
        $totalIncome = 0.0;
        $totalExpense = 0.0;
        $totalSavings = 0.0;
        $totalInvoicesBs = 0.0;
        $totalInvoicesUsd = 0.0;
        $invoicesCount = 0;

        foreach ($records as $r) {
            $type = $r['type'] ?? '';
            $amountBs = (float)($r['amount'] ?? 0);
            $amountUsd = (float)($r['amount_usd'] ?? 0);

            if (in_array($type, ['income', 'return', 'exchange_in', 'transfer_in'])) {
                $totalIncome += $amountBs;
            } elseif (in_array($type, ['expense', 'exchange_out', 'transfer_out'])) {
                $totalExpense += $amountBs;
            } elseif ($type === 'savings') {
                $totalSavings += $amountBs;
            }

            if (!empty($r['has_invoice']) && $r['has_invoice']) {
                $invoicesCount++;
                $totalInvoicesBs += $amountBs;
                $totalInvoicesUsd += $amountUsd;
            }
        }

        $summary = [
            'total_income'       => $totalIncome,
            'total_expense'      => $totalExpense,
            'total_savings'      => $totalSavings,
            'net_balance'        => ($totalIncome - $totalExpense),
            'invoices_count'     => $invoicesCount,
            'total_invoices_bs'  => $totalInvoicesBs,
            'total_invoices_usd' => $totalInvoicesUsd,
            'total_records'      => count($records),
        ];

        return $this->response->setJSON([
            'status'  => 'success',
            'data'    => $records,
            'summary' => $summary
        ]);
    }
    
    public function delete($id)
    {
         $model = new TransactionModel();
         $accountModel = new AccountModel();
         
         $db = \Config\Database::connect();
         $db->transStart();

         try {
             $transaction = $model->find($id);
             
             if (!$transaction) {
                 return $this->response->setJSON(['status' => 'error', 'message' => 'Transacción no encontrada']);
             }

             // 1. Calculate Amount to Revert
             // If amount (Bs) is > 0, use it. Otherwise use amount_usd (for USD logic)
             $amountToRevert = ($transaction['amount'] > 0) ? $transaction['amount'] : $transaction['amount_usd'];
             
             $account = $accountModel->find($transaction['account_id']);
             if ($account) {
                 $currentBalance = $account['balance'];
                 $newBalance = $currentBalance;
                 $type = $transaction['type'];

                 // 2. Apply Reversal Logic
                 // If original action reduced balance (Expense, Outgoing), we ADD it back.
                 // If original action increased balance (Income, Incoming), we SUBTRACT it.
                 if (in_array($type, ['expense', 'savings', 'exchange_out', 'transfer_out'])) {
                     $newBalance += $amountToRevert;
                 } elseif (in_array($type, ['income', 'return', 'exchange_in', 'transfer_in'])) {
                     $newBalance -= $amountToRevert;
                 }

                 // 3. Update Account
                 $accountModel->update($transaction['account_id'], ['balance' => $newBalance]);

                 // Log the balance change for audit
                 $auditImpact = [
                    'account_id'      => $transaction['account_id'],
                    'balance_before'  => $currentBalance,
                    'balance_after'   => $newBalance,
                    'reverted_amount' => $amountToRevert
                 ];
             }

             // 4. Update linked OCR Invoice status if exists
             if ($db->tableExists('ocr_invoices')) {
                 $db->table('ocr_invoices')
                    ->where('transaction_id', $id)
                    ->update([
                        'status'     => 'cancelled',
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
             }

             // 5. Delete Transaction Items if exists
             if ($db->tableExists('transaction_items')) {
                 $db->table('transaction_items')
                    ->where('transaction_id', $id)
                    ->delete();
             }

             // 6. Delete Transaction
             $model->delete($id);
             
             // 7. Audit Log
             AuditLogModel::log('transactions', 'delete', $id, $transaction, null, $auditImpact ?? [], "Eliminación con reverso de saldo");

             $db->transComplete();

             if ($db->transStatus() === false) {
                 throw new \Exception('Error al procesar la eliminación');
             }

             return $this->response->setJSON(['status' => 'success']);

         } catch (\Exception $e) {
             $db->transRollback();
             return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
         }
    }
    
    public function getItems($transactionId)
    {
        $db = \Config\Database::connect();
        
        // 1. Get from transaction_items
        $items = [];
        if ($db->tableExists('transaction_items')) {
            $items = $db->table('transaction_items')
                        ->where('transaction_id', $transactionId)
                        ->get()
                        ->getResultArray();
        }
        
        // 2. Get from ocr_invoices if linked
        $invoice = null;
        if ($db->tableExists('ocr_invoices')) {
            $invoice = $db->table('ocr_invoices')
                          ->where('transaction_id', $transactionId)
                          ->get()
                          ->getRowArray();
        }

        // Fallback: If transaction_items is empty but invoice has items_json
        if (empty($items) && !empty($invoice['items_json'])) {
            $decoded = json_decode($invoice['items_json'], true);
            if (is_array($decoded)) {
                foreach ($decoded as $it) {
                    $items[] = [
                        'id'             => $it['id'] ?? null,
                        'transaction_id' => $transactionId,
                        'name'           => $it['name'] ?? 'Producto / Servicio',
                        'description'    => !empty($it['tax_type']) ? "IVA: {$it['tax_type']}" : ($it['description'] ?? ''),
                        'quantity'       => $it['quantity'] ?? 1,
                        'price'          => $it['price'] ?? 0,
                        'price_usd'      => $it['price_usd'] ?? 0,
                    ];
                }
            }
        }
        
        return $this->response->setJSON([
            'status'  => 'success', 
            'items'   => $items,
            'invoice' => $invoice
        ]);
    }
}
