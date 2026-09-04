<?php
$db = \Config\Database::connect();
$forge = \Config\Database::forge();

// 1. Create sale_statuses table
if (!$db->tableExists('sale_statuses')) {
    $forge->addField([
        'id' => [
            'type'           => 'INT',
            'constraint'     => 11,
            'unsigned'       => true,
            'auto_increment' => true,
        ],
        'name' => [
            'type'       => 'VARCHAR',
            'constraint' => 100,
        ],
        'color' => [
            'type'       => 'VARCHAR',
            'constraint' => 50, // e.g., 'bg-yellow-100 text-yellow-800'
        ],
        'created_at datetime default current_timestamp',
    ]);
    $forge->addKey('id', true);
    $forge->createTable('sale_statuses');
    echo "Table sale_statuses created.\n";
    
    // Seed Defaults
    $data = [
        ['name' => 'Pendiente', 'color' => 'bg-slate-100 text-slate-600'],
        ['name' => 'En Proceso', 'color' => 'bg-indigo-100 text-indigo-600'],
        ['name' => 'Por Entregar', 'color' => 'bg-orange-100 text-orange-600'],
        ['name' => 'Entregado', 'color' => 'bg-emerald-100 text-emerald-600'],
        ['name' => 'Cancelado', 'color' => 'bg-rose-100 text-rose-600']
    ];
    $db->table('sale_statuses')->insertBatch($data);
    echo "Seeded sale_statuses.\n";
}

// 2. Add status_id to sales table
if (!$db->fieldExists('order_status_id', 'sales')) {
    $forge->addColumn('sales', [
        'order_status_id' => [
            'type' => 'INT',
            'constraint' => 11,
            'unsigned' => true,
            'default' => 1 // Default to Pendiente
        ]
    ]);
    echo "Column order_status_id added to sales.\n";
} else {
    echo "Column order_status_id already exists.\n";
}

echo "Migration Complete.";
