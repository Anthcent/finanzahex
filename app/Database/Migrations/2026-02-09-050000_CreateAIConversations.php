<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAIConversations extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'auto_increment' => true,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'messages' => [
                'type' => 'JSON',
            ],
            'created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('created_at'); // Index
        $this->forge->createTable('ai_conversations', true);
    }

    public function down()
    {
        $this->forge->dropTable('ai_conversations');
    }
}
