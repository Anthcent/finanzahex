<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCharacteristicsToInventoryItems extends Migration
{
    public function up()
    {
        $fields = [
            'characteristics' => [
                'type' => 'TEXT', // We will store JSON here
                'null' => true,
                'after' => 'unit'
            ]
        ];
        if (!$this->db->fieldExists('characteristics', 'inventory_items')) {
            $this->forge->addColumn('inventory_items', $fields);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('inventory_items', 'characteristics');
    }
}
