<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\TransactionValue;

class DataManagerController extends BaseController
{
    private const SESSION_KEY = 'data_manager_unlocked';
    private const GROUPS = [
        'transactions' => ['label' => 'Movimientos financieros', 'table' => 'transactions', 'icon' => 'receipt_long'],
        'printing' => ['label' => 'Órdenes de impresión', 'table' => 'print_orders', 'icon' => 'print'],
        'ocr' => ['label' => 'Facturas OCR', 'table' => 'ocr_invoices', 'icon' => 'document_scanner'],
        'sales' => ['label' => 'Ventas', 'table' => 'sales', 'icon' => 'storefront'],
        'currency' => ['label' => 'Operaciones en divisas', 'table' => 'currency_operations', 'icon' => 'currency_exchange'],
        'inventory' => ['label' => 'Movimientos de inventario', 'table' => 'inventory_movements', 'icon' => 'inventory_2'],
        'ai' => ['label' => 'Conversaciones IA', 'table' => 'ai_conversations', 'icon' => 'smart_toy'],
        'audit' => ['label' => 'Bitácora', 'table' => 'audit_logs', 'icon' => 'policy'],
    ];

    public function index()
    {
        return view('config/data_manager');
    }

    public function login()
    {
        $payload = $this->request->getJSON(true) ?? [];
        $session = session();
        $lockedUntil = (int) $session->get('data_manager_locked_until');
        if ($lockedUntil > time()) return $this->error('Demasiados intentos. Espera un minuto.', 429);

        $configuredPin = (string) env('dataManager.pin', '1397');
        if (!hash_equals($configuredPin, (string) ($payload['pin'] ?? ''))) {
            $attempts = (int) $session->get('data_manager_attempts') + 1;
            $session->set('data_manager_attempts', $attempts);
            if ($attempts >= 5) {
                $session->set('data_manager_locked_until', time() + 60);
                $session->remove('data_manager_attempts');
            }
            return $this->error('PIN incorrecto.', 403);
        }

        $session->regenerate(true);
        $session->set(self::SESSION_KEY, true);
        $session->remove(['data_manager_attempts', 'data_manager_locked_until']);
        return $this->response->setJSON(['status' => 'success']);
    }

    public function logout()
    {
        session()->remove(self::SESSION_KEY);
        return $this->response->setJSON(['status' => 'success']);
    }

    public function summary()
    {
        if (!$this->authorized()) return $this->error('Acceso bloqueado.', 401);
        $db = \Config\Database::connect();
        $groups = [];
        foreach (self::GROUPS as $key => $meta) {
            $groups[] = [
                'key' => $key, 'label' => $meta['label'], 'icon' => $meta['icon'],
                'count' => $db->tableExists($meta['table']) ? $db->table($meta['table'])->countAllResults() : 0,
            ];
        }
        return $this->response->setJSON(['status' => 'success', 'groups' => $groups]);
    }

    public function records()
    {
        if (!$this->authorized()) return $this->error('Acceso bloqueado.', 401);
        $group = (string) $this->request->getGet('group');
        $search = trim((string) $this->request->getGet('search'));
        if (!isset(self::GROUPS[$group])) return $this->error('Grupo no válido.');

        $db = \Config\Database::connect();
        $table = self::GROUPS[$group]['table'];
        if (!$db->tableExists($table)) return $this->response->setJSON(['status' => 'success', 'records' => []]);
        $builder = $db->table($table)->limit(200)->orderBy('id', 'DESC');
        $searchFields = $this->searchFields($group);
        if ($search !== '' && $searchFields) {
            $builder->groupStart();
            foreach ($searchFields as $index => $field) $index === 0 ? $builder->like($field, $search) : $builder->orLike($field, $search);
            $builder->groupEnd();
        }
        $rows = $builder->get()->getResultArray();
        return $this->response->setJSON([
            'status' => 'success',
            'records' => array_map(fn(array $row) => $this->normalize($group, $row), $rows),
        ]);
    }

    public function deleteSelected()
    {
        if (!$this->authorized()) return $this->error('Acceso bloqueado.', 401);
        $payload = $this->request->getJSON(true) ?? [];
        $group = (string) ($payload['group'] ?? '');
        $ids = array_values(array_unique(array_filter(array_map('intval', $payload['ids'] ?? []))));
        if (!isset(self::GROUPS[$group]) || !$ids) return $this->error('No hay registros válidos seleccionados.');
        if (($payload['confirmation'] ?? '') !== 'ELIMINAR') return $this->error('Confirmación inválida.');

        try {
            $deleted = $this->deleteGroup($group, $ids);
            return $this->response->setJSON(['status' => 'success', 'deleted' => $deleted]);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function purge()
    {
        if (!$this->authorized()) return $this->error('Acceso bloqueado.', 401);
        $payload = $this->request->getJSON(true) ?? [];
        $group = (string) ($payload['group'] ?? '');
        $required = $group === 'all' ? 'LIMPIAR TODO' : 'ELIMINAR';
        if (($payload['confirmation'] ?? '') !== $required) return $this->error('Escribe la confirmación exacta.');

        $db = \Config\Database::connect();
        try {
            if ($group === 'all') {
                $db->transBegin();
                $this->deleteTables([
                    'transaction_items', 'sale_details', 'sale_payments', 'account_transfers',
                    'ocr_invoices', 'transactions', 'currency_operations', 'print_orders',
                    'sales', 'inventory_movements', 'ai_conversations', 'audit_logs',
                ]);
                if ($db->tableExists('accounts')) {
                    $db->table('accounts')->where('type', 'temporary')->delete();
                    $db->table('accounts')->where('type !=', 'temporary')->update(['balance' => 0, 'initial_balance' => 0]);
                }
                if ($db->tableExists('inventory_items')) $db->table('inventory_items')->where('id >', 0)->update(['stock' => 0]);
                if ($db->transStatus() === false) throw new \RuntimeException('No se pudo completar la limpieza total.');
                $db->transCommit();
                return $this->response->setJSON(['status' => 'success']);
            }
            if (!isset(self::GROUPS[$group])) return $this->error('Grupo no válido.');
            $table = self::GROUPS[$group]['table'];
            $ids = $db->tableExists($table) ? array_column($db->table($table)->select('id')->get()->getResultArray(), 'id') : [];
            $deleted = $ids ? $this->deleteGroup($group, array_map('intval', $ids)) : 0;
            return $this->response->setJSON(['status' => 'success', 'deleted' => $deleted]);
        } catch (\Throwable $e) {
            $db->transRollback();
            return $this->error($e->getMessage(), 500);
        }
    }

    private function deleteGroup(string $group, array $ids): int
    {
        $db = \Config\Database::connect();
        $db->transBegin();
        try {
            if ($group === 'transactions') {
                $this->deleteTransactions($ids);
            } elseif ($group === 'printing') {
                $transactionIds = $db->table('transactions')->select('id')->whereIn('print_order_id', $ids)->get()->getResultArray();
                $this->deleteTransactions(array_column($transactionIds, 'id'));
                $db->table('print_orders')->whereIn('id', $ids)->delete();
            } elseif ($group === 'ocr') {
                $transactionIds = $db->table('ocr_invoices')->select('transaction_id')->whereIn('id', $ids)->get()->getResultArray();
                $this->deleteTransactions(array_filter(array_column($transactionIds, 'transaction_id')));
                $db->table('ocr_invoices')->whereIn('id', $ids)->delete();
            } elseif ($group === 'currency') {
                $transactionIds = $db->table('transactions')->select('id')->whereIn('currency_operation_id', $ids)->get()->getResultArray();
                $this->deleteTransactions(array_column($transactionIds, 'id'));
                $db->table('currency_operations')->whereIn('id', $ids)->delete();
            } elseif ($group === 'sales') {
                $transactionIds = [];
                foreach ($ids as $id) {
                    $matches = $db->table('transactions')->select('id')->groupStart()
                        ->where('description', "Venta #{$id}")
                        ->orLike('description', "Venta #{$id} -", 'after')
                        ->orLike('description', "Abono venta #{$id} -", 'after')
                        ->groupEnd()->get()->getResultArray();
                    $transactionIds = array_merge($transactionIds, array_column($matches, 'id'));
                }
                $this->deleteTransactions($transactionIds);
                foreach (['sale_details', 'sale_payments'] as $table) if ($db->tableExists($table)) $db->table($table)->whereIn('sale_id', $ids)->delete();
                $db->table('sales')->whereIn('id', $ids)->delete();
            } else {
                $table = self::GROUPS[$group]['table'];
                if ($db->tableExists($table)) $db->table($table)->whereIn('id', $ids)->delete();
            }
            if ($db->transStatus() === false) throw new \RuntimeException('La base de datos rechazó la eliminación.');
            $db->transCommit();
            return count($ids);
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    private function deleteTransactions(array $ids): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) return;
        $db = \Config\Database::connect();
        $rows = $db->table('transactions')->whereIn('id', $ids)->get()->getResultArray();

        // A transfer/currency operation is atomic: include its other ledger side.
        foreach ($rows as $row) {
            if (!empty($row['transfer_group_id'])) {
                $ids = array_merge($ids, array_column($db->table('transactions')->select('id')->where('transfer_group_id', $row['transfer_group_id'])->get()->getResultArray(), 'id'));
            }
            if (!empty($row['currency_operation_id'])) {
                $ids = array_merge($ids, array_column($db->table('transactions')->select('id')->where('currency_operation_id', $row['currency_operation_id'])->get()->getResultArray(), 'id'));
            }
        }
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $rows = $db->table('transactions')->whereIn('id', $ids)->get()->getResultArray();
        $transferGroups = array_values(array_unique(array_filter(array_column($rows, 'transfer_group_id'))));
        $currencyOperations = array_values(array_unique(array_filter(array_column($rows, 'currency_operation_id'))));
        $rateRow = $db->tableExists('settings') ? $db->table('settings')->where('key', 'bcv_usd_rate')->get()->getRowArray() : null;
        $fallbackRate = max(1.0, (float) ($rateRow['value'] ?? 1));
        foreach ($rows as $row) {
            $account = $db->table('accounts')->where('id', $row['account_id'])->get()->getRowArray();
            if (!$account || ($account['status'] ?? '') === 'deleted') continue;
            $native = strtoupper($account['currency'] ?? 'BS') === 'USD'
                ? TransactionValue::inDollars($row, $fallbackRate)
                : TransactionValue::inBolivars($row, $fallbackRate);
            $reduced = in_array($row['type'], ['expense', 'savings', 'exchange_out', 'transfer_out', 'currency_reversal_out'], true);
            $increased = in_array($row['type'], ['income', 'return', 'exchange_in', 'transfer_in', 'currency_reversal_in'], true);
            $balance = (float) $account['balance'] + ($reduced ? $native : ($increased ? -$native : 0));
            $db->table('accounts')->where('id', $account['id'])->update(['balance' => round($balance, 2)]);

            if (!empty($row['print_order_id']) && $db->tableExists('print_orders')) {
                $order = $db->table('print_orders')->where('id', $row['print_order_id'])->get()->getRowArray();
                if ($order) {
                    $paidBs = max(0, (float) $order['paid_bs'] - TransactionValue::inBolivars($row, $fallbackRate));
                    $paidUsd = max(0, (float) $order['paid_usd'] - TransactionValue::inDollars($row, $fallbackRate));
                    $status = $paidBs <= 0.009 && $paidUsd <= 0.009 ? 'pending' : 'partial';
                    $db->table('print_orders')->where('id', $order['id'])->update(['paid_bs' => $paidBs, 'paid_usd' => $paidUsd, 'status' => $status]);
                }
            }
        }
        if ($db->tableExists('transaction_items')) $db->table('transaction_items')->whereIn('transaction_id', $ids)->delete();
        if ($db->tableExists('ocr_invoices')) $db->table('ocr_invoices')->whereIn('transaction_id', $ids)->update(['transaction_id' => null, 'status' => 'cancelled']);
        if ($db->tableExists('print_orders')) $db->table('print_orders')->whereIn('transaction_id', $ids)->update(['transaction_id' => null]);
        $db->table('transactions')->whereIn('id', $ids)->delete();
        if ($transferGroups && $db->tableExists('account_transfers')) $db->table('account_transfers')->whereIn('request_id', $transferGroups)->delete();
        if ($currencyOperations && $db->tableExists('currency_operations')) $db->table('currency_operations')->whereIn('id', $currencyOperations)->delete();
    }

    private function deleteTables(array $tables): void
    {
        $db = \Config\Database::connect();
        foreach ($tables as $table) {
            if ($db->tableExists($table)) $db->table($table)->where('id >', 0)->delete();
        }
    }

    private function normalize(string $group, array $row): array
    {
        $titleFields = [
            'transactions' => 'description', 'printing' => 'customer_name', 'ocr' => 'merchant',
            'sales' => 'customer', 'currency' => 'operation_type', 'inventory' => 'reference',
            'ai' => 'title', 'audit' => 'user_note',
        ];
        $dateFields = ['sales' => 'date', 'currency' => 'operation_date'];
        $amountFields = ['transactions' => 'amount', 'printing' => 'total_bs', 'ocr' => 'total_bs', 'sales' => 'amount', 'currency' => 'total_bs', 'inventory' => 'quantity'];
        return [
            'id' => (int) $row['id'],
            'title' => (string) ($row[$titleFields[$group]] ?? "Registro #{$row['id']}"),
            'subtitle' => $this->subtitle($group, $row),
            'date' => (string) ($row[$dateFields[$group] ?? 'created_at'] ?? ''),
            'amount' => isset($amountFields[$group]) ? (float) ($row[$amountFields[$group]] ?? 0) : null,
        ];
    }

    private function subtitle(string $group, array $row): string
    {
        return match ($group) {
            'transactions' => ucfirst((string) ($row['type'] ?? 'movimiento')),
            'printing' => 'Orden ' . ucfirst((string) ($row['status'] ?? '')),
            'ocr' => 'Factura ' . ucfirst((string) ($row['status'] ?? '')),
            'sales' => (string) ($row['product'] ?? 'Venta'),
            'currency' => 'Operación en divisas',
            'inventory' => ucfirst((string) ($row['type'] ?? 'movimiento')),
            'audit' => (string) (($row['module'] ?? 'Sistema') . ' · ' . ($row['action'] ?? 'acción')),
            default => 'Registro #' . $row['id'],
        };
    }

    private function searchFields(string $group): array
    {
        return match ($group) {
            'transactions' => ['description', 'owner'], 'printing' => ['customer_name', 'details'],
            'ocr' => ['merchant', 'rif', 'invoice_number'], 'sales' => ['customer', 'product', 'reference'],
            'currency' => ['reference', 'notes'], 'inventory' => ['reference', 'type'],
            'ai' => ['title'], 'audit' => ['user_note', 'module', 'action'], default => [],
        };
    }

    private function authorized(): bool
    {
        return session()->get(self::SESSION_KEY) === true;
    }

    private function error(string $message, int $status = 400)
    {
        return $this->response->setStatusCode($status)->setJSON(['status' => 'error', 'message' => $message]);
    }
}
