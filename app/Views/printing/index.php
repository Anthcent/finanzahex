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
                <!-- Search & Status Summary -->
                <div class="flex items-center justify-between mb-4 bg-white/90 backdrop-blur-md px-4 py-2.5 rounded-2xl shadow-2xs border border-slate-200/80">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span class="text-xs font-bold text-slate-600">Catálogo de Servicios</span>
                    </div>
                    <span class="text-[11px] font-bold text-slate-400"><?= count($products) ?> items listados</span>
                </div>

                <!-- Product Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                    <?php foreach ($products as $p): ?>
                    <button @click="addToCart(<?= htmlspecialchars(json_encode($p)) ?>)" 
                            class="bg-white hover:bg-emerald-50/30 p-3.5 rounded-2xl shadow-2xs hover:shadow-md border border-slate-200/70 hover:border-emerald-300 flex flex-col items-center justify-center gap-2 active:scale-95 transition-all group relative overflow-hidden h-32 text-center">
                        <div class="w-11 h-11 rounded-2xl bg-emerald-50 group-hover:bg-emerald-100 text-emerald-700 flex items-center justify-center transition-colors">
                            <span class="material-icons text-2xl group-hover:scale-110 transition-transform"><?= $p['icon'] ?? 'print' ?></span>
                        </div>
                        <div class="w-full">
                            <p class="font-bold text-xs leading-tight text-slate-800 line-clamp-1 group-hover:text-emerald-950 transition-colors"><?= $p['name'] ?></p>
                            <div class="text-[10px] font-black text-slate-500 mt-1">
                                <?php if($p['price_bs'] > 0): ?>
                                    <span class="text-emerald-700 font-extrabold">Bs. <?= number_format($p['price_bs'], 2) ?></span>
                                    <span class="text-[9px] text-slate-400 font-bold ml-1">$<?= number_format($p['price_usd'], 2) ?></span>
                                <?php else: ?>
                                    <span class="text-emerald-700 font-extrabold">$<?= number_format($p['price_usd'], 2) ?></span>
                                    <span class="text-[9px] text-slate-400 font-bold ml-1">Bs. <?= number_format($p['price_usd'] * 50, 2) ?></span>
                                <?php endif; ?>
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
                            <span class="text-sm font-black text-emerald-800" x-text="'$ ' + totalUsd.toFixed(2)"></span>
                        </div>

                        <button @click="checkoutModal.open = true" 
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
                        <p class="text-xs font-bold text-emerald-700" x-text="'$' + totalUsd.toFixed(2)"></p>
                    </div>
                </div>
                <button @click="cartOpen = true" class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center active:scale-95" title="Ver carrito">
                    <span class="material-icons text-lg">keyboard_arrow_up</span>
                </button>
                <button @click="checkoutModal.open = true" class="bg-gradient-to-r from-emerald-600 to-teal-700 text-white px-5 py-2.5 rounded-xl font-black text-sm shadow-md shadow-emerald-950/20 active:scale-95">
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
                                <p class="text-xs font-bold text-emerald-700" x-text="'$ ' + totalUsd.toFixed(2)"></p>
                            </div>
                        </div>
                        <button @click="cartOpen = false; checkoutModal.open = true" class="w-full bg-gradient-to-r from-emerald-600 to-teal-700 text-white font-black py-4 rounded-2xl shadow-xl shadow-emerald-950/20 text-base active:scale-98">
                            Confirmar y Cobrar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Debts Tab -->
        <div x-show="tab === 'debts'">
            <!-- Search Bar -->
            <div class="mb-4 sticky top-18 z-30 bg-slate-50/90 backdrop-blur-md py-2">
                <div class="relative">
                    <span class="material-icons absolute left-3.5 top-3 text-slate-400 text-lg">search</span>
                    <input type="text" x-model="searchQuery" placeholder="Buscar por cliente, detalle o número..." class="w-full bg-white border border-slate-200/90 rounded-2xl pl-10 pr-10 py-3 text-xs sm:text-sm font-bold text-slate-700 outline-none focus:border-emerald-500 shadow-2xs">
                    <button x-show="searchQuery" @click="searchQuery = ''" class="absolute right-3.5 top-3 text-slate-400 hover:text-slate-600">
                        <span class="material-icons text-sm">close</span>
                    </button>
                </div>
            </div>

            <!-- Debts Cards List -->
            <div class="space-y-3 pb-24">
                <template x-for="order in filteredOrders" :key="order.id">
                    <div class="bg-white p-4 rounded-2xl shadow-2xs hover:shadow-md border border-slate-200/80 flex flex-col gap-3 relative overflow-hidden transition-all">
                        <div class="flex justify-between items-start gap-2">
                            <div>
                                <h3 class="font-black text-slate-900 text-sm leading-tight" x-text="order.customer_name || 'Cliente sin nombre'"></h3>
                                <div class="text-[11px] text-slate-500 mt-1 leading-snug">
                                    <template x-for="detail in parseDetails(order.details)">
                                        <span class="inline-block bg-slate-100 text-slate-700 px-2 py-0.5 rounded-lg mr-1 mb-1 font-medium" x-text="detail"></span>
                                    </template>
                                </div>
                                <p class="text-[10px] font-bold text-slate-400 mt-0.5" x-text="order.created_at"></p>
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
                
                <div x-show="filteredOrders.length === 0" class="text-center py-16 bg-white/60 rounded-3xl border border-dashed border-slate-200">
                    <span class="material-icons text-4xl text-emerald-400 mb-2">check_circle</span>
                    <p class="font-bold text-slate-600 text-sm">¡Al día! No hay deudas pendientes</p>
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
                        <span class="text-xs font-bold text-slate-400" x-text="'$ ' + totalUsd.toFixed(2)"></span>
                    </div>
                </div>
                <button @click="checkoutModal.open = false" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 flex items-center justify-center">
                    <span class="material-icons text-base">close</span>
                </button>
            </div>

            <div class="space-y-4">
                <!-- Customer Name Autocomplete -->
                <div>
                    <label class="block text-[11px] font-black text-slate-500 uppercase tracking-wider mb-1.5">Cliente / Nota</label>
                    <div class="relative group">
                        <input type="text" x-model="customer_name" @input="searchCustomers()" @focus="customerSuggestions.show = true" @click.away="customerSuggestions.show = false" class="w-full bg-slate-50 border border-slate-200 rounded-2xl pl-4 pr-10 py-3 font-bold text-slate-800 outline-none focus:border-emerald-500 text-sm" placeholder="Opcional (Ej. Juan Pérez)">
                        <button @click="toggleFavorite()" class="absolute right-3 top-3 text-slate-300 hover:text-amber-400 transition-colors" :class="isFavorite ? 'text-amber-400' : ''" title="Marcar como frecuente">
                            <span class="material-icons text-xl" x-text="isFavorite ? 'star' : 'star_border'"></span>
                        </button>
                        
                        <!-- Suggestions Dropdown -->
                        <div x-show="customerSuggestions.show && customerSuggestions.list.length > 0" class="absolute top-full left-0 right-0 bg-white shadow-xl rounded-2xl border border-slate-100 mt-1 max-h-40 overflow-y-auto z-50 customize-scrollbar">
                            <template x-for="cust in customerSuggestions.list" :key="cust.id">
                                <div @click="selectCustomer(cust)" class="px-4 py-2.5 hover:bg-emerald-50 cursor-pointer flex justify-between items-center transition-colors">
                                    <span class="font-bold text-slate-700 text-xs" x-text="cust.name"></span>
                                    <span x-show="cust.is_favorite == 1" class="material-icons text-xs text-amber-400">star</span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Payment Breakdown Card -->
                <div class="bg-emerald-50/50 rounded-2xl p-4 border border-emerald-100/80">
                    <div class="flex justify-between items-center mb-3">
                        <span class="text-[10px] font-black text-emerald-800 uppercase tracking-wider">Abono Inicial</span>
                        <button @click="toggleDebt()" class="text-[10px] font-black px-2.5 py-1 rounded-xl transition-colors border" :class="(paidBs == 0 && paidUsd == 0) ? 'bg-white text-emerald-700 border-emerald-200 shadow-2xs' : 'text-rose-600 bg-rose-50 border-rose-200 hover:bg-rose-100'">
                            <span x-text="(paidBs == 0 && paidUsd == 0) ? 'Restaurar Pago' : 'Sin Pago (Deuda Total)'"></span>
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="text-[10px] font-bold text-slate-500 mb-1 block">Monto en Bs.</label>
                            <input type="number" step="0.01" x-model.number="paidBs" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-sm font-black text-slate-800 outline-none focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-emerald-700 mb-1 block">Monto en USD</label>
                            <input type="number" step="0.01" x-model.number="paidUsd" class="w-full bg-white border border-emerald-200 rounded-xl px-3 py-2.5 text-sm font-black text-emerald-700 outline-none focus:border-emerald-500">
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
                </div>
            </div>

            <button @click="checkout()" :disabled="loading" class="w-full bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white font-black py-4 rounded-2xl shadow-lg shadow-emerald-950/20 active:scale-98 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
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
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-[10px] font-bold text-slate-500 mb-1 block">Abono Bs.</label>
                    <input type="number" step="0.01" x-model.number="payModal.amount_bs" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2.5 font-black text-sm text-slate-800 outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="text-[10px] font-bold text-emerald-700 mb-1 block">Abono USD</label>
                    <input type="number" step="0.01" x-model.number="payModal.amount_usd" class="w-full bg-white border border-emerald-200 rounded-xl px-3 py-2.5 font-black text-sm text-emerald-700 outline-none focus:border-emerald-500">
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

            <button @click="submitPayment()" class="w-full bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white font-black py-3.5 rounded-2xl shadow-lg shadow-emerald-950/20 active:scale-98 transition-all">
                Registrar Abono
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
                exchangeRate: 50, account_id: '<?= $defaultAccount ?? ($accounts[0]['id'] ?? 1) ?>',
                customer_name: '', loading: false, message: '',
                totalBs: 0, totalUsd: 0, paidBs: 0, paidUsd: 0,
                
                checkoutModal: { open: false },
                payModal: { open: false, orderId: null, amount_bs: 0, amount_usd: 0, account_id: '<?= $accounts[0]['id'] ?? 1 ?>', customer: '', history: [] },
                deleteModal: { open: false, orderId: null, revert: false },
                transDeleteModal: { open: false, transId: null },
                detailsModal: { open: false, order: null, items: [], transactions: [], loading: false },
                editModal: { open: false, id: null, customer_name: '', status: '' },
                
                customerSuggestions: { show: false, list: [] }, isFavorite: false,
                searchQuery: '', historySearch: '', historyFilter: 'all',

                init() { this.fetchRate(); this.fetchHistory(); this.updateTotals(); },
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
                },
                getLineTotalBs(item) { 
                    let q = parseInt(item.quantity) || 0; 
                    return (item.price_bs > 0 ? item.price_bs * q : item.price_usd * q * this.exchangeRate).toFixed(2); 
                },
                getButtonText() { 
                    return this.loading ? 'Procesando...' : ((this.paidBs > 0 || this.paidUsd > 0) ? 'Confirmar y Guardar' : 'Registrar como Deuda'); 
                },
                toggleDebt() { 
                    if(this.paidBs == 0 && this.paidUsd == 0) { 
                        this.paidBs = this.totalBs.toFixed(2); 
                        this.paidUsd = 0; 
                    } else { 
                        this.paidBs = 0; 
                        this.paidUsd = 0; 
                    } 
                },
                
                async checkout() {
                    if (this.cart.length === 0) return;
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
                            this.paidBs = 0; 
                            this.paidUsd = 0; 
                            this.updateTotals(); 
                            this.checkoutModal.open = false; 
                            this.cartOpen = false; 
                            this.fetchHistory(); 
                            this.message = '¡Venta registrada con éxito!'; 
                            setTimeout(() => this.message = '', 3000); 
                        } else { 
                            alert('Error: ' + data.message); 
                        }
                    } catch(e) { 
                        alert('Error de conexión'); 
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

                async searchCustomers() { 
                    if(this.customer_name.length < 2) { 
                        this.customerSuggestions.list = []; 
                        return; 
                    } 
                    try { 
                        let res = await fetch('<?= base_url('printing/customers') ?>?term=' + encodeURIComponent(this.customer_name)); 
                        let data = await res.json(); 
                        if(data.status === 'success') this.customerSuggestions.list = data.data; 
                    } catch(e){} 
                },
                selectCustomer(c) { 
                    this.customer_name = c.name; 
                    this.isFavorite = (c.is_favorite == 1); 
                    this.customerSuggestions.show = false; 
                },
                async toggleFavorite() { 
                    this.isFavorite = !this.isFavorite; 
                    if(this.customer_name) {
                        await fetch('<?= base_url('printing/toggle-favorite') ?>', { 
                            method: 'POST', 
                            body: JSON.stringify({ name: this.customer_name, favorite: this.isFavorite }) 
                        }); 
                    }
                }
            }
        }
    </script>
</body>
</html>
