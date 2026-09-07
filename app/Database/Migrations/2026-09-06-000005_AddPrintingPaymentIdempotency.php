<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPrintingPaymentIdempotency extends Migration
{
    private const INDEX_NAME = 'uq_transactions_payment_request_id';

    public function up()
    {
        if (!$this->db->fieldExists('payment_request_id', 'transactions')) {
            $this->forge->addColumn('transactions', [
                'payment_request_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => true,
                ],
            ]);
        }

        $this->forge->addUniqueKey('payment_request_id', self::INDEX_NAME);
        $this->forge->processIndexes('transactions');
    }

    public function down()
    {
        $this->forge->dropKey('transactions', self::INDEX_NAME);
        if ($this->db->fieldExists('payment_request_id', 'transactions')) {
            $this->forge->dropColumn('transactions', 'payment_request_id');
        }
    }
}
