<?php

namespace App\Libraries;

class PrintCatalog
{
    public static function seed($db): void
    {
        if ($db->table('print_products')->countAllResults() == 0) {
            $data = [
                ['name' => 'Copia B/N', 'price_bs' => 2.00, 'price_usd' => 0.05, 'category' => 'Copias', 'icon' => 'description', 'color' => 'slate'],
                ['name' => 'Copia Color', 'price_bs' => 5.00, 'price_usd' => 0.12, 'category' => 'Copias', 'icon' => 'palette', 'color' => 'pink'],
                ['name' => 'Impresión Texto', 'price_bs' => 3.00, 'price_usd' => 0.08, 'category' => 'Impresiones', 'icon' => 'article', 'color' => 'blue'],
                ['name' => 'Impresión Imagen', 'price_bs' => 6.00, 'price_usd' => 0.15, 'category' => 'Impresiones', 'icon' => 'image', 'color' => 'indigo'],
                ['name' => 'Fondo Negro', 'price_bs' => 10.00, 'price_usd' => 0.25, 'category' => 'Documentos', 'icon' => 'badge', 'color' => 'emerald'],
                ['name' => 'Título', 'price_bs' => 15.00, 'price_usd' => 0.35, 'category' => 'Documentos', 'icon' => 'school', 'color' => 'amber'],
                ['name' => 'Escaneo', 'price_bs' => 5.00, 'price_usd' => 0.12, 'category' => 'Servicios', 'icon' => 'scanner', 'color' => 'cyan'],
                ['name' => 'Plastificado Carta', 'price_bs' => 20.00, 'price_usd' => 0.50, 'category' => 'Materiales', 'icon' => 'layers', 'color' => 'rose'],
                ['name' => 'Anillado', 'price_bs' => 25.00, 'price_usd' => 0.60, 'category' => 'Materiales', 'icon' => 'menu_book', 'color' => 'orange'],
                ['name' => 'Franela personalizada', 'price_bs' => 0.00, 'price_usd' => 8.00, 'category' => 'Franelas', 'icon' => 'checkroom', 'color' => 'violet'],
                ['name' => 'Resma de papel', 'price_bs' => 0.00, 'price_usd' => 6.00, 'category' => 'Papelería', 'icon' => 'inventory_2', 'color' => 'amber'],
            ];

            if ($db->fieldExists('category_id', 'print_products') && $db->tableExists('print_product_categories')) {
                $categories = $db->table('print_product_categories')->get()->getResultArray();
                $categoryIds = array_column($categories, 'id', 'name');
                foreach ($data as &$product) {
                    $product['category_id'] = $categoryIds[$product['category']] ?? null;
                    $product['product_type'] = in_array($product['category'], ['Papelería', 'Franelas'], true) ? 'product' : 'service';
                    $product['unit'] = $product['category'] === 'Papelería' ? 'paquete' : 'unidad';
                    $product['is_active'] = 1;
                    $product['sku'] = null;
                    $product['description'] = null;
                    $product['characteristics_json'] = null;
                    $product['created_at'] = date('Y-m-d H:i:s');
                    $product['updated_at'] = date('Y-m-d H:i:s');
                    if ($product['name'] === 'Franela personalizada') {
                        $product['product_type'] = 'custom';
                        $product['characteristics_json'] = json_encode([
                            ['name' => 'Talla', 'type' => 'select', 'required' => true, 'options' => array_map(static fn ($size) => ['label' => $size, 'price_bs' => 0, 'price_usd' => $size === 'XXL' ? 1 : 0], ['S', 'M', 'L', 'XL', 'XXL'])],
                            ['name' => 'Color', 'type' => 'text', 'required' => true, 'options' => []],
                            ['name' => 'Estampado', 'type' => 'select', 'required' => true, 'options' => [
                                ['label' => 'Frente', 'price_bs' => 0, 'price_usd' => 0],
                                ['label' => 'Frente y espalda', 'price_bs' => 0, 'price_usd' => 2],
                            ]],
                        ], JSON_UNESCAPED_UNICODE);
                    }
                }
                unset($product);
            }
            $db->table('print_products')->insertBatch($data);
        }

        if ($db->fieldExists('category_id', 'print_products') && $db->tableExists('print_product_categories')) {
            foreach ($db->table('print_product_categories')->select('id, name')->get()->getResultArray() as $category) {
                $db->table('print_products')
                    ->where('category', $category['name'])
                    ->where('category_id IS NULL')
                    ->update(['category_id' => $category['id']]);
            }
        }
    }
}
