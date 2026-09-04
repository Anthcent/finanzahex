<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class InstallCheck implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $path = trim($request->getUri()->getPath(), '/');
        if ($path === 'health' || $path === 'debug-check') {
            return;
        }

        // Verify database connectivity
        try {
            $db = \Config\Database::connect();
            $db->connect();
        } catch (\Throwable $e) {
            // Friendly setup page if DB is not configured or offline
            return Services::response()->setStatusCode(503)->setBody($this->renderDbSetupNotice($e->getMessage()));
        }

        // Run migrations if not yet recorded
        $lockFile = WRITEPATH . 'installed.lock';
        if (!file_exists($lockFile)) {
            try {
                $migrate = Services::migrations();
                $migrate->latest();
                @file_put_contents($lockFile, 'Installed on ' . date('Y-m-d H:i:s'));
            } catch (\Throwable $e) {
                return Services::response()->setStatusCode(500)->setBody("Error en migraciones: " . $e->getMessage());
            }
        }
    }

    private function renderDbSetupNotice(string $errorMessage): string
    {
        return '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Base de Datos - Fi-Hex Wallet</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: "Plus Jakarta Sans", sans-serif; }</style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-lg w-full bg-slate-900 border border-emerald-500/30 rounded-3xl p-6 sm:p-8 shadow-2xl relative overflow-hidden">
        <div class="absolute -right-16 -top-16 w-48 h-48 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="flex items-center gap-3 mb-6">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-700 flex items-center justify-center text-white font-black text-xl shadow-lg shadow-emerald-950/50">
                ⬡
            </div>
            <div>
                <h1 class="text-xl font-extrabold text-white">Fi-Hex Wallet</h1>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                    Esperando Base de Datos
                </span>
            </div>
        </div>
        <p class="text-slate-300 text-sm mb-4 leading-relaxed">
            El contenedor y el servidor web están <strong class="text-emerald-400">100% operativos</strong>. Para completar el inicio, vincula una base de datos en <strong>Dokploy</strong> o <strong>Hexper Ops</strong>.
        </p>
        <div class="bg-slate-950/80 rounded-2xl p-4 border border-slate-800 mb-5">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Paso a paso en Dokploy:</p>
            <ol class="text-xs text-slate-300 space-y-2 list-decimal list-inside">
                <li>Ve a tu panel de Dokploy y crea un servicio de <strong>PostgreSQL</strong> (o MySQL).</li>
                <li>En tu aplicación Fi-Hex, abre la pestaña <strong>Environment</strong>.</li>
                <li>Agrega la variable <code class="text-emerald-400 bg-emerald-950/50 px-1.5 py-0.5 rounded">DATABASE_URL</code> con la URL de tu base de datos (o asocia el servicio).</li>
                <li>Guarda los cambios y haz clic en <strong>Redeploy</strong>.</li>
            </ol>
        </div>
        <div class="text-[11px] text-slate-500 bg-slate-950/50 rounded-xl p-3 border border-slate-900 font-mono break-all">
            Detalle del intento: ' . htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') . '
        </div>
    </div>
</body>
</html>';
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
