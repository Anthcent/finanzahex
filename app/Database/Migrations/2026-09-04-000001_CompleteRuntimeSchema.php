<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CompleteRuntimeSchema extends Migration
{
    public function up()
    {
        $this->db->resetDataCache();
        $this->forge->addField([
            'key' => ['type' => 'VARCHAR', 'constraint' => 255],
            'value' => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->forge->addKey('key', true);
        $this->forge->createTable('settings', true);

        $id = ['type' => 'INT', 'auto_increment' => true];
        $money = ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0];
        $date = ['type' => 'DATETIME', 'null' => true];
        $this->forge->addField([
            'id' => $id,
            'name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'price_bs' => $money,
            'price_usd' => $money,
            'category' => ['type' => 'VARCHAR', 'constraint' => 100, 'default' => 'General'],
            'icon' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'print'],
            'color' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'indigo'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('print_products', true);

        $this->forge->addField([
            'id' => $id,
            'name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'is_favorite' => ['type' => 'SMALLINT', 'default' => 0],
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('name');
        $this->forge->createTable('customers', true);

        $this->forge->addField([
            'id' => $id,
            'customer_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'details' => ['type' => 'TEXT', 'null' => true],
            'total_bs' => $money,
            'total_usd' => $money,
            'paid_bs' => $money,
            'paid_usd' => $money,
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'],
            'created_at' => $date,
            'transaction_id' => ['type' => 'INT', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('print_orders', true);

        // Upgrade partially initialized installations without replacing their data.
        $columns = [
            'accounts' => [
                'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
                'parent_account_id' => ['type' => 'INT', 'null' => true],
                'tenure_type' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'none'],
            ],
            'print_orders' => ['transaction_id' => ['type' => 'INT', 'null' => true]],
            'transactions' => ['exchange_rate' => ['type' => 'DECIMAL', 'constraint' => '15,4', 'default' => 1]],
            'transaction_items' => [
                'description' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'price_usd' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            ],
        ];
        foreach ($columns as $table => $fields) {
            foreach ($fields as $name => $definition) {
                if (!$this->db->fieldExists($name, $table)) {
                    $this->forge->addColumn($table, [$name => $definition]);
                }
            }
        }
        \App\Libraries\PrintCatalog::seed($this->db);
    }

    public function down()
    {
        // Existing installations may already own these tables. Never drop user data.
        throw new \RuntimeException('Restore a verified backup to roll back the runtime schema.');
    }
}
