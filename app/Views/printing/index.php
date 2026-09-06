<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Impresiones & POS - Fi-Hex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <meta name="theme-color" content="#047857">
    <link rel="manifest" href="<?= base_url('manifest.json') ?>">
    <style>
        body { font-family: 'Plus Jakarta Sans', 'Outfit', sans-serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        @keyframes slide-up { from { transform: translateY(100%); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .animate-slide-up { animation: slide-up 0.28s cubic-bezier(0.16, 1, 0.3, 1); }
        .customize-scrollbar::-webkit-scrollbar { width: 5px; }
        .customize-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .customize-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
        [x-cloak] { display: none !important; }
        .safe-bottom { padding-bottom: env(safe-area-inset-bottom, 1rem); }
        @media print {
            body * { visibility: hidden; }
            #debt-printable-ticket, #debt-printable-ticket * { visibility: visible; }
            #debt-printable-ticket { position: fixed; left: 0; top: 0; width: 100%; max-width: 80mm; margin: 0 auto; padding: 10px; background: white; color: black; box-shadow: none; border: none; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-emerald-50/60 via-slate-50 to-teal-50/40 min-h-screen text-slate-800 antialiased selection:bg-emerald-500 selection:text-white" x-data="posApp()">

    <!-- Top Navigation Header -->
    <header class="fixed top-0 inset-x-0 bg-white/90 backdrop-blur-xl z-40 border-b border-slate-200/80 h-16 transition-all shadow-xs">
        <div class="max-w-5xl mx-auto h-full px-4 flex items-center justify-between gap-3">
            <!-- Left: Back button & Monogram Brand -->
            <div class="flex items-center gap-2.5 min-w-0">
                <a href="<?= base_url() ?>" class="w-9 h-9 flex items-center justify-center rounded-2xl bg-slate-100/80 hover:bg-emerald-50 text-slate-600 hover:text-emerald-700 transition-colors border border-slate-200/60 active:scale-95 shrink-0" title="Volver al inicio">
                    <span class="material-icons text-xl">arrow_back</span>
                </a>
                <div class="w-9 h-9 rounded-2xl bg-gradient-to-br from-emerald-600 to-teal-700 text-white flex items-center justify-center shadow-md shadow-emerald-950/20 ring-1 ring-emerald-400/40 shrink-0">
                    <span class="material-icons text-lg">print</span>
                </div>
                <div class="leading-tight min-w-0">
                    <h1 class="font-black text-slate-900 tracking-tight text-sm sm:text-base truncate">
                        Impresiones <span class="bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent">& POS</span>
                    </h1>
                    <p class="text-[9px] font-bold text-slate-400 hidden sm:flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Facturación & Deudas</span>
                    </p>
                </div>
            </div>

            <!-- Right Toolbar: Tasa BCV & Settings -->
            <div class="flex items-center gap-2 shrink-0">
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
    <div class="max-w-5xl mx-auto px-4 mt-20 mb-5 sticky top-18 z-30">
        <div class="bg-white/90 backdrop-blur-md rounded-2xl p-1.5 shadow-sm border border-slate-200/80 flex gap-1.5">
            <button @click="tab = 'pos'" 
                    :class="tab === 'pos' ? 'bg-gradient-to-r from-emerald-600 to-teal-700 text-white shadow-md shadow-emerald-950/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/60'" 
                    class="flex-1 py-2.5 rounded-xl font-bold text-xs sm:text-sm transition-all flex items-center justify-center gap-1.5 active:scale-98">
                <span class="material-icons text-base">point_of_sale</span>
                <span>Venta</span>
            </button>
            <button @click="fetchHistory(); tab = 'debts'" 
                    :class="tab === 'debts' ? 'bg-gradient-to-r from-emerald-600 to-teal-700 text-white shadow-md shadow-emerald-950/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/60'" 
                    class="flex-1 py-2.5 rounded-xl font-bold text-xs sm:text-sm transition-all flex items-center justify-center gap-1.5 active:scale-98 relative">
                <span class="material-icons text-base">schedule</span>
                <span>Deudas</span>
                <span x-show="debtsCount > 0" x-text="debtsCount" class="bg-rose-500 text-white text-[10px] font-black px-1.5 py-0.2 rounded-full min-w-[18px] text-center ml-0.5"></span>
            </button>
            <button @click="tab = 'history'; fetchMovements()" 
                    :class="tab === 'history' ? 'bg-gradient-to-r from-emerald-600 to-teal-700 text-white shadow-md shadow-emerald-950/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/60'" 
                    class="flex-1 py-2.5 rounded-xl font-bold text-xs sm:text-sm transition-all flex items-center justify-center gap-1.5 active:scale-98">
                <span class="material-icons text-base">history</span>
                <span>Historial</span>
            </button>
        </div>
    </div>

    <!-- Main Container Area -->
    <main class="pb-36 px-4 max-w-5xl mx-auto">
        
        <!-- POS Tab -->
        <div x-show="tab === 'pos'" class="flex flex-col md:flex-row gap-5">
            
            <!-- Products Section -->
            <div class="flex-1 min-w-0">
                <!-- Fast product finder -->
                <div class="mb-4 bg-white/90 backdrop-blur-md p-3 rounded-2xl shadow-2xs border border-slate-200/80 space-y-2.5">
                    <div class="relative">
                        <span class="material-icons absolute left-3 top-2.5 text-slate-400 text-lg">search</span>
                        <input type="search" x-model="productSearch" placeholder="Buscar servicio..." class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-9 py-2.5 text-sm font-bold text-slate-700 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100">
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
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                    <?php foreach ($products as $p): ?>
                    <button x-show="matchesProduct(<?= htmlspecialchars(json_encode($p['name'])) ?>, <?= htmlspecialchars(json_encode($p['category'])) ?>)" @click="addToCart(<?= htmlspecialchars(json_encode($p)) ?>)"
                            :class="cartQuantity(<?= (int) $p['id'] ?>) > 0 ? 'border-emerald-400 ring-2 ring-emerald-100 bg-emerald-50/40' : 'border-slate-200/70 bg-white'"
                            class="hover:bg-emerald-50/30 p-3.5 rounded-2xl shadow-2xs hover:shadow-md border hover:border-emerald-300 flex flex-col items-center justify-center gap-2 active:scale-95 transition-all group relative overflow-hidden h-32 text-center">
                        <span x-show="cartQuantity(<?= (int) $p['id'] ?>) > 0" x-text="cartQuantity(<?= (int) $p['id'] ?>)" class="absolute top-2 right-2 min-w-6 h-6 px-1.5 rounded-full bg-emerald-600 text-white text-[11px] font-black flex items-center justify-center shadow-md"></span>
                        <div class="w-11 h-11 rounded-2xl bg-emerald-50 group-hover:bg-emerald-100 text-emerald-700 flex items-center justify-center transition-colors">
                            <span class="material-icons text-2xl group-hover:scale-110 transition-transform"><?= $p['icon'] ?? 'print' ?></span>
                        </div>
                        <div class="w-full">
                            <p class="font-bold text-xs leading-tight text-slate-800 line-clamp-1 group-hover:text-emerald-950 transition-colors"><?= $p['name'] ?></p>
                            <div class="text-[10px] font-black text-slate-500 mt-1">
                                <span class="text-emerald-700 font-extrabold" x-text="'Bs. ' + unitPriceBs(<?= (float) $p['price_bs'] ?>, <?= (float) $p['price_usd'] ?>).toFixed(2)"></span>
                                <span class="text-[9px] text-slate-400 font-bold ml-1" x-text="'$' + formatUsd(unitPriceUsd(<?= (float) $p['price_bs'] ?>, <?= (float) $p['price_usd'] ?>))"></span>
                            </div>
                        </div>
                    </button>
                    <?php endforeach; ?>
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
            <div class="hidden md:block w-88 lg:w-96 shrink-0">
                <div class="bg-white/95 backdrop-blur-xl rounded-[2rem] shadow-xl p-5 sticky top-24 border border-slate-200/80">
                    <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="material-icons text-emerald-600 text-lg">shopping_cart</span>
                            <h2 class="font-black text-slate-900 text-sm uppercase tracking-wide">Orden en Curso</h2>
                        </div>
                        <button @click="cart = []; updateTotals()" class="text-[11px] text-rose-500 hover:text-rose-700 font-bold uppercase transition-colors" x-show="cart.length > 0">
                            Vaciar
                        </button>
                    </div>

                    <!-- Cart Item Rows -->
                    <div class="space-y-3 mb-5 max-h-[42vh] overflow-y-auto customize-scrollbar pr-1">
                        <template x-for="(item, index) in cart" :key="index">
                            <div class="group flex flex-col gap-2 bg-slate-50/70 p-3 rounded-2xl border border-slate-100">
                                <div class="flex justify-between items-start">
                                    <div class="min-w-0 flex-1 pr-2">
                                        <p class="font-bold text-slate-800 text-xs truncate" x-text="item.name"></p>
                                        <p class="text-[10px] font-black text-emerald-700 mt-0.5" x-text="'Bs. ' + getLineTotalBs(item)"></p>
                                    </div>
                                    <div class="flex items-center gap-1.5 shrink-0 bg-white p-1 rounded-xl border border-slate-200/60 shadow-2xs">
                                        <button @click="item.quantity > 1 ? item.quantity-- : removeFromCart(index); updateTotals()" class="w-6 h-6 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center font-black text-xs active:scale-95 transition-all">-</button>
                                        <span class="font-black text-xs w-5 text-center text-slate-800" x-text="item.quantity"></span>
                                        <button @click="item.quantity++; updateTotals()" class="w-6 h-6 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white flex items-center justify-center font-black text-xs active:scale-95 transition-all">+</button>
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
                    <div class="space-y-1.5 mb-5 border-t border-slate-100 pt-4 bg-emerald-50/40 -mx-5 -mb-5 p-5 rounded-b-[2rem]">
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
        <div x-show="tab === 'pos'" class="md:hidden">
            <div class="fixed bottom-3 inset-x-3 bg-white/95 backdrop-blur-xl border border-slate-200/80 rounded-2xl p-3.5 z-40 shadow-xl flex items-center justify-between gap-3 safe-bottom" 
                 x-show="!cartOpen && cart.length > 0">
                <div @click="cartOpen = true" class="flex-1 cursor-pointer min-w-0">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider flex items-center gap-1">
                        <span class="material-icons text-xs text-emerald-600">shopping_bag</span>
                        <span x-text="cart.reduce((total, item) => total + parseInt(item.quantity || 0), 0)"></span> items seleccionados
                    </p>
                    <div class="flex items-baseline gap-2 mt-0.5">
                        <p class="text-lg font-black text-slate-900" x-text="'Bs. ' + totalBs.toFixed(2)"></p>
                        <p class="text-xs font-bold text-emerald-700" x-text="'$' + formatUsd(totalUsd)"></p>
                    </div>
                </div>
                <button @click="cartOpen = true" class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center active:scale-95" title="Ver carrito">
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
                                        <div class="flex items-center gap-2 bg-white p-1 rounded-xl border border-slate-200/80 shadow-2xs">
                                            <button @click="item.quantity > 1 ? item.quantity-- : removeFromCart(index); updateTotals()" class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center font-black text-sm active:scale-95">-</button>
                                            <span class="w-6 text-center font-black text-sm text-slate-900" x-text="item.quantity"></span>
                                            <button @click="item.quantity++; updateTotals()" class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center font-black text-sm active:scale-95">+</button>
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
        <div x-show="tab === 'debts'" class="space-y-4">
            <!-- Metrics Cards Header -->
            <section class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 sm:gap-3">
                <div class="col-span-2 sm:col-span-1 bg-gradient-to-br from-rose-600 to-orange-500 text-white rounded-2xl p-3.5 shadow-sm">
                    <p class="text-[9px] font-black uppercase tracking-widest text-rose-100">Total por cobrar</p>
                    <p class="text-xl sm:text-2xl font-black mt-1" x-text="'$' + formatUsd(debtMetrics.totalUsd)"></p>
                    <p class="text-[10px] font-bold text-rose-100 mt-0.5" x-text="'Bs. ' + formatBs(debtMetrics.totalBs) + ' ref.'"></p>
                </div>
                <div class="bg-white rounded-2xl p-3 sm:p-3.5 border border-slate-200/80 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="material-icons text-amber-500 text-lg">receipt_long</span>
                        <span class="text-[9px] font-black text-slate-400 uppercase">ÓRDENES</span>
                    </div>
                    <p class="text-xl sm:text-2xl font-black text-slate-900 mt-1" x-text="debtMetrics.count"></p>
                    <p class="text-[10px] font-bold text-slate-400">créditos activos</p>
                </div>
                <div class="bg-white rounded-2xl p-3 sm:p-3.5 border border-slate-200/80 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="material-icons text-sky-600 text-lg">groups</span>
                        <span class="text-[9px] font-black text-slate-400 uppercase">CLIENTES</span>
                    </div>
                    <p class="text-xl sm:text-2xl font-black text-slate-900 mt-1" x-text="debtMetrics.customers"></p>
                    <p class="text-[10px] font-bold text-slate-400">con saldo abierto</p>
                </div>
                <div class="bg-white rounded-2xl p-3 sm:p-3.5 border shadow-2xs" :class="debtMetrics.overdue ? 'border-rose-200' : 'border-slate-200/80'">
                    <div class="flex items-center justify-between">
                        <span class="material-icons text-lg" :class="debtMetrics.overdue ? 'text-rose-600' : 'text-emerald-600'" x-text="debtMetrics.overdue ? 'notification_important' : 'event_available'"></span>
                        <span class="text-[9px] font-black text-slate-400 uppercase">VENCIDAS</span>
                    </div>
                    <p class="text-xl sm:text-2xl font-black mt-1" :class="debtMetrics.overdue ? 'text-rose-600' : 'text-slate-900'" x-text="debtMetrics.overdue"></p>
                    <p class="text-[10px] font-bold text-slate-400">plazo superado</p>
                </div>
            </section>

            <!-- Search & Filters Toolbar -->
            <section class="bg-white rounded-2xl border border-slate-200/80 p-3 sm:p-3.5 shadow-2xs space-y-3">
                <div class="flex flex-col md:flex-row gap-2.5">
                    <div class="relative flex-1 min-w-0">
                        <span class="material-icons absolute left-3.5 top-2.5 text-slate-400 text-lg">search</span>
                        <input x-ref="debtSearchInput" type="search" x-model="debtFilters.search" placeholder="Buscar cliente, servicio, teléfono, notas o # orden..." class="w-full h-10 bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-9 text-xs sm:text-sm font-bold outline-none focus:bg-white focus:border-emerald-500">
                        <button type="button" x-show="debtFilters.search" @click="debtFilters.search = ''" class="absolute right-2 top-2 w-6 h-6 rounded-lg text-slate-400 hover:bg-slate-200 flex items-center justify-center" title="Limpiar"><span class="material-icons text-sm">close</span></button>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 md:w-auto">
                        <select x-model="debtFilters.age" class="h-10 bg-slate-50 border border-slate-200 rounded-xl px-2.5 text-[11px] font-black outline-none focus:border-emerald-500">
                            <option value="all">Toda antigüedad</option>
                            <option value="overdue">Vencidas</option>
                            <option value="today">Vence hoy</option>
                            <option value="due_soon">Vencen en 7 días</option>
                            <option value="old">Más de 30 días</option>
                            <option value="no_due">Sin fecha límite</option>
                        </select>
                        <select x-model="debtFilters.payment" class="h-10 bg-slate-50 border border-slate-200 rounded-xl px-2.5 text-[11px] font-black outline-none focus:border-emerald-500">
                            <option value="all">Todos los pagos</option>
                            <option value="none">Sin abonos</option>
                            <option value="partial">Con abonos</option>
                        </select>
                        <select x-model="debtFilters.sort" class="col-span-2 sm:col-span-1 h-10 bg-slate-50 border border-slate-200 rounded-xl px-2.5 text-[11px] font-black outline-none focus:border-emerald-500">
                            <option value="priority">Prioridad de cobro</option>
                            <option value="amount_desc">Mayor deuda</option>
                            <option value="oldest">Más antiguas</option>
                            <option value="recent">Más recientes</option>
                            <option value="customer">Por cliente (A-Z)</option>
                        </select>
                    </div>
                </div>

                <!-- Quick Filter Pills -->
                <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar pb-0.5 pt-0.5">
                    <button type="button" @click="setDebtPill('all')" :class="isDebtPillActive('all') ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="h-7 px-2.5 rounded-lg text-[10px] font-black shrink-0 transition-all flex items-center gap-1.5">
                        <span>Todas</span>
                        <span class="text-[9px] px-1.5 py-0.2 rounded-full" :class="isDebtPillActive('all') ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'" x-text="orders.filter(o => o.status !== 'paid').length"></span>
                    </button>
                    <button type="button" @click="setDebtPill('overdue')" :class="isDebtPillActive('overdue') ? 'bg-rose-600 text-white shadow-xs' : 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200/60'" class="h-7 px-2.5 rounded-lg text-[10px] font-black shrink-0 transition-all flex items-center gap-1">
                        <span class="material-icons text-xs">warning</span>
                        <span>Vencidas</span>
                        <span class="text-[9px] px-1.5 py-0.2 rounded-full" :class="isDebtPillActive('overdue') ? 'bg-white/20 text-white' : 'bg-rose-200 text-rose-800'" x-text="debtPillCounts.overdue"></span>
                    </button>
                    <button type="button" @click="setDebtPill('today')" :class="isDebtPillActive('today') ? 'bg-orange-500 text-white shadow-xs' : 'bg-orange-50 text-orange-700 hover:bg-orange-100 border border-orange-200/60'" class="h-7 px-2.5 rounded-lg text-[10px] font-black shrink-0 transition-all flex items-center gap-1">
                        <span class="material-icons text-xs">bolt</span>
                        <span>Vence Hoy</span>
                        <span class="text-[9px] px-1.5 py-0.2 rounded-full" :class="isDebtPillActive('today') ? 'bg-white/20 text-white' : 'bg-orange-200 text-orange-800'" x-text="debtPillCounts.today"></span>
                    </button>
                    <button type="button" @click="setDebtPill('due_soon')" :class="isDebtPillActive('due_soon') ? 'bg-amber-500 text-white shadow-xs' : 'bg-amber-50 text-amber-800 hover:bg-amber-100 border border-amber-200/60'" class="h-7 px-2.5 rounded-lg text-[10px] font-black shrink-0 transition-all flex items-center gap-1">
                        <span class="material-icons text-xs">schedule</span>
                        <span>Próx. 7 días</span>
                        <span class="text-[9px] px-1.5 py-0.2 rounded-full" :class="isDebtPillActive('due_soon') ? 'bg-white/20 text-white' : 'bg-amber-200 text-amber-900'" x-text="debtPillCounts.due_soon"></span>
                    </button>
                    <button type="button" @click="setDebtPill('old')" :class="isDebtPillActive('old') ? 'bg-purple-600 text-white shadow-xs' : 'bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200/60'" class="h-7 px-2.5 rounded-lg text-[10px] font-black shrink-0 transition-all flex items-center gap-1">
                        <span class="material-icons text-xs">history</span>
                        <span>+30 días</span>
                        <span class="text-[9px] px-1.5 py-0.2 rounded-full" :class="isDebtPillActive('old') ? 'bg-white/20 text-white' : 'bg-purple-200 text-purple-900'" x-text="debtPillCounts.old"></span>
                    </button>
                    <button type="button" @click="setDebtPill('none_paid')" :class="isDebtPillActive('none_paid') ? 'bg-slate-700 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="h-7 px-2.5 rounded-lg text-[10px] font-black shrink-0 transition-all flex items-center gap-1">
                        <span>Sin abonos</span>
                        <span class="text-[9px] px-1.5 py-0.2 rounded-full" :class="isDebtPillActive('none_paid') ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'" x-text="debtPillCounts.none_paid"></span>
                    </button>
                    <button type="button" @click="setDebtPill('partial_paid')" :class="isDebtPillActive('partial_paid') ? 'bg-emerald-600 text-white shadow-xs' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200/60'" class="h-7 px-2.5 rounded-lg text-[10px] font-black shrink-0 transition-all flex items-center gap-1">
                        <span>Con abonos</span>
                        <span class="text-[9px] px-1.5 py-0.2 rounded-full" :class="isDebtPillActive('partial_paid') ? 'bg-white/20 text-white' : 'bg-emerald-200 text-emerald-800'" x-text="debtPillCounts.partial_paid"></span>
                    </button>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2 pt-1 border-t border-slate-100">
                    <div class="flex bg-slate-100 rounded-xl p-1">
                        <button type="button" @click="debtViewMode = 'debts'" :class="debtViewMode === 'debts' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500'" class="h-8 px-3 rounded-lg text-[11px] font-black flex items-center gap-1.5 transition-all"><span class="material-icons text-sm">view_agenda</span>Deudas</button>
                        <button type="button" @click="debtViewMode = 'customers'" :class="debtViewMode === 'customers' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500'" class="h-8 px-3 rounded-lg text-[11px] font-black flex items-center gap-1.5 transition-all"><span class="material-icons text-sm">group</span>Clientes</button>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button type="button" @click="openDebtConfigModal('ticket')" class="h-8 px-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-black flex items-center gap-1 transition-all" title="Configurar tickets y mensajes WhatsApp de cobranza">
                            <span class="material-icons text-xs">settings</span>
                            <span class="hidden sm:inline">Configurar</span>
                        </button>
                        <button type="button" @click="copyCollectionReport()" class="h-8 px-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-black flex items-center gap-1 transition-all" title="Copiar resumen general de cobranzas">
                            <span class="material-icons text-xs">content_copy</span>
                            <span class="hidden sm:inline">Copiar reporte</span>
                        </button>
                        <span class="text-[11px] font-bold text-slate-400" x-text="filteredDebtsList.length + (filteredDebtsList.length === 1 ? ' resultado' : ' resultados')"></span>
                        <button type="button" x-show="hasDebtFilters" @click="resetDebtFilters()" class="h-8 px-2.5 rounded-xl bg-slate-100 text-slate-600 text-[10px] font-black hover:bg-slate-200">Limpiar filtros</button>
                    </div>
                </div>
            </section>

            <!-- Mode 1: Individual Debts List -->
            <div x-show="debtViewMode === 'debts'" class="grid md:grid-cols-2 gap-3 sm:gap-4 pb-24">
                <template x-for="order in filteredDebtsList" :key="order.id">
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs hover:shadow-md transition-all overflow-hidden flex flex-col justify-between">
                        <div class="h-1.5" :class="debtAccent(order)"></div>
                        <div class="p-4 sm:p-4.5 flex-1 flex flex-col justify-between gap-3">
                            <div>
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-1.5 mb-1">
                                            <span class="text-[9px] font-black uppercase tracking-wider text-slate-400" x-text="'Orden #' + order.id"></span>
                                            <span class="text-[9px] font-black px-2 py-0.5 rounded-md" :class="dueBadge(order).class" x-text="dueBadge(order).label"></span>
                                            <span x-show="order.reminder_count > 0" class="text-[9px] font-bold bg-sky-50 text-sky-700 border border-sky-100 px-1.5 py-0.5 rounded-md" x-text="order.reminder_count + ' aviso(s)'"></span>
                                        </div>
                                        <h3 class="font-black text-slate-900 text-sm leading-tight truncate cursor-pointer hover:text-emerald-700" @click="focusCustomer(order.customer_name)" x-text="order.customer_name || 'Cliente sin nombre'"></h3>
                                        <div class="text-[11px] text-slate-500 mt-1 leading-snug">
                                            <template x-for="detail in parseDetails(order.details)">
                                                <span class="inline-block bg-slate-100 text-slate-700 px-2 py-0.5 rounded-lg mr-1 mb-1 font-medium text-[10px]" x-text="detail"></span>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <p class="text-lg font-black text-rose-600" x-text="'$' + formatUsd(orderRemainingUsd(order))"></p>
                                        <p class="text-[10px] font-bold text-slate-400" x-text="'Bs. ' + formatBs(orderRemainingBs(order))"></p>
                                    </div>
                                </div>

                                <!-- Progress bar -->
                                <div class="mt-3">
                                    <div class="flex justify-between text-[10px] font-black mb-1">
                                        <span class="text-emerald-700" x-text="'$' + formatUsd(orderPaidUsd(order)) + ' abonado'"></span>
                                        <span class="text-slate-400" x-text="Math.round(orderPaidPercent(order)) + '%'"></span>
                                    </div>
                                    <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-600 rounded-full transition-all" :style="'width:' + orderPaidPercent(order) + '%'"></div>
                                    </div>
                                </div>

                                <!-- Date tags -->
                                <div class="grid grid-cols-2 gap-2 mt-3 text-[10px]">
                                    <div class="bg-slate-50 rounded-xl px-2.5 py-1.5 border border-slate-100">
                                        <p class="font-bold text-slate-400 text-[9px]">Fecha de orden</p>
                                        <p class="font-black text-slate-700 mt-0.5 truncate" x-text="formatDateStr(order.created_at) + ' · ' + daysOld(order.created_at) + 'd'"></p>
                                    </div>
                                    <div class="bg-slate-50 rounded-xl px-2.5 py-1.5 border border-slate-100">
                                        <p class="font-bold text-slate-400 text-[9px]">Último aviso</p>
                                        <p class="font-black text-slate-700 mt-0.5 truncate" x-text="order.last_reminder_at ? formatDateStr(order.last_reminder_at) : 'Sin recordatorios'"></p>
                                    </div>
                                </div>

                                <!-- Notes preview -->
                                <div x-show="order.collection_notes" class="mt-2.5 text-[10px] font-semibold text-slate-600 bg-amber-50/70 border border-amber-100 rounded-xl px-2.5 py-1.5 line-clamp-2" x-text="order.collection_notes"></div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="grid grid-cols-[auto_1fr_1fr_1.1fr] gap-1.5 pt-1">
                                <button type="button" @click="openDebtPrintModal(order)" class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-black flex items-center justify-center transition-all active:scale-95 shrink-0" title="Imprimir ticket / comprobante de deuda">
                                    <span class="material-icons text-sm">print</span>
                                </button>
                                <button type="button" @click="openWhatsAppModal(order)" class="h-9 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-black text-[10px] flex items-center justify-center gap-1 transition-all active:scale-95" title="Enviar cobranza por WhatsApp">
                                    <span class="material-icons text-sm">chat</span>
                                    <span>WhatsApp</span>
                                </button>
                                <button type="button" @click="openDebtDetails(order)" class="h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-black text-[10px] flex items-center justify-center gap-1 transition-all active:scale-95" title="Ver detalle y cobranza">
                                    <span class="material-icons text-sm">visibility</span>
                                    <span>Detalle</span>
                                </button>
                                <button type="button" @click="openPayModal(order)" class="h-9 rounded-xl bg-slate-900 hover:bg-emerald-700 text-white font-black text-[10px] flex items-center justify-center gap-1 transition-all active:scale-95 shadow-xs" title="Registrar abono">
                                    <span class="material-icons text-sm">payments</span>
                                    <span>Abonar</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>

                <div x-show="filteredDebtsList.length === 0" class="col-span-full text-center py-16 bg-white/70 rounded-3xl border border-dashed border-slate-200">
                    <span class="material-icons text-4xl text-emerald-400 mb-2">check_circle</span>
                    <p class="font-black text-slate-800 text-sm" x-text="orders.filter(o => o.status !== 'paid').length ? 'No encontramos coincidencias' : '¡Todo al día! No hay deudas pendientes'"></p>
                    <p class="text-xs font-bold text-slate-400 mt-1" x-text="orders.filter(o => o.status !== 'paid').length ? 'Prueba cambiando los filtros o búsqueda.' : 'Excelente control de cuentas.'"></p>
                    <button type="button" x-show="orders.filter(o => o.status !== 'paid').length" @click="resetDebtFilters()" class="mt-3 h-8 px-4 rounded-xl bg-slate-900 text-white text-[11px] font-black">Mostrar todas</button>
                </div>
            </div>

            <!-- Mode 2: Customers Grouped View -->
            <div x-show="debtViewMode === 'customers'" class="grid md:grid-cols-2 lg:grid-cols-3 gap-3 pb-24">
                <template x-for="cust in debtCustomerGroups" :key="cust.key">
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-2xs hover:border-emerald-300 hover:shadow-md transition-all flex flex-col justify-between">
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-100 flex items-center justify-center font-black text-xs shrink-0" x-text="initials(cust.name)"></div>
                                    <div class="min-w-0">
                                        <h4 class="font-black text-sm text-slate-900 truncate" x-text="cust.name"></h4>
                                        <p class="text-[10px] font-bold text-slate-400 mt-0.5" x-text="cust.count + (cust.count === 1 ? ' orden pendiente' : ' órdenes pendientes')"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-between items-end mt-3.5 pt-3 border-t border-slate-100">
                                <div>
                                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Total adeudado</p>
                                    <p class="text-base font-black text-rose-600 mt-0.5" x-text="'$' + formatUsd(cust.totalUsd)"></p>
                                    <p class="text-[10px] font-bold text-slate-400" x-text="'Bs. ' + formatBs(cust.totalUsd * exchangeRate)"></p>
                                </div>
                                <div class="text-right text-[10px] font-bold">
                                    <p class="text-rose-600 font-black" x-show="cust.overdue > 0" x-text="cust.overdue + ' vencida(s)'"></p>
                                    <p class="text-slate-400" x-text="'Más antigua: ' + cust.oldestDays + 'd'"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Customer Action Buttons -->
                        <div class="grid grid-cols-2 gap-2 mt-3.5 pt-3 border-t border-slate-100">
                            <button type="button" @click="openCustomerStatement(cust)" class="h-8 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-black text-[10px] flex items-center justify-center gap-1 transition-all active:scale-95">
                                <span class="material-icons text-xs">receipt</span>
                                <span>Estado de cuenta</span>
                            </button>
                            <button type="button" @click="focusCustomer(cust.name)" class="h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-black text-[10px] flex items-center justify-center gap-1 transition-all active:scale-95">
                                <span class="material-icons text-xs">list</span>
                                <span>Ver órdenes</span>
                            </button>
                        </div>
                    </div>
                </template>

                <div x-show="debtCustomerGroups.length === 0" class="col-span-full text-center py-16 bg-white/70 rounded-3xl border border-dashed border-slate-200">
                    <span class="material-icons text-4xl text-emerald-400 mb-2">groups</span>
                    <p class="font-black text-slate-800 text-sm">No hay clientes con saldo pendiente</p>
                </div>
            </div>
        </div>

        <!-- History Tab -->
        <div x-show="tab === 'history'">
            <!-- Metrics & Filters -->
            <div class="mb-5 sticky top-18 z-30 bg-slate-50/90 backdrop-blur-md py-2 space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-white p-3.5 rounded-2xl shadow-2xs border border-slate-200/80">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Cobrado (Hoy)</p>
                        <p class="text-xl font-black text-emerald-600 mt-0.5" x-text="'Bs. ' + historyMetrics.today_bs.toFixed(2)"></p>
                    </div>
                    <div class="bg-white p-3.5 rounded-2xl shadow-2xs border border-slate-200/80">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Deuda Total</p>
                        <p class="text-xl font-black text-rose-600 mt-0.5" x-text="'Bs. ' + historyMetrics.debt_bs.toFixed(2)"></p>
                    </div>
                </div>

                <div class="flex gap-2.5">
                    <div class="relative flex-1">
                        <span class="material-icons absolute left-3.5 top-3 text-slate-400 text-base">search</span>
                        <input type="text" x-model="historySearch" placeholder="Buscar en historial..." class="w-full bg-white border border-slate-200/90 rounded-2xl pl-10 pr-3 py-2.5 text-xs sm:text-sm font-bold text-slate-700 outline-none focus:border-emerald-500 shadow-2xs">
                    </div>
                    <select x-model="historyFilter" class="bg-white border border-slate-200/90 rounded-2xl px-3 py-2 text-xs font-bold text-slate-700 shadow-2xs outline-none">
                        <option value="all">Todos</option>
                        <option value="pending">Deudas</option>
                        <option value="partial">Parcial</option>
                        <option value="paid">Pagados</option>
                    </select>
                </div>
            </div>

            <!-- History List -->
            <div class="space-y-3 pb-24">
                <template x-for="order in filteredHistory" :key="order.id">
                    <div class="bg-white p-4 rounded-2xl shadow-2xs border border-slate-200/80 hover:shadow-md hover:border-emerald-200 transition-all cursor-pointer" @click="openOrderDetails(order)">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <h3 class="font-black text-slate-900 text-sm leading-tight" x-text="order.customer_name || 'Sin Nombre'"></h3>
                                <p class="text-[10px] font-bold text-slate-400 mt-0.5" x-text="order.created_at"></p>
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
                    </div>
                </template>

                <div x-show="filteredHistory.length === 0" class="text-center py-16 bg-white/60 rounded-3xl border border-dashed border-slate-200">
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
        <div class="bg-white rounded-t-[2.5rem] sm:rounded-3xl shadow-2xl w-full sm:max-w-md relative z-10 p-6 space-y-4 max-h-[92vh] overflow-y-auto customize-scrollbar animate-slide-up safe-bottom">
            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                <div>
                    <h3 class="font-black text-lg text-slate-900">Confirmar Cobro</h3>
                    <div class="flex items-baseline gap-2 mt-0.5">
                        <span class="text-base font-black text-emerald-700" x-text="'Bs. ' + totalBs.toFixed(2)"></span>
                        <span class="text-xs font-bold text-slate-400" x-text="'$ ' + formatUsd(totalUsd)"></span>
                    </div>
                </div>
                <button @click="checkoutModal.open = false" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 flex items-center justify-center">
                    <span class="material-icons text-base">close</span>
                </button>
            </div>

            <div class="space-y-4">
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
                <div class="bg-emerald-50/50 rounded-2xl p-4 border border-emerald-100/80">
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

                    <!-- Optional debt follow-up fields for POS -->
                    <div x-show="paymentMode !== 'full'" class="pt-2 pb-1 border-t border-emerald-100 space-y-2">
                        <p class="text-[10px] font-black uppercase text-emerald-800 tracking-wider">Datos de cobro (opcional)</p>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="text-[9px] font-bold text-slate-500 block mb-0.5">Teléfono / WhatsApp</label>
                                <input type="tel" x-model="checkoutCollection.customer_phone" placeholder="Ej. 0412 1234567" class="w-full bg-white border border-slate-200 rounded-xl px-2.5 py-2 text-xs font-bold text-slate-800 outline-none focus:border-emerald-500">
                            </div>
                            <div>
                                <label class="text-[9px] font-bold text-slate-500 block mb-0.5">Fecha límite</label>
                                <input type="date" x-model="checkoutCollection.due_date" class="w-full bg-white border border-slate-200 rounded-xl px-2.5 py-2 text-xs font-bold text-slate-800 outline-none focus:border-emerald-500">
                            </div>
                        </div>
                        <div>
                            <input type="text" x-model="checkoutCollection.collection_notes" maxlength="255" placeholder="Notas de cobranza u observación..." class="w-full bg-white border border-slate-200 rounded-xl px-2.5 py-2 text-xs font-semibold text-slate-800 outline-none focus:border-emerald-500">
                        </div>
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
            <button @click="checkout()" :disabled="loading || !canCheckout" class="w-full bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white font-black py-4 rounded-2xl shadow-lg shadow-emerald-950/20 active:scale-98 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
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

            <div class="bg-rose-50/60 p-4 rounded-2xl border border-rose-100 text-center">
                <p class="text-[10px] font-black uppercase tracking-wider text-rose-500">Deuda Pendiente</p>
                <p class="text-2xl font-black text-rose-600 mt-0.5" x-text="'Bs. ' + calculateDebt(payModal.order || {}, 'Bs')"></p>
                <div class="flex items-center justify-center gap-2 mt-1">
                    <span class="text-xs font-bold text-slate-400" x-text="'$ ' + calculateDebt(payModal.order || {}, 'USD')"></span>
                </div>
                <div class="flex items-center justify-center gap-1.5 mt-2.5">
                    <button type="button" @click="setPaymentPercentage(100)" class="text-[10px] font-black text-rose-700 bg-rose-100/90 hover:bg-rose-200 px-2.5 py-1 rounded-lg active:scale-95 transition-all">100% Total</button>
                    <button type="button" @click="setPaymentPercentage(50)" class="text-[10px] font-black text-slate-700 bg-white border border-slate-200 hover:bg-slate-100 px-2.5 py-1 rounded-lg active:scale-95 transition-all">50% Mitad</button>
                    <button type="button" @click="setPaymentPercentage(25)" class="text-[10px] font-black text-slate-700 bg-white border border-slate-200 hover:bg-slate-100 px-2.5 py-1 rounded-lg active:scale-95 transition-all">25% Cuarto</button>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-[10px] font-bold text-slate-500 mb-1 block">Abono Bs.</label>
                    <input type="number" step="0.01" min="0" x-model.number="payModal.amount_bs" @input="syncPayModal('bs')" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2.5 font-black text-sm text-slate-800 outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="text-[10px] font-bold text-emerald-700 mb-1 block">Abono USD</label>
                    <input type="number" step="0.01" min="0" x-model.number="payModal.amount_usd" @input="syncPayModal('usd')" class="w-full bg-white border border-emerald-200 rounded-xl px-3 py-2.5 font-black text-sm text-emerald-700 outline-none focus:border-emerald-500">
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
                            <span class="text-emerald-700 font-black" x-text="(p.amount > 0 ? 'Bs. ' + p.amount : '') + (p.amount_usd > 0 ? ' $' + p.amount_usd : '')"></span>
                        </div>
                    </template>
                </div>
            </div>

            <button @click="submitPayment()" :disabled="loading" class="w-full bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white font-black py-3.5 rounded-2xl shadow-lg shadow-emerald-950/20 active:scale-98 transition-all disabled:opacity-50">
                <span x-text="loading ? 'Registrando...' : 'Registrar Abono'"></span>
            </button>
        </div>
    </div>

    <!-- Debt Detail & Collection Management Modal -->
    <div x-show="debtDetailModal.open" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4" x-cloak>
        <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-xs" @click="debtDetailModal.open = false"></div>
        <div class="bg-white rounded-t-[2.5rem] sm:rounded-3xl shadow-2xl w-full sm:max-w-lg relative z-10 p-5 sm:p-6 space-y-4 max-h-[92vh] overflow-y-auto customize-scrollbar animate-slide-up safe-bottom">
            <div class="flex justify-between items-start border-b border-slate-100 pb-3">
                <div class="min-w-0">
                    <p class="text-[9px] font-black uppercase tracking-widest text-emerald-600">Gestión de Cobranza</p>
                    <h3 class="font-black text-lg text-slate-900 truncate" x-text="debtDetailModal.order?.customer_name || 'Cliente'"></h3>
                    <p class="text-[10px] font-bold text-slate-400" x-text="debtDetailModal.order ? 'Orden #' + debtDetailModal.order.id + ' · ' + formatDateStr(debtDetailModal.order.created_at) : ''"></p>
                </div>
                <button @click="debtDetailModal.open = false" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 flex items-center justify-center shrink-0">
                    <span class="material-icons text-base">close</span>
                </button>
            </div>

            <!-- Balances -->
            <div class="grid grid-cols-3 gap-2">
                <div class="col-span-3 sm:col-span-1 rounded-2xl bg-rose-50 border border-rose-100 p-3">
                    <p class="text-[9px] font-black uppercase text-rose-500">Saldo pendiente</p>
                    <p class="text-xl font-black text-rose-600 mt-0.5" x-text="'$' + formatUsd(orderRemainingUsd(debtDetailModal.order))"></p>
                    <p class="text-[10px] font-bold text-rose-400" x-text="'Bs. ' + formatBs(orderRemainingBs(debtDetailModal.order))"></p>
                </div>
                <div class="rounded-2xl bg-slate-50 border border-slate-200 p-3">
                    <p class="text-[9px] font-black uppercase text-slate-400">Total</p>
                    <p class="text-sm font-black text-slate-800 mt-0.5" x-text="'$' + formatUsd(orderTotalUsd(debtDetailModal.order))"></p>
                    <p class="text-[10px] font-bold text-slate-400" x-text="'Bs. ' + parseFloat(debtDetailModal.order?.total_bs || 0).toFixed(2)"></p>
                </div>
                <div class="rounded-2xl bg-emerald-50 border border-emerald-100 p-3">
                    <p class="text-[9px] font-black uppercase text-emerald-600">Abonado</p>
                    <p class="text-sm font-black text-emerald-700 mt-0.5" x-text="'$' + formatUsd(orderPaidUsd(debtDetailModal.order))"></p>
                    <p class="text-[10px] font-bold text-emerald-600/80" x-text="'Bs. ' + parseFloat(debtDetailModal.order?.paid_bs || 0).toFixed(2)"></p>
                </div>
            </div>

            <!-- Items -->
            <div>
                <h4 class="text-[10px] font-black uppercase text-slate-400 tracking-wider mb-1.5">Servicios de la orden</h4>
                <div class="space-y-1.5 max-h-36 overflow-y-auto customize-scrollbar border border-slate-100 rounded-xl p-2 bg-slate-50/60">
                    <template x-for="item in debtDetailModal.items" :key="item">
                        <p class="text-xs font-bold text-slate-700 bg-white p-2 rounded-lg border border-slate-100 shadow-2xs" x-text="item"></p>
                    </template>
                    <p x-show="debtDetailModal.items.length === 0" class="text-xs font-bold text-slate-400 p-2">Sin detalles de servicios.</p>
                </div>
            </div>

            <!-- Payments History -->
            <div>
                <h4 class="text-[10px] font-black uppercase text-slate-400 tracking-wider mb-1.5">Historial de abonos</h4>
                <div class="space-y-1.5 max-h-32 overflow-y-auto customize-scrollbar">
                    <template x-for="p in debtDetailModal.payments" :key="p.id">
                        <div class="flex justify-between items-center bg-slate-50 p-2.5 rounded-xl border border-slate-100 text-xs">
                            <div>
                                <p class="font-black text-slate-800" x-text="formatDateStr(p.created_at)"></p>
                                <p class="text-[10px] text-slate-400" x-text="p.account_name || 'Caja'"></p>
                            </div>
                            <span class="text-emerald-700 font-black" x-text="(p.amount > 0 ? 'Bs. ' + p.amount : '') + (p.amount_usd > 0 ? ' $' + p.amount_usd : '')"></span>
                        </div>
                    </template>
                    <p x-show="debtDetailModal.payments.length === 0" class="text-xs font-bold text-slate-400 bg-slate-50 p-3 rounded-xl">No se han registrado abonos previos.</p>
                </div>
            </div>

            <!-- Collection Form -->
            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-3.5 space-y-3">
                <div class="flex items-center gap-1.5">
                    <span class="material-icons text-slate-500 text-sm">edit_note</span>
                    <h4 class="text-xs font-black text-slate-800">Seguimiento y cobranza</h4>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <div>
                        <label class="text-[10px] font-black text-slate-500 block mb-1">Teléfono / WhatsApp</label>
                        <input type="tel" x-model="collectionForm.customer_phone" placeholder="Ej. 0412 1234567" class="w-full h-10 bg-white border border-slate-200 rounded-xl px-3 text-xs font-bold outline-none focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-500 block mb-1">Fecha límite</label>
                        <input type="date" x-model="collectionForm.due_date" class="w-full h-10 bg-white border border-slate-200 rounded-xl px-3 text-xs font-bold outline-none focus:border-emerald-500">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-[10px] font-black text-slate-500 block mb-1">Notas de cobranza</label>
                        <textarea x-model="collectionForm.collection_notes" rows="2" maxlength="2000" placeholder="Acuerdos, promesa de pago, comentarios..." class="w-full bg-white border border-slate-200 rounded-xl p-2.5 text-xs font-semibold outline-none resize-none focus:border-emerald-500"></textarea>
                    </div>
                </div>
                <p x-show="debtDetailModal.error" x-text="debtDetailModal.error" class="text-xs font-bold text-rose-600"></p>
                <button type="button" @click="saveDebtCollection()" :disabled="debtDetailModal.saving" class="w-full h-10 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-black text-xs disabled:opacity-50 transition-all flex items-center justify-center gap-1.5">
                    <span class="material-icons text-sm" x-show="!debtDetailModal.saving">save</span>
                    <span x-text="debtDetailModal.saving ? 'Guardando...' : 'Guardar datos de cobranza'"></span>
                </button>
            </div>

            <!-- Footer actions -->
            <div class="grid grid-cols-[auto_1fr_1fr_1fr] gap-1.5 pt-1 border-t border-slate-100">
                <button type="button" @click="openDebtPrintModal(debtDetailModal.order)" class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-black flex items-center justify-center shrink-0" title="Imprimir ticket / comprobante de deuda">
                    <span class="material-icons text-base">print</span>
                </button>
                <button type="button" @click="copyDebtInvoice(debtDetailModal.order)" class="h-10 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-black flex items-center justify-center gap-1">
                    <span class="material-icons text-sm">content_copy</span>
                    <span>Copiar</span>
                </button>
                <button type="button" @click="openWhatsAppModal(debtDetailModal.order)" class="h-10 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-black flex items-center justify-center gap-1">
                    <span class="material-icons text-sm">chat</span>
                    <span>WhatsApp</span>
                </button>
                <button type="button" @click="debtDetailModal.open = false; openPayModal(debtDetailModal.order)" class="h-10 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-700 text-white text-xs font-black flex items-center justify-center gap-1 shadow-xs">
                    <span class="material-icons text-sm">payments</span>
                    <span>Abonar</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Customer Statement Modal (Estado de Cuenta Consolidado) -->
    <div x-show="customerStatementModal.open" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4" x-cloak>
        <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-xs" @click="customerStatementModal.open = false"></div>
        <div class="bg-white rounded-t-[2.5rem] sm:rounded-3xl shadow-2xl w-full sm:max-w-xl relative z-10 p-5 sm:p-6 space-y-4 max-h-[92vh] overflow-y-auto customize-scrollbar animate-slide-up safe-bottom">
            <div class="flex justify-between items-start border-b border-slate-100 pb-3">
                <div class="min-w-0">
                    <p class="text-[9px] font-black uppercase tracking-widest text-emerald-600">Estado de Cuenta Consolidado</p>
                    <h3 class="font-black text-lg text-slate-900 truncate" x-text="customerStatementModal.customer?.name || 'Cliente'"></h3>
                    <p class="text-[10px] font-bold text-slate-400" x-text="(customerStatementModal.orders.length) + ' orden(es) pendiente(s)'"></p>
                </div>
                <button @click="customerStatementModal.open = false" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 flex items-center justify-center shrink-0">
                    <span class="material-icons text-base">close</span>
                </button>
            </div>

            <!-- Balances -->
            <div class="grid grid-cols-3 gap-2">
                <div class="col-span-3 sm:col-span-1 rounded-2xl bg-rose-50 border border-rose-100 p-3">
                    <p class="text-[9px] font-black uppercase text-rose-500">Saldo pendiente</p>
                    <p class="text-xl font-black text-rose-600 mt-0.5" x-text="'$' + formatUsd(customerStatementModal.totalUsd)"></p>
                    <p class="text-[10px] font-bold text-rose-400" x-text="'Bs. ' + formatBs(customerStatementModal.totalBs)"></p>
                </div>
                <div class="rounded-2xl bg-slate-50 border border-slate-200 p-3">
                    <p class="text-[9px] font-black uppercase text-slate-400">Total facturado</p>
                    <p class="text-sm font-black text-slate-800 mt-0.5" x-text="'$' + formatUsd(customerStatementModal.totalInvoiceUsd)"></p>
                    <p class="text-[10px] font-bold text-slate-400" x-text="'Bs. ' + formatBs(customerStatementModal.totalInvoiceBs)"></p>
                </div>
                <div class="rounded-2xl bg-emerald-50 border border-emerald-100 p-3">
                    <p class="text-[9px] font-black uppercase text-emerald-600">Total abonado</p>
                    <p class="text-sm font-black text-emerald-700 mt-0.5" x-text="'$' + formatUsd(customerStatementModal.totalPaidUsd)"></p>
                    <p class="text-[10px] font-bold text-emerald-600/80" x-text="'Bs. ' + formatBs(customerStatementModal.totalPaidBs)"></p>
                </div>
            </div>

            <!-- Phone & Actions -->
            <div class="flex items-center justify-between bg-slate-50 p-3 rounded-2xl border border-slate-200">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="material-icons text-emerald-600 text-lg shrink-0">phone</span>
                    <div class="min-w-0">
                        <p class="text-[9px] font-black text-slate-400 uppercase">Teléfono de contacto</p>
                        <p class="text-xs font-black text-slate-800 truncate" x-text="customerStatementModal.phone || 'No registrado'"></p>
                    </div>
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                    <button type="button" @click="sendCustomerStatementWhatsApp()" class="h-8 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs flex items-center gap-1 shadow-xs transition-all active:scale-95">
                        <span class="material-icons text-sm">chat</span>
                        <span>WhatsApp Consolidado</span>
                    </button>
                    <button type="button" @click="copyCustomerStatementText()" class="h-8 px-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 font-black text-xs flex items-center gap-1 transition-all active:scale-95" title="Copiar estado de cuenta">
                        <span class="material-icons text-sm">content_copy</span>
                    </button>
                </div>
            </div>

            <!-- Open orders list -->
            <div class="space-y-2">
                <h4 class="text-[10px] font-black uppercase text-slate-400 tracking-wider">Órdenes pendientes de este cliente</h4>
                <div class="space-y-2 max-h-60 overflow-y-auto customize-scrollbar">
                    <template x-for="ord in customerStatementModal.orders" :key="ord.id">
                        <div class="bg-white border border-slate-200 rounded-2xl p-3 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-black text-slate-900" x-text="'Orden #' + ord.id"></span>
                                    <span class="text-[9px] font-black px-2 py-0.5 rounded-md" :class="dueBadge(ord).class" x-text="dueBadge(ord).label"></span>
                                    <span class="text-[10px] font-bold text-slate-400" x-text="formatDateStr(ord.created_at)"></span>
                                </div>
                                <div class="text-[11px] text-slate-500 mt-1 truncate">
                                    <template x-for="detail in parseDetails(ord.details)">
                                        <span class="inline-block bg-slate-100 text-slate-700 px-1.5 py-0.2 rounded mr-1 text-[10px]" x-text="detail"></span>
                                    </template>
                                </div>
                            </div>
                            <div class="flex items-center justify-between sm:justify-end gap-3 shrink-0 border-t sm:border-t-0 pt-2 sm:pt-0 border-slate-100">
                                <div class="text-right">
                                    <p class="text-sm font-black text-rose-600" x-text="'$' + formatUsd(orderRemainingUsd(ord))"></p>
                                    <p class="text-[9px] font-bold text-slate-400" x-text="'Bs. ' + formatBs(orderRemainingBs(ord))"></p>
                                </div>
                                <button type="button" @click="customerStatementModal.open = false; openPayModal(ord)" class="h-8 px-3 rounded-xl bg-slate-900 hover:bg-emerald-700 text-white text-[10px] font-black transition-all shadow-xs">
                                    Abonar
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="pt-2 border-t border-slate-100 flex justify-end">
                <button type="button" @click="customerStatementModal.open = false" class="h-10 px-5 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs hover:bg-slate-200">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    <!-- WhatsApp Message Modal with Template Picker -->
    <div x-show="whatsappModal.open" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4" x-cloak>
        <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-xs" @click="whatsappModal.open = false"></div>
        <div class="bg-white rounded-t-[2.5rem] sm:rounded-3xl shadow-2xl w-full sm:max-w-lg relative z-10 p-5 sm:p-6 space-y-4 max-h-[92vh] overflow-y-auto customize-scrollbar animate-slide-up safe-bottom">
            <div class="flex justify-between items-start border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                        <span class="material-icons text-xl">chat</span>
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-black text-base text-slate-900 truncate">Enviar Factura por WhatsApp</h3>
                        <p class="text-[10px] font-bold text-slate-400 truncate" x-text="'Orden #' + (whatsappModal.order?.id || '') + ' · ' + (whatsappModal.order?.customer_name || 'Cliente')"></p>
                    </div>
                </div>
                <div class="flex items-center gap-1 shrink-0">
                    <button type="button" @click="openDebtConfigModal('whatsapp')" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:text-emerald-700 hover:bg-emerald-50 flex items-center justify-center transition-all" title="Configurar plantillas WhatsApp">
                        <span class="material-icons text-base">settings</span>
                    </button>
                    <button @click="whatsappModal.open = false" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 flex items-center justify-center">
                        <span class="material-icons text-base">close</span>
                    </button>
                </div>
            </div>

            <!-- Phone input -->
            <div>
                <label class="text-[10px] font-black uppercase text-slate-400 tracking-wider block mb-1">Número de WhatsApp</label>
                <div class="relative">
                    <span class="material-icons absolute left-3 top-2.5 text-slate-400 text-lg">phone</span>
                    <input type="tel" x-model="whatsappModal.phone" placeholder="Ej. 0412 1234567 o +584121234567" class="w-full h-10 bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-3 text-xs font-bold outline-none focus:bg-white focus:border-emerald-500">
                </div>
            </div>

            <!-- Template Selector Buttons -->
            <div>
                <label class="text-[10px] font-black uppercase text-slate-400 tracking-wider block mb-1.5">Plantilla de Mensaje</label>
                <div class="grid grid-cols-3 gap-1.5">
                    <button type="button" @click="setWhatsAppTemplate('friendly')" :class="whatsappModal.template === 'friendly' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="py-2 px-2 rounded-xl text-[10px] font-black text-center transition-all">
                        😊 Amistoso
                    </button>
                    <button type="button" @click="setWhatsAppTemplate('detailed')" :class="whatsappModal.template === 'detailed' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="py-2 px-2 rounded-xl text-[10px] font-black text-center transition-all">
                        📄 Factura Ítems
                    </button>
                    <button type="button" @click="setWhatsAppTemplate('urgent')" :class="whatsappModal.template === 'urgent' ? 'bg-rose-600 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="py-2 px-2 rounded-xl text-[10px] font-black text-center transition-all">
                        🚨 Urgente
                    </button>
                </div>
            </div>

            <!-- Message Preview & Edit -->
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="text-[10px] font-black uppercase text-slate-400 tracking-wider">Vista previa / Personalizar texto</label>
                    <span class="text-[9px] font-bold text-slate-400">Editable antes de enviar</span>
                </div>
                <textarea x-model="whatsappModal.message" rows="7" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-mono font-medium outline-none resize-none focus:bg-white focus:border-emerald-500"></textarea>
            </div>

            <!-- Action Buttons -->
            <div class="grid grid-cols-2 gap-2 pt-1 border-t border-slate-100">
                <button type="button" @click="copyWhatsAppModalText()" class="h-11 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-black flex items-center justify-center gap-1.5 transition-all active:scale-95">
                    <span class="material-icons text-base">content_copy</span>
                    <span>Copiar mensaje</span>
                </button>
                <button type="button" @click="sendWhatsAppModal()" class="h-11 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black flex items-center justify-center gap-1.5 transition-all shadow-md shadow-emerald-950/20 active:scale-95">
                    <span class="material-icons text-base">chat</span>
                    <span>Abrir WhatsApp</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Printable Debt Ticket / Receipt Modal -->
    <div x-show="debtPrintModal.open" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4" x-cloak>
        <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-xs no-print" @click="debtPrintModal.open = false"></div>
        <div class="bg-white rounded-t-[2.5rem] sm:rounded-3xl shadow-2xl w-full sm:max-w-md relative z-10 p-5 sm:p-6 space-y-4 max-h-[92vh] overflow-y-auto customize-scrollbar animate-slide-up safe-bottom">
            <div class="flex justify-between items-center border-b border-slate-100 pb-3 no-print">
                <div>
                    <h3 class="font-black text-base text-slate-900">Comprobante de Deuda</h3>
                    <p class="text-[10px] font-bold text-slate-400">Formato ticket para compartir o imprimir</p>
                </div>
                <div class="flex items-center gap-1">
                    <button type="button" @click="openDebtConfigModal('ticket')" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:text-emerald-700 hover:bg-emerald-50 flex items-center justify-center transition-all" title="Configurar datos del comprobante">
                        <span class="material-icons text-base">settings</span>
                    </button>
                    <button @click="debtPrintModal.open = false" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 flex items-center justify-center">
                        <span class="material-icons text-base">close</span>
                    </button>
                </div>
            </div>

            <!-- Ticket Card to Print / Screenshot -->
            <div id="debt-printable-ticket" class="bg-white p-5 rounded-2xl border border-slate-200 text-slate-900 text-xs font-mono space-y-3 shadow-xs">
                <div class="text-center pb-2 border-b border-dashed border-slate-300">
                    <p class="font-black text-sm tracking-wider uppercase" x-text="debtConfig.print_ticket_business_name || 'FINANZAHEX'"></p>
                    <p class="text-[10px] text-slate-500 font-sans" x-text="debtConfig.print_ticket_subtitle || 'Servicios de Impresión & POS'"></p>
                    <p class="text-[10px] text-slate-500 font-sans" x-show="debtConfig.print_ticket_rif" x-text="'RIF: ' + debtConfig.print_ticket_rif"></p>
                    <p class="text-[10px] text-slate-500 font-sans" x-show="debtConfig.print_ticket_phone" x-text="'Tel: ' + debtConfig.print_ticket_phone"></p>
                    <p class="text-[9px] text-slate-400 font-sans mt-0.5" x-show="debtConfig.print_ticket_address" x-text="debtConfig.print_ticket_address"></p>
                    <p class="text-[10px] font-bold text-slate-700 font-sans mt-1">COMPROBANTE DE CUENTA POR COBRAR</p>
                </div>

                <div class="space-y-1 text-[11px]">
                    <div class="flex justify-between">
                        <span class="text-slate-500 font-sans">Orden #:</span>
                        <span class="font-bold" x-text="debtPrintModal.order?.id"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500 font-sans">Fecha emisión:</span>
                        <span class="font-bold" x-text="formatDateStr(debtPrintModal.order?.created_at)"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500 font-sans">Cliente:</span>
                        <span class="font-bold" x-text="debtPrintModal.order?.customer_name || 'Sin nombre'"></span>
                    </div>
                    <div class="flex justify-between" x-show="debtPrintModal.order?.customer_phone">
                        <span class="text-slate-500 font-sans">Teléfono:</span>
                        <span class="font-bold" x-text="debtPrintModal.order?.customer_phone"></span>
                    </div>
                    <div class="flex justify-between" x-show="debtPrintModal.order?.due_date">
                        <span class="text-slate-500 font-sans">Fecha límite:</span>
                        <span class="font-bold text-rose-600" x-text="formatDateStr(debtPrintModal.order?.due_date)"></span>
                    </div>
                </div>

                <div class="border-t border-dashed border-slate-300 pt-2">
                    <p class="text-[10px] uppercase font-bold text-slate-400 font-sans mb-1">Servicios:</p>
                    <div class="space-y-1">
                        <template x-for="item in parseDetails(debtPrintModal.order?.details)">
                            <div class="text-[10px] text-slate-700" x-text="'• ' + item"></div>
                        </template>
                    </div>
                </div>

                <div class="border-t border-dashed border-slate-300 pt-2 space-y-1 text-xs">
                    <div class="flex justify-between">
                        <span class="font-sans text-slate-500">Total Orden:</span>
                        <span class="font-bold" x-text="'$' + formatUsd(orderTotalUsd(debtPrintModal.order)) + ' (Bs. ' + parseFloat(debtPrintModal.order?.total_bs || 0).toFixed(2) + ')'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-sans text-slate-500">Abonado:</span>
                        <span class="font-bold text-emerald-700" x-text="'$' + formatUsd(orderPaidUsd(debtPrintModal.order)) + ' (Bs. ' + parseFloat(debtPrintModal.order?.paid_bs || 0).toFixed(2) + ')'"></span>
                    </div>
                    <div class="flex justify-between items-center text-sm font-black pt-1 border-t border-slate-300">
                        <span class="uppercase">SALDO PENDIENTE:</span>
                        <span class="text-rose-600" x-text="'$' + formatUsd(orderRemainingUsd(debtPrintModal.order))"></span>
                    </div>
                    <div class="text-right text-[10px] font-bold text-slate-500">
                        <span x-text="'Equiv. Bs. ' + formatBs(orderRemainingBs(debtPrintModal.order)) + ' (Tasa: Bs. ' + Number(exchangeRate).toFixed(2) + ')'"></span>
                    </div>
                </div>

                <div x-show="debtPrintModal.order?.collection_notes" class="border-t border-dashed border-slate-300 pt-2">
                    <p class="text-[9px] uppercase font-bold text-slate-400 font-sans">Notas / Acuerdo:</p>
                    <p class="text-[10px] font-sans text-slate-600 mt-0.5" x-text="debtPrintModal.order?.collection_notes"></p>
                </div>

                <div x-show="debtConfig.print_ticket_payment_info" class="border-t border-dashed border-slate-300 pt-2 font-sans">
                    <p class="text-[9px] uppercase font-bold text-slate-400">Datos para Pago / Transferencia:</p>
                    <p class="text-[10px] text-slate-700 whitespace-pre-line mt-0.5 font-mono" x-text="debtConfig.print_ticket_payment_info"></p>
                </div>

                <div class="border-t border-dashed border-slate-300 pt-3 text-center text-[9px] text-slate-500 font-sans space-y-1">
                    <p x-text="debtConfig.print_ticket_footer || 'Por favor conserve este comprobante para su control. ¡Gracias por su preferencia!'"></p>
                    <div class="pt-6 border-b border-slate-400 w-3/4 mx-auto"></div>
                    <p class="text-[8px] text-slate-400 uppercase">Firma de Conformidad</p>
                </div>
            </div>

            <!-- Ticket Actions -->
            <div class="space-y-2 pt-1 border-t border-slate-100 no-print">
                <!-- Primary Action: Share image on WhatsApp -->
                <button type="button" @click="shareDebtTicketImage()" :disabled="sharingTicketImage" class="w-full h-11 rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-xs font-black flex items-center justify-center gap-2 shadow-md shadow-emerald-950/20 transition-all active:scale-98">
                    <template x-if="!sharingTicketImage">
                        <span class="flex items-center gap-1.5">
                            <span class="material-icons text-base">photo_camera</span>
                            <span>Compartir Captura en WhatsApp</span>
                        </span>
                    </template>
                    <template x-if="sharingTicketImage">
                        <span class="flex items-center gap-1.5">
                            <span class="material-icons text-base animate-spin">sync</span>
                            <span>Generando captura...</span>
                        </span>
                    </template>
                </button>

                <!-- Secondary buttons: Copy photo, Download photo, Print -->
                <div class="grid grid-cols-3 gap-2">
                    <button type="button" @click="copyTicketImage()" :disabled="sharingTicketImage" class="h-9 rounded-xl bg-slate-100 hover:bg-slate-200 disabled:opacity-50 text-slate-700 text-[10px] font-black flex items-center justify-center gap-1 transition-all" title="Copiar foto al portapapeles (para pegar con Ctrl+V)">
                        <span class="material-icons text-xs">content_copy</span>
                        <span>Copiar foto</span>
                    </button>
                    <button type="button" @click="downloadTicketImage()" :disabled="sharingTicketImage" class="h-9 rounded-xl bg-slate-100 hover:bg-slate-200 disabled:opacity-50 text-slate-700 text-[10px] font-black flex items-center justify-center gap-1 transition-all" title="Descargar archivo PNG">
                        <span class="material-icons text-xs">download</span>
                        <span>Descargar</span>
                    </button>
                    <button type="button" @click="printDebtTicket()" class="h-9 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-[10px] font-black flex items-center justify-center gap-1 transition-all shadow-xs">
                        <span class="material-icons text-xs">print</span>
                        <span>Imprimir</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Debt & Ticket Configuration Modal -->
    <div x-show="debtConfigModal.open" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4" x-cloak>
        <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-xs" @click="debtConfigModal.open = false"></div>
        <div class="bg-white rounded-t-[2.5rem] sm:rounded-3xl shadow-2xl w-full sm:max-w-xl relative z-10 p-5 sm:p-6 space-y-4 max-h-[92vh] overflow-y-auto customize-scrollbar animate-slide-up safe-bottom">
            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
                        <span class="material-icons text-xl">tune</span>
                    </div>
                    <div>
                        <h3 class="font-black text-base text-slate-900">Configuración de Cobranzas</h3>
                        <p class="text-[10px] font-bold text-slate-400">Personaliza comprobantes de deuda y WhatsApp</p>
                    </div>
                </div>
                <button @click="debtConfigModal.open = false" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 flex items-center justify-center">
                    <span class="material-icons text-base">close</span>
                </button>
            </div>

            <!-- Tab Switcher -->
            <div class="bg-slate-100 p-1 rounded-xl flex gap-1">
                <button type="button" @click="debtConfigModal.tab = 'ticket'" :class="debtConfigModal.tab === 'ticket' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'" class="flex-1 py-1.5 rounded-lg text-xs font-black transition-all flex items-center justify-center gap-1.5">
                    <span class="material-icons text-sm">receipt_long</span>
                    <span>Datos del Ticket</span>
                </button>
                <button type="button" @click="debtConfigModal.tab = 'whatsapp'" :class="debtConfigModal.tab === 'whatsapp' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'" class="flex-1 py-1.5 rounded-lg text-xs font-black transition-all flex items-center justify-center gap-1.5">
                    <span class="material-icons text-sm">chat</span>
                    <span>Mensajes WhatsApp</span>
                </button>
            </div>

            <!-- Tab 1: Ticket Settings -->
            <div x-show="debtConfigModal.tab === 'ticket'" class="space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Nombre Comercial</label>
                        <input type="text" x-model="debtConfigModal.form.print_ticket_business_name" placeholder="Ej: FINANZAHEX" class="w-full h-10 bg-slate-50 border border-slate-200 rounded-xl px-3 text-xs font-bold outline-none focus:bg-white focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Subtítulo / Razón Social</label>
                        <input type="text" x-model="debtConfigModal.form.print_ticket_subtitle" placeholder="Ej: Servicios de Impresión & POS" class="w-full h-10 bg-slate-50 border border-slate-200 rounded-xl px-3 text-xs font-bold outline-none focus:bg-white focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">RIF / Identificación</label>
                        <input type="text" x-model="debtConfigModal.form.print_ticket_rif" placeholder="Ej: J-12345678-9" class="w-full h-10 bg-slate-50 border border-slate-200 rounded-xl px-3 text-xs font-bold outline-none focus:bg-white focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Teléfono de Contacto</label>
                        <input type="text" x-model="debtConfigModal.form.print_ticket_phone" placeholder="Ej: +58 412 1234567" class="w-full h-10 bg-slate-50 border border-slate-200 rounded-xl px-3 text-xs font-bold outline-none focus:bg-white focus:border-emerald-500">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Dirección / Ubicación</label>
                    <input type="text" x-model="debtConfigModal.form.print_ticket_address" placeholder="Ej: Av. Principal, C.C. Plaza, Local 12" class="w-full h-10 bg-slate-50 border border-slate-200 rounded-xl px-3 text-xs font-bold outline-none focus:bg-white focus:border-emerald-500">
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Cuentas Bancarias / Pago Móvil (para el comprobante)</label>
                    <textarea x-model="debtConfigModal.form.print_ticket_payment_info" rows="3" placeholder="Ej: Banco Banesco: 0134-XXXX...&#10;Pago Móvil: 0412-1234567, V-12345678, Banesco" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs font-mono outline-none resize-none focus:bg-white focus:border-emerald-500"></textarea>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Pie de Página / Agradecimiento</label>
                    <input type="text" x-model="debtConfigModal.form.print_ticket_footer" placeholder="Ej: Por favor conserve este comprobante. ¡Gracias por su preferencia!" class="w-full h-10 bg-slate-50 border border-slate-200 rounded-xl px-3 text-xs font-bold outline-none focus:bg-white focus:border-emerald-500">
                </div>
            </div>

            <!-- Tab 2: WhatsApp Templates -->
            <div x-show="debtConfigModal.tab === 'whatsapp'" class="space-y-3">
                <div class="bg-emerald-50/70 border border-emerald-200/80 rounded-2xl p-3 text-[11px] text-emerald-900">
                    <p class="font-bold flex items-center gap-1 mb-1 text-emerald-800">
                        <span class="material-icons text-sm">code</span>
                        <span>Etiquetas dinámicas disponibles:</span>
                    </p>
                    <p class="text-[10px] text-emerald-800/80 leading-relaxed font-mono">
                        <span class="bg-white/80 px-1 py-0.5 rounded mr-1">{cliente}</span>
                        <span class="bg-white/80 px-1 py-0.5 rounded mr-1">{orden}</span>
                        <span class="bg-white/80 px-1 py-0.5 rounded mr-1">{fecha}</span>
                        <span class="bg-white/80 px-1 py-0.5 rounded mr-1">{vencimiento}</span>
                        <span class="bg-white/80 px-1 py-0.5 rounded mr-1">{servicios}</span>
                        <span class="bg-white/80 px-1 py-0.5 rounded mr-1">{total}</span>
                        <span class="bg-white/80 px-1 py-0.5 rounded mr-1">{abonado}</span>
                        <span class="bg-white/80 px-1 py-0.5 rounded mr-1">{pendiente_usd}</span>
                        <span class="bg-white/80 px-1 py-0.5 rounded mr-1">{pendiente_bs}</span>
                        <span class="bg-white/80 px-1 py-0.5 rounded mr-1">{negocio}</span>
                        <span class="bg-white/80 px-1 py-0.5 rounded mr-1">{datos_pago}</span>
                        <span class="bg-white/80 px-1 py-0.5 rounded">{notas}</span>
                    </p>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Plantilla 1: Amistoso 😊</label>
                    <textarea x-model="debtConfigModal.form.print_wa_friendly" rows="4" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs font-mono outline-none resize-none focus:bg-white focus:border-emerald-500"></textarea>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Plantilla 2: Factura Ítems 📄</label>
                    <textarea x-model="debtConfigModal.form.print_wa_detailed" rows="5" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs font-mono outline-none resize-none focus:bg-white focus:border-emerald-500"></textarea>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Plantilla 3: Urgente / Vencimiento 🚨</label>
                    <textarea x-model="debtConfigModal.form.print_wa_urgent" rows="4" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs font-mono outline-none resize-none focus:bg-white focus:border-emerald-500"></textarea>
                </div>

                <div class="pt-1 flex justify-between items-center">
                    <button type="button" @click="resetDefaultWaTemplates()" class="text-[11px] font-bold text-slate-500 hover:text-slate-800 underline">
                        Restablecer plantillas predeterminadas
                    </button>
                </div>
            </div>

            <!-- Error message if any -->
            <div x-show="debtConfigModal.error" class="bg-rose-50 text-rose-700 text-xs p-2.5 rounded-xl font-bold" x-text="debtConfigModal.error"></div>

            <!-- Modal Footer -->
            <div class="pt-2 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" @click="debtConfigModal.open = false" class="h-10 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-all">
                    Cancelar
                </button>
                <button type="button" @click="saveDebtConfig()" :disabled="debtConfigModal.saving" class="h-10 px-5 rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-xs font-black transition-all shadow-md shadow-emerald-950/20 flex items-center gap-1.5 active:scale-95">
                    <span class="material-icons text-sm" x-show="!debtConfigModal.saving">save</span>
                    <span class="material-icons text-sm animate-spin" x-show="debtConfigModal.saving">sync</span>
                    <span x-text="debtConfigModal.saving ? 'Guardando...' : 'Guardar Configuración'"></span>
                </button>
            </div>
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
                tab: new URLSearchParams(window.location.search).get('tab') || '<?= $initialTab ?? 'pos' ?>', 
                cartOpen: false, cart: [], orders: [], movements: [],
                exchangeRate: 50, account_id: '<?= !empty($defaultAccount) ? $defaultAccount : '' ?>',
                customer_name: '', loading: false, message: '',
                totalBs: 0, totalUsd: 0, paidBs: 0, paidUsd: 0,
                productSearch: '', activeCategory: 'all', paymentMode: 'full', checkoutError: '',
                checkoutCollection: { customer_phone: '', due_date: '', collection_notes: '' },
                
                checkoutModal: { open: false },
                payModal: { open: false, orderId: null, order: null, amount_bs: 0, amount_usd: 0, account_id: '<?= $accounts[0]['id'] ?? '' ?>', customer: '', history: [] },
                deleteModal: { open: false, orderId: null, revert: false },
                transDeleteModal: { open: false, transId: null },
                detailsModal: { open: false, order: null, items: [], transactions: [], loading: false },
                editModal: { open: false, id: null, customer_name: '', status: '' },
                debtDetailModal: { open: false, order: null, items: [], payments: [], loading: false, saving: false, error: '' },
                collectionForm: { customer_phone: '', due_date: '', collection_notes: '' },
                debtFilters: { search: '', age: 'all', payment: 'all', sort: 'priority' },
                debtViewMode: 'debts',
                whatsappModal: { open: false, order: null, phone: '', template: 'detailed', message: '' },
                customerStatementModal: { open: false, customer: null, orders: [], phone: '', totalUsd: 0, totalBs: 0, totalPaidUsd: 0, totalPaidBs: 0, totalInvoiceUsd: 0, totalInvoiceBs: 0 },
                debtPrintModal: { open: false, order: null },
                debtConfig: {
                    print_ticket_business_name: <?= json_encode($settings['print_ticket_business_name'] ?? 'FINANZAHEX') ?>,
                    print_ticket_subtitle: <?= json_encode($settings['print_ticket_subtitle'] ?? 'Servicios de Impresión & POS') ?>,
                    print_ticket_rif: <?= json_encode($settings['print_ticket_rif'] ?? '') ?>,
                    print_ticket_phone: <?= json_encode($settings['print_ticket_phone'] ?? '') ?>,
                    print_ticket_address: <?= json_encode($settings['print_ticket_address'] ?? '') ?>,
                    print_ticket_payment_info: <?= json_encode($settings['print_ticket_payment_info'] ?? '') ?>,
                    print_ticket_footer: <?= json_encode($settings['print_ticket_footer'] ?? 'Por favor conserve este comprobante para su control. ¡Gracias por su preferencia!') ?>,
                    print_wa_friendly: <?= json_encode($settings['print_wa_friendly'] ?? '') ?>,
                    print_wa_detailed: <?= json_encode($settings['print_wa_detailed'] ?? '') ?>,
                    print_wa_urgent: <?= json_encode($settings['print_wa_urgent'] ?? '') ?>,
                },
                debtConfigModal: {
                    open: false,
                    tab: 'ticket',
                    saving: false,
                    error: '',
                    form: {}
                },
                sharingTicketImage: false,
                
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

                matchesProduct(name, category) {
                    let query = this.productSearch.trim().toLowerCase();
                    let matchesSearch = !query || name.toLowerCase().includes(query);
                    let matchesCategory = this.activeCategory === 'all' || category === this.activeCategory;
                    return matchesSearch && matchesCategory;
                },

                cartQuantity(productId) {
                    let item = this.cart.find(row => Number(row.id) === Number(productId));
                    return item ? parseInt(item.quantity || 0) : 0;
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
                                paid_usd: parseFloat(this.paidUsd || 0),
                                customer_phone: this.checkoutCollection?.customer_phone || '',
                                due_date: this.checkoutCollection?.due_date || '',
                                collection_notes: this.checkoutCollection?.collection_notes || ''
                            }) 
                        });
                        let data = await res.json();
                        if(data.status === 'success') { 
                            this.cart = []; 
                            this.customer_name = ''; 
                            this.customerProfile = { name: '', orders: [], totalOrders: 0, loading: false, expanded: false };
                            this.checkoutCollection = { customer_phone: '', due_date: '', collection_notes: '' };
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
                    this.payModal.open = true; 
                    this.fetchPayHistory(order.id); 
                },
                async fetchPayHistory(id) { 
                    try { 
                        let res = await fetch('<?= base_url('printing/payments') ?>/' + id); 
                        let data = await res.json(); 
                        if(data.status === 'success') this.payModal.history = data.data; 
                    } catch(e){} 
                },
                
                async submitPayment() {
                    if ((this.payModal.amount_bs > 0 || this.payModal.amount_usd > 0) && !this.payModal.account_id) { 
                        alert('Seleccione una cuenta de destino'); 
                        return; 
                    }
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
                            alert(data.message); 
                        }
                    } catch(e) {
                        alert('Error al registrar abono');
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
                },

                // Debt management calculations and methods
                orderRemainingUsd(o) {
                    if (!o) return 0;
                    let total = Number(o.total_usd || 0);
                    let paid = Number(o.paid_usd || 0) + (Number(o.paid_bs || 0) / Math.max(this.exchangeRate, 1));
                    return Math.max(0, total - paid);
                },
                orderRemainingBs(o) {
                    if (!o) return 0;
                    let total = Number(o.total_bs || 0);
                    let paid = Number(o.paid_bs || 0) + (Number(o.paid_usd || 0) * this.exchangeRate);
                    return Math.max(0, total - paid);
                },
                orderTotalUsd(o) {
                    return Number(o?.total_usd || 0);
                },
                orderPaidUsd(o) {
                    if (!o) return 0;
                    return Number(o.paid_usd || 0) + (Number(o.paid_bs || 0) / Math.max(this.exchangeRate, 1));
                },
                orderPaidPercent(o) {
                    let total = this.orderTotalUsd(o);
                    if (total <= 0) return 0;
                    return Math.min(100, (this.orderPaidUsd(o) / total) * 100);
                },
                parseDate(value) {
                    if (!value) return null;
                    let s = String(value).trim();
                    let normalized = s.length === 10 ? s + 'T00:00:00' : s.replace(' ', 'T');
                    let d = new Date(normalized);
                    return Number.isNaN(d.getTime()) ? null : d;
                },
                daysOld(dateVal) {
                    let d = this.parseDate(dateVal);
                    if (!d) return 0;
                    return Math.max(0, Math.floor((new Date().setHours(0,0,0,0) - d.setHours(0,0,0,0)) / 86400000));
                },
                dueInfo(o) {
                    if (!o?.due_date) return { state: 'none', days: null };
                    let due = this.parseDate(o.due_date);
                    if (!due) return { state: 'none', days: null };
                    let today = new Date(); today.setHours(0,0,0,0); due.setHours(0,0,0,0);
                    let days = Math.ceil((due - today) / 86400000);
                    if (days < 0) return { state: 'overdue', days };
                    if (days === 0) return { state: 'today', days };
                    if (days <= 7) return { state: 'soon', days };
                    return { state: 'future', days };
                },
                dueBadge(o) {
                    let info = this.dueInfo(o);
                    if (info.state === 'overdue') return { label: 'Vencida ' + Math.abs(info.days) + 'd', class: 'bg-rose-100 text-rose-700' };
                    if (info.state === 'today') return { label: 'Vence hoy', class: 'bg-orange-100 text-orange-700' };
                    if (info.state === 'soon') return { label: 'Vence en ' + info.days + 'd', class: 'bg-amber-100 text-amber-700' };
                    if (info.state === 'future') return { label: 'Límite ' + this.formatDateStr(o.due_date), class: 'bg-sky-100 text-sky-700' };
                    return { label: this.daysOld(o?.created_at) + ' días abierta', class: 'bg-slate-100 text-slate-600' };
                },
                debtAccent(o) {
                    let state = this.dueInfo(o).state;
                    if (state === 'overdue') return 'bg-rose-500';
                    if (state === 'today' || state === 'soon') return 'bg-amber-500';
                    return this.orderPaidUsd(o) > 0 ? 'bg-emerald-500' : 'bg-slate-300';
                },
                formatDateStr(value) {
                    let d = this.parseDate(value);
                    return d ? d.toLocaleDateString('es-VE', { day: '2-digit', month: 'short', year: 'numeric' }) : '';
                },
                formatBs(val) {
                    return new Intl.NumberFormat('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(val || 0));
                },
                initials(name) {
                    return String(name || '?').split(/\s+/).slice(0,2).map(p => p[0]).join('').toUpperCase();
                },

                get debtMetrics() {
                    let open = this.orders.filter(o => o.status !== 'paid');
                    return {
                        totalUsd: open.reduce((sum, o) => sum + this.orderRemainingUsd(o), 0),
                        totalBs: open.reduce((sum, o) => sum + this.orderRemainingBs(o), 0),
                        count: open.length,
                        customers: new Set(open.map(o => String(o.customer_name || 'Sin nombre').trim().toLowerCase())).size,
                        overdue: open.filter(o => this.dueInfo(o).state === 'overdue').length,
                    };
                },
                get hasDebtFilters() {
                    return this.debtFilters.search || this.debtFilters.age !== 'all' || this.debtFilters.payment !== 'all' || this.debtFilters.sort !== 'priority';
                },
                resetDebtFilters() {
                    this.debtFilters = { search: '', age: 'all', payment: 'all', sort: 'priority' };
                },
                setDebtPill(pill) {
                    if (pill === 'all') {
                        this.debtFilters.age = 'all';
                        this.debtFilters.payment = 'all';
                    } else if (pill === 'overdue') {
                        this.debtFilters.age = 'overdue';
                        this.debtFilters.payment = 'all';
                    } else if (pill === 'today') {
                        this.debtFilters.age = 'today';
                        this.debtFilters.payment = 'all';
                    } else if (pill === 'due_soon') {
                        this.debtFilters.age = 'due_soon';
                        this.debtFilters.payment = 'all';
                    } else if (pill === 'old') {
                        this.debtFilters.age = 'old';
                        this.debtFilters.payment = 'all';
                    } else if (pill === 'none_paid') {
                        this.debtFilters.age = 'all';
                        this.debtFilters.payment = 'none';
                    } else if (pill === 'partial_paid') {
                        this.debtFilters.age = 'all';
                        this.debtFilters.payment = 'partial';
                    }
                },
                isDebtPillActive(pill) {
                    if (pill === 'all') return this.debtFilters.age === 'all' && this.debtFilters.payment === 'all';
                    if (pill === 'overdue') return this.debtFilters.age === 'overdue';
                    if (pill === 'today') return this.debtFilters.age === 'today';
                    if (pill === 'due_soon') return this.debtFilters.age === 'due_soon';
                    if (pill === 'old') return this.debtFilters.age === 'old';
                    if (pill === 'none_paid') return this.debtFilters.payment === 'none';
                    if (pill === 'partial_paid') return this.debtFilters.payment === 'partial';
                    return false;
                },
                get debtPillCounts() {
                    let open = this.orders.filter(o => o.status !== 'paid');
                    return {
                        all: open.length,
                        overdue: open.filter(o => this.dueInfo(o).state === 'overdue').length,
                        today: open.filter(o => this.dueInfo(o).state === 'today').length,
                        due_soon: open.filter(o => ['today', 'soon'].includes(this.dueInfo(o).state)).length,
                        old: open.filter(o => this.daysOld(o.created_at) > 30).length,
                        none_paid: open.filter(o => this.orderPaidUsd(o) <= 0).length,
                        partial_paid: open.filter(o => this.orderPaidUsd(o) > 0).length,
                    };
                },
                focusCustomer(name) {
                    this.debtFilters.search = name;
                    this.debtViewMode = 'debts';
                    this.$nextTick(() => this.$refs.debtSearchInput?.focus());
                },
                get debtCustomerGroups() {
                    let groups = {};
                    this.filteredDebtsList.forEach(o => {
                        let name = String(o.customer_name || 'Cliente sin nombre').trim();
                        let key = name.toLowerCase();
                        if (!groups[key]) groups[key] = { key, name, count: 0, totalUsd: 0, overdue: 0, oldestDays: 0 };
                        groups[key].count++;
                        groups[key].totalUsd += this.orderRemainingUsd(o);
                        groups[key].overdue += this.dueInfo(o).state === 'overdue' ? 1 : 0;
                        groups[key].oldestDays = Math.max(groups[key].oldestDays, this.daysOld(o.created_at));
                    });
                    return Object.values(groups).sort((a, b) => b.totalUsd - a.totalUsd);
                },
                get filteredDebtsList() {
                    let query = this.debtFilters.search.trim().toLowerCase();
                    let open = this.orders.filter(o => o.status !== 'paid');
                    let list = open.filter(o => {
                        let haystack = [o.id, o.customer_name, JSON.stringify(o.details || ''), o.customer_phone || '', o.collection_notes || ''].join(' ').toLowerCase();
                        let searchMatch = !query || haystack.includes(query);
                        let due = this.dueInfo(o);
                        let ageMatch = true;
                        if (this.debtFilters.age === 'overdue') ageMatch = due.state === 'overdue';
                        if (this.debtFilters.age === 'today') ageMatch = due.state === 'today';
                        if (this.debtFilters.age === 'due_soon') ageMatch = ['today', 'soon'].includes(due.state);
                        if (this.debtFilters.age === 'old') ageMatch = this.daysOld(o.created_at) > 30;
                        if (this.debtFilters.age === 'no_due') ageMatch = due.state === 'none';
                        let paymentMatch = this.debtFilters.payment === 'all' || (this.debtFilters.payment === 'none' ? this.orderPaidUsd(o) <= 0 : this.orderPaidUsd(o) > 0);
                        return searchMatch && ageMatch && paymentMatch;
                    });
                    return list.sort((a, b) => {
                        if (this.debtFilters.sort === 'amount_desc') return this.orderRemainingUsd(b) - this.orderRemainingUsd(a);
                        if (this.debtFilters.sort === 'oldest') return String(a.created_at).localeCompare(String(b.created_at));
                        if (this.debtFilters.sort === 'recent') return String(b.created_at).localeCompare(String(a.created_at));
                        if (this.debtFilters.sort === 'customer') return String(a.customer_name || '').localeCompare(String(b.customer_name || ''), 'es');
                        const priority = order => ({ overdue: 0, today: 1, soon: 2, future: 3, none: 4 })[this.dueInfo(order).state];
                        return priority(a) - priority(b) || this.orderRemainingUsd(b) - this.orderRemainingUsd(a);
                    });
                },

                async openDebtDetails(order) {
                    if (!order) return;
                    this.debtDetailModal.order = order;
                    this.debtDetailModal.items = this.parseDetails(order.details);
                    this.debtDetailModal.payments = [];
                    this.debtDetailModal.error = '';
                    this.debtDetailModal.open = true;
                    this.collectionForm = {
                        customer_phone: order.customer_phone || '',
                        due_date: order.due_date || '',
                        collection_notes: order.collection_notes || '',
                    };
                    try {
                        let res = await fetch('<?= base_url('printing/payments') ?>/' + order.id);
                        let data = await res.json();
                        if (data.status === 'success') {
                            this.debtDetailModal.payments = data.data || [];
                        }
                    } catch(e) {}
                },
                async saveDebtCollection() {
                    if (!this.debtDetailModal.order) return;
                    this.debtDetailModal.saving = true;
                    this.debtDetailModal.error = '';
                    try {
                        let res = await fetch('<?= base_url('printing/update-debt') ?>', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                order_id: this.debtDetailModal.order.id,
                                ...this.collectionForm
                            })
                        });
                        let data = await res.json();
                        if (!res.ok || data.status !== 'success') {
                            throw new Error(data.message || 'No se pudo guardar la cobranza');
                        }
                        if (data.order) {
                            let idx = this.orders.findIndex(o => o.id == data.order.id);
                            if (idx !== -1) this.orders[idx] = data.order;
                            this.debtDetailModal.order = data.order;
                        }
                        this.message = 'Datos de cobranza actualizados';
                        setTimeout(() => this.message = '', 3000);
                    } catch(e) {
                        this.debtDetailModal.error = e.message;
                    } finally {
                        this.debtDetailModal.saving = false;
                    }
                },
                syncPayModal(source) {
                    if (this.exchangeRate <= 0) return;
                    if (source === 'usd') {
                        this.payModal.amount_bs = this.payModal.amount_usd === '' ? '' : Number((Number(this.payModal.amount_usd) * this.exchangeRate).toFixed(2));
                    } else if (source === 'bs') {
                        this.payModal.amount_usd = this.payModal.amount_bs === '' ? '' : Number((Number(this.payModal.amount_bs) / this.exchangeRate).toFixed(2));
                    }
                },
                setPaymentPercentage(percentage) {
                    if (!this.payModal.order) return;
                    let remUsd = this.orderRemainingUsd(this.payModal.order);
                    let targetUsd = Number(((remUsd * percentage) / 100).toFixed(2));
                    this.payModal.amount_usd = targetUsd;
                    this.payModal.amount_bs = Number((targetUsd * this.exchangeRate).toFixed(2));
                },
                useFullDebtBalance() {
                    this.setPaymentPercentage(100);
                },
                debtInvoiceText(order) {
                    return this.buildWhatsAppText(order, 'detailed');
                },
                whatsappPhone(phone) {
                    let digits = String(phone || '').replace(/\D/g, '');
                    if (digits.startsWith('0')) digits = '58' + digits.slice(1);
                    if (digits.length === 10 && !digits.startsWith('58')) digits = '58' + digits;
                    return digits;
                },
                openWhatsAppModal(order, defaultTemplate = 'detailed') {
                    if (!order) return;
                    this.whatsappModal.order = order;
                    this.whatsappModal.phone = order.customer_phone || '';
                    this.whatsappModal.template = defaultTemplate;
                    this.whatsappModal.message = this.buildWhatsAppText(order, defaultTemplate);
                    this.whatsappModal.open = true;
                },
                setWhatsAppTemplate(key) {
                    this.whatsappModal.template = key;
                    this.whatsappModal.message = this.buildWhatsAppText(this.whatsappModal.order, key);
                },
                defaultWaTemplates() {
                    return {
                        friendly: [
                            '¡Hola, {cliente}! 👋',
                            '',
                            'Esperamos que estés muy bien. Te saludamos de parte de {negocio}.',
                            'Te escribimos con un cordial recordatorio sobre tu orden *#{orden}* del {fecha}.',
                            '',
                            'Saldo pendiente: *{pendiente_usd}* ({pendiente_bs})',
                            '{vencimiento}',
                            '',
                            'Puedes realizar tu pago cuando gustes:',
                            '{datos_pago}',
                            '',
                            'Al transferir, por favor compártenos el comprobante por este medio.',
                            '¡Que tengas un excelente día!'
                        ].join('\n'),
                        detailed: [
                            '📄 *FACTURA DE DEUDA - {negocio}*',
                            '',
                            'Cliente: *{cliente}*',
                            'Orden: *#{orden}*',
                            'Fecha: {fecha}',
                            '{vencimiento}',
                            '',
                            '*Servicios de la orden:*',
                            '{servicios}',
                            '',
                            'Total orden: {total}',
                            'Total abonado: {abonado}',
                            '*SALDO PENDIENTE:* {pendiente_usd} ({pendiente_bs})',
                            '{notas}',
                            '',
                            'Datos de pago:',
                            '{datos_pago}',
                            '',
                            'Al efectuar el pago, por favor remítenos el comprobante por este chat. ¡Muchas gracias por tu preferencia!'
                        ].join('\n'),
                        urgent: [
                            '⚠️ *AVISO DE COBRANZA - {negocio}*',
                            '',
                            'Estimado(a) *{cliente}*,',
                            'Le notificamos que la orden *#{orden}* presenta saldo pendiente de pago.',
                            '',
                            '*Monto adeudado:* {pendiente_usd} ({pendiente_bs})',
                            '*Fecha de orden:* {fecha}',
                            '{vencimiento}',
                            '',
                            'Datos de pago:',
                            '{datos_pago}',
                            '',
                            'Le solicitamos comunicarse a la brevedad posible para concretar su pago o coordinar un acuerdo.',
                            'Agradecemos su pronta atención.'
                        ].join('\n')
                    };
                },
                buildWhatsAppText(order, templateKey) {
                    if (!order) return '';
                    let customer = order.customer_name || 'Estimado(a) cliente';
                    let remUsd = '$' + this.formatUsd(this.orderRemainingUsd(order));
                    let remBs = 'Bs. ' + this.formatBs(this.orderRemainingBs(order));
                    let totUsd = '$' + this.formatUsd(this.orderTotalUsd(order));
                    let totBs = 'Bs. ' + parseFloat(order.total_bs || 0).toFixed(2);
                    let paidUsd = '$' + this.formatUsd(this.orderPaidUsd(order));
                    let dateStr = this.formatDateStr(order.created_at);
                    let dueStr = order.due_date ? 'Vencimiento: ' + this.formatDateStr(order.due_date) : '';
                    let detailsList = this.parseDetails(order.details);
                    let servicesStr = detailsList.length ? detailsList.map(d => '• ' + d).join('\n') : '• Servicios generales';
                    let negocio = this.debtConfig.print_ticket_business_name || 'FINANZAHEX';
                    let notasStr = order.collection_notes ? '*Nota:* ' + order.collection_notes : '';
                    let datosPago = (this.debtConfig.print_ticket_payment_info || '').trim();

                    let template = '';
                    let defaults = this.defaultWaTemplates();
                    if (templateKey === 'friendly') {
                        template = this.debtConfig.print_wa_friendly || defaults.friendly;
                    } else if (templateKey === 'urgent') {
                        template = this.debtConfig.print_wa_urgent || defaults.urgent;
                    } else {
                        template = this.debtConfig.print_wa_detailed || defaults.detailed;
                    }

                    let text = template
                        .replace(/\{cliente\}/g, customer)
                        .replace(/\{orden\}/g, order.id)
                        .replace(/\{fecha\}/g, dateStr)
                        .replace(/\{vencimiento\}/g, dueStr)
                        .replace(/\{servicios\}/g, servicesStr)
                        .replace(/\{total\}/g, totUsd + ' (' + totBs + ')')
                        .replace(/\{abonado\}/g, paidUsd)
                        .replace(/\{pendiente_usd\}/g, remUsd)
                        .replace(/\{pendiente_bs\}/g, remBs)
                        .replace(/\{negocio\}/g, negocio)
                        .replace(/\{notas\}/g, notasStr)
                        .replace(/\{datos_pago\}/g, datosPago);

                    return text.split('\n').filter((line, idx, arr) => !(line.trim() === '' && arr[idx - 1]?.trim() === '')).join('\n').trim();
                },
                openDebtConfigModal(tab = 'ticket') {
                    this.debtConfigModal.tab = tab;
                    this.debtConfigModal.error = '';
                    let defaults = this.defaultWaTemplates();
                    this.debtConfigModal.form = {
                        print_ticket_business_name: this.debtConfig.print_ticket_business_name || '',
                        print_ticket_subtitle: this.debtConfig.print_ticket_subtitle || '',
                        print_ticket_rif: this.debtConfig.print_ticket_rif || '',
                        print_ticket_phone: this.debtConfig.print_ticket_phone || '',
                        print_ticket_address: this.debtConfig.print_ticket_address || '',
                        print_ticket_payment_info: this.debtConfig.print_ticket_payment_info || '',
                        print_ticket_footer: this.debtConfig.print_ticket_footer || '',
                        print_wa_friendly: this.debtConfig.print_wa_friendly || defaults.friendly,
                        print_wa_detailed: this.debtConfig.print_wa_detailed || defaults.detailed,
                        print_wa_urgent: this.debtConfig.print_wa_urgent || defaults.urgent,
                    };
                    this.debtConfigModal.open = true;
                },
                resetDefaultWaTemplates() {
                    let defaults = this.defaultWaTemplates();
                    this.debtConfigModal.form.print_wa_friendly = defaults.friendly;
                    this.debtConfigModal.form.print_wa_detailed = defaults.detailed;
                    this.debtConfigModal.form.print_wa_urgent = defaults.urgent;
                },
                async saveDebtConfig() {
                    this.debtConfigModal.saving = true;
                    this.debtConfigModal.error = '';
                    try {
                        let res = await fetch('<?= base_url('printing/save-debt-settings') ?>', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ settings: this.debtConfigModal.form })
                        });
                        let data = await res.json();
                        if (!res.ok || data.status !== 'success') {
                            throw new Error(data.message || 'Error al guardar la configuración');
                        }
                        if (data.settings) {
                            this.debtConfig = { ...this.debtConfig, ...data.settings };
                        }
                        if (this.whatsappModal.open && this.whatsappModal.order) {
                            this.whatsappModal.message = this.buildWhatsAppText(this.whatsappModal.order, this.whatsappModal.template);
                        }
                        this.debtConfigModal.open = false;
                        this.message = 'Configuración de cobranzas guardada correctamente';
                        setTimeout(() => this.message = '', 3000);
                    } catch(e) {
                        this.debtConfigModal.error = e.message || 'No se pudo guardar la configuración';
                    } finally {
                        this.debtConfigModal.saving = false;
                    }
                },
                async sendWhatsAppModal() {
                    if (!this.whatsappModal.order) return;
                    let order = this.whatsappModal.order;
                    let phone = this.whatsappPhone(this.whatsappModal.phone || order.customer_phone);
                    let text = this.whatsappModal.message || this.buildWhatsAppText(order, this.whatsappModal.template);
                    let url = 'https://wa.me/' + phone + '?text=' + encodeURIComponent(text);
                    window.open(url, '_blank', 'noopener,noreferrer');
                    this.whatsappModal.open = false;

                    try {
                        let res = await fetch('<?= base_url('printing/record-reminder') ?>', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ order_id: order.id })
                        });
                        let data = await res.json();
                        if (data.status === 'success' && data.data) {
                            order.reminder_count = data.data.reminder_count;
                            order.last_reminder_at = data.data.last_reminder_at;
                            let idx = this.orders.findIndex(o => o.id == order.id);
                            if (idx !== -1) this.orders[idx] = { ...this.orders[idx], ...data.data };
                        }
                    } catch(e) {}
                },
                async copyWhatsAppModalText() {
                    let text = this.whatsappModal.message;
                    if (!text) return;
                    try {
                        await navigator.clipboard.writeText(text);
                        this.message = 'Mensaje copiado al portapapeles';
                        setTimeout(() => this.message = '', 3000);
                    } catch(e) {
                        alert('No se pudo copiar');
                    }
                },
                shareWhatsAppDebt(order) {
                    this.openWhatsAppModal(order, 'detailed');
                },
                async copyDebtInvoice(order) {
                    if (!order) return;
                    try {
                        await navigator.clipboard.writeText(this.buildWhatsAppText(order, 'detailed'));
                        this.message = 'Detalle de cobro copiado';
                        setTimeout(() => this.message = '', 3000);
                    } catch(e) {
                        alert('No se pudo copiar el texto');
                    }
                },
                openCustomerStatement(cust) {
                    if (!cust) return;
                    let openOrders = this.orders.filter(o => o.status !== 'paid' && String(o.customer_name || 'Cliente sin nombre').trim().toLowerCase() === cust.key);
                    let phone = '';
                    for (let o of openOrders) {
                        if (o.customer_phone) { phone = o.customer_phone; break; }
                    }
                    let totalUsd = openOrders.reduce((sum, o) => sum + this.orderRemainingUsd(o), 0);
                    let totalBs = openOrders.reduce((sum, o) => sum + this.orderRemainingBs(o), 0);
                    let totalPaidUsd = openOrders.reduce((sum, o) => sum + this.orderPaidUsd(o), 0);
                    let totalPaidBs = openOrders.reduce((sum, o) => sum + (parseFloat(o.paid_bs || 0)), 0);
                    let totalInvoiceUsd = openOrders.reduce((sum, o) => sum + this.orderTotalUsd(o), 0);
                    let totalInvoiceBs = openOrders.reduce((sum, o) => sum + (parseFloat(o.total_bs || 0)), 0);

                    this.customerStatementModal = {
                        open: true,
                        customer: cust,
                        orders: openOrders,
                        phone: phone,
                        totalUsd: totalUsd,
                        totalBs: totalBs,
                        totalPaidUsd: totalPaidUsd,
                        totalPaidBs: totalPaidBs,
                        totalInvoiceUsd: totalInvoiceUsd,
                        totalInvoiceBs: totalInvoiceBs,
                    };
                },
                buildCustomerStatementWhatsApp() {
                    let m = this.customerStatementModal;
                    if (!m.customer) return '';
                    let lines = [
                        '📋 *ESTADO DE CUENTA CONSOLIDADO - IMPRESIONES*',
                        '',
                        'Estimado(a) *' + m.customer.name + '*,',
                        'le enviamos el resumen de sus órdenes pendientes a la fecha:',
                        ''
                    ];
                    m.orders.forEach((ord, i) => {
                        let details = this.parseDetails(ord.details);
                        let detailText = details.length ? ' (' + details.slice(0, 2).join(', ') + (details.length > 2 ? '...' : '') + ')' : '';
                        lines.push((i + 1) + '. *Orden #' + ord.id + '* (' + this.formatDateStr(ord.created_at) + ')');
                        lines.push('   Saldo: *$' + this.formatUsd(this.orderRemainingUsd(ord)) + '* · Bs. ' + this.formatBs(this.orderRemainingBs(ord)) + detailText);
                    });
                    lines.push(
                        '',
                        '--------------------------------',
                        '*TOTAL ACUMULADO:* *$' + this.formatUsd(m.totalUsd) + '*',
                        '*Equivalente en Bs:* Bs. ' + this.formatBs(m.totalBs),
                        '--------------------------------',
                        '',
                        'Por favor confirmar su pago enviando el comprobante por esta vía.',
                        '¡Agradecemos su preferencia!'
                    );
                    return lines.join('\n');
                },
                async sendCustomerStatementWhatsApp() {
                    let m = this.customerStatementModal;
                    if (!m.customer || !m.orders.length) return;
                    let phone = this.whatsappPhone(m.phone);
                    let text = this.buildCustomerStatementWhatsApp();
                    let url = 'https://wa.me/' + phone + '?text=' + encodeURIComponent(text);
                    window.open(url, '_blank', 'noopener,noreferrer');

                    let orderIds = m.orders.map(o => o.id);
                    try {
                        let res = await fetch('<?= base_url('printing/record-reminder') ?>', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ order_ids: orderIds })
                        });
                        let data = await res.json();
                        if (data.status === 'success' && data.updated) {
                            Object.entries(data.updated).forEach(([id, ch]) => {
                                let idx = this.orders.findIndex(o => o.id == id);
                                if (idx !== -1) this.orders[idx] = { ...this.orders[idx], ...ch };
                            });
                        }
                    } catch(e) {}
                },
                async copyCustomerStatementText() {
                    let text = this.buildCustomerStatementWhatsApp();
                    if (!text) return;
                    try {
                        await navigator.clipboard.writeText(text);
                        this.message = 'Estado de cuenta copiado al portapapeles';
                        setTimeout(() => this.message = '', 3000);
                    } catch(e) {
                        alert('No se pudo copiar');
                    }
                },
                openDebtPrintModal(order) {
                    if (!order) return;
                    this.debtPrintModal.order = order;
                    this.debtPrintModal.open = true;
                },
                printDebtTicket() {
                    window.print();
                },
                async generateTicketCanvas() {
                    let el = document.getElementById('debt-printable-ticket');
                    if (!el) return null;
                    if (document.fonts && document.fonts.ready) {
                        try { await document.fonts.ready; } catch(e) {}
                    }
                    return await html2canvas(el, {
                        scale: 2,
                        useCORS: true,
                        backgroundColor: '#ffffff',
                        logging: false
                    });
                },
                async shareDebtTicketImage() {
                    if (!this.debtPrintModal.order || this.sharingTicketImage) return;
                    let order = this.debtPrintModal.order;
                    this.sharingTicketImage = true;
                    try {
                        let canvas = await this.generateTicketCanvas();
                        if (!canvas) throw new Error('No se pudo generar la imagen del ticket');
                        
                        let blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
                        if (!blob) throw new Error('No se pudo procesar la imagen PNG');

                        let filename = 'ticket_deuda_orden_' + order.id + '.png';
                        let file = new File([blob], filename, { type: 'image/png' });
                        let phone = this.whatsappPhone(order.customer_phone);
                        let caption = 'Comprobante de Deuda - Orden #' + order.id + ' (' + (order.customer_name || 'Cliente') + ') - Saldo Pendiente: $' + this.formatUsd(this.orderRemainingUsd(order));

                        if (navigator.canShare && navigator.canShare({ files: [file] })) {
                            await navigator.share({
                                files: [file],
                                title: 'Ticket Orden #' + order.id,
                                text: caption,
                            });
                            this.message = '¡Comprobante compartido con éxito!';
                            setTimeout(() => this.message = '', 3000);
                        } else {
                            let copied = false;
                            if (navigator.clipboard && window.ClipboardItem) {
                                try {
                                    await navigator.clipboard.write([
                                        new ClipboardItem({ 'image/png': blob })
                                    ]);
                                    copied = true;
                                } catch(err) {}
                            }

                            let waUrl = 'https://wa.me/' + (phone || '') + '?text=' + encodeURIComponent(caption);
                            window.open(waUrl, '_blank', 'noopener,noreferrer');

                            if (copied) {
                                alert('¡Captura del ticket copiada al portapapeles!\n\nSe abrió WhatsApp. En el chat, presiona Ctrl + V para pegar la imagen del comprobante.');
                            } else {
                                let downloadUrl = URL.createObjectURL(blob);
                                let link = document.createElement('a');
                                link.href = downloadUrl;
                                link.download = filename;
                                link.click();
                                URL.revokeObjectURL(downloadUrl);
                                alert('Se descargó la captura del ticket y se abrió WhatsApp para que puedas adjuntarla en el chat.');
                            }
                        }
                    } catch(e) {
                        if (e.name !== 'AbortError') {
                            alert('No se pudo compartir la captura: ' + (e.message || e));
                        }
                    } finally {
                        this.sharingTicketImage = false;
                    }
                },
                async copyTicketImage() {
                    if (!this.debtPrintModal.order || this.sharingTicketImage) return;
                    this.sharingTicketImage = true;
                    try {
                        let canvas = await this.generateTicketCanvas();
                        if (!canvas) throw new Error('No se pudo generar la imagen');
                        let blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
                        if (navigator.clipboard && window.ClipboardItem) {
                            await navigator.clipboard.write([
                                new ClipboardItem({ 'image/png': blob })
                            ]);
                            this.message = '¡Foto del ticket copiada! Pégala con Ctrl+V';
                            setTimeout(() => this.message = '', 3500);
                        } else {
                            throw new Error('Tu navegador no admite copiar imágenes al portapapeles directamente. Puedes usar "Descargar" o "Compartir".');
                        }
                    } catch(e) {
                        alert(e.message || 'No se pudo copiar la imagen al portapapeles');
                    } finally {
                        this.sharingTicketImage = false;
                    }
                },
                async downloadTicketImage() {
                    if (!this.debtPrintModal.order || this.sharingTicketImage) return;
                    this.sharingTicketImage = true;
                    try {
                        let canvas = await this.generateTicketCanvas();
                        if (!canvas) throw new Error('No se pudo generar la imagen');
                        let orderId = this.debtPrintModal.order.id;
                        let link = document.createElement('a');
                        link.download = 'ticket_deuda_orden_' + orderId + '.png';
                        link.href = canvas.toDataURL('image/png');
                        link.click();
                        this.message = 'Captura descargada correctamente';
                        setTimeout(() => this.message = '', 3000);
                    } catch(e) {
                        alert('Error al descargar captura: ' + (e.message || e));
                    } finally {
                        this.sharingTicketImage = false;
                    }
                },
                async copyCollectionReport() {
                    let open = this.orders.filter(o => o.status !== 'paid');
                    let totalUsd = open.reduce((sum, o) => sum + this.orderRemainingUsd(o), 0);
                    let totalBs = open.reduce((sum, o) => sum + this.orderRemainingBs(o), 0);
                    let overdue = open.filter(o => this.dueInfo(o).state === 'overdue');
                    let groups = this.debtCustomerGroups.slice(0, 5);

                    let lines = [
                        '📊 *REPORTE DE CUENTAS POR COBRAR - IMPRESIONES*',
                        'Fecha: ' + new Date().toLocaleDateString('es-VE'),
                        '',
                        '*Total por cobrar:* $' + this.formatUsd(totalUsd) + ' (Bs. ' + this.formatBs(totalBs) + ')',
                        '*Órdenes pendientes:* ' + open.length,
                        '*Clientes con saldo:* ' + this.debtCustomerGroups.length,
                        '*Órdenes vencidas:* ' + overdue.length,
                    ];

                    if (groups.length) {
                        lines.push('', '*Top clientes con mayor saldo:*');
                        groups.forEach((c, i) => {
                            lines.push((i + 1) + '. ' + c.name + ': $' + this.formatUsd(c.totalUsd) + ' (' + c.count + ' ord.)');
                        });
                    }

                    try {
                        await navigator.clipboard.writeText(lines.join('\n'));
                        this.message = 'Resumen de cobranza copiado al portapapeles';
                        setTimeout(() => this.message = '', 3000);
                    } catch(e) {
                        alert('No se pudo copiar el reporte');
                    }
                }
            }
        }
    </script>
</body>
</html>
