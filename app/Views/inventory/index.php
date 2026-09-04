<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700;900&display=swap" rel="stylesheet">

    <!-- PWA -->
    <meta name="theme-color" content="#1e1b4b">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="<?= base_url('manifest.json') ?>">
    <link rel="apple-touch-icon" href="<?= base_url('favicon.ico') ?>">
    
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('<?= base_url("sw.js") ?>')
                    .then(reg => console.log('SW registrado', reg))
                    .catch(err => console.log('SW error', err));
            });
        }
    </script>
    
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen">
    
    <!-- Hero Header -->
    <div class="bg-gradient-to-br from-slate-900 via-purple-900 to-indigo-900 text-white pb-24 rounded-b-[3rem] shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-white opacity-5 rounded-full blur-3xl -mr-20 -mt-20"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-purple-500 opacity-10 rounded-full blur-2xl -ml-10 -mb-10"></div>

        <div class="p-6 pt-8 max-w-md mx-auto relative z-10">
            <div class="flex items-center justify-between mb-8">
                <a href="<?= base_url() ?>" class="w-10 h-10 rounded-full bg-white/10 backdrop-blur-md flex items-center justify-center text-white hover:bg-white/20 transition-all border border-white/10">
                    <span class="material-icons text-sm">arrow_back</span>
                </a>
                <div class="text-right">
                    <p class="text-xs font-medium text-purple-300 uppercase tracking-widest">Módulo</p>
                    <h1 class="text-2xl font-black tracking-tight">Inventario</h1>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4 border border-white/10 shadow-lg">
                    <p class="text-purple-200 text-[10px] font-bold uppercase tracking-wider mb-1">Total Items</p>
                    <h2 class="text-3xl font-black text-white"><?= $total_items ?></h2>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4 border border-white/10 shadow-lg">
                    <p class="text-rose-200 text-[10px] font-bold uppercase tracking-wider mb-1">Bajo Stock</p>
                    <h2 class="text-3xl font-black text-white"><?= $low_stock ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Menu Cards -->
    <div class="max-w-md mx-auto px-6 -mt-16 space-y-4 pb-12 relative z-20">
        
        <!-- Items Management -->
        <a href="<?= base_url('inventory/items') ?>" class="block bg-white p-6 rounded-3xl shadow-[0_20px_40px_-15px_rgba(0,0,0,0.1)] hover:shadow-[0_25px_50px_-10px_rgba(168,85,247,0.15)] transition-all active:scale-95 group border border-slate-50 relative overflow-hidden">
            <div class="absolute right-0 top-0 w-32 h-32 bg-purple-50 rounded-bl-full opacity-50 transition-transform group-hover:scale-110 origin-top-right"></div>
            <div class="relative z-10 flex items-center space-x-5">
                <div class="w-16 h-16 rounded-2xl bg-purple-600 text-white flex items-center justify-center shadow-lg shadow-purple-200 group-hover:rotate-6 transition-transform">
                    <span class="material-icons text-3xl">inventory_2</span>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-slate-800 group-hover:text-purple-700 transition-colors">Productos</h2>
                    <p class="text-sm text-slate-500 font-medium">Gestionar catálogo y existencias</p>
                </div>
            </div>
        </a>

        <!-- Movements -->
        <a href="<?= base_url('inventory/movements') ?>" class="block bg-white p-6 rounded-3xl shadow-[0_20px_40px_-15px_rgba(0,0,0,0.1)] hover:shadow-[0_25px_50px_-10px_rgba(99,102,241,0.15)] transition-all active:scale-95 group border border-slate-50 relative overflow-hidden">
            <div class="absolute right-0 top-0 w-32 h-32 bg-indigo-50 rounded-bl-full opacity-50 transition-transform group-hover:scale-110 origin-top-right"></div>
            <div class="relative z-10 flex items-center space-x-5">
                <div class="w-16 h-16 rounded-2xl bg-indigo-600 text-white flex items-center justify-center shadow-lg shadow-indigo-200 group-hover:-rotate-6 transition-transform">
                    <span class="material-icons text-3xl">sync_alt</span>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-slate-800 group-hover:text-indigo-600 transition-colors">Movimientos</h2>
                    <p class="text-sm text-slate-500 font-medium">Entradas, salidas y ajustes</p>
                </div>
            </div>
        </a>

    </div>
</body>
</html>
