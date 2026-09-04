<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCurrencyFieldsToTransactions extends Migration
{
    public function up()
    {
        $fields = [
            'amount_usd' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0.00,
                'after'      => 'amount'
            ],
            'exchange_rate' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,4',
                'default'    => 1.0000,
                'after'      => 'amount_usd'
            ]
        ];

        // Idempotency check
        if (!$this->db->fieldExists('amount_usd', 'transactions')) {
            $this->forge->addColumn('transactions', $fields);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('transactions', ['amount_usd', 'exchange_rate']);
    }
}
