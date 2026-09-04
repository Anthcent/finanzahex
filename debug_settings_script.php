<?php
// Load CodeIgniter framework
require 'index.php'; 

use Config\Database;

$db = Database::connect();

echo "Checking 'settings' table...\n";

if (!$db->tableExists('settings')) {
    echo "Table 'settings' DOES NOT EXIST.\n";
    
    // Create it
    $forge = \Config\Database::forge();
    $fields = [
        'id' => [
            'type'           => 'INT',
            'constraint'     => 5,
            'unsigned'       => true,
            'auto_increment' => true,
        ],
        'key' => [
            'type'       => 'VARCHAR',
            'constraint' => '100',
            'unique'     => true,
        ],
        'value' => [
            'type' => 'TEXT',
            'null' => true,
        ],
    ];
    $forge->addField($fields);
    $forge->addPrimaryKey('id');
    $forge->createTable('settings', true);
    
    echo "Table 'settings' created.\n";
} else {
    echo "Table 'settings' exists.\n";
    $fields = $db->getFieldData('settings');
    foreach ($fields as $field) {
        echo " - " . $field->name . " (" . $field->type . ")\n";
    }
}

// Try to insert/update a test key
echo "\nTesting Insert/Update...\n";
$builder = $db->table('settings');
$exists = $builder->where('key', 'test_key')->countAllResults();

if ($exists) {
    echo "Key 'test_key' exists. Updating...\n";
    $builder->where('key', 'test_key')->update(['value' => 'updated_value']);
} else {
    echo "Key 'test_key' does not exist. Inserting...\n";
    $builder->insert(['key' => 'test_key', 'value' => 'initial_value']);
}

echo "Operation complete.\n";

// Check result
$row = $builder->where('key', 'test_key')->get()->getRowArray();
echo "Value for 'test_key': " . ($row ? $row['value'] : 'NOT FOUND') . "\n";
