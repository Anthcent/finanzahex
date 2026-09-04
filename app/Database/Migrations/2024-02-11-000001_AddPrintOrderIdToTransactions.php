<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPrintOrderIdToTransactions extends Migration
{
    public function up()
    {
        $fields = [
            'print_order_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'category_id'
            ]
        ];

        if (!$this->db->fieldExists('print_order_id', 'transactions')) {
            $this->forge->addColumn('transactions', $fields);
            
            // Add index for performance
            $this->db->query('ALTER TABLE transactions ADD INDEX(print_order_id)');
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('print_order_id', 'transactions')) {
            $this->forge->dropColumn('transactions', 'print_order_id');
        }
    }
}
