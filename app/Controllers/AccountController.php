<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AccountModel;
use App\Models\AccountTransferModel;
use App\Models\AuditLogModel;
use App\Models\CategoryModel;
use App\Models\TransactionModel;

class AccountController extends BaseController
{
    public function index()
    {
        return view('accounts/index');
    }

    public function fetch()
    {
        $accounts = (new AccountModel())->where('status !=', 'deleted')->orderBy('status', 'ASC')->findAll();
        return $this->response->setJSON(['status' => 'success', 'data' => $accounts]);
    }

    public function createTemporary()
    {
        $json = $this->request->getJSON();
        $name = trim((string) ($json->name ?? ''));
        $amount = round((float) ($json->amount ?? 0), 2);
        $sourceId = (int) ($json->source_id ?? 0);

        if ($name === '' || $amount <= 0 || !$sourceId) return $this->error('Indica nombre, cuenta origen y un monto válido.');

        $accounts = new AccountModel();
        $db = \Config\Database::connect();
        $db->transBegin();
        try {
            $source = $this->lockAccounts([$sourceId])[$sourceId] ?? null;
            if (!$source || $source['status'] !== 'active') throw new \RuntimeException('La cuenta origen no está disponible.');
            if ((float) $source['balance'] < $amount) throw new \RuntimeException('Saldo insuficiente en la cuenta origen.');

            $currency = $source['currency'] ?? 'Bs';
            $newId = $accounts->insert([
                'name' => $name, 'balance' => 0, 'initial_balance' => $amount,
                'type' => 'temporary', 'status' => 'active', 'parent_account_id' => $sourceId,
                'currency' => $currency, 'tenure_type' => $source['tenure_type'] ?? 'none',
            ]);
            if (!$newId) throw new \RuntimeException('No se pudo crear el fondo temporal.');

            $this->recordTransfer($source, $accounts->find($newId), $amount, 'fund_allocation', null, "Asignación inicial: {$name}");
            $db->transCommit();
            return $this->response->setJSON(['status' => 'success', 'id' => $newId]);
        } catch (\Throwable $e) {
            $db->transRollback();
            return $this->error($e->getMessage());
        }
    }

    public function closeTemporary($id)
    {
        return $this->finishTemporary((int) $id, false);
    }

    public function transfer()
    {
        $json = $this->request->getJSON();
        $sourceId = (int) ($json->source_id ?? 0);
        $destId = (int) ($json->dest_id ?? 0);
        $amount = round((float) ($json->amount ?? 0), 2);
        $categoryId = !empty($json->category_id) ? (int) $json->category_id : null;
        $note = trim((string) ($json->note ?? ''));
        $requestId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($json->request_id ?? '')) ?: bin2hex(random_bytes(16));

        if (!$sourceId || !$destId || $amount <= 0) return $this->error('Selecciona ambas cuentas e indica un monto válido.');
        if ($sourceId === $destId) return $this->error('La cuenta origen y destino deben ser diferentes.');

        $transfers = new AccountTransferModel();
        if ($transfers->where('request_id', $requestId)->first()) {
            return $this->response->setJSON(['status' => 'success', 'duplicate' => true]);
        }

        $accounts = new AccountModel();
        $db = \Config\Database::connect();
        $db->transBegin();
        try {
            $locked = $this->lockAccounts([$sourceId, $destId]);
            $source = $locked[$sourceId] ?? null;
            $dest = $locked[$destId] ?? null;
            if (!$source || !$dest || $source['status'] !== 'active' || $dest['status'] !== 'active') throw new \RuntimeException('Una de las cuentas no está disponible.');
            if (strtoupper($source['currency'] ?? 'BS') !== strtoupper($dest['currency'] ?? 'BS')) throw new \RuntimeException('Las cuentas deben usar la misma moneda.');
            if ((float) $source['balance'] < $amount) throw new \RuntimeException('Saldo insuficiente en la cuenta origen.');

            $this->recordTransfer($source, $dest, $amount, 'standard', $categoryId, $note, $requestId);
            AuditLogModel::log('accounts', 'transfer', $sourceId, null, null, [
                'request_id' => $requestId, 'source_id' => $sourceId, 'destination_id' => $destId, 'amount' => $amount,
            ], "Transferencia: {$source['name']} -> {$dest['name']}");
            $db->transCommit();
            return $this->response->setJSON(['status' => 'success', 'request_id' => $requestId]);
        } catch (\Throwable $e) {
            $db->transRollback();
            return $this->error($e->getMessage());
        }
    }

    public function add()
    {
        $json = $this->request->getJSON();
        $name = trim((string) ($json->name ?? ''));
        $balance = is_numeric($json->balance ?? null) ? round((float) $json->balance, 2) : 0;
        if ($name === '') return $this->error('El nombre de la cuenta es obligatorio.');

        $model = new AccountModel();
        $id = $model->insert([
            'name' => $name, 'balance' => $balance, 'initial_balance' => $balance,
            'type' => 'general', 'status' => 'active', 'currency' => $json->currency ?? 'Bs',
            'tenure_type' => $json->tenure_type ?? 'none',
        ]);
        if (!$id) return $this->error('No se pudo crear la cuenta.');
        AuditLogModel::log('accounts', 'create', $id, null, $json, ['initial_balance' => $balance], "Creación de cuenta: {$name}");
        return $this->response->setJSON(['status' => 'success']);
    }

    public function delete($id)
    {
        $accounts = new AccountModel();
        $account = $accounts->find((int) $id);
        if (!$account) return $this->error('Cuenta no encontrada.');

        if ($account['type'] === 'temporary') return $this->finishTemporary((int) $id, true);

        if ($accounts->where('parent_account_id', $id)->where('status', 'active')->countAllResults() > 0) {
            return $this->error('Primero liquida o elimina los fondos temporales vinculados a esta cuenta.');
        }
        if (abs((float) $account['balance']) > 0.009) return $this->error('Transfiere el saldo restante antes de eliminar esta cuenta.');

        // Preserve its ledger: a deleted account is hidden, not physically erased.
        $accounts->update($id, ['status' => 'deleted', 'closed_at' => date('Y-m-d H:i:s')]);
        AuditLogModel::log('accounts', 'delete', $id, $account, null, ['soft_deleted' => true], "Eliminación de cuenta: {$account['name']}");
        return $this->response->setJSON(['status' => 'success']);
    }

    public function updateBalance()
    {
        $json = $this->request->getJSON();
        $id = (int) ($json->id ?? 0);
        $balance = round((float) ($json->balance ?? 0), 2);
        $model = new AccountModel();
        $before = $model->find($id);
        if (!$before) return $this->error('Cuenta no encontrada.');
        $model->update($id, ['balance' => $balance]);
        AuditLogModel::log('accounts', 'update_balance', $id, $before, ['balance' => $balance], [
            'balance_before' => $before['balance'], 'balance_after' => $balance,
        ], 'Ajuste manual de balance');
        return $this->response->setJSON(['status' => 'success']);
    }

    private function finishTemporary(int $id, bool $delete)
    {
        $accounts = new AccountModel();
        $db = \Config\Database::connect();
        $db->transBegin();
        try {
            $candidate = $accounts->find($id);
            $lockIds = [$id];
            if (!empty($candidate['parent_account_id'])) $lockIds[] = (int) $candidate['parent_account_id'];
            $locked = $this->lockAccounts($lockIds);
            $fund = $locked[$id] ?? null;
            if (!$fund || $fund['type'] !== 'temporary') throw new \RuntimeException('El fondo temporal no existe.');
            if ($fund['status'] === 'deleted') throw new \RuntimeException('El fondo ya fue eliminado.');

            $remaining = max(0, round((float) $fund['balance'], 2));
            if ($fund['status'] === 'active' && $remaining > 0) {
                $parent = $locked[(int) $fund['parent_account_id']] ?? null;
                if (!$parent) throw new \RuntimeException('La cuenta de origen ya no existe.');
                if (strtoupper($parent['currency'] ?? 'BS') !== strtoupper($fund['currency'] ?? 'BS')) throw new \RuntimeException('La moneda del fondo no coincide con su cuenta de origen.');
                $this->recordTransfer($fund, $parent, $remaining, $delete ? 'fund_deletion' : 'fund_liquidation', null, $delete ? 'Saldo devuelto al eliminar fondo' : 'Saldo no utilizado devuelto');
            }

            $accounts->update($id, [
                'balance' => 0,
                'status' => $delete ? 'deleted' : 'closed',
                'closed_at' => date('Y-m-d H:i:s'),
            ]);
            AuditLogModel::log('accounts', $delete ? 'delete_fund' : 'close_fund', $id, $fund, null, [
                'initial_balance' => $fund['initial_balance'] ?? 0,
                'returned_balance' => $remaining,
                'used_balance' => max(0, (float) ($fund['initial_balance'] ?? 0) - $remaining),
            ], ($delete ? 'Eliminación' : 'Liquidación') . " de fondo: {$fund['name']}");
            $db->transCommit();
            return $this->response->setJSON(['status' => 'success', 'returned' => $remaining]);
        } catch (\Throwable $e) {
            $db->transRollback();
            return $this->error($e->getMessage());
        }
    }

    private function recordTransfer(array $source, array $dest, float $amount, string $type, ?int $categoryId = null, string $note = '', ?string $requestId = null): void
    {
        $accounts = new AccountModel();
        $transactions = new TransactionModel();
        $transfers = new AccountTransferModel();
        $requestId ??= bin2hex(random_bytes(16));
        if ($transfers->where('request_id', $requestId)->first()) return;

        $sourceBefore = (float) $source['balance'];
        $destBefore = (float) $dest['balance'];
        if ($sourceBefore < $amount) throw new \RuntimeException('Saldo insuficiente en la cuenta origen.');
        $sourceAfter = round($sourceBefore - $amount, 2);
        $destAfter = round($destBefore + $amount, 2);
        $currency = $source['currency'] ?? 'Bs';
        $categoryId ??= $this->getValidCategoryId();
        $money = strtoupper($currency) === 'USD'
            ? ['amount' => 0, 'amount_usd' => $amount]
            : ['amount' => $amount, 'amount_usd' => 0];

        if (!$accounts->update($source['id'], ['balance' => $sourceAfter]) || !$accounts->update($dest['id'], ['balance' => $destAfter])) {
            throw new \RuntimeException('No se pudieron actualizar los saldos.');
        }
        $suffix = $note !== '' ? ": {$note}" : '';
        $base = ['category_id' => $categoryId, 'exchange_rate' => 0, 'owner' => 'System', 'transfer_group_id' => $requestId] + $money;
        if (!$transactions->insert($base + [
            'account_id' => $source['id'], 'type' => 'transfer_out',
            'balance_before' => $sourceBefore, 'balance_after' => $sourceAfter,
            'description' => "Transferencia a {$dest['name']}{$suffix}",
        ])) throw new \RuntimeException('No se pudo registrar la salida de la transferencia.');
        if (!$transactions->insert($base + [
            'account_id' => $dest['id'], 'type' => 'transfer_in',
            'balance_before' => $destBefore, 'balance_after' => $destAfter,
            'description' => "Transferencia desde {$source['name']}{$suffix}",
        ])) throw new \RuntimeException('No se pudo registrar la entrada de la transferencia.');

        if (!$transfers->insert([
            'request_id' => $requestId, 'transfer_type' => $type,
            'source_account_id' => $source['id'], 'destination_account_id' => $dest['id'],
            'amount' => $amount, 'currency' => $currency, 'category_id' => $categoryId, 'note' => $note,
            'source_balance_before' => $sourceBefore, 'source_balance_after' => $sourceAfter,
            'destination_balance_before' => $destBefore, 'destination_balance_after' => $destAfter,
            'status' => 'completed',
        ])) throw new \RuntimeException('No se pudo auditar la transferencia.');
    }

    private function getValidCategoryId(): int
    {
        $category = (new CategoryModel())->first();
        if (!$category) throw new \RuntimeException('Debes crear al menos una categoría antes de mover fondos.');
        return (int) $category['id'];
    }

    /** @return array<int,array> */
    private function lockAccounts(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids === []) return [];
        $db = \Config\Database::connect();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $suffix = stripos((string) $db->DBDriver, 'sqlite') !== false ? '' : ' FOR UPDATE';
        $rows = $db->query("SELECT * FROM accounts WHERE id IN ({$placeholders}) ORDER BY id{$suffix}", $ids)->getResultArray();
        $result = [];
        foreach ($rows as $row) $result[(int) $row['id']] = $row;
        return $result;
    }

    private function error(string $message)
    {
        return $this->response->setJSON(['status' => 'error', 'message' => $message]);
    }
}
