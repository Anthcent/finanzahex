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
            'accounts' => $accountModel->findAll(),
            'categories' => $categoryModel->findAll(),
            'owners' => ['Arianny', 'Anthony', 'Negocio']
        ];

        return view('history/index', $data);
    }

    public function fetch()
    {
        $request = $this->request->getJSON();
        $filters = (array)$request;

        $model = new TransactionModel();
        $records = $model->getFilteredRecords($filters);

        return $this->response->setJSON(['status' => 'success', 'data' => $records]);
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
                    'account_id' => $transaction['account_id'],
                    'balance_before' => $currentBalance,
                    'balance_after' => $newBalance,
                    'reverted_amount' => $amountToRevert
                 ];
             }

             // 4. Delete Transaction
             $model->delete($id);
             
             // 5. Audit Log
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
        $builder = $db->table('transaction_items');
        $items = $builder->where('transaction_id', $transactionId)->get()->getResultArray();
        
        return $this->response->setJSON(['status' => 'success', 'items' => $items]);
    }
}
