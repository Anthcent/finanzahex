<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOwnerToTransactions extends Migration
{
    public function up()
    {
        $fields = [
            'owner' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'default'    => 'Business',
                'after'      => 'type'
            ],
        ];
        if (!$this->db->fieldExists('owner', 'transactions')) {
            $this->forge->addColumn('transactions', $fields);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('transactions', 'owner');
    }
}
