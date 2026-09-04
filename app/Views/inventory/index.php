<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Inventario | Fi-Hex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- PWA -->
    <meta name="theme-color" content="#022c22">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="<?= base_url('manifest.json') ?>">
    <link rel="apple-touch-icon" href="<?= base_url('favicon.ico') ?>">
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .safe-bottom { padding-bottom: max(1.5rem, env(safe-area-inset-bottom)); }
        .safe-top { padding-top: max(0.75rem, env(safe-area-inset-top)); }
    </style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800 antialiased">
    
    <!-- Hero Executive Header -->
    <div class="bg-gradient-to-r from-emerald-950 via-slate-900 to-teal-950 text-white pb-16 rounded-b-[2.5rem] shadow-xl relative overflow-hidden safe-top">
        <div class="absolute top-0 right-0 w-72 h-72 bg-emerald-500/10 rounded-full blur-3xl -mr-20 -mt-20 pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 w-60 h-60 bg-teal-500/10 rounded-full blur-3xl -ml-20 -mb-20 pointer-events-none"></div>

        <div class="p-4 pt-4 max-w-md mx-auto relative z-10">
            <div class="flex items-center justify-between mb-5">
                <a href="<?= base_url() ?>" class="w-10 h-10 rounded-2xl bg-white/10 hover:bg-white/20 active:scale-95 flex items-center justify-center text-white transition-all border border-white/10" title="Volver">
                    <span class="material-icons text-xl">arrow_back</span>
                </a>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-400 p-[1.5px]">
                        <div class="w-full h-full bg-slate-950 rounded-[9px] flex items-center justify-center">
                            <span class="text-[10px] font-black text-emerald-300">FW</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-[9px] font-black text-emerald-300 uppercase tracking-widest block">Módulo</span>
                        <h1 class="text-base font-black tracking-tight text-white leading-tight">Inventario & Stock</h1>
                    </div>
                </div>
            </div>

            <!-- Summary KPI Cards -->
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/15 shadow-md">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-emerald-200 text-[10px] font-black uppercase tracking-wider">Total Ítems</span>
                        <span class="material-icons text-emerald-300 text-sm">inventory_2</span>
                    </div>
                    <h2 class="text-3xl font-black text-white"><?= $total_items ?></h2>
                    <p class="text-[10px] text-emerald-200/70 font-semibold mt-0.5">En catálogo</p>
                </div>
                
                <div class="rounded-2xl p-4 border shadow-md <?= $low_stock > 0 ? 'bg-rose-500/20 border-rose-400/30' : 'bg-white/10 border-white/15' ?> backdrop-blur-md">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-[10px] font-black uppercase tracking-wider <?= $low_stock > 0 ? 'text-rose-200' : 'text-slate-300' ?>">Bajo Stock</span>
                        <span class="material-icons text-sm <?= $low_stock > 0 ? 'text-rose-300' : 'text-slate-300' ?>">warning_amber</span>
                    </div>
                    <h2 class="text-3xl font-black text-white"><?= $low_stock ?></h2>
                    <p class="text-[10px] font-semibold mt-0.5 <?= $low_stock > 0 ? 'text-rose-200' : 'text-slate-400' ?>"><?= $low_stock > 0 ? '¡Requiere reposición!' : 'Nivel óptimo' ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Navigation Actions -->
    <div class="max-w-md mx-auto px-4 -mt-7 space-y-3 pb-20 relative z-20 safe-bottom">
        
        <!-- Items Management -->
        <a href="<?= base_url('inventory/items') ?>" class="block bg-white p-4 sm:p-5 rounded-3xl shadow-xs hover:shadow-md transition-all active:scale-98 group border border-slate-100 relative overflow-hidden">
            <div class="flex items-center space-x-4">
                <div class="w-13 h-13 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-700 text-white flex items-center justify-center shadow-md shadow-emerald-950/20 group-hover:scale-105 transition-transform shrink-0">
                    <span class="material-icons text-2xl">inventory_2</span>
                </div>
                <div class="min-w-0 flex-1">
                    <h2 class="text-base font-black text-slate-900 group-hover:text-emerald-700 transition-colors">Catálogo de Productos</h2>
                    <p class="text-xs text-slate-500 font-bold">Existencias, precios y categorías</p>
                </div>
                <span class="material-icons text-slate-300 group-hover:text-emerald-600 transition-colors">chevron_right</span>
            </div>
        </a>

        <!-- Movements -->
        <a href="<?= base_url('inventory/movements') ?>" class="block bg-white p-4 sm:p-5 rounded-3xl shadow-xs hover:shadow-md transition-all active:scale-98 group border border-slate-100 relative overflow-hidden">
            <div class="flex items-center space-x-4">
                <div class="w-13 h-13 rounded-2xl bg-gradient-to-tr from-blue-600 to-blue-700 text-white flex items-center justify-center shadow-md shadow-blue-950/20 group-hover:scale-105 transition-transform shrink-0">
                    <span class="material-icons text-2xl">sync_alt</span>
                </div>
                <div class="min-w-0 flex-1">
                    <h2 class="text-base font-black text-slate-900 group-hover:text-blue-600 transition-colors">Movimientos de Stock</h2>
                    <p class="text-xs text-slate-500 font-bold">Entradas, salidas y ajustes de inventario</p>
                </div>
                <span class="material-icons text-slate-300 group-hover:text-blue-600 transition-colors">chevron_right</span>
            </div>
        </a>

    </div>
</body>
</html>
