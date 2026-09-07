<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ExpandAccountLedger extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('initial_balance', 'accounts')) {
            $this->forge->addColumn('accounts', [
                'initial_balance' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
                'closed_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
        }

        if (!$this->db->fieldExists('balance_before', 'transactions')) {
            $this->forge->addColumn('transactions', [
                'balance_before' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true],
                'balance_after' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true],
                'transfer_group_id' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            ]);
        }

        $this->forge->addField([
            'id' => ['type' => 'INT', 'auto_increment' => true],
            'request_id' => ['type' => 'VARCHAR', 'constraint' => 64],
            'transfer_type' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'standard'],
            'source_account_id' => ['type' => 'INT'],
            'destination_account_id' => ['type' => 'INT'],
            'amount' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'currency' => ['type' => 'VARCHAR', 'constraint' => 8, 'default' => 'Bs'],
            'category_id' => ['type' => 'INT', 'null' => true],
            'note' => ['type' => 'TEXT', 'null' => true],
            'source_balance_before' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'source_balance_after' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'destination_balance_before' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'destination_balance_after' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'completed'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('request_id', 'uq_account_transfers_request');
        $this->forge->addKey(['source_account_id', 'destination_account_id'], false, false, 'idx_account_transfers_accounts');
        $this->forge->createTable('account_transfers', true);

        // Older temporary funds did not inherit their source currency.
        foreach ($this->db->table('accounts')->where('type', 'temporary')->get()->getResultArray() as $fund) {
            if (empty($fund['parent_account_id'])) continue;
            $parent = $this->db->table('accounts')->where('id', $fund['parent_account_id'])->get()->getRowArray();
            if ($parent) {
                $this->db->table('accounts')->where('id', $fund['id'])->update([
                    'currency' => $parent['currency'] ?? 'Bs',
                    'tenure_type' => $parent['tenure_type'] ?? 'none',
                ]);
            }
        }

        // Reconstruct the best available historical balance trail from today's balance.
        $positive = ['income', 'return', 'exchange_in', 'transfer_in', 'currency_reversal_in'];
        foreach ($this->db->table('accounts')->select('id, balance, currency, type, status')->get()->getResultArray() as $account) {
            if (($account['type'] ?? '') === 'temporary') {
                $running = 0.0;
                $highestBalance = 0.0;
                $rows = $this->db->table('transactions')->where('account_id', $account['id'])
                    ->orderBy('created_at', 'ASC')->orderBy('id', 'ASC')->get()->getResultArray();
                foreach ($rows as $row) {
                    $native = strtoupper((string) ($account['currency'] ?? 'BS')) === 'USD'
                        ? ((float) ($row['amount_usd'] ?? 0) ?: (float) ($row['amount'] ?? 0))
                        : (float) ($row['amount'] ?? 0);
                    $delta = in_array($row['type'], $positive, true) ? $native : -$native;
                    $after = $running + $delta;
                    $this->db->table('transactions')->where('id', $row['id'])->update([
                        'balance_before' => $running, 'balance_after' => $after,
                    ]);
                    $running = $after;
                    $highestBalance = max($highestBalance, $running);
                }
                $update = ['initial_balance' => $highestBalance];
                if (($account['status'] ?? 'active') === 'active') $update['balance'] = max(0, $running);
                $this->db->table('accounts')->where('id', $account['id'])->update($update);
                continue;
            }

            $running = (float) $account['balance'];
            $highestBalance = $running;
            $rows = $this->db->table('transactions')->where('account_id', $account['id'])
                ->orderBy('created_at', 'DESC')->orderBy('id', 'DESC')->get()->getResultArray();
            foreach ($rows as $row) {
                $native = strtoupper((string) ($account['currency'] ?? 'BS')) === 'USD'
                    ? ((float) ($row['amount_usd'] ?? 0) ?: (float) ($row['amount'] ?? 0))
                    : (float) ($row['amount'] ?? 0);
                $delta = in_array($row['type'], $positive, true) ? $native : -$native;
                $before = $running - $delta;
                $this->db->table('transactions')->where('id', $row['id'])->update([
                    'balance_before' => $before,
                    'balance_after' => $running,
                ]);
                $highestBalance = max($highestBalance, $running, $before);
                $running = $before;
            }
        }
    }

    public function down()
    {
        $this->forge->dropTable('account_transfers', true);
        foreach (['balance_before', 'balance_after', 'transfer_group_id'] as $field) {
            if ($this->db->fieldExists($field, 'transactions')) $this->forge->dropColumn('transactions', $field);
        }
        foreach (['initial_balance', 'closed_at'] as $field) {
            if ($this->db->fieldExists($field, 'accounts')) $this->forge->dropColumn('accounts', $field);
        }
    }
}
