<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class InitialSchema extends Migration
{
    public function up()
    {
        // Disable foreign key checks for reset if needed, though usually not needed for UP
        // $this->db->disableForeignKeyChecks();

        // 1. Accounts Table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'type' => [
                'type'       => 'ENUM',
                'constraint' => ['cash', 'bank', 'wallet', 'investment'],
            ],
            'balance' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0.00,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('accounts', true);

        // 2. Categories Table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'type' => [
                'type'       => 'ENUM',
                'constraint' => ['income', 'expense', 'investment', 'transfer'],
            ],
            'icon' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('categories', true);

        // 3. Transactions Table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'auto_increment' => true,
            ],
            'account_id' => [
                'type' => 'INT',
            ],
            'category_id' => [
                'type' => 'INT',
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
            ],
            'type' => [
                'type'       => 'ENUM',
                'constraint' => ['income', 'expense', 'investment', 'transfer'],
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('account_id', 'accounts', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('category_id', 'categories', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('transactions', true);

        // 4. Transaction Items Table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'auto_increment' => true,
            ],
            'transaction_id' => [
                'type' => 'INT',
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'quantity' => [
                'type'    => 'INT',
                'default' => 1,
            ],
            'price' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
            ],
            // 'total' generated column is not universally supported in all CI drivers easily via forge, 
            // so we might skip the stored generated text for portability or leave it if MySQL is guaranteed.
            // Using standard column for now to avoid migration issues, handled by app logic or trigger if needed.
            // But preserving schema.sql intent:
            // total DECIMAL(15, 2) GENERATED ALWAYS AS (quantity * price) STORED
            // CI4 Forge doesn't support GENERATED ALWAYS AS natively easily in addField without raw SQL string mostly.
            // We will add it as a normal column for now to be safe, or direct query.
        ]);
        // Workaround for Generated Column
         $this->forge->addField("total DECIMAL(15, 2) GENERATED ALWAYS AS (quantity * price) STORED");

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('transaction_id', 'transactions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('transaction_items', true);

        // Seeds
        $this->seedData();
    }

    private function seedData()
    {
        // Seed Accounts
        if ($this->db->table('accounts')->countAllResults() == 0) {
            $data = [
                ['name' => 'Efectivo', 'type' => 'cash', 'balance' => 0],
                ['name' => 'Banco Principal', 'type' => 'bank', 'balance' => 0],
            ];
            $this->db->table('accounts')->insertBatch($data);
        }

        // Seed Categories
        if ($this->db->table('categories')->countAllResults() == 0) {
            $categories = [
                ['name' => 'Comida', 'type' => 'expense', 'icon' => 'fast-food'],
                ['name' => 'Transporte', 'type' => 'expense', 'icon' => 'car'],
                ['name' => 'Salario', 'type' => 'income', 'icon' => 'cash'],
                ['name' => 'Ventas', 'type' => 'income', 'icon' => 'store'],
            ];
            $this->db->table('categories')->insertBatch($categories);
        }
    }

    public function down()
    {
        $this->forge->dropTable('transaction_items');
        $this->forge->dropTable('transactions');
        $this->forge->dropTable('categories');
        $this->forge->dropTable('accounts');
    }
}
