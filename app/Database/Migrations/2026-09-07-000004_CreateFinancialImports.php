<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFinancialImports extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('financial_import_batches')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'import_type' => ['type' => 'VARCHAR', 'constraint' => 32],
                'source_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'account_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'status' => ['type' => 'VARCHAR', 'constraint' => 24, 'default' => 'pending'],
                'item_count' => ['type' => 'INT', 'default' => 0],
                'raw_json' => ['type' => 'TEXT', 'null' => true],
                'created_at' => ['type' => $this->db->DBDriver === 'Postgre' ? 'TIMESTAMP' : 'DATETIME', 'null' => true],
                'updated_at' => ['type' => $this->db->DBDriver === 'Postgre' ? 'TIMESTAMP' : 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['import_type', 'status']);
            $this->forge->createTable('financial_import_batches');
        }

        if (!$this->db->tableExists('financial_import_items')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'batch_id' => ['type' => 'INT', 'unsigned' => true],
                'account_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'direction' => ['type' => 'VARCHAR', 'constraint' => 12, 'default' => 'credit'],
                'currency' => ['type' => 'VARCHAR', 'constraint' => 8, 'default' => 'BS'],
                'amount' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
                'exchange_rate' => ['type' => 'DECIMAL', 'constraint' => '15,4', 'default' => 0],
                'movement_date' => ['type' => $this->db->DBDriver === 'Postgre' ? 'TIMESTAMP' : 'DATETIME', 'null' => true],
                'reference' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'bank' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
                'counterparty' => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => true],
                'description' => ['type' => 'TEXT', 'null' => true],
                'confidence' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
                'duplicate_key' => ['type' => 'VARCHAR', 'constraint' => 64],
                'status' => ['type' => 'VARCHAR', 'constraint' => 24, 'default' => 'pending'],
                'suggested_action' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'transaction'],
                'matched_order_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'matched_item_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'transaction_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'payment_request_id' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'meta_json' => ['type' => 'TEXT', 'null' => true],
                'created_at' => ['type' => $this->db->DBDriver === 'Postgre' ? 'TIMESTAMP' : 'DATETIME', 'null' => true],
                'updated_at' => ['type' => $this->db->DBDriver === 'Postgre' ? 'TIMESTAMP' : 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['batch_id', 'status']);
            $this->forge->addKey('duplicate_key');
            $this->forge->addKey('matched_order_id');
            $this->forge->addForeignKey('batch_id', 'financial_import_batches', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('financial_import_items');
        }
    }

    public function down()
    {
        $this->forge->dropTable('financial_import_items', true);
        $this->forge->dropTable('financial_import_batches', true);
    }
}
