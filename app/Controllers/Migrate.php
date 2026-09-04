<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use Config\Services;

class Migrate extends Controller
{
    public function index()
    {
        $migrate = \Config\Services::migrations();

        try {
            $migrate->latest();
            echo '<div style="font-family: sans-serif; padding: 20px; background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; border-radius: 8px;">';
            echo '<h1>✅ Migración Exitosa</h1>';
            echo '<p>La base de datos se ha actualizado correctamente.</p>';
            echo '<a href="' . base_url() . '" style="display: inline-block; margin-top: 10px; padding: 10px 20px; background: #059669; color: white; text-decoration: none; border-radius: 5px;">Volver al Sistema</a>';
            echo '</div>';
        } catch (\Throwable $e) {
            echo '<div style="font-family: sans-serif; padding: 20px; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; border-radius: 8px;">';
            echo '<h1>❌ Error en Migración</h1>';
            echo '<p>' . $e->getMessage() . '</p>';
            echo '<pre>' . $e->getTraceAsString() . '</pre>';
            echo '</div>';
        }
    }
}
