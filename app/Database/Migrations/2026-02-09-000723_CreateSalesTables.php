<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSalesTables extends Migration
{
    public function up()
    {
        $isPg = ($this->db->DBDriver === 'Postgre');
        $createdCol = $isPg ? 'created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP' : 'created_at DATETIME DEFAULT CURRENT_TIMESTAMP';
        $updatedCol = $isPg ? 'updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP' : 'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';

        // Sales Table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'date' => [
                'type' => 'DATE',
            ],
            'product' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
            ],
            'amount_usd' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
            ],
            'exchange_rate' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,4',
            ],
            'customer' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['paid', 'partial'],
                'default'    => 'paid',
            ],
            'reference' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'paid_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0,
            ],
            'paid_amount_usd' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0,
            ],
            $createdCol,
            $updatedCol,
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('sales', true);

        // Sale Payments Table (For partial payments)
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'sale_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
            ],
            'amount_usd' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
            ],
            'rate' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,4',
            ],
            'date' => [
                'type' => 'DATE',
            ],
            'reference' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
            ],
            $createdCol,
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('sale_id', 'sales', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('sale_payments', true);
    }

    public function down()
    {
        $this->forge->dropTable('sale_payments');
        $this->forge->dropTable('sales');
    }
}
