<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAuditLogsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'module' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'comment'    => 'transactions, accounts, sales, printing',
            ],
            'action' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'comment'    => 'create, update, delete, transfer, payment',
            ],
            'record_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'data_before' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON snapshot before change',
            ],
            'data_after' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON snapshot after change',
            ],
            'impact' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON: {account_id, account_name, balance_before, balance_after, delta}',
            ],
            'user_note' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('module');
        $this->forge->addKey('action');
        $this->forge->addKey('created_at');
        $this->forge->createTable('audit_logs', true);
    }

    public function down()
    {
        $this->forge->dropTable('audit_logs', true);
    }
}
