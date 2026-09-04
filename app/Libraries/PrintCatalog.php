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
                ['name' => 'Impresión Texto', 'price_bs' => 3.00, 'price_usd' => 0.08, 'category' => 'Impresión', 'icon' => 'article', 'color' => 'blue'],
                ['name' => 'Impresión Imagen', 'price_bs' => 6.00, 'price_usd' => 0.15, 'category' => 'Impresión', 'icon' => 'image', 'color' => 'indigo'],
                ['name' => 'Fondo Negro', 'price_bs' => 10.00, 'price_usd' => 0.25, 'category' => 'Documentos', 'icon' => 'badge', 'color' => 'emerald'],
                ['name' => 'Título', 'price_bs' => 15.00, 'price_usd' => 0.35, 'category' => 'Documentos', 'icon' => 'school', 'color' => 'amber'],
                ['name' => 'Escaneo', 'price_bs' => 5.00, 'price_usd' => 0.12, 'category' => 'Servicios', 'icon' => 'scanner', 'color' => 'cyan'],
                ['name' => 'Plastificado Carta', 'price_bs' => 20.00, 'price_usd' => 0.50, 'category' => 'Materiales', 'icon' => 'layers', 'color' => 'rose'],
                ['name' => 'Anillado', 'price_bs' => 25.00, 'price_usd' => 0.60, 'category' => 'Materiales', 'icon' => 'menu_book', 'color' => 'orange'],
            ];
            $db->table('print_products')->insertBatch($data);
        }
    }
}
