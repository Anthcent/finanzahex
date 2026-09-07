<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOcrInvoicesTable extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('ocr_invoices')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'transaction_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                ],
                'account_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => false,
                ],
                'category_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                ],
                'merchant' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => false,
                ],
                'rif' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => true,
                ],
                'invoice_number' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => true,
                ],
                'model_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'default' => 'GENERIC_RECEIPT',
                ],
                'model_label' => [
                    'type' => 'VARCHAR',
                    'constraint' => 128,
                    'default' => 'Factura / Recibo General',
                ],
                'invoice_date' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'invoice_time' => [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'null' => true,
                ],
                'total_bs' => [
                    'type' => 'DECIMAL',
                    'constraint' => '14,2',
                    'default' => 0.00,
                ],
                'total_usd' => [
                    'type' => 'DECIMAL',
                    'constraint' => '14,2',
                    'default' => 0.00,
                ],
                'exchange_rate' => [
                    'type' => 'DECIMAL',
                    'constraint' => '14,4',
                    'default' => 50.0000,
                ],
                'subtotal' => [
                    'type' => 'DECIMAL',
                    'constraint' => '14,2',
                    'default' => 0.00,
                ],
                'exento' => [
                    'type' => 'DECIMAL',
                    'constraint' => '14,2',
                    'default' => 0.00,
                ],
                'base_imponible' => [
                    'type' => 'DECIMAL',
                    'constraint' => '14,2',
                    'default' => 0.00,
                ],
                'iva_amount' => [
                    'type' => 'DECIMAL',
                    'constraint' => '14,2',
                    'default' => 0.00,
                ],
                'iva_rate' => [
                    'type' => 'DECIMAL',
                    'constraint' => '5,2',
                    'default' => 16.00,
                ],
                'igtf_amount' => [
                    'type' => 'DECIMAL',
                    'constraint' => '14,2',
                    'default' => 0.00,
                ],
                'payment_method' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => true,
                ],
                'cashea_amount' => [
                    'type' => 'DECIMAL',
                    'constraint' => '14,2',
                    'default' => 0.00,
                ],
                'owner' => [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'default' => 'Negocio',
                ],
                'items_json' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'raw_text' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'status' => [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'default' => 'approved',
                ],
                'quick_scan' => [
                    'type' => 'INT',
                    'constraint' => 1,
                    'default' => 0,
                ],
                'reviewed_at' => [
                    'type' => $this->db->DBDriver === 'Postgre' ? 'TIMESTAMP' : 'DATETIME',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => $this->db->DBDriver === 'Postgre' ? 'TIMESTAMP' : 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => $this->db->DBDriver === 'Postgre' ? 'TIMESTAMP' : 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addKey('status');
            $this->forge->addKey('account_id');
            $this->forge->addKey('transaction_id');
            $this->forge->createTable('ocr_invoices');
        }
    }

    public function down()
    {
        if ($this->db->tableExists('ocr_invoices')) {
            $this->forge->dropTable('ocr_invoices');
        }
    }
}
