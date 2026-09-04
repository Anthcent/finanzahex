<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCurrencyToAccounts extends Migration
{
    public function up()
    {
        $fields = [
            'currency' => [
                'type'       => 'VARCHAR',
                'constraint' => 3,
                'default'    => 'Bs',
                'after'      => 'status'
            ],
            'tenure_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'none',
                'after'      => 'currency'
            ],
        ];

        // Check if columns exist before adding to avoid errors if partially run
        $db = \Config\Database::connect();
        if (!$db->fieldExists('currency', 'accounts')) {
            $this->forge->addColumn('accounts', $fields);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('accounts', ['currency', 'tenure_type']);
    }
}
