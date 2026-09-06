<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ExpandSalesDebtManagement extends Migration
{
    public function up()
    {
        $salesFields = [
            'customer_phone' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'null' => true,
            ],
            'due_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'collection_notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'last_reminder_at' => [
                'type' => $this->db->DBDriver === 'Postgre' ? 'TIMESTAMP' : 'DATETIME',
                'null' => true,
            ],
            'reminder_count' => [
                'type' => 'INT',
                'default' => 0,
            ],
        ];

        foreach ($salesFields as $name => $definition) {
            if (!$this->db->fieldExists($name, 'sales')) {
                $this->forge->addColumn('sales', [$name => $definition]);
            }
        }

        if (!$this->db->fieldExists('account_id', 'sale_payments')) {
            $this->forge->addColumn('sale_payments', [
                'account_id' => [
                    'type' => 'INT',
                    'null' => true,
                ],
            ]);
        }
    }

    public function down()
    {
        foreach (['customer_phone', 'due_date', 'collection_notes', 'last_reminder_at', 'reminder_count'] as $column) {
            if ($this->db->fieldExists($column, 'sales')) {
                $this->forge->dropColumn('sales', $column);
            }
        }

        if ($this->db->fieldExists('account_id', 'sale_payments')) {
            $this->forge->dropColumn('sale_payments', 'account_id');
        }
    }
}
