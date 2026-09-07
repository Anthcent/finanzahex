<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ExpandPrintingProductCatalog extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('print_product_categories')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'auto_increment' => true],
                'name' => ['type' => 'VARCHAR', 'constraint' => 100],
                'icon' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'category'],
                'color' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'emerald'],
                'sort_order' => ['type' => 'INT', 'default' => 0],
                'is_active' => ['type' => 'SMALLINT', 'default' => 1],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('name');
            $this->forge->createTable('print_product_categories');
        }

        $columns = [
            'category_id' => ['type' => 'INT', 'null' => true],
            'product_type' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'service'],
            'sku' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'description' => ['type' => 'TEXT', 'null' => true],
            'unit' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'unidad'],
            'characteristics_json' => ['type' => 'TEXT', 'null' => true],
            'is_active' => ['type' => 'SMALLINT', 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ];
        foreach ($columns as $name => $definition) {
            if (!$this->db->fieldExists($name, 'print_products')) {
                $this->forge->addColumn('print_products', [$name => $definition]);
            }
        }

        $now = date('Y-m-d H:i:s');
        $defaults = [
            ['name' => 'Impresiones', 'icon' => 'print', 'color' => 'blue', 'sort_order' => 10],
            ['name' => 'Copias', 'icon' => 'content_copy', 'color' => 'slate', 'sort_order' => 12],
            ['name' => 'Documentos', 'icon' => 'description', 'color' => 'emerald', 'sort_order' => 13],
            ['name' => 'Materiales', 'icon' => 'layers', 'color' => 'orange', 'sort_order' => 14],
            ['name' => 'Papelería', 'icon' => 'edit_note', 'color' => 'amber', 'sort_order' => 20],
            ['name' => 'Franelas', 'icon' => 'checkroom', 'color' => 'violet', 'sort_order' => 30],
            ['name' => 'Personalizados', 'icon' => 'auto_awesome', 'color' => 'pink', 'sort_order' => 40],
            ['name' => 'Servicios', 'icon' => 'design_services', 'color' => 'emerald', 'sort_order' => 50],
            ['name' => 'General', 'icon' => 'category', 'color' => 'slate', 'sort_order' => 90],
        ];
        foreach ($defaults as $category) {
            if ($this->db->table('print_product_categories')->where('name', $category['name'])->countAllResults() === 0) {
                $category['is_active'] = 1;
                $category['created_at'] = $now;
                $category['updated_at'] = $now;
                $this->db->table('print_product_categories')->insert($category);
            }
        }

        $legacyCategories = $this->db->table('print_products')
            ->select('category')
            ->where('category IS NOT NULL')
            ->groupBy('category')
            ->get()->getResultArray();
        foreach ($legacyCategories as $legacy) {
            $name = trim((string) ($legacy['category'] ?? ''));
            if ($name !== '' && $this->db->table('print_product_categories')->where('name', $name)->countAllResults() === 0) {
                $this->db->table('print_product_categories')->insert([
                    'name' => $name,
                    'icon' => 'category',
                    'color' => 'emerald',
                    'sort_order' => 60,
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $categories = $this->db->table('print_product_categories')->get()->getResultArray();
        foreach ($categories as $category) {
            $this->db->table('print_products')
                ->where('category', $category['name'])
                ->where('category_id IS NULL')
                ->update(['category_id' => $category['id'], 'updated_at' => $now]);
        }
    }

    public function down()
    {
        foreach (['updated_at', 'created_at', 'is_active', 'characteristics_json', 'unit', 'description', 'sku', 'product_type', 'category_id'] as $column) {
            if ($this->db->fieldExists($column, 'print_products')) {
                $this->forge->dropColumn('print_products', $column);
            }
        }
        $this->forge->dropTable('print_product_categories', true);
    }
}
