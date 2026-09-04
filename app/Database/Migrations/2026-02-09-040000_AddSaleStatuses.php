<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSaleStatuses extends Migration
{
    public function up()
    {
        $isPg = ($this->db->DBDriver === 'Postgre');
        $createdCol = $isPg ? 'created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP' : 'created_at DATETIME DEFAULT CURRENT_TIMESTAMP';

        // 1. Create sale_statuses table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'color' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
            ],
            $createdCol,
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('sale_statuses', true);

        // 2. Add order_status_id to sales table
        // Check if column exists is handled by forge->addColumn which creates it.
        // We assume 'sales' table exists from previous migration (CreateSalesTables)
        $fields = [
            'order_status_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 1,
                'after'      => 'status' // Place after 'status' enum if possible
            ],
        ];
        // Only add if not exists, but forge addColumn throws if exists usually? 
        // CI4 addColumn usually just adds. To be safe, we can check.
        if ($this->db->tableExists('sales') && !$this->db->fieldExists('order_status_id', 'sales')) {
            $this->forge->addColumn('sales', $fields);
        }

        // 3. Seed Statuses
        $seedData = [
            ['name' => 'Pendiente', 'color' => 'bg-slate-100 text-slate-600'],
            ['name' => 'En Proceso', 'color' => 'bg-indigo-100 text-indigo-600'],
            ['name' => 'Por Entregar', 'color' => 'bg-orange-100 text-orange-600'],
            ['name' => 'Entregado', 'color' => 'bg-emerald-100 text-emerald-600'],
            ['name' => 'Cancelado', 'color' => 'bg-rose-100 text-rose-600'],
        ];
        
        foreach ($seedData as $status) {
            $exists = $this->db->table('sale_statuses')->where('name', $status['name'])->countAllResults();
            if ($exists == 0) {
                $this->db->table('sale_statuses')->insert($status);
            }
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('order_status_id', 'sales')) {
            $this->forge->dropColumn('sales', 'order_status_id');
        }
        $this->forge->dropTable('sale_statuses');
    }
}
