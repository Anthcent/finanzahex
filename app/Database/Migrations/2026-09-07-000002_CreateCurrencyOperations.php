<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCurrencyOperations extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'auto_increment' => true],
            'request_id' => ['type' => 'VARCHAR', 'constraint' => 64],
            'operation_type' => ['type' => 'VARCHAR', 'constraint' => 20],
            'source_account_id' => ['type' => 'INT'],
            'destination_account_id' => ['type' => 'INT'],
            'subtotal_bs' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'commission_percent' => ['type' => 'DECIMAL', 'constraint' => '8,4', 'default' => 0],
            'commission_bs' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'total_bs' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'amount_usd' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'quoted_rate' => ['type' => 'DECIMAL', 'constraint' => '15,4', 'default' => 0],
            'effective_rate' => ['type' => 'DECIMAL', 'constraint' => '15,4', 'default' => 0],
            'reference' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'owner' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'Negocio'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'completed'],
            'operation_date' => ['type' => 'DATETIME'],
            'reversed_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('request_id', 'uq_currency_operations_request');
        $this->forge->addKey(['operation_type', 'operation_date'], false, false, 'idx_currency_operations_history');
        $this->forge->createTable('currency_operations', true);

        if (!$this->db->fieldExists('currency_operation_id', 'transactions')) {
            $this->forge->addColumn('transactions', [
                'currency_operation_id' => ['type' => 'INT', 'null' => true],
            ]);
            $this->forge->addKey('currency_operation_id', false, false, 'idx_transactions_currency_operation');
            $this->forge->processIndexes('transactions');
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('currency_operation_id', 'transactions')) {
            $this->forge->dropColumn('transactions', 'currency_operation_id');
        }
        $this->forge->dropTable('currency_operations', true);
    }
}
