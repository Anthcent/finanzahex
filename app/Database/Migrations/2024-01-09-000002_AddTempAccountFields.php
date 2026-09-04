<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTempAccountFields extends Migration
{
    public function up()
    {
        $addFields = [];
        if (!$this->db->fieldExists('status', 'accounts')) {
            $addFields['status'] = $fields['status'];
        }
        if (!$this->db->fieldExists('parent_account_id', 'accounts')) {
            $addFields['parent_account_id'] = $fields['parent_account_id'];
        }

        if (!empty($addFields)) {
            $this->forge->addColumn('accounts', $addFields);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('accounts', ['status', 'parent_account_id']);
    }
}
