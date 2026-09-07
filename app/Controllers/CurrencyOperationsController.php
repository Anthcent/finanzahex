<?php

namespace App\Controllers;

use App\Libraries\CurrencyOperationCalculator;
use App\Models\AccountModel;
use App\Models\AuditLogModel;
use App\Models\CategoryModel;
use App\Models\CurrencyOperationModel;
use App\Models\TransactionModel;
use Throwable;

class CurrencyOperationsController extends BaseController
{
    public function index()
    {
        $accountModel = new AccountModel();
        $operationModel = new CurrencyOperationModel();
        $accounts = $accountModel->where('status', 'active')->orderBy('name')->findAll();
        $accountNames = [];
        foreach ($accounts as $account) {
            $accountNames[(int) $account['id']] = $account['name'];
        }

        $type = trim((string) $this->request->getGet('type'));
        $status = trim((string) $this->request->getGet('status'));
        $query = trim((string) $this->request->getGet('q'));
        if (in_array($type, ['purchase', 'transfer'], true)) {
            $operationModel->where('operation_type', $type);
        }
        if (in_array($status, ['completed', 'reversed'], true)) {
            $operationModel->where('status', $status);
        }
        if ($query !== '') {
            $operationModel->groupStart()->like('reference', $query)->orLike('notes', $query)->groupEnd();
        }
        $operations = $operationModel->orderBy('operation_date', 'DESC')->orderBy('id', 'DESC')->findAll(100);
        foreach ($operations as &$operation) {
            $operation['source_name'] = $accountNames[(int) $operation['source_account_id']] ?? 'Cuenta eliminada';
            $operation['destination_name'] = $accountNames[(int) $operation['destination_account_id']] ?? 'Cuenta eliminada';
        }
        unset($operation);

        $rateRow = \Config\Database::connect()->table('settings')->select('value')->where('key', 'bcv_usd_rate')->get()->getRowArray();

        return view('currency_operations/index', [
            'accounts' => $accounts,
            'operations' => $operations,
            'filters' => ['type' => $type, 'status' => $status, 'q' => $query],
            'exchangeRate' => max(0, (float) ($rateRow['value'] ?? 0)),
        ]);
    }

    public function store()
    {
        $payload = $this->request->getJSON(true) ?: $this->request->getPost();
        $requestId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($payload['request_id'] ?? ''));
        if ($requestId === '' || strlen($requestId) > 64) {
            return $this->error('No se pudo identificar la solicitud. Recarga e intenta nuevamente.');
        }

        $operationModel = new CurrencyOperationModel();
        $existing = $operationModel->where('request_id', $requestId)->first();
        if ($existing) {
            return $this->response->setJSON(['status' => 'success', 'id' => $existing['id'], 'duplicate' => true]);
        }

        $type = (string) ($payload['operation_type'] ?? '');
        if (!in_array($type, ['purchase', 'transfer'], true)) {
            return $this->error('Tipo de operación inválido.');
        }

        $sourceId = (int) ($payload['source_account_id'] ?? 0);
        $destinationId = (int) ($payload['destination_account_id'] ?? 0);
        if (!$sourceId || !$destinationId || $sourceId === $destinationId) {
            return $this->error('Selecciona cuentas de origen y destino diferentes.');
        }

        $db = \Config\Database::connect();
        $db->transBegin();
        try {
            $accounts = $this->lockAccounts($sourceId, $destinationId);
            $source = $accounts[$sourceId] ?? null;
            $destination = $accounts[$destinationId] ?? null;
            if (!$source || !$destination || $source['status'] !== 'active' || $destination['status'] !== 'active') {
                throw new \RuntimeException('Una de las cuentas no existe o está inactiva.');
            }

            $amountUsd = round((float) ($payload['amount_usd'] ?? 0), 2);
            $quotedRate = round((float) ($payload['quoted_rate'] ?? 0), 4);
            if ($amountUsd <= 0) {
                throw new \InvalidArgumentException('El monto en dólares debe ser mayor que cero.');
            }

            if ($type === 'purchase') {
                if (strtoupper((string) $source['currency']) === 'USD' || strtoupper((string) $destination['currency']) !== 'USD') {
                    throw new \InvalidArgumentException('La compra debe salir de una cuenta en bolívares y llegar a una cuenta USD.');
                }
                if ($quotedRate <= 0) {
                    throw new \InvalidArgumentException('La tasa acordada debe ser mayor que cero.');
                }
                $values = CurrencyOperationCalculator::purchase(
                    (float) ($payload['subtotal_bs'] ?? 0),
                    (float) ($payload['commission_percent'] ?? 0),
                    $amountUsd
                );
                if ((float) $source['balance'] < $values['total_bs']) {
                    throw new \RuntimeException('Saldo insuficiente: el origen no cubre el total más la comisión.');
                }
                $sourceDebit = $values['total_bs'];
            } else {
                if (strtoupper((string) $source['currency']) !== 'USD' || strtoupper((string) $destination['currency']) !== 'USD') {
                    throw new \InvalidArgumentException('El movimiento debe realizarse entre dos cuentas USD.');
                }
                if ((float) $source['balance'] < $amountUsd) {
                    throw new \RuntimeException('Saldo USD insuficiente en la cuenta de origen.');
                }
                $values = [
                    'subtotal_bs' => 0, 'commission_percent' => 0, 'commission_bs' => 0,
                    'total_bs' => 0, 'amount_usd' => $amountUsd, 'effective_rate' => 0,
                ];
                $sourceDebit = $amountUsd;
            }

            $operationDate = $this->validDate((string) ($payload['operation_date'] ?? ''));
            $operationId = $operationModel->insert(array_merge($values, [
                'request_id' => $requestId,
                'operation_type' => $type,
                'source_account_id' => $sourceId,
                'destination_account_id' => $destinationId,
                'quoted_rate' => $quotedRate,
                'reference' => trim((string) ($payload['reference'] ?? '')) ?: null,
                'notes' => trim((string) ($payload['notes'] ?? '')) ?: null,
                'owner' => trim((string) ($payload['owner'] ?? 'Negocio')) ?: 'Negocio',
                'status' => 'completed',
                'operation_date' => $operationDate,
            ]), true);
            if (!$operationId) {
                throw new \RuntimeException('No se pudo registrar la operación.');
            }

            $db->table('accounts')->where('id', $sourceId)->update(['balance' => (float) $source['balance'] - $sourceDebit]);
            $db->table('accounts')->where('id', $destinationId)->update(['balance' => (float) $destination['balance'] + $amountUsd]);

            $categoryId = $this->categoryId();
            $transactionModel = new TransactionModel();
            $rate = $values['effective_rate'] > 0 ? $values['effective_rate'] : $quotedRate;
            $label = $type === 'purchase' ? 'Compra de divisas' : 'Movimiento de divisas';
            $transactionModel->insert([
                'account_id' => $sourceId, 'category_id' => $categoryId, 'currency_operation_id' => $operationId,
                'amount' => $type === 'purchase' ? $sourceDebit : 0, 'amount_usd' => $type === 'transfer' ? $amountUsd : 0,
                'exchange_rate' => $rate, 'type' => $type === 'purchase' ? 'exchange_out' : 'transfer_out',
                'owner' => $payload['owner'] ?? 'Negocio', 'description' => "$label hacia {$destination['name']}", 'created_at' => $operationDate,
            ]);
            $transactionModel->insert([
                'account_id' => $destinationId, 'category_id' => $categoryId, 'currency_operation_id' => $operationId,
                'amount' => 0, 'amount_usd' => $amountUsd, 'exchange_rate' => $rate,
                'type' => $type === 'purchase' ? 'exchange_in' : 'transfer_in',
                'owner' => $payload['owner'] ?? 'Negocio', 'description' => "$label desde {$source['name']}", 'created_at' => $operationDate,
            ]);

            if ($db->transStatus() === false) {
                throw new \RuntimeException('No se pudo confirmar la operación.');
            }
            $db->transCommit();
            AuditLogModel::log('currency_operations', 'create', $operationId, null, $payload, [
                'source_delta' => -$sourceDebit, 'destination_delta' => $amountUsd,
            ], $label);

            return $this->response->setJSON(['status' => 'success', 'id' => $operationId]);
        } catch (Throwable $e) {
            $db->transRollback();
            $existing = $operationModel->where('request_id', $requestId)->first();
            if ($existing) {
                return $this->response->setJSON(['status' => 'success', 'id' => $existing['id'], 'duplicate' => true]);
            }
            return $this->error($e->getMessage());
        }
    }

    public function reverse(int $id)
    {
        $model = new CurrencyOperationModel();
        $db = \Config\Database::connect();
        $db->transBegin();
        try {
            $operation = $model->find($id);
            if (!$operation || $operation['status'] !== 'completed') {
                throw new \RuntimeException('La operación no existe o ya fue anulada.');
            }
            $sourceId = (int) $operation['source_account_id'];
            $destinationId = (int) $operation['destination_account_id'];
            $accounts = $this->lockAccounts($sourceId, $destinationId);
            $source = $accounts[$sourceId] ?? null;
            $destination = $accounts[$destinationId] ?? null;
            $amountUsd = (float) $operation['amount_usd'];
            if (!$source || !$destination || (float) $destination['balance'] < $amountUsd) {
                throw new \RuntimeException('No se puede anular: la cuenta destino ya no tiene suficientes USD.');
            }
            $refund = $operation['operation_type'] === 'purchase' ? (float) $operation['total_bs'] : $amountUsd;
            $db->table('accounts')->where('id', $sourceId)->update(['balance' => (float) $source['balance'] + $refund]);
            $db->table('accounts')->where('id', $destinationId)->update(['balance' => (float) $destination['balance'] - $amountUsd]);
            $model->update($id, ['status' => 'reversed', 'reversed_at' => date('Y-m-d H:i:s')]);

            $categoryId = $this->categoryId();
            $transactions = new TransactionModel();
            $transactions->insert([
                'account_id' => $sourceId, 'category_id' => $categoryId, 'currency_operation_id' => $id,
                'amount' => $operation['operation_type'] === 'purchase' ? $refund : 0,
                'amount_usd' => $operation['operation_type'] === 'transfer' ? $amountUsd : 0,
                'exchange_rate' => $operation['effective_rate'] ?: $operation['quoted_rate'], 'type' => 'currency_reversal_in',
                'owner' => $operation['owner'], 'description' => "Anulación de operación de divisas #$id",
            ]);
            $transactions->insert([
                'account_id' => $destinationId, 'category_id' => $categoryId, 'currency_operation_id' => $id,
                'amount' => 0, 'amount_usd' => $amountUsd,
                'exchange_rate' => $operation['effective_rate'] ?: $operation['quoted_rate'], 'type' => 'currency_reversal_out',
                'owner' => $operation['owner'], 'description' => "Anulación de operación de divisas #$id",
            ]);
            if ($db->transStatus() === false) {
                throw new \RuntimeException('No se pudo confirmar la anulación.');
            }
            $db->transCommit();
            AuditLogModel::log('currency_operations', 'reverse', $id, $operation, null, ['source_refund' => $refund, 'destination_debit' => $amountUsd], "Anulación de divisas #$id");
            return $this->response->setJSON(['status' => 'success']);
        } catch (Throwable $e) {
            $db->transRollback();
            return $this->error($e->getMessage());
        }
    }

    private function lockAccounts(int $sourceId, int $destinationId): array
    {
        $db = \Config\Database::connect();
        $suffix = $db->DBDriver === 'SQLite3' ? '' : ' FOR UPDATE';
        $rows = $db->query('SELECT * FROM accounts WHERE id IN (?, ?) ORDER BY id' . $suffix, [$sourceId, $destinationId])->getResultArray();
        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['id']] = $row;
        }
        return $result;
    }

    private function categoryId(): int
    {
        $category = (new CategoryModel())->orderBy('id')->first();
        if (!$category) {
            throw new \RuntimeException('Crea al menos una categoría antes de registrar divisas.');
        }
        return (int) $category['id'];
    }

    private function validDate(string $value): string
    {
        $timestamp = $value !== '' ? strtotime($value) : false;
        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : date('Y-m-d H:i:s');
    }

    private function error(string $message)
    {
        return $this->response->setStatusCode(422)->setJSON(['status' => 'error', 'message' => $message]);
    }
}
