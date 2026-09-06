<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ExpandPrintingDebtManagement extends Migration
{
    public function up()
    {
        $fields = [
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

        foreach ($fields as $name => $definition) {
            if (!$this->db->fieldExists($name, 'print_orders')) {
                $this->forge->addColumn('print_orders', [$name => $definition]);
            }
        }
    }

    public function down()
    {
        foreach (['customer_phone', 'due_date', 'collection_notes', 'last_reminder_at', 'reminder_count'] as $column) {
            if ($this->db->fieldExists($column, 'print_orders')) {
                $this->forge->dropColumn('print_orders', $column);
            }
        }
    }
}
