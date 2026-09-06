<?php
$printColorHexes = [
    'slate' => '#475569', 'red' => '#dc2626', 'orange' => '#ea580c', 'amber' => '#d97706',
    'yellow' => '#ca8a04', 'lime' => '#65a30d', 'green' => '#16a34a', 'emerald' => '#059669',
    'teal' => '#0d9488', 'cyan' => '#0891b2', 'sky' => '#0284c7', 'blue' => '#2563eb',
    'indigo' => '#4f46e5', 'violet' => '#7c3aed', 'purple' => '#9333ea', 'fuchsia' => '#c026d3',
    'pink' => '#db2777', 'rose' => '#e11d48',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Impresiones & POS - Fi-Hex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <meta name="theme-color" content="#047857">
    <link rel="manifest" href="<?= base_url('manifest.json') ?>">
    <style>
        :root { color-scheme: light; }
        body { font-family: 'Plus Jakarta Sans', 'Outfit', sans-serif; }
        button, input, select { -webkit-tap-highlight-color: transparent; }
        button:focus-visible, a:focus-visible, input:focus-visible, select:focus-visible { outline: 3px solid rgba(16, 185, 129, .22); outline-offset: 2px; }
        .workspace-surface { background: rgba(255, 255, 255, .92); border: 1px solid rgba(226, 232, 240, .92); box-shadow: 0 18px 45px -32px rgba(15, 23, 42, .35); }
        .soft-grid { background-image: radial-gradient(circle at 1px 1px, rgba(15, 118, 110, .07) 1px, transparent 0); background-size: 22px 22px; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        @keyframes slide-up { from { transform: translateY(100%); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .animate-slide-up { animation: slide-up 0.28s cubic-bezier(0.16, 1, 0.3, 1); }
        .customize-scrollbar::-webkit-scrollbar { width: 5px; }
        .customize-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .customize-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
        [x-cloak] { display: none !important; }
        .safe-bottom { padding-bottom: env(safe-area-inset-bottom, 1rem); }
    </style>
</head>
<body class="bg-[#f4f7f6] soft-grid min-h-screen text-slate-800 antialiased selection:bg-emerald-500 selection:text-white" x-data="posApp()" @keydown.window="handleShortcut($event)">

    <!-- Top Navigation Header -->
    <header class="fixed top-0 inset-x-0 bg-white/95 backdrop-blur-xl z-40 border-b border-slate-200/80 h-16 transition-all">
        <div class="max-w-7xl mx-auto h-full px-3 sm:px-5 flex items-center justify-between gap-3">
            <!-- Left: Back button & Monogram Brand -->
            <div class="flex items-center gap-2.5 min-w-0">
                <a href="<?= base_url() ?>" class="w-9 h-9 flex items-center justify-center rounded-2xl bg-slate-100/80 hover:bg-emerald-50 text-slate-600 hover:text-emerald-700 transition-colors border border-slate-200/60 active:scale-95 shrink-0" title="Volver al inicio">
                    <span class="material-icons text-xl">arrow_back</span>
                </a>
                <div class="w-9 h-9 rounded-2xl bg-gradient-to-br from-emerald-600 to-teal-700 text-white flex items-center justify-center shadow-md shadow-emerald-950/20 ring-1 ring-emerald-400/40 shrink-0">
                    <span class="material-icons text-lg">print</span>
                </div>
                <div class="leading-tight min-w-0">
                    <h1 class="font-black text-slate-950 tracking-tight text-sm sm:text-base truncate">
                        Caja de <span class="bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent">impresiones</span>
                    </h1>
                    <p class="text-[9px] font-bold text-slate-400 hidden sm:flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Ventas, clientes y cobros</span>
                    </p>
                </div>
            </div>

            <!-- Right Toolbar: Tasa BCV & Settings -->
            <div class="flex items-center gap-2 shrink-0">
                <div class="hidden lg:flex items-center gap-2 text-[10px] font-bold text-slate-400 mr-1">
                    <span class="border border-slate-200 bg-slate-50 rounded-lg px-2 py-1">/ Buscar</span>
                    <span class="border border-slate-200 bg-slate-50 rounded-lg px-2 py-1">F2 Cobrar</span>
                </div>
                <!-- BCV Rate Pill -->
                <div class="h-9 flex items-center bg-emerald-50/60 border border-emerald-200/80 rounded-2xl px-2.5 py-1 shadow-2xs">
                    <div class="flex flex-col items-end leading-none">
                        <div class="flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            <span class="text-[7.5px] font-black uppercase tracking-wider text-emerald-800">TASA BCV</span>
                        </div>
                        <div class="flex items-baseline mt-0.5">
                            <span class="text-[9px] font-black text-emerald-700 mr-0.5">Bs</span>
                            <input type="number" step="0.01" x-model.number="exchangeRate" @input="updateTotals()" class="w-14 font-mono font-black text-emerald-950 bg-transparent text-right outline-none text-xs p-0 border-none">
                        </div>
                    </div>
                    <button type="button" @click="fetchRate()" class="ml-1.5 pl-1 border-l border-emerald-200 text-emerald-600 hover:text-emerald-800 transition-colors" title="Actualizar Tasa">
                        <span class="material-icons text-[13px]">sync</span>
                    </button>
                </div>

                <!-- Settings Button -->
                <a href="<?= base_url('printing/settings') ?>" class="w-9 h-9 flex items-center justify-center rounded-2xl bg-white hover:bg-emerald-50 text-slate-600 hover:text-emerald-700 border border-slate-200/80 hover:border-emerald-300 shadow-2xs transition-all active:scale-95" title="Configurar Productos">
                    <span class="material-icons text-base">settings</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Navigation Tabs -->
    <div class="max-w-7xl mx-auto px-3 sm:px-5 mt-20 mb-5 sticky top-[72px] z-30">
        <div class="workspace-surface backdrop-blur-md rounded-2xl p-1.5 flex gap-1.5 max-w-xl">
            <button @click="tab = 'pos'" 
                    :class="tab === 'pos' ? 'bg-gradient-to-r from-emerald-600 to-teal-700 text-white shadow-md shadow-emerald-950/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/60'" 
                    class="flex-1 py-2.5 rounded-xl font-bold text-xs sm:text-sm transition-all flex items-center justify-center gap-1.5 active:scale-98" title="Venta rápida (Alt+1)">
                <span class="material-icons text-base">point_of_sale</span>
                <span>Venta</span>
                <span x-show="cartUnits > 0" x-text="cartUnits" class="bg-white/20 text-current text-[9px] font-black px-1.5 py-0.5 rounded-full min-w-[18px] text-center"></span>
            </button>
            <button @click="fetchHistory(); tab = 'debts'" 
                    :class="tab === 'debts' ? 'bg-gradient-to-r from-emerald-600 to-teal-700 text-white shadow-md shadow-emerald-950/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/60'" 
                    class="flex-1 py-2.5 rounded-xl font-bold text-xs sm:text-sm transition-all flex items-center justify-center gap-1.5 active:scale-98 relative" title="Cobros pendientes (Alt+2)">
                <span class="material-icons text-base">schedule</span>
                <span>Deudas</span>
                <span x-show="debtsCount > 0" x-text="debtsCount" class="bg-rose-500 text-white text-[10px] font-black px-1.5 py-0.2 rounded-full min-w-[18px] text-center ml-0.5"></span>
            </button>
            <button @click="tab = 'history'; fetchMovements()" 
                    :class="tab === 'history' ? 'bg-gradient-to-r from-emerald-600 to-teal-700 text-white shadow-md shadow-emerald-950/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/60'" 
                    class="flex-1 py-2.5 rounded-xl font-bold text-xs sm:text-sm transition-all flex items-center justify-center gap-1.5 active:scale-98" title="Historial (Alt+3)">
                <span class="material-icons text-base">history</span>
                <span>Historial</span>
            </button>
        </div>
    </div>

    <!-- Main Container Area -->
    <main class="pb-36 px-3 sm:px-5 max-w-7xl mx-auto">
        
        <!-- POS Tab -->
        <div x-show="tab === 'pos'" class="flex flex-col lg:flex-row gap-5 xl:gap-6 items-start">
            
            <!-- Products Section -->
            <div class="flex-1 min-w-0">
                <!-- Fast product finder -->
                <div class="mb-4 workspace-surface backdrop-blur-md p-3 sm:p-4 rounded-2xl space-y-3">
                    <div class="relative">
                        <span class="material-icons absolute left-3.5 top-3 text-slate-400 text-lg">search</span>
                        <input x-ref="productSearch" type="search" x-model="productSearch" placeholder="Buscar servicio por nombre..." class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-11 pr-10 py-3 text-sm font-bold text-slate-700 outline-none focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 transition-colors">
                        <button x-show="productSearch" @click="productSearch = ''" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-700" title="Limpiar búsqueda">
                            <span class="material-icons text-lg">close</span>
                        </button>
                    </div>
                    <div class="flex gap-2 overflow-x-auto no-scrollbar">
                        <button @click="activeCategory = 'all'" :class="activeCategory === 'all' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-3 py-1.5 rounded-xl text-[11px] font-black whitespace-nowrap transition-colors">Todos</button>
                        <?php foreach (array_values(array_unique(array_column($products, 'category'))) as $category): ?>
                        <button @click="activeCategory = <?= htmlspecialchars(json_encode($category)) ?>" :class="activeCategory === <?= htmlspecialchars(json_encode($category)) ?> ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'" class="px-3 py-1.5 rounded-xl text-[11px] font-black whitespace-nowrap transition-colors"><?= esc($category) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Product Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-2.5 sm:gap-3">
                    <?php foreach ($products as $p): ?>
                    <button x-show="matchesProduct(<?= htmlspecialchars(json_encode($p['name'])) ?>, <?= htmlspecialchars(json_encode($p['category'])) ?>)" @click="addToCart(<?= htmlspecialchars(json_encode($p)) ?>)"
                            :class="cartQuantity(<?= (int) $p['id'] ?>) > 0 ? 'border-emerald-400 ring-2 ring-emerald-100 bg-emerald-50/40' : 'border-slate-200/70 bg-white'"
                            class="hover:bg-emerald-50/40 p-3 sm:p-3.5 rounded-2xl shadow-2xs hover:shadow-lg hover:-translate-y-0.5 border hover:border-emerald-300 flex flex-col items-start justify-between gap-2 active:scale-[.97] transition-all group relative overflow-hidden min-h-[118px] text-left">
                        <span x-show="cartQuantity(<?= (int) $p['id'] ?>) > 0" x-text="cartQuantity(<?= (int) $p['id'] ?>)" class="absolute top-2 right-2 min-w-6 h-6 px-1.5 rounded-full bg-emerald-600 text-white text-[11px] font-black flex items-center justify-center shadow-md"></span>
                        <?php $productColor = $printColorHexes[$p['color'] ?? 'emerald'] ?? $printColorHexes['emerald']; ?>
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center transition-colors" style="background-color: <?= $productColor ?>18; color: <?= $productColor ?>">
                            <span class="material-icons text-xl group-hover:scale-110 transition-transform"><?= $p['icon'] ?? 'print' ?></span>
                        </div>
                        <div class="w-full">
                            <p class="font-black text-xs sm:text-[13px] leading-tight text-slate-800 line-clamp-2 group-hover:text-emerald-950 transition-colors"><?= $p['name'] ?></p>
                            <div class="text-[10px] font-black text-slate-500 mt-1.5 flex items-baseline gap-1.5">
                                <span class="text-emerald-700 font-extrabold" x-text="'Bs. ' + unitPriceBs(<?= (float) $p['price_bs'] ?>, <?= (float) $p['price_usd'] ?>).toFixed(2)"></span>
                                <span class="text-[9px] text-slate-400 font-bold" x-text="'$' + formatUsd(unitPriceUsd(<?= (float) $p['price_bs'] ?>, <?= (float) $p['price_usd'] ?>))"></span>
                            </div>
                        </div>
                    </button>
                    <?php endforeach; ?>
                </div>

                <div x-show="catalog.length > 0 && visibleProductsCount === 0" x-cloak class="text-center py-14 bg-white/70 rounded-3xl border border-dashed border-slate-200">
                    <span class="material-icons text-4xl text-slate-300 mb-2">search_off</span>
                    <p class="font-black text-slate-600 text-sm">No encontramos servicios</p>
                    <button type="button" @click="productSearch = ''; activeCategory = 'all'" class="mt-3 text-xs font-black text-emerald-700 bg-emerald-50 px-3 py-2 rounded-xl">Limpiar búsqueda</button>
                </div>

                <?php if (empty($products)): ?>
                <div class="text-center py-16 bg-white/60 rounded-3xl border border-dashed border-slate-200">
                    <span class="material-icons text-4xl text-slate-300 mb-2">inventory_2</span>
                    <p class="font-bold text-slate-600 text-sm">No hay productos registrados</p>
                    <a href="<?= base_url('printing/settings') ?>" class="inline-flex items-center gap-1.5 mt-3 text-xs font-bold text-emerald-700 bg-emerald-50 px-3.5 py-2 rounded-xl hover:bg-emerald-100 transition-colors">
                        <span class="material-icons text-sm">add</span> Crear producto
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Desktop Sticky Cart Panel -->
            <div class="hidden lg:block w-[390px] xl:w-[420px] shrink-0">
                <div class="workspace-surface backdrop-blur-xl rounded-3xl p-5 sticky top-[140px] overflow-hidden">
                    <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <div class="w-9 h-9 rounded-xl bg-slate-900 text-white flex items-center justify-center"><span class="material-icons text-lg">shopping_cart</span></div>
                            <div>
                                <h2 class="font-black text-slate-900 text-sm">Orden en curso</h2>
                                <p class="text-[10px] font-bold text-slate-400" x-text="cartUnits + (cartUnits === 1 ? ' unidad seleccionada' : ' unidades seleccionadas')"></p>
                            </div>
                        </div>
                        <button @click="cart = []; updateTotals()" class="text-[11px] text-rose-500 hover:text-rose-700 font-bold uppercase transition-colors" x-show="cart.length > 0">
                            Vaciar
                        </button>
                    </div>

                    <!-- Cart Item Rows -->
                    <div class="space-y-2.5 mb-5 max-h-[46vh] overflow-y-auto customize-scrollbar pr-1">
                        <template x-for="(item, index) in cart" :key="index">
                            <div class="group flex flex-col gap-2 bg-slate-50/80 p-3 rounded-2xl border border-slate-200/70">
                                <div class="flex justify-between items-start">
                                    <div class="min-w-0 flex-1 pr-2">
                                        <p class="font-bold text-slate-800 text-xs truncate" x-text="item.name"></p>
                                        <p class="text-[10px] font-black text-emerald-700 mt-0.5" x-text="'Bs. ' + getLineTotalBs(item)"></p>
                                    </div>
                                    <div class="flex items-center gap-1 shrink-0 bg-white p-1 rounded-xl border border-slate-200/70">
                                        <button @click="decreaseItem(index)" class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center font-black text-xs active:scale-95 transition-all">−</button>
                                        <input type="number" min="1" max="999" x-model.number="item.quantity" @input="updateTotals()" @change="normalizeQuantity(item)" class="font-black text-xs w-10 h-7 text-center text-slate-800 bg-transparent outline-none">
                                        <button @click="increaseItem(item)" class="w-7 h-7 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white flex items-center justify-center font-black text-xs active:scale-95 transition-all">+</button>
                                    </div>
                                </div>
                                <input type="text" x-model="item.note" placeholder="Nota del pedido..." class="text-[11px] bg-white border border-slate-200/70 rounded-xl px-2.5 py-1.5 w-full outline-none focus:border-emerald-500 text-slate-700">
                            </div>
                        </template>
                        
                        <div x-show="cart.length === 0" class="py-12 flex flex-col items-center justify-center text-slate-400 gap-2">
                            <div class="w-14 h-14 rounded-full bg-slate-50 flex items-center justify-center text-slate-300">
                                <span class="material-icons text-3xl">shopping_bag</span>
                            </div>
                            <p class="text-xs font-bold text-slate-400">Carrito vacío</p>
                            <p class="text-[10px] text-slate-400 text-center max-w-[160px]">Toca un servicio o producto para agregarlo a la orden.</p>
                        </div>
                    </div>

                    <!-- Totals Box -->
                    <div class="space-y-1.5 mb-5 border-t border-slate-100 pt-4 bg-gradient-to-br from-emerald-50 to-teal-50/60 -mx-5 -mb-5 p-5 rounded-b-3xl">
                        <div class="flex justify-between items-baseline">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Bs</span>
                            <span class="text-2xl font-black text-slate-900 tracking-tight" x-text="'Bs. ' + totalBs.toFixed(2)"></span>
                        </div>
                        <div class="flex justify-between items-baseline mb-4">
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total USD</span>
                            <span class="text-sm font-black text-emerald-800" x-text="'$ ' + formatUsd(totalUsd)"></span>
                        </div>

                        <button @click="openCheckout()"
                                :disabled="cart.length === 0" 
                                class="w-full bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white font-black py-3.5 rounded-2xl shadow-lg shadow-emerald-950/20 active:scale-98 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                            <span>Cobrar / Facturar</span>
                            <span class="material-icons text-base">arrow_forward</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Floating Bottom Bar (Cart Trigger) -->
        <div x-show="tab === 'pos'" class="lg:hidden">
            <div class="fixed bottom-3 inset-x-3 bg-white/95 backdrop-blur-xl border border-slate-200/80 rounded-2xl p-3.5 z-40 shadow-xl flex items-center justify-between gap-3 safe-bottom" 
                 x-show="!cartOpen && cart.length > 0">
                <div @click="cartOpen = true" class="flex-1 cursor-pointer min-w-0">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider flex items-center gap-1">
                        <span class="material-icons text-xs text-emerald-600">shopping_bag</span>
                        <span x-text="cartUnits"></span> unidades seleccionadas
                    </p>
                    <div class="flex items-baseline gap-2 mt-0.5">
                        <p class="text-lg font-black text-slate-900" x-text="'Bs. ' + totalBs.toFixed(2)"></p>
                        <p class="text-xs font-bold text-emerald-700" x-text="'$' + formatUsd(totalUsd)"></p>
                    </div>
                </div>
                <button @click="cartOpen = true" class="w-11 h-11 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center active:scale-95" title="Ver carrito">
                    <span class="material-icons text-lg">keyboard_arrow_up</span>
                </button>
                <button @click="openCheckout()" class="bg-gradient-to-r from-emerald-600 to-teal-700 text-white px-5 py-2.5 rounded-xl font-black text-sm shadow-md shadow-emerald-950/20 active:scale-95">
                    Cobrar
                </button>
            </div>

            <!-- Mobile Full Slide-up Drawer -->
            <div x-show="cartOpen" class="fixed inset-0 z-50 flex items-end" x-cloak>
                <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-xs" @click="cartOpen = false"></div>
                <div class="bg-white rounded-t-[2.5rem] w-full max-h-[88vh] flex flex-col relative z-10 animate-slide-up shadow-2xl safe-bottom">
                    <div class="w-full flex justify-center pt-3 pb-1" @click="cartOpen = false">
                        <div class="w-12 h-1.5 bg-slate-300 rounded-full"></div>
                    </div>
                    
                    <div class="p-5 flex-1 overflow-y-auto customize-scrollbar">
                        <div class="flex justify-between items-center mb-4 pb-2 border-b border-slate-100">
                            <div class="flex items-center gap-2">
                                <span class="material-icons text-emerald-600">shopping_cart</span>
                                <h2 class="text-lg font-black text-slate-900">Tu Pedido</h2>
                            </div>
                            <button @click="cart = []; updateTotals(); cartOpen = false" class="text-xs text-rose-500 font-black uppercase">
                                Limpiar
                            </button>
                        </div>

                        <div class="space-y-3">
                            <template x-for="(item, index) in cart" :key="index">
                                <div class="flex flex-col gap-2.5 bg-slate-50/80 p-3.5 rounded-2xl border border-slate-100">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="flex-1 min-w-0">
                                            <p class="font-black text-slate-800 text-sm leading-tight truncate" x-text="item.name"></p>
                                            <p class="text-xs font-black text-emerald-700 mt-0.5" x-text="'Bs. ' + getLineTotalBs(item)"></p>
                                        </div>
                                        <div class="flex items-center gap-1 bg-white p-1 rounded-xl border border-slate-200/80 shadow-2xs">
                                            <button @click="decreaseItem(index)" class="w-10 h-10 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center font-black text-base active:scale-95">−</button>
                                            <input type="number" min="1" max="999" x-model.number="item.quantity" @input="updateTotals()" @change="normalizeQuantity(item)" class="w-12 h-10 text-center font-black text-sm text-slate-900 bg-transparent outline-none">
                                            <button @click="increaseItem(item)" class="w-10 h-10 rounded-lg bg-emerald-600 text-white flex items-center justify-center font-black text-base active:scale-95">+</button>
                                        </div>
                                    </div>
                                    <input type="text" x-model="item.note" placeholder="Nota o detalle del item..." class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs outline-none focus:border-emerald-500">
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="p-5 border-t border-slate-200/70 bg-slate-50/80">
                        <div class="flex justify-between items-baseline mb-4">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total a Pagar</span>
                            <div class="text-right">
                                <p class="text-2xl font-black text-slate-900" x-text="'Bs. ' + totalBs.toFixed(2)"></p>
                                <p class="text-xs font-bold text-emerald-700" x-text="'$ ' + formatUsd(totalUsd)"></p>
                            </div>
                        </div>
                        <button @click="cartOpen = false; openCheckout()" class="w-full bg-gradient-to-r from-emerald-600 to-teal-700 text-white font-black py-4 rounded-2xl shadow-xl shadow-emerald-950/20 text-base active:scale-98">
                            Confirmar y Cobrar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Debts Tab -->
        <div x-show="tab === 'debts'">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                <div class="workspace-surface rounded-2xl p-3.5 col-span-2 lg:col-span-1">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Por cobrar</p>
                    <p class="text-xl font-black text-rose-600 mt-1" x-text="'Bs. ' + debtTotalBs.toFixed(2)"></p>
                </div>
                <div class="workspace-surface rounded-2xl p-3.5">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Órdenes</p>
                    <p class="text-xl font-black text-slate-900 mt-1" x-text="debtsCount"></p>
                </div>
                <div class="workspace-surface rounded-2xl p-3.5">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Clientes</p>
                    <p class="text-xl font-black text-slate-900 mt-1" x-text="debtCustomersCount"></p>
                </div>
                <div class="workspace-surface rounded-2xl p-3.5">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Con abonos</p>
                    <p class="text-xl font-black text-amber-600 mt-1" x-text="partialDebtsCount"></p>
                </div>
            </div>
            <!-- Search Bar -->
            <div class="mb-4 workspace-surface rounded-2xl p-3">
                <div class="relative">
                    <span class="material-icons absolute left-3.5 top-3 text-slate-400 text-lg">search</span>
                    <input type="text" x-model="searchQuery" placeholder="Buscar cliente, servicio o número de orden..." class="w-full bg-slate-50 border border-slate-200/90 rounded-xl pl-10 pr-10 py-3 text-xs sm:text-sm font-bold text-slate-700 outline-none focus:bg-white focus:border-emerald-500">
                    <button x-show="searchQuery" @click="searchQuery = ''" class="absolute right-3.5 top-3 text-slate-400 hover:text-slate-600">
                        <span class="material-icons text-sm">close</span>
                    </button>
                </div>
            </div>

            <!-- Debts Cards List -->
            <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-3 pb-24">
                <template x-for="order in filteredOrders" :key="order.id">
                    <div class="workspace-surface p-4 rounded-2xl hover:shadow-lg hover:-translate-y-0.5 flex flex-col gap-3 relative overflow-hidden transition-all">
                        <div class="flex justify-between items-start gap-2">
                            <div>
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-wider" x-text="'Orden #' + order.id + ' · ' + formatCustomerDate(order.created_at)"></p>
                                <h3 class="font-black text-slate-900 text-sm leading-tight mt-1" x-text="order.customer_name || 'Cliente sin nombre'"></h3>
                                <div class="text-[11px] text-slate-500 mt-1 leading-snug">
                                    <template x-for="detail in parseDetails(order.details)">
                                        <span class="inline-block bg-slate-100 text-slate-700 px-2 py-0.5 rounded-lg mr-1 mb-1 font-medium" x-text="detail"></span>
                                    </template>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-xl text-[10px] font-black uppercase tracking-wider border shrink-0" 
                                  :class="{
                                      'bg-emerald-50 text-emerald-700 border-emerald-200': order.status === 'paid', 
                                      'bg-amber-50 text-amber-700 border-amber-200': order.status === 'partial', 
                                      'bg-rose-50 text-rose-600 border-rose-200': order.status === 'pending'
                                  }" 
                                  x-text="order.status === 'paid' ? 'Pagado' : (order.status === 'partial' ? 'Parcial' : 'Pendiente')">
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-3 bg-slate-50/80 rounded-xl p-2.5 border border-slate-100">
                            <div>
                                <p class="text-[9px] uppercase font-bold text-slate-400 tracking-wider">Total Orden</p>
                                <div class="font-black text-slate-800 text-sm" x-text="'Bs. ' + parseFloat(order.total_bs).toFixed(2)"></div>
                            </div>
                            <div class="text-right">
                                <p class="text-[9px] uppercase font-black text-rose-500 tracking-wider">Deuda Pendiente</p>
                                <div class="font-black text-rose-600 text-sm" x-text="'Bs. ' + calculateDebt(order, 'Bs')"></div>
                                <div class="text-[10px] font-bold text-rose-500/80" x-text="'$ ' + calculateDebt(order, 'USD')"></div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 pt-1">
                            <button x-show="order.status !== 'paid'" 
                                    @click="openPayModal(order)" 
                                    class="flex-1 bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white py-2.5 rounded-xl font-bold shadow-xs text-xs flex items-center justify-center gap-1.5 active:scale-98 transition-all">
                                <span class="material-icons text-sm">payments</span>
                                <span>Abonar Pago</span>
                            </button>
                            <button @click="openEditModal(order)" class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-emerald-50 text-slate-600 hover:text-emerald-700 flex items-center justify-center transition-colors border border-slate-200/60" title="Editar orden">
                                <span class="material-icons text-base">edit</span>
                            </button>
                            <button @click="confirmDelete(order.id)" class="w-10 h-10 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 flex items-center justify-center transition-colors border border-rose-200/60" title="Eliminar orden">
                                <span class="material-icons text-base">delete</span>
                            </button>
                        </div>
                    </div>
                </template>
                
                <div x-show="filteredOrders.length === 0" class="md:col-span-2 xl:col-span-3 text-center py-16 bg-white/70 rounded-3xl border border-dashed border-slate-200">
                    <span class="material-icons text-4xl text-emerald-400 mb-2">check_circle</span>
                    <p class="font-bold text-slate-600 text-sm">¡Al día! No hay deudas pendientes</p>
                </div>
            </div>
        </div>

        <!-- History Tab -->
        <div x-show="tab === 'history'">
            <!-- Metrics & Filters -->
            <div class="mb-5 space-y-3">
                <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
                    <div class="workspace-surface p-3.5 rounded-2xl">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Cobrado (Hoy)</p>
                        <p class="text-xl font-black text-emerald-600 mt-0.5" x-text="'Bs. ' + historyMetrics.today_bs.toFixed(2)"></p>
                    </div>
                    <div class="workspace-surface p-3.5 rounded-2xl">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Deuda Total</p>
                        <p class="text-xl font-black text-rose-600 mt-0.5" x-text="'Bs. ' + historyMetrics.debt_bs.toFixed(2)"></p>
                    </div>
                    <div class="workspace-surface p-3.5 rounded-2xl col-span-2 lg:col-span-1">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Resultados</p>
                        <p class="text-xl font-black text-slate-900 mt-0.5" x-text="filteredHistory.length"></p>
                    </div>
                </div>

                <div class="workspace-surface rounded-2xl p-3 flex flex-col sm:flex-row gap-2.5">
                    <div class="relative flex-1 min-w-0">
                        <span class="material-icons absolute left-3.5 top-3 text-slate-400 text-base">search</span>
                        <input type="text" x-model="historySearch" placeholder="Buscar cliente, servicio u orden..." class="w-full bg-slate-50 border border-slate-200/90 rounded-xl pl-10 pr-3 py-2.5 text-xs sm:text-sm font-bold text-slate-700 outline-none focus:bg-white focus:border-emerald-500">
                    </div>
                    <select x-model="historyFilter" class="w-full sm:w-auto bg-slate-50 border border-slate-200/90 rounded-xl px-3 py-2.5 text-xs font-bold text-slate-700 outline-none focus:bg-white focus:border-emerald-500">
                        <option value="all">Todos</option>
                        <option value="pending">Deudas</option>
                        <option value="partial">Parcial</option>
                        <option value="paid">Pagados</option>
                    </select>
                </div>
            </div>

            <!-- History List -->
            <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-3 pb-24">
                <template x-for="order in filteredHistory" :key="order.id">
                    <button type="button" class="workspace-surface p-4 rounded-2xl hover:shadow-lg hover:-translate-y-0.5 hover:border-emerald-200 transition-all cursor-pointer text-left" @click="openOrderDetails(order)">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <h3 class="font-black text-slate-900 text-sm leading-tight" x-text="order.customer_name || 'Sin Nombre'"></h3>
                                <p class="text-[10px] font-bold text-slate-400 mt-0.5" x-text="'Orden #' + order.id + ' · ' + formatCustomerDate(order.created_at)"></p>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-xl text-[10px] font-black uppercase tracking-wider border shrink-0" 
                                  :class="{
                                      'bg-emerald-50 text-emerald-700 border-emerald-200': order.status === 'paid', 
                                      'bg-amber-50 text-amber-700 border-amber-200': order.status === 'partial', 
                                      'bg-rose-50 text-rose-600 border-rose-200': order.status === 'pending'
                                  }" 
                                  x-text="order.status === 'paid' ? 'Pagado' : (order.status === 'partial' ? 'Parcial' : 'Pendiente')">
                            </span>
                        </div>
                        <div class="flex justify-between items-end pt-1">
                            <div class="text-[11px] text-slate-500 max-w-[65%] truncate">
                                <template x-for="detail in parseDetails(order.details)">
                                    <span x-text="detail + ' '" class="inline-block truncate"></span>
                                </template>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-sm font-black text-slate-900" x-text="'Bs. ' + parseFloat(order.total_bs).toFixed(2)"></p>
                                <p class="text-[10px] font-bold text-rose-500" x-show="order.status !== 'paid'" x-text="'Debe: Bs. ' + calculateDebt(order, 'Bs')"></p>
                            </div>
                        </div>
                    </button>
                </template>

                <div x-show="filteredHistory.length === 0" class="md:col-span-2 xl:col-span-3 text-center py-16 bg-white/70 rounded-3xl border border-dashed border-slate-200">
                    <span class="material-icons text-4xl text-slate-300 mb-2">receipt_long</span>
                    <p class="font-bold text-slate-500 text-sm">No hay registros con ese criterio</p>
                </div>
            </div>
        </div>

    </main>

    <!-- ==================== RESPONSIVE MODALS ==================== -->

    <!-- Checkout Modal -->
    <div x-show="checkoutModal.open" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4" x-cloak>
        <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-xs" @click="checkoutModal.open = false"></div>
        <div class="bg-white rounded-t-[2rem] sm:rounded-3xl shadow-2xl w-full sm:max-w-3xl relative z-10 p-5 sm:p-6 space-y-5 max-h-[94vh] overflow-y-auto customize-scrollbar animate-slide-up safe-bottom">
            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-emerald-600">Finalizar orden</p>
                    <h3 class="font-black text-xl text-slate-900">Confirmar cobro</h3>
                    <div class="flex items-baseline gap-2 mt-0.5">
                        <span class="text-base font-black text-emerald-700" x-text="'Bs. ' + totalBs.toFixed(2)"></span>
                        <span class="text-xs font-bold text-slate-400" x-text="'$ ' + formatUsd(totalUsd)"></span>
                    </div>
                </div>
                <button @click="checkoutModal.open = false" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 flex items-center justify-center">
                    <span class="material-icons text-base">close</span>
                </button>
            </div>

            <div class="grid lg:grid-cols-2 gap-5 items-start">
                <!-- Customer Name Autocomplete -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider">Cliente</label>
                        <button type="button" @click="$refs.customerInput.focus(); openCustomerPicker()" class="inline-flex items-center gap-1 text-[10px] font-black text-emerald-700 hover:text-emerald-900 bg-emerald-50 hover:bg-emerald-100 px-2.5 py-1 rounded-lg transition-colors">
                            <span class="material-icons text-sm">people</span>
                            Buscar clientes
                        </button>
                    </div>
                    <div class="relative group" @click.outside="customerSuggestions.show = false">
                        <span class="material-icons absolute left-3.5 top-3 text-slate-400 text-lg">person_search</span>
                        <input x-ref="customerInput" type="text" x-model="customer_name" @input="queueCustomerSearch()" @focus="openCustomerPicker()" @keydown.enter.prevent="chooseFirstCustomer()" @keydown.escape="customerSuggestions.show = false" autocomplete="off" class="w-full bg-slate-50 border border-slate-200 rounded-2xl pl-11 pr-11 py-3 font-bold text-slate-800 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm" placeholder="Escribe o selecciona un cliente">
                        <button type="button" x-show="customer_name" @click="toggleFavorite()" class="absolute right-3 top-3 text-slate-300 hover:text-amber-400 transition-colors" :class="isFavorite ? 'text-amber-400' : ''" title="Guardar como cliente frecuente">
                            <span class="material-icons text-xl" x-text="isFavorite ? 'star' : 'star_border'"></span>
                        </button>

                        <!-- Suggestions Dropdown -->
                        <div x-show="customerSuggestions.show" x-cloak class="absolute top-full left-0 right-0 bg-white shadow-2xl rounded-2xl border border-slate-200 mt-1.5 max-h-72 overflow-y-auto z-50 customize-scrollbar">
                            <div class="sticky top-0 bg-white/95 backdrop-blur-sm px-3.5 py-2 border-b border-slate-100 flex items-center justify-between">
                                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400" x-text="customer_name.trim() ? 'Resultados' : 'Frecuentes y recientes'"></span>
                                <span class="text-[10px] font-bold text-slate-400" x-show="!customerSuggestions.loading" x-text="customerSuggestions.list.length + ' clientes'"></span>
                            </div>
                            <div x-show="customerSuggestions.loading" class="px-4 py-5 text-center text-xs font-bold text-slate-400">
                                <span class="material-icons animate-spin text-base align-middle mr-1">refresh</span> Buscando...
                            </div>
                            <template x-for="cust in customerSuggestions.list" :key="cust.key || cust.name">
                                <button type="button" @click="selectCustomer(cust)" class="w-full px-3.5 py-3 hover:bg-emerald-50 cursor-pointer flex justify-between items-center gap-3 transition-colors border-b border-slate-50 last:border-0 text-left">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center font-black text-xs shrink-0" x-text="customerInitials(cust.name)"></div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1">
                                                <span class="font-black text-slate-800 text-xs truncate" x-text="cust.name"></span>
                                                <span x-show="cust.is_favorite == 1" class="material-icons text-sm text-amber-400">star</span>
                                            </div>
                                            <p class="text-[10px] text-slate-400 font-bold mt-0.5" x-text="cust.last_order_at ? 'Última compra ' + formatCustomerDate(cust.last_order_at) : 'Cliente guardado'"></p>
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-end gap-1 shrink-0">
                                        <span x-show="cust.order_count > 0" class="text-[9px] font-black text-slate-500 bg-slate-100 px-2 py-0.5 rounded-lg" x-text="cust.order_count + (cust.order_count == 1 ? ' orden' : ' órdenes')"></span>
                                        <span x-show="cust.open_orders > 0" class="text-[9px] font-black text-rose-600 bg-rose-50 px-2 py-0.5 rounded-lg" x-text="cust.open_orders + (cust.open_orders == 1 ? ' pendiente' : ' pendientes')"></span>
                                    </div>
                                </button>
                            </template>
                            <div x-show="!customerSuggestions.loading && customerSuggestions.list.length === 0" class="px-5 py-6 text-center">
                                <span class="material-icons text-2xl text-slate-300">person_add</span>
                                <p class="text-xs font-black text-slate-500 mt-1">No encontramos ese nombre</p>
                                <p class="text-[10px] text-slate-400 mt-0.5">Puedes continuar y se guardará al registrar la orden.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Compact customer history -->
                    <div x-show="customerProfile.name" x-cloak class="mt-2.5 rounded-2xl border overflow-hidden" :class="customerOpenOrders > 0 ? 'bg-rose-50/40 border-rose-100' : 'bg-slate-50 border-slate-200'">
                        <div class="px-3.5 py-2.5 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Historial del cliente</p>
                                <p class="text-xs font-black text-slate-800 truncate mt-0.5" x-text="customerProfile.name"></p>
                            </div>
                            <div x-show="customerProfile.loading" class="text-[10px] font-bold text-slate-400"><span class="material-icons animate-spin text-sm align-middle">refresh</span></div>
                            <div x-show="!customerProfile.loading" class="text-right shrink-0">
                                <p class="text-[10px] font-black text-slate-500" x-text="customerProfile.totalOrders + (customerProfile.totalOrders === 1 ? ' orden registrada' : ' órdenes registradas')"></p>
                                <p x-show="customerOpenOrders > 0" class="text-[10px] font-black text-rose-600" x-text="'Pendiente: Bs. ' + customerDebtBs.toFixed(2)"></p>
                            </div>
                        </div>
                        <div x-show="!customerProfile.loading && customerProfile.orders.length > 0" class="border-t border-slate-200/70 bg-white/70">
                            <template x-for="order in customerPreviewOrders" :key="order.id">
                                <button type="button" @click="openOrderDetails(order)" class="w-full px-3.5 py-2.5 flex items-center justify-between gap-3 text-left hover:bg-white transition-colors border-b border-slate-100 last:border-0">
                                    <div class="min-w-0">
                                        <p class="text-[10px] font-black text-slate-700" x-text="'Orden #' + order.id + ' · ' + formatCustomerDate(order.created_at)"></p>
                                        <p class="text-[9px] text-slate-400 truncate mt-0.5" x-text="parseDetails(order.details).join(' · ')"></p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <p class="text-[10px] font-black text-slate-800" x-text="'Bs. ' + Number(order.total_bs).toFixed(2)"></p>
                                        <span class="text-[9px] font-black" :class="order.status === 'paid' ? 'text-emerald-600' : 'text-rose-600'" x-text="order.status === 'paid' ? 'Pagada' : 'Pendiente'"></span>
                                    </div>
                                </button>
                            </template>
                            <button type="button" x-show="customerProfile.orders.length > 3" @click="customerProfile.expanded = !customerProfile.expanded" class="w-full py-2 text-[10px] font-black text-emerald-700 hover:bg-emerald-50" x-text="customerProfile.expanded ? 'Mostrar menos' : 'Ver más órdenes'"></button>
                        </div>
                        <div x-show="!customerProfile.loading && customerProfile.orders.length === 0" class="px-3.5 pb-3 text-[10px] font-bold text-slate-400">Todavía no tiene órdenes registradas.</div>
                    </div>
                </div>

                <!-- Payment Breakdown Card -->
                <div class="bg-gradient-to-br from-emerald-50 to-teal-50/70 rounded-2xl p-4 border border-emerald-100/80">
                    <div class="mb-3">
                        <span class="text-[10px] font-black text-emerald-800 uppercase tracking-wider">Forma de registro</span>
                        <div class="grid grid-cols-3 gap-1.5 mt-2 bg-white/70 p-1.5 rounded-xl border border-emerald-100">
                            <button @click="setPaymentMode('full')" :class="paymentMode === 'full' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-500 hover:bg-emerald-50'" class="py-2 rounded-lg text-[10px] font-black transition-all">Pago total</button>
                            <button @click="setPaymentMode('partial')" :class="paymentMode === 'partial' ? 'bg-amber-500 text-white shadow-sm' : 'text-slate-500 hover:bg-amber-50'" class="py-2 rounded-lg text-[10px] font-black transition-all">Abono</button>
                            <button @click="setPaymentMode('debt')" :class="paymentMode === 'debt' ? 'bg-rose-500 text-white shadow-sm' : 'text-slate-500 hover:bg-rose-50'" class="py-2 rounded-lg text-[10px] font-black transition-all">Deuda</button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mb-3" x-show="paymentMode !== 'debt'">
                        <div>
                            <label class="text-[10px] font-bold text-slate-500 mb-1 block">Monto en Bs.</label>
                            <input type="number" min="0" step="0.01" x-model.number="paidBs" @input="paymentMode = 'partial'" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-sm font-black text-slate-800 outline-none focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-emerald-700 mb-1 block">Monto en USD</label>
                            <input type="number" min="0" step="0.01" x-model.number="paidUsd" @input="paymentMode = 'partial'" class="w-full bg-white border border-emerald-200 rounded-xl px-3 py-2.5 text-sm font-black text-emerald-700 outline-none focus:border-emerald-500">
                        </div>
                    </div>

                    <div class="flex justify-between text-[11px] font-bold mb-3" x-show="paymentMode === 'partial'">
                        <span class="text-slate-500">Saldo pendiente</span>
                        <span class="text-rose-600" x-text="'Bs. ' + remainingBs.toFixed(2)"></span>
                    </div>

                    <div x-show="paidBs > 0 || paidUsd > 0">
                        <label class="text-[10px] font-bold text-slate-500 mb-1 block">Cuenta de Destino</label>
                        <select x-model="account_id" class="w-full text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-xl px-3 py-2.5 outline-none focus:border-emerald-500">
                            <?php foreach($accounts as $acc): ?>
                            <option value="<?= $acc['id'] ?>"><?= $acc['name'] ?> (<?= $acc['currency'] ?? 'Bs' ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (empty($accounts)): ?>
                    <p x-show="paymentMode !== 'debt'" class="mt-3 text-xs font-bold text-rose-700 bg-rose-50 border border-rose-200 rounded-xl p-3">Debes crear una cuenta activa antes de registrar pagos.</p>
                    <?php endif; ?>
                </div>
            </div>

            <p x-show="checkoutError" x-text="checkoutError" class="text-xs font-bold text-rose-700 bg-rose-50 border border-rose-200 rounded-xl px-3 py-2"></p>
            <button @click="checkout()" :disabled="loading || !canCheckout" class="w-full bg-slate-950 hover:bg-emerald-700 text-white font-black py-4 rounded-2xl shadow-lg shadow-slate-950/20 active:scale-[.99] transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 text-base">
                <span x-show="!loading" x-text="getButtonText()"></span>
                <span x-show="loading" class="material-icons animate-spin text-sm">refresh</span>
            </button>
        </div>
    </div>

    <!-- Pay Modal (Abonar a Deuda) -->
    <div x-show="payModal.open" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4" x-cloak>
        <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-xs" @click="payModal.open = false"></div>
        <div class="bg-white rounded-t-[2.5rem] sm:rounded-3xl shadow-2xl w-full sm:max-w-md relative z-10 p-6 space-y-4 max-h-[92vh] overflow-y-auto customize-scrollbar animate-slide-up safe-bottom">
            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                <div>
                    <h3 class="font-black text-lg text-slate-900">Abonar a Deuda</h3>
                    <p class="text-xs font-bold text-slate-400" x-text="'Cliente: ' + (payModal.customer || 'Sin nombre')"></p>
                </div>
                <button @click="payModal.open = false" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 flex items-center justify-center">
                    <span class="material-icons text-base">close</span>
                </button>
            </div>

            <div class="bg-rose-50/60 p-4 rounded-2xl border border-rose-100 text-center relative">
                <p class="text-[10px] font-black uppercase tracking-wider text-rose-500">Deuda Pendiente</p>
                <p class="text-2xl font-black text-rose-600 mt-0.5" x-text="'Bs. ' + calculateDebt(payModal.order || {}, 'Bs')"></p>
                <button type="button" @click="fillRemainingDebt()" class="mt-2 text-[10px] font-black text-rose-700 bg-white border border-rose-200 hover:bg-rose-100 px-3 py-1.5 rounded-lg transition-colors">Usar monto completo</button>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-[10px] font-bold text-slate-500 mb-1 block">Abono Bs.</label>
                    <input type="number" min="0" step="0.01" x-model.number="payModal.amount_bs" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2.5 font-black text-sm text-slate-800 outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="text-[10px] font-bold text-emerald-700 mb-1 block">Abono USD</label>
                    <input type="number" min="0" step="0.01" x-model.number="payModal.amount_usd" class="w-full bg-white border border-emerald-200 rounded-xl px-3 py-2.5 font-black text-sm text-emerald-700 outline-none focus:border-emerald-500">
                </div>
            </div>

            <div>
                <label class="text-[10px] font-bold text-slate-500 mb-1 block">Cuenta de Destino</label>
                <select x-model="payModal.account_id" class="w-full text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-xl px-3 py-2.5 outline-none focus:border-emerald-500">
                    <?php foreach($accounts as $acc): ?>
                    <option value="<?= $acc['id'] ?>"><?= $acc['name'] ?> (<?= $acc['currency'] ?? 'Bs' ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Abonos Previos -->
            <div x-show="payModal.history && payModal.history.length > 0" class="border-t border-slate-100 pt-3">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2">Historial de Pagos de esta Orden</p>
                <div class="space-y-1.5 max-h-32 overflow-y-auto customize-scrollbar">
                    <template x-for="p in payModal.history" :key="p.id">
                        <div class="flex justify-between items-center bg-slate-50 px-3 py-2 rounded-xl text-xs font-bold text-slate-700">
                            <span class="text-[10px] text-slate-400" x-text="p.created_at"></span>
                            <span class="text-emerald-700" x-text="(p.amount > 0 ? 'Bs. ' + p.amount : '') + (p.amount_usd > 0 ? ' $' + p.amount_usd : '')"></span>
                        </div>
                    </template>
                </div>
            </div>

            <p x-show="payModal.error" x-text="payModal.error" class="text-xs font-bold text-rose-700 bg-rose-50 border border-rose-200 rounded-xl px-3 py-2"></p>
            <button @click="submitPayment()" :disabled="payModal.loading || !canSubmitPayment" class="w-full bg-slate-950 hover:bg-emerald-700 text-white font-black py-3.5 rounded-2xl shadow-lg shadow-slate-950/20 active:scale-[.99] transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                <span x-show="!payModal.loading">Registrar abono</span>
                <span x-show="payModal.loading" class="material-icons animate-spin text-base">refresh</span>
            </button>
        </div>
    </div>

    <!-- Edit Modal -->
    <div x-show="editModal.open" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4" x-cloak>
        <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-xs" @click="editModal.open = false"></div>
        <div class="bg-white rounded-t-[2.5rem] sm:rounded-3xl shadow-2xl w-full sm:max-w-md relative z-10 p-6 space-y-4 max-h-[90vh] overflow-y-auto customize-scrollbar animate-slide-up safe-bottom">
            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                <h3 class="font-black text-lg text-slate-900">Editar Orden #<span x-text="editModal.id"></span></h3>
                <button @click="editModal.open = false" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 flex items-center justify-center">
                    <span class="material-icons text-base">close</span>
                </button>
            </div>

            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Nombre del Cliente</label>
                    <input type="text" x-model="editModal.customer_name" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-sm font-bold text-slate-800 outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Estado de la Orden</label>
                    <select x-model="editModal.status" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-sm font-bold text-slate-800 outline-none focus:border-emerald-500">
                        <option value="pending">Pendiente</option>
                        <option value="partial">Parcial</option>
                        <option value="paid">Pagado</option>
                    </select>
                </div>
            </div>

            <div class="flex gap-2.5 pt-2">
                <button @click="editModal.open = false" class="flex-1 py-3 font-bold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">
                    Cancelar
                </button>
                <button @click="saveOrderEdit()" class="flex-1 py-3 font-black text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md transition-all">
                    Guardar Cambios
                </button>
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <div x-show="detailsModal.open" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4" x-cloak>
        <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-xs" @click="detailsModal.open = false"></div>
        <div class="bg-white rounded-t-[2.5rem] sm:rounded-3xl shadow-2xl w-full sm:max-w-md relative z-10 p-6 space-y-4 max-h-[85vh] overflow-y-auto customize-scrollbar animate-slide-up safe-bottom">
            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                <h3 class="font-black text-lg text-slate-900">Detalles de la Orden</h3>
                <button @click="detailsModal.open = false" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 flex items-center justify-center">
                    <span class="material-icons text-base">close</span>
                </button>
            </div>

            <div class="space-y-2">
                <h4 class="text-[10px] font-black uppercase text-slate-400 tracking-wider">Items Comprados</h4>
                <div class="space-y-1.5">
                    <template x-for="line in detailsModal.items">
                        <p class="text-xs font-bold text-slate-700 bg-slate-50 p-2.5 rounded-xl border border-slate-100" x-text="line"></p>
                    </template>
                </div>
            </div>

            <div x-show="detailsModal.transactions.length > 0">
                <h4 class="text-[10px] font-black uppercase text-slate-400 tracking-wider mt-4 mb-2">Transacciones Registradas</h4>
                <template x-for="t in detailsModal.transactions">
                    <div class="text-xs font-bold flex justify-between items-center bg-slate-50 p-2.5 rounded-xl border border-slate-100 mb-1.5">
                        <span class="text-slate-500 text-[11px]" x-text="t.created_at"></span>
                        <div class="flex items-center gap-2">
                            <span class="text-emerald-700 font-black" x-text="(t.amount > 0 ? 'Bs. '+t.amount : '') + (t.amount_usd > 0 ? ' $'+t.amount_usd : '')"></span>
                            <button @click="confirmTransDelete(t.id)" class="text-rose-500 hover:text-rose-700" title="Eliminar transacción">
                                <span class="material-icons text-sm">delete</span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <button @click="detailsModal.open = false" class="w-full bg-slate-100 py-3 rounded-xl font-black text-slate-700 mt-4 hover:bg-slate-200 transition-colors">
                Cerrar
            </button>
        </div>
    </div>

    <!-- Delete Modal -->
    <div x-show="deleteModal.open" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
        <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-xs" @click="deleteModal.open = false"></div>
        <div class="bg-white rounded-3xl p-6 relative z-10 w-full max-w-sm shadow-2xl space-y-4">
            <h3 class="font-black text-lg text-slate-900">¿Eliminar Orden?</h3>
            <p class="text-xs text-slate-500">Esta acción no se puede deshacer.</p>
            <div class="flex items-center gap-2 p-3 bg-rose-50/60 rounded-xl border border-rose-100">
                <input type="checkbox" x-model="deleteModal.revert" id="revert" class="w-4 h-4 rounded text-rose-600 focus:ring-rose-500">
                <label for="revert" class="text-xs font-bold text-rose-700">Revertir dinero de la cuenta / caja</label>
            </div>
            <div class="flex gap-2.5">
                <button @click="deleteModal.open = false" class="flex-1 py-3 font-bold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">Cancelar</button>
                <button @click="deleteOrder()" class="flex-1 py-3 font-black text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-md transition-all">Eliminar</button>
            </div>
        </div>
    </div>

    <!-- Transaction Delete Modal -->
    <div x-show="transDeleteModal.open" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
        <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-xs" @click="transDeleteModal.open = false"></div>
        <div class="bg-white rounded-3xl p-6 relative z-10 w-full max-w-sm shadow-2xl space-y-4">
            <h3 class="font-black text-lg text-slate-900">Eliminar Transacción</h3>
            <p class="text-xs text-slate-500">Selecciona si deseas ajustar el saldo en la cuenta o solo remover el registro.</p>
            <div class="flex gap-2.5">
                <button @click="deleteTransaction(false)" class="flex-1 py-3 font-bold text-slate-700 bg-slate-100 rounded-xl hover:bg-slate-200 text-xs">Solo Registro</button>
                <button @click="deleteTransaction(true)" class="flex-1 py-3 font-black text-white bg-rose-600 rounded-xl hover:bg-rose-700 text-xs shadow-md">Revertir Dinero</button>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div x-show="message" x-transition class="fixed bottom-8 left-1/2 -translate-x-1/2 bg-slate-900/95 backdrop-blur-md text-white px-5 py-2.5 rounded-2xl shadow-2xl z-50 text-xs font-bold flex items-center gap-2 border border-slate-800">
        <span class="material-icons text-emerald-400 text-sm">check_circle</span>
        <span x-text="message"></span>
    </div>

    <!-- Alpine.js Application Logic -->
    <script>
        function posApp() {
            return {
                tab: 'pos', cartOpen: false, cart: [], orders: [], movements: [],
                catalog: <?= json_encode($products, JSON_UNESCAPED_UNICODE) ?>,
                exchangeRate: 50, account_id: '<?= !empty($defaultAccount) ? $defaultAccount : '' ?>',
                customer_name: '', loading: false, message: '',
                totalBs: 0, totalUsd: 0, paidBs: 0, paidUsd: 0,
                productSearch: '', activeCategory: 'all', paymentMode: 'full', checkoutError: '',
                
                checkoutModal: { open: false },
                payModal: { open: false, orderId: null, amount_bs: 0, amount_usd: 0, account_id: '<?= $accounts[0]['id'] ?? '' ?>', customer: '', history: [], loading: false, error: '' },
                deleteModal: { open: false, orderId: null, revert: false },
                transDeleteModal: { open: false, transId: null },
                detailsModal: { open: false, order: null, items: [], transactions: [], loading: false },
                editModal: { open: false, id: null, customer_name: '', status: '' },
                
                customerSuggestions: { show: false, list: [], loading: false },
                customerProfile: { name: '', orders: [], totalOrders: 0, loading: false, expanded: false },
                customerSearchTimer: null, customerRequestId: 0, customerProfileRequestId: 0,
                isFavorite: false,
                searchQuery: '', historySearch: '', historyFilter: 'all',

                init() {
                    this.restoreCart();
                    this.fetchRate();
                    this.fetchHistory();
                    this.updateTotals();
                },
                async fetchRate() { 
                    try { 
                        let res = await fetch('<?= base_url('currency/get-rate') ?>'); 
                        let data = await res.json(); 
                        if (data.rate > 0) {
                            this.exchangeRate = data.rate;
                            this.updateTotals();
                        }
                    } catch(e){} 
                },
                async fetchHistory() { 
                    try { 
                        let res = await fetch('<?= base_url('printing/history') ?>?t=' + Date.now()); 
                        let data = await res.json(); 
                        if (data.status === 'success') this.orders = data.data; 
                        if(this.tab === 'history') this.fetchMovements(); 
                    } catch(e){} 
                },
                async fetchMovements() { 
                    try { 
                        let res = await fetch('<?= base_url('printing/movements') ?>?t=' + Date.now()); 
                        let data = await res.json(); 
                        if (data.status === 'success') this.movements = data.data; 
                    } catch(e){} 
                },
                
                parseDetails(str) { 
                    try { 
                        return JSON.parse(str); 
                    } catch(e) { 
                        return [str]; 
                    } 
                },

                handleShortcut(event) {
                    let target = event.target;
                    let isTyping = target && ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName);
                    if (event.key === 'Escape') {
                        this.customerSuggestions.show = false;
                        this.cartOpen = false;
                        this.checkoutModal.open = false;
                        this.payModal.open = false;
                        return;
                    }
                    if (event.key === '/' && !isTyping) {
                        event.preventDefault();
                        this.tab = 'pos';
                        this.$nextTick(() => this.$refs.productSearch?.focus());
                        return;
                    }
                    if (event.key === 'F2' && !isTyping && this.cart.length > 0 && !this.checkoutModal.open) {
                        event.preventDefault();
                        this.openCheckout();
                        return;
                    }
                    if (event.altKey && ['1', '2', '3'].includes(event.key)) {
                        event.preventDefault();
                        this.tab = event.key === '1' ? 'pos' : (event.key === '2' ? 'debts' : 'history');
                        if (this.tab !== 'pos') this.fetchHistory();
                    }
                },

                matchesProduct(name, category) {
                    let query = this.productSearch.trim().toLowerCase();
                    let matchesSearch = !query || name.toLowerCase().includes(query);
                    let matchesCategory = this.activeCategory === 'all' || category === this.activeCategory;
                    return matchesSearch && matchesCategory;
                },

                get visibleProductsCount() {
                    return this.catalog.filter(product => this.matchesProduct(product.name, product.category)).length;
                },

                cartQuantity(productId) {
                    let item = this.cart.find(row => Number(row.id) === Number(productId));
                    return item ? parseInt(item.quantity || 0) : 0;
                },

                get cartUnits() {
                    return this.cart.reduce((total, item) => total + (parseInt(item.quantity) || 0), 0);
                },

                restoreCart() {
                    try {
                        let saved = JSON.parse(localStorage.getItem('fihex_printing_cart') || '[]');
                        if (Array.isArray(saved)) this.cart = saved;
                    } catch (e) {
                        this.cart = [];
                    }
                },

                persistCart() {
                    try {
                        localStorage.setItem('fihex_printing_cart', JSON.stringify(this.cart));
                    } catch (e) {}
                },
                
                addToCart(product) {
                    let exists = this.cart.find(i => i.id === product.id);
                    if (exists) { 
                        exists.quantity++; 
                    } else { 
                        this.cart.push({ 
                            id: product.id, 
                            name: product.name, 
                            price_bs: parseFloat(product.price_bs), 
                            price_usd: parseFloat(product.price_usd), 
                            quantity: 1, 
                            note: '' 
                        }); 
                    }
                    this.updateTotals();
                },
                removeFromCart(index) { 
                    this.cart.splice(index, 1); 
                    this.updateTotals(); 
                },
                increaseItem(item) {
                    item.quantity = Math.min(999, (parseInt(item.quantity) || 0) + 1);
                    this.updateTotals();
                },
                decreaseItem(index) {
                    let item = this.cart[index];
                    if (!item) return;
                    if ((parseInt(item.quantity) || 0) <= 1) {
                        this.removeFromCart(index);
                        return;
                    }
                    item.quantity--;
                    this.updateTotals();
                },
                normalizeQuantity(item) {
                    item.quantity = Math.max(1, Math.min(999, parseInt(item.quantity) || 1));
                    this.updateTotals();
                },
                updateTotals() {
                    let bs = 0, usd = 0;
                    this.cart.forEach(i => {
                        let q = parseInt(i.quantity) || 0;
                        if(i.price_bs > 0) { 
                            bs += i.price_bs * q; 
                            usd += (i.price_bs * q) / (this.exchangeRate > 0 ? this.exchangeRate : 1); 
                        } else { 
                            usd += i.price_usd * q; 
                            bs += (i.price_usd * q) * this.exchangeRate; 
                        }
                    });
                    this.totalBs = bs; 
                    this.totalUsd = usd;
                    this.persistCart();
                },
                getLineTotalBs(item) { 
                    let q = parseInt(item.quantity) || 0; 
                    return (item.price_bs > 0 ? item.price_bs * q : item.price_usd * q * this.exchangeRate).toFixed(2); 
                },
                unitPriceBs(priceBs, priceUsd) {
                    return Number(priceBs) > 0 ? Number(priceBs) : Number(priceUsd) * this.exchangeRate;
                },
                unitPriceUsd(priceBs, priceUsd) {
                    return Number(priceBs) > 0 ? Number(priceBs) / Math.max(this.exchangeRate, 1) : Number(priceUsd);
                },
                formatUsd(value) {
                    let amount = Number(value || 0);
                    return amount > 0 && amount < 0.01 ? amount.toFixed(4) : amount.toFixed(2);
                },
                getButtonText() { 
                    if (this.loading) return 'Procesando...';
                    if (this.paymentMode === 'debt') return 'Registrar deuda';
                    return this.paymentMode === 'partial' ? 'Registrar venta y abono' : 'Cobrar y registrar';
                },

                openCheckout() {
                    if (this.cart.length === 0) return;
                    this.checkoutError = '';
                    this.setPaymentMode('full');
                    this.checkoutModal.open = true;
                },

                setPaymentMode(mode) {
                    this.paymentMode = mode;
                    this.checkoutError = '';
                    if (mode === 'full') {
                        this.paidBs = Number(this.totalBs.toFixed(2));
                        this.paidUsd = 0;
                    } else {
                        this.paidBs = 0;
                        this.paidUsd = 0;
                    }
                },

                get remainingBs() {
                    let paid = Number(this.paidBs || 0) + (Number(this.paidUsd || 0) * Number(this.exchangeRate || 0));
                    return Math.max(0, this.totalBs - paid);
                },

                get canCheckout() {
                    if (this.cart.length === 0 || this.exchangeRate <= 0) return false;
                    let paidBs = Number(this.paidBs || 0);
                    let paidUsd = Number(this.paidUsd || 0);
                    if (paidBs < 0 || paidUsd < 0) return false;
                    if ((paidBs + paidUsd * this.exchangeRate) > (this.totalBs + 0.05)) return false;
                    if ((paidBs > 0 || paidUsd > 0) && (!this.account_id || this.account_id === '0')) return false;
                    return true;
                },
                
                async checkout() {
                    if (this.cart.length === 0) return;
                    this.checkoutError = '';
                    if (!this.canCheckout) {
                        this.checkoutError = 'Revisa el pago, la tasa y la cuenta seleccionada.';
                        return;
                    }
                    this.loading = true;
                    try {
                        let res = await fetch('<?= base_url('printing/store') ?>', { 
                            method: 'POST', 
                            headers: { 'Content-Type': 'application/json' }, 
                            body: JSON.stringify({ 
                                items: this.cart, 
                                customer_name: this.customer_name, 
                                account_id: this.account_id, 
                                exchange_rate: this.exchangeRate, 
                                paid_bs: parseFloat(this.paidBs || 0), 
                                paid_usd: parseFloat(this.paidUsd || 0) 
                            }) 
                        });
                        let data = await res.json();
                        if(data.status === 'success') { 
                            this.cart = []; 
                            this.customer_name = ''; 
                            this.customerProfile = { name: '', orders: [], totalOrders: 0, loading: false, expanded: false };
                            this.isFavorite = false;
                            this.paidBs = 0; 
                            this.paidUsd = 0; 
                            this.paymentMode = 'full';
                            this.updateTotals(); 
                            this.checkoutModal.open = false; 
                            this.cartOpen = false; 
                            this.fetchHistory(); 
                            this.message = '¡Orden #' + data.order_id + ' registrada con éxito!';
                            setTimeout(() => this.message = '', 3000); 
                        } else { 
                            this.checkoutError = data.message || 'No se pudo registrar la orden.';
                        }
                    } catch(e) { 
                        this.checkoutError = 'No se pudo conectar con el servidor. Intenta nuevamente.';
                    } finally { 
                        this.loading = false; 
                    }
                },

                openPayModal(order) { 
                    this.payModal.order = order; 
                    this.payModal.orderId = order.id; 
                    this.payModal.customer = order.customer_name; 
                    this.payModal.total_bs = parseFloat(order.total_bs); 
                    this.payModal.paid_bs = parseFloat(order.paid_bs); 
                    this.payModal.paid_usd = parseFloat(order.paid_usd); 
                    this.payModal.amount_bs = 0; 
                    this.payModal.amount_usd = 0; 
                    this.payModal.error = '';
                    this.payModal.loading = false;
                    this.payModal.history = [];
                    this.payModal.open = true; 
                    this.fetchPayHistory(order.id); 
                },
                fillRemainingDebt() {
                    this.payModal.amount_bs = Number(this.calculateDebt(this.payModal.order || {}, 'Bs'));
                    this.payModal.amount_usd = 0;
                    this.payModal.error = '';
                },
                get canSubmitPayment() {
                    let amountBs = Number(this.payModal.amount_bs || 0);
                    let amountUsd = Number(this.payModal.amount_usd || 0);
                    if (amountBs < 0 || amountUsd < 0 || (amountBs === 0 && amountUsd === 0)) return false;
                    if (!this.payModal.account_id || this.payModal.account_id === '0') return false;
                    let debtBs = Number(this.calculateDebt(this.payModal.order || {}, 'Bs'));
                    return (amountBs + amountUsd * this.exchangeRate) <= (debtBs + 0.05);
                },
                async fetchPayHistory(id) { 
                    try { 
                        let res = await fetch('<?= base_url('printing/payments') ?>/' + id); 
                        let data = await res.json(); 
                        if(data.status === 'success') this.payModal.history = data.data; 
                    } catch(e){} 
                },
                
                async submitPayment() {
                    this.payModal.error = '';
                    if (!this.canSubmitPayment) {
                        this.payModal.error = 'Revisa el monto y la cuenta. El abono no puede superar la deuda.';
                        return;
                    }
                    this.payModal.loading = true;
                    try {
                        let res = await fetch('<?= base_url('printing/add-payment') ?>', { 
                            method: 'POST', 
                            headers: { 'Content-Type': 'application/json' }, 
                            body: JSON.stringify({ 
                                order_id: this.payModal.orderId, 
                                account_id: this.payModal.account_id, 
                                amount_bs: parseFloat(this.payModal.amount_bs || 0), 
                                amount_usd: parseFloat(this.payModal.amount_usd || 0), 
                                rate: this.exchangeRate 
                            }) 
                        });
                        let data = await res.json();
                        if(data.status === 'success') { 
                            this.payModal.open = false; 
                            if(data.order) { 
                                let idx = this.orders.findIndex(o => o.id == data.order.id); 
                                if(idx !== -1) this.orders[idx] = data.order; 
                            }
                            this.fetchHistory(); 
                            this.message = 'Abono registrado correctamente'; 
                            setTimeout(() => this.message = '', 3000); 
                        } else { 
                            this.payModal.error = data.message || 'No se pudo registrar el abono.';
                        }
                    } catch(e) {
                        this.payModal.error = 'No se pudo conectar con el servidor.';
                    } finally {
                        this.payModal.loading = false;
                    }
                },

                confirmDelete(id) { 
                    this.deleteModal.orderId = id; 
                    this.deleteModal.revert = false; 
                    this.deleteModal.open = true; 
                },
                async deleteOrder() { 
                    try { 
                        await fetch('<?= base_url('printing/delete-order') ?>/' + this.deleteModal.orderId + '?revert=' + this.deleteModal.revert, { method: 'POST' }); 
                        this.deleteModal.open = false; 
                        this.fetchHistory(); 
                        this.message = 'Orden eliminada'; 
                        setTimeout(() => this.message = '', 3000); 
                    } catch(e){} 
                },
                
                confirmTransDelete(id) { 
                    this.transDeleteModal.transId = id; 
                    this.transDeleteModal.open = true; 
                },
                async deleteTransaction(revert) { 
                    try { 
                        let res = await fetch('<?= base_url('printing/delete-transaction') ?>/' + this.transDeleteModal.transId + '?revert=' + revert, { method: 'POST' }); 
                        let data = await res.json(); 
                        if(data.status === 'success') { 
                            this.transDeleteModal.open = false; 
                            if(data.order) { 
                                let idx = this.orders.findIndex(o => o.id == data.order.id); 
                                if(idx !== -1) this.orders[idx] = data.order; 
                            } 
                            this.detailsModal.open = false; 
                            this.fetchHistory(); 
                            this.message = 'Transacción eliminada'; 
                            setTimeout(() => this.message = '', 3000); 
                        } 
                    } catch(e){} 
                },

                async openOrderDetails(order) { 
                    this.detailsModal.order = order; 
                    this.detailsModal.items = this.parseDetails(order.details); 
                    this.detailsModal.transactions = []; 
                    this.detailsModal.open = true; 
                    try { 
                        let res = await fetch('<?= base_url('printing/payments') ?>/' + order.id); 
                        let data = await res.json(); 
                        if (data.status === 'success') this.detailsModal.transactions = data.data; 
                    } catch(e){} 
                },

                openEditModal(order) { 
                    this.editModal = { 
                        open: true, 
                        id: order.id, 
                        customer_name: order.customer_name, 
                        status: order.status 
                    }; 
                },

                async saveOrderEdit() {
                    try {
                        let res = await fetch('<?= base_url('printing/update-order') ?>', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                id: this.editModal.id,
                                customer_name: this.editModal.customer_name,
                                status: this.editModal.status
                            })
                        });
                        let data = await res.json();
                        if (data.status === 'success') {
                            this.editModal.open = false;
                            this.fetchHistory();
                            this.message = 'Orden actualizada';
                            setTimeout(() => this.message = '', 3000);
                        } else {
                            alert(data.message || 'Error al actualizar');
                        }
                    } catch(e) {
                        alert('Error al conectar');
                    }
                },

                get filteredOrders() { 
                    if(this.tab === 'debts') return this.orders.filter(o => o.status !== 'paid' && this.matchSearch(o, this.searchQuery)); 
                    return this.orders; 
                },
                get filteredHistory() { 
                    let list = this.orders; 
                    if(this.historyFilter !== 'all') list = list.filter(o => o.status === this.historyFilter); 
                    if(this.historySearch) list = list.filter(o => this.matchSearch(o, this.historySearch)); 
                    return list; 
                },
                get debtsCount() { 
                    return this.orders.filter(o => o.status !== 'paid').length; 
                },
                get debtTotalBs() {
                    return this.orders.reduce((total, order) => {
                        return total + (order.status === 'paid' ? 0 : Number(this.calculateDebt(order, 'Bs')));
                    }, 0);
                },
                get debtCustomersCount() {
                    return new Set(this.orders.filter(order => order.status !== 'paid').map(order => (order.customer_name || 'Sin nombre').trim().toLowerCase())).size;
                },
                get partialDebtsCount() {
                    return this.orders.filter(order => order.status === 'partial').length;
                },
                get historyMetrics() { 
                    let t = new Date().toISOString().split('T')[0], m = { today_bs: 0, debt_bs: 0 }; 
                    this.filteredHistory.forEach(o => { 
                        if(o.status !== 'paid') m.debt_bs += parseFloat(this.calculateDebt(o, 'Bs')); 
                        if(o.created_at && o.created_at.startsWith(t)) m.today_bs += parseFloat(o.paid_bs) + (parseFloat(o.paid_usd) * this.exchangeRate); 
                    }); 
                    return m; 
                },
                matchSearch(o, q) { 
                    if(!q) return true; 
                    let l = q.toLowerCase(); 
                    return (o.customer_name || '').toLowerCase().includes(l) || o.id.toString().includes(l) || JSON.stringify(o.details).toLowerCase().includes(l); 
                },
                calculateDebt(o, c) { 
                    let dBs = Math.max(0, parseFloat(o.total_bs) - parseFloat(o.paid_bs) - (parseFloat(o.paid_usd) * this.exchangeRate)); 
                    let dUsd = Math.max(0, parseFloat(o.total_usd) - parseFloat(o.paid_usd) - (parseFloat(o.paid_bs) / (this.exchangeRate > 0 ? this.exchangeRate : 1))); 
                    return c === 'Bs' ? dBs.toFixed(2) : dUsd.toFixed(2); 
                },

                openCustomerPicker() {
                    this.customerSuggestions.show = true;
                    this.searchCustomers(this.customer_name);
                },

                queueCustomerSearch() {
                    this.customerSuggestions.show = true;
                    this.isFavorite = false;
                    if (this.customerProfile.name !== this.customer_name.trim()) {
                        this.customerProfile = { name: '', orders: [], totalOrders: 0, loading: false, expanded: false };
                    }
                    clearTimeout(this.customerSearchTimer);
                    this.customerSuggestions.loading = true;
                    this.customerSearchTimer = setTimeout(() => this.searchCustomers(this.customer_name), 220);
                },

                async searchCustomers(term = '') {
                    let requestId = ++this.customerRequestId;
                    this.customerSuggestions.loading = true;
                    try {
                        let res = await fetch('<?= base_url('printing/customers') ?>?term=' + encodeURIComponent(term.trim()));
                        let data = await res.json();
                        if (requestId === this.customerRequestId && data.status === 'success') {
                            this.customerSuggestions.list = data.data;
                        }
                    } catch(e) {
                        if (requestId === this.customerRequestId) this.customerSuggestions.list = [];
                    } finally {
                        if (requestId === this.customerRequestId) this.customerSuggestions.loading = false;
                    }
                },

                selectCustomer(c) {
                    this.customer_name = c.name;
                    this.isFavorite = Number(c.is_favorite) === 1;
                    this.customerSuggestions.show = false;
                    this.loadCustomerHistory(c.name);
                },

                chooseFirstCustomer() {
                    if (this.customerSuggestions.loading) return;
                    let typedName = this.customer_name.trim().toLowerCase();
                    let exactMatch = this.customerSuggestions.list.find(customer => customer.name.toLowerCase() === typedName);
                    if (exactMatch) this.selectCustomer(exactMatch);
                },

                async loadCustomerHistory(name) {
                    let customerName = (name || '').trim();
                    if (!customerName) {
                        this.customerProfile = { name: '', orders: [], totalOrders: 0, loading: false, expanded: false };
                        return;
                    }
                    let requestId = ++this.customerProfileRequestId;
                    this.customerProfile = { name: customerName, orders: [], totalOrders: 0, loading: true, expanded: false };
                    try {
                        let res = await fetch('<?= base_url('printing/customer-orders') ?>?name=' + encodeURIComponent(customerName));
                        let data = await res.json();
                        if (requestId === this.customerProfileRequestId && data.status === 'success') {
                            this.customerProfile.orders = data.data;
                            this.customerProfile.totalOrders = Number(data.meta?.order_count || data.data.length);
                        }
                    } catch (e) {
                        if (requestId === this.customerProfileRequestId) this.customerProfile.orders = [];
                    } finally {
                        if (requestId === this.customerProfileRequestId) this.customerProfile.loading = false;
                    }
                },

                customerInitials(name) {
                    return (name || '').trim().split(/\s+/).slice(0, 2).map(part => part.charAt(0)).join('').toUpperCase() || '?';
                },

                formatCustomerDate(value) {
                    if (!value) return '';
                    let date = value.toString().slice(0, 10).split('-');
                    return date.length === 3 ? date[2] + '/' + date[1] + '/' + date[0].slice(2) : value;
                },

                get customerPreviewOrders() {
                    return this.customerProfile.expanded ? this.customerProfile.orders : this.customerProfile.orders.slice(0, 3);
                },

                get customerOpenOrders() {
                    return this.customerProfile.orders.filter(order => order.status !== 'paid').length;
                },

                get customerDebtBs() {
                    return this.customerProfile.orders.reduce((total, order) => {
                        return total + (order.status === 'paid' ? 0 : Number(this.calculateDebt(order, 'Bs')));
                    }, 0);
                },

                async toggleFavorite() {
                    let name = this.customer_name.trim();
                    if (!name) return;
                    let nextValue = !this.isFavorite;
                    try {
                        let res = await fetch('<?= base_url('printing/toggle-favorite') ?>', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ name, favorite: nextValue })
                        });
                        let data = await res.json();
                        if (data.status === 'success') {
                            this.isFavorite = nextValue;
                            let customer = this.customerSuggestions.list.find(item => item.name.toLowerCase() === name.toLowerCase());
                            if (customer) customer.is_favorite = nextValue ? 1 : 0;
                            this.message = nextValue ? 'Cliente guardado como frecuente' : 'Cliente removido de frecuentes';
                            setTimeout(() => this.message = '', 2200);
                        }
                    } catch (e) {
                        this.checkoutError = 'No se pudo actualizar el cliente frecuente.';
                    }
                }
            }
        }
    </script>
</body>
</html>
