<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impresiones y Papelería</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <meta name="theme-color" content="#4f46e5">
    <link rel="manifest" href="<?= base_url('manifest.json') ?>">
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        @keyframes slide-up { from { transform: translateY(100%); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .animate-slide-up { animation: slide-up 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
        .customize-scrollbar::-webkit-scrollbar { width: 4px; }
        .customize-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .customize-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800" x-data="posApp()">

    <!-- Header -->
    <div class="fixed top-0 inset-x-0 bg-white/80 backdrop-blur-md z-40 border-b border-slate-100 h-16 flex items-center justify-between px-4">
        <a href="<?= base_url() ?>" class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-slate-100 text-slate-500 transition-colors">
            <span class="material-icons">arrow_back</span>
        </a>
        <h1 class="text-lg font-bold text-slate-800">Impresiones</h1>
        <a href="<?= base_url('printing/settings') ?>" class="w-10 h-10 flex items-center justify-center rounded-xl bg-indigo-600 text-white shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition-all active:scale-95">
            <span class="material-icons">settings</span>
        </a>
    </div>

    <!-- Tabs -->
    <div class="max-w-5xl mx-auto px-4 mt-20 mb-6 sticky top-20 z-30">
        <div class="bg-white/90 backdrop-blur rounded-2xl p-1.5 shadow-sm border border-slate-100 flex gap-1">
            <button @click="tab = 'pos'" :class="tab === 'pos' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'text-slate-500 hover:bg-slate-50'" class="flex-1 py-3 rounded-xl font-bold text-sm transition-all flex items-center justify-center gap-2">
                <span class="material-icons text-sm">point_of_sale</span> Venta
            </button>
            <button @click="fetchHistory(); tab = 'debts'" :class="tab === 'debts' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'text-slate-500 hover:bg-slate-50'" class="flex-1 py-3 rounded-xl font-bold text-sm transition-all flex items-center justify-center gap-2">
                <span class="material-icons text-sm">schedule</span> Deudas
                <span x-show="debtsCount > 0" x-text="debtsCount" class="bg-rose-500 text-white text-[10px] px-1.5 rounded-full"></span>
            </button>
            <button @click="tab = 'history'; fetchMovements()" :class="tab === 'history' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'text-slate-500 hover:bg-slate-50'" class="flex-1 py-3 rounded-xl font-bold text-sm transition-all flex items-center justify-center gap-2">
                <span class="material-icons text-sm">history</span> Historial
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="pb-40 px-4 max-w-5xl mx-auto">
        
        <!-- POS Tab -->
        <div x-show="tab === 'pos'" class="flex flex-col md:flex-row gap-6">
            <!-- Product Grid -->
            <div class="flex-1">
                <div class="flex items-center justify-between mb-4 bg-white p-3 rounded-2xl shadow-sm border border-slate-100">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Tasa BCV</span>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-indigo-600">Bs.</span>
                         <input type="number" x-model.number="exchangeRate" @input="updateTotals()" class="w-16 font-black text-indigo-800 bg-transparent text-right outline-none">
                    </div>
                </div>
                <!-- Categories & Grid -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                    <?php foreach ($products as $p): ?>
                    <button @click="addToCart(<?= htmlspecialchars(json_encode($p)) ?>)" 
                            class="bg-white p-4 rounded-2xl shadow-sm hover:shadow-md border border-slate-50 flex flex-col items-center justify-center gap-2 active:scale-95 transition-all group relative overflow-hidden h-32">
                        <div class="absolute inset-0 bg-<?= $p['color'] ?? 'indigo' ?>-50 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <span class="material-icons text-3xl text-<?= $p['color'] ?? 'indigo' ?>-500 relative z-10"><?= $p['icon'] ?? 'print' ?></span>
                        <div class="text-center relative z-10">
                            <p class="font-bold text-sm leading-tight text-slate-700"><?= $p['name'] ?></p>
                            <p class="text-[10px] font-bold text-slate-400 mt-1">
                                <?php if($p['price_bs'] > 0): ?>
                                    Bs. <?= $p['price_bs'] ?> <span class="text-[9px] text-slate-300 font-normal ml-1">$<?= $p['price_usd'] ?></span>
                                <?php else: ?>
                                    $<?= $p['price_usd'] ?> <span class="text-[9px] text-slate-300 font-normal ml-1">Bs. <?= number_format($p['price_usd'] * 50, 2) ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Desktop Cart -->
            <div class="hidden md:block w-96 flex-shrink-0">
                <div class="bg-white rounded-[2rem] shadow-xl p-6 sticky top-24 border border-slate-100">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="font-bold text-slate-800">Orden Actual</h2>
                        <button @click="cart = []; updateTotals()" class="text-xs text-rose-500 font-bold uppercase hover:underline" x-show="cart.length > 0">Limpiar</button>
                    </div>
                    <div class="space-y-3 mb-6 max-h-[40vh] overflow-y-auto customize-scrollbar pr-2">
                        <template x-for="(item, index) in cart" :key="index">
                            <div class="group flex flex-col gap-2 border-b border-slate-50 pb-3 last:border-0">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-bold text-slate-700 text-sm" x-text="item.name"></p>
                                        <p class="text-[10px] text-slate-400" x-text="'Bs ' + getLineTotalBs(item)"></p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button @click="item.quantity > 1 ? item.quantity-- : removeFromCart(index); updateTotals()" class="w-6 h-6 rounded-lg bg-slate-50 text-slate-500 flex items-center justify-center font-bold hover:bg-slate-100 text-xs">-</button>
                                        <span class="font-bold text-sm w-4 text-center" x-text="item.quantity"></span>
                                        <button @click="item.quantity++; updateTotals()" class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold hover:bg-indigo-100 text-xs">+</button>
                                    </div>
                                </div>
                                <input type="text" x-model="item.note" placeholder="Nota..." class="text-[10px] bg-transparent border-b border-dashed border-slate-200 w-full outline-none focus:border-indigo-300">
                            </div>
                        </template>
                        <div x-show="cart.length === 0" class="py-12 flex flex-col items-center justify-center text-slate-300 gap-2">
                            <span class="material-icons text-4xl">shopping_cart</span><p class="text-xs font-medium">Carrito Vacío</p>
                        </div>
                    </div>
                    <div class="space-y-1 mb-6 border-t border-slate-100 pt-4">
                        <div class="flex justify-between items-end"><span class="text-sm font-bold text-slate-500">Total Bs</span><span class="text-2xl font-black text-slate-800" x-text="'Bs ' + totalBs.toFixed(2)"></span></div>
                        <div class="flex justify-between items-end"><span class="text-xs font-bold text-slate-400">Total USD</span><span class="text-md font-bold text-slate-600" x-text="'$ ' + totalUsd.toFixed(2)"></span></div>
                    </div>
                    <button @click="checkoutModal.open = true" :disabled="cart.length === 0" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 rounded-xl shadow-lg shadow-indigo-200 active:scale-95 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                        <span>Cobrar</span><span class="material-icons text-sm">arrow_forward</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Bottom Sheet Cart -->
        <div x-show="tab === 'pos'" class="md:hidden">
             <div class="fixed bottom-0 inset-x-0 bg-white border-t border-slate-100 p-4 z-40 shadow-[0_-4px_10px_rgba(0,0,0,0.05)] flex items-center justify-between" x-show="!cartOpen && cart.length > 0">
                 <div @click="cartOpen = true" class="flex-1 cursor-pointer">
                      <p class="text-[10px] font-bold text-slate-400 uppercase"><span x-text="cart.reduce((total, item) => total + parseInt(item.quantity || 0), 0)"></span> Items</p>
                      <div class="flex items-baseline gap-2"><p class="text-xl font-black text-slate-800" x-text="'Bs ' + totalBs.toFixed(2)"></p><p class="text-xs font-bold text-slate-400" x-text="'$' + totalUsd.toFixed(2)"></p></div>
                 </div>
                 <button @click="cartOpen = true" class="w-12 h-12 rounded-full bg-slate-100 text-indigo-600 flex items-center justify-center"><span class="material-icons">keyboard_arrow_up</span></button>
                 <button @click="checkoutModal.open = true" class="ml-3 bg-indigo-600 text-white px-6 py-3 rounded-xl font-bold shadow-lg shadow-indigo-200">Cobrar</button>
             </div>
             <div x-show="cartOpen" class="fixed inset-0 z-50 flex items-end" x-cloak>
                 <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="cartOpen = false"></div>
                 <div class="bg-white rounded-t-[2rem] w-full max-h-[85vh] flex flex-col relative z-10 animate-slide-up shadow-2xl">
                     <div class="w-full flex justify-center pt-3 pb-1" @click="cartOpen = false"><div class="w-12 h-1.5 bg-slate-200 rounded-full"></div></div>
                     <div class="p-6 flex-1 overflow-y-auto">
                         <div class="flex justify-between items-center mb-6"><h2 class="text-xl font-black text-slate-800">Tu Pedido</h2><button @click="cart = []; updateTotals(); cartOpen = false" class="text-xs text-rose-500 font-bold uppercase">Limpiar</button></div>
                         <div class="space-y-4">
                             <template x-for="(item, index) in cart" :key="index">
                                 <div class="flex flex-col gap-3 border-b border-slate-50 pb-4 last:border-0 last:pb-0">
                                     <div class="flex items-center gap-4">
                                         <div class="flex-1"><p class="font-bold text-slate-800 text-lg leading-tight" x-text="item.name"></p><p class="text-xs text-slate-400 mt-0.5" x-text="'Bs ' + getLineTotalBs(item)"></p></div>
                                         <div class="flex flex-col gap-2 items-end">
                                             <div class="flex items-center gap-3 bg-slate-50 rounded-2xl p-1.5">
                                                 <button @click="item.quantity > 1 ? item.quantity-- : removeFromCart(index); updateTotals()" class="w-10 h-10 rounded-xl bg-white shadow-sm text-slate-500 flex items-center justify-center font-bold text-xl active:scale-95">-</button>
                                                 <span class="w-8 text-center font-black text-lg text-slate-800" x-text="item.quantity"></span>
                                                 <button @click="item.quantity++; updateTotals()" class="w-10 h-10 rounded-xl bg-indigo-600 shadow-md shadow-indigo-200 text-white flex items-center justify-center font-bold text-xl active:scale-95">+</button>
                                             </div>
                                         </div>
                                     </div>
                                     <input type="text" x-model="item.note" placeholder="Nota del item..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm outline-none focus:border-indigo-400">
                                 </div>
                             </template>
                         </div>
                     </div>
                     <div class="p-6 border-t border-slate-100 bg-slate-50/50 pb-8">
                         <div class="flex justify-between items-end mb-4"><span class="text-sm font-bold text-slate-500">Total a Pagar</span><div class="text-right"><p class="text-3xl font-black text-slate-800" x-text="'Bs ' + totalBs.toFixed(2)"></p><p class="text-sm font-bold text-slate-400" x-text="'$ ' + totalUsd.toFixed(2)"></p></div></div>
                         <button @click="cartOpen = false; checkoutModal.open = true" class="w-full bg-indigo-600 text-white font-bold py-4 rounded-2xl shadow-xl shadow-indigo-200 text-lg">Confirmar Pedido</button>
                     </div>
                 </div>
             </div>
        </div>

        <!-- Debts & History Tabs (Simplified for length) -->
        <div x-show="tab === 'debts'">
            <div class="mb-4 sticky top-0 z-30 bg-slate-50/95 backdrop-blur py-2">
                <div class="relative"><span class="material-icons absolute left-3 top-2.5 text-slate-400">search</span><input type="text" x-model="searchQuery" placeholder="Buscar..." class="w-full bg-white border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-sm font-bold text-slate-700 outline-none focus:border-indigo-500"></div>
            </div>
            <!-- Mobile List -->
            <div class="space-y-3 pb-24">
                <template x-for="order in filteredOrders" :key="order.id">
                    <div class="bg-white p-3 rounded-xl shadow-sm border border-slate-100 flex flex-col gap-2 relative overflow-hidden">
                        <div class="flex justify-between items-start">
                             <div><h3 class="font-bold text-slate-800 text-sm mb-0.5" x-text="order.customer_name || 'Cliente sin nombre'"></h3><div class="text-[10px] text-slate-500 mb-1 leading-tight"><template x-for="detail in parseDetails(order.details)"><div x-text="detail"></div></template></div><p class="text-[9px] font-bold text-slate-400" x-text="order.created_at"></p></div>
                             <span class="px-2 py-0.5 rounded-[6px] text-[9px] font-black uppercase tracking-wider border" :class="{'bg-emerald-50 text-emerald-600 border-emerald-100': order.status === 'paid', 'bg-amber-50 text-amber-600 border-amber-100': order.status === 'partial', 'bg-rose-50 text-rose-500 border-rose-100': order.status === 'pending'}" x-text="order.status"></span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 bg-slate-50 rounded-lg p-2 border border-slate-100">
                            <div><p class="text-[9px] uppercase font-bold text-slate-400">Total</p><div class="font-black text-slate-700 text-sm" x-text="'Bs ' + parseFloat(order.total_bs).toFixed(2)"></div></div>
                            <div class="text-right"><p class="text-[9px] uppercase font-bold text-slate-400">Deuda</p><div class="font-black text-rose-500 text-sm" x-text="'Bs ' + calculateDebt(order, 'Bs')"></div><div class="text-[9px] text-rose-400" x-text="'$ ' + calculateDebt(order, 'USD')"></div></div>
                        </div>
                        <div class="flex gap-2 pt-1">
                            <button x-show="order.status !== 'paid'" @click="openPayModal(order)" class="flex-1 bg-indigo-600 text-white py-1.5 rounded-lg font-bold shadow-sm text-[10px] flex items-center justify-center gap-1"><span class="material-icons text-[12px]">payments</span> Abonar</button>
                            <button @click="openEditModal(order)" class="bg-white text-indigo-600 px-3 py-1.5 rounded-lg border border-indigo-100 shadow-sm"><span class="material-icons text-[14px]">edit</span></button>
                            <button @click="confirmDelete(order.id)" class="bg-white text-rose-500 px-3 py-1.5 rounded-lg border border-rose-100 shadow-sm"><span class="material-icons text-[14px]">delete</span></button>
                        </div>
                    </div>
                </template>
                <div x-show="filteredOrders.length === 0" class="text-center py-12 text-slate-400"><p class="font-medium">No hay deudas pendientes</p></div>
            </div>
        </div>

        <div x-show="tab === 'history'">
             <!-- Metrics & Filters -->
             <div class="mb-6 sticky top-0 z-30 bg-slate-50/95 backdrop-blur py-2 space-y-3">
                 <div class="grid grid-cols-2 gap-3">
                    <div class="bg-white p-3 rounded-xl shadow-sm border border-slate-100"><p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Cobrado (Hoy)</p><p class="text-lg font-black text-emerald-600" x-text="'Bs ' + historyMetrics.today_bs.toFixed(2)"></p></div>
                    <div class="bg-white p-3 rounded-xl shadow-sm border border-slate-100"><p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Deuda Total</p><p class="text-lg font-black text-rose-500" x-text="'Bs ' + historyMetrics.debt_bs.toFixed(2)"></p></div>
                 </div>
                 <div class="flex gap-3"><input type="text" x-model="historySearch" placeholder="Buscar..." class="flex-1 bg-white border border-slate-200 rounded-xl px-4 py-2 text-sm"><select x-model="historyFilter" class="bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs"><option value="all">Todos</option><option value="pending">Deudas</option><option value="paid">Pagados</option></select></div>
            </div>
            <div class="space-y-3 pb-24">
                 <template x-for="order in filteredHistory" :key="order.id">
                     <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-100 hover:shadow-md transition-shadow cursor-pointer" @click="openOrderDetails(order)">
                         <div class="flex justify-between items-start mb-2">
                             <div><h3 class="font-bold text-slate-800" x-text="order.customer_name || 'Sin Nombre'"></h3><p class="text-[10px] text-slate-400" x-text="order.created_at"></p></div>
                             <span class="px-2 py-0.5 rounded-[6px] text-[9px] font-black uppercase tracking-wider border" :class="{'bg-emerald-50 text-emerald-600 border-emerald-100': order.status === 'paid', 'bg-amber-50 text-amber-600 border-amber-100': order.status === 'partial', 'bg-rose-50 text-rose-500 border-rose-100': order.status === 'pending'}" x-text="order.status"></span>
                         </div>
                         <div class="flex justify-between items-end">
                             <div class="text-[10px] text-slate-500 max-w-[60%] truncate"><template x-for="detail in parseDetails(order.details)"><span x-text="detail + ' '" class="block truncate"></span></template></div>
                             <div class="text-right"><p class="text-sm font-black text-slate-700" x-text="'Bs ' + parseFloat(order.total_bs).toFixed(2)"></p><p class="text-[10px] text-rose-400" x-show="order.status !== 'paid'" x-text="'Debe: Bs ' + calculateDebt(order, 'Bs')"></p></div>
                         </div>
                     </div>
                 </template>
            </div>
        </div>
    </div>

    <!-- Modals (Include Only Checkout & Pay for now, others are implicit) -->
    <!-- Checkout Modal -->
    <div x-show="checkoutModal.open" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="checkoutModal.open = false"></div>
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md relative z-10 p-6 space-y-5 animate-slide-up">
            <div class="flex justify-between items-center border-b border-slate-100 pb-4">
                <div><h3 class="font-black text-xl text-slate-800">Confirmar Venta</h3><div class="flex items-baseline gap-2"><span class="text-lg font-black text-indigo-600" x-text="'Bs ' + totalBs.toFixed(2)"></span><span class="text-xs font-bold text-slate-400" x-text="'$ ' + totalUsd.toFixed(2)"></span></div></div>
                <button @click="checkoutModal.open = false" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center"><span class="material-icons text-sm">close</span></button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Cliente / Nota</label>
                    <div class="relative group">
                         <input type="text" x-model="customer_name" @input="searchCustomers()" @focus="customerSuggestions.show = true" @click.away="customerSuggestions.show = false" class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-4 pr-10 py-3 font-bold text-slate-800 outline-none focus:border-indigo-500" placeholder="Opcional">
                         <button @click="toggleFavorite()" class="absolute right-3 top-3 text-slate-300 hover:text-amber-400 transition-colors" :class="isFavorite ? 'text-amber-400' : ''"><span class="material-icons" x-text="isFavorite ? 'star' : 'star_border'"></span></button>
                         <div x-show="customerSuggestions.show && customerSuggestions.list.length > 0" class="absolute top-full left-0 right-0 bg-white shadow-xl rounded-xl border border-slate-100 mt-1 max-h-40 overflow-y-auto z-50">
                             <template x-for="cust in customerSuggestions.list" :key="cust.id"><div @click="selectCustomer(cust)" class="px-4 py-2 hover:bg-indigo-50 cursor-pointer flex justify-between items-center"><span class="font-bold text-slate-700 text-sm" x-text="cust.name"></span><span x-show="cust.is_favorite == 1" class="material-icons text-[14px] text-amber-400">star</span></div></template>
                         </div>
                    </div>
                </div>
                <div class="bg-indigo-50/50 rounded-xl p-4 border border-indigo-100">
                    <div class="flex justify-between items-center mb-3"><span class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest">Pago Realizado</span><button @click="toggleDebt()" class="text-[10px] font-bold px-2 py-1 rounded-lg transition-colors border" :class="(paidBs == 0 && paidUsd == 0) ? 'bg-white text-emerald-600 border-emerald-200 shadow-sm' : 'text-rose-500 bg-rose-50 border-transparent hover:bg-rose-100'"><span x-text="(paidBs == 0 && paidUsd == 0) ? 'Restaurar Pago' : 'Sin Pago (Deuda)'"></span></button></div>
                    <div class="grid grid-cols-2 gap-3 mb-3">
                         <div><label class="text-[10px] font-bold text-slate-500">En Bs</label><input type="number" step="0.01" x-model.number="paidBs" class="w-full bg-white border border-slate-200 rounded-lg px-2 py-2 text-sm font-bold text-slate-800 outline-none focus:border-indigo-500"></div>
                         <div><label class="text-[10px] font-bold text-emerald-600">En USD</label><input type="number" step="0.01" x-model.number="paidUsd" class="w-full bg-white border border-emerald-200 rounded-lg px-2 py-2 text-sm font-bold text-emerald-600 outline-none focus:border-emerald-500"></div>
                    </div>
                    <div x-show="paidBs > 0 || paidUsd > 0"><label class="text-[10px] font-bold text-slate-500 mb-1 block">Cuenta Destino</label><select x-model="account_id" class="w-full text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-lg px-2 py-2 outline-none"><?php foreach($accounts as $acc): ?><option value="<?= $acc['id'] ?>"><?= $acc['name'] ?> (<?= $acc['currency'] ?? 'Bs' ?>)</option><?php endforeach; ?></select></div>
                </div>
            </div>
            <button @click="checkout()" :disabled="loading" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 rounded-xl shadow-lg shadow-indigo-200 active:scale-95 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"><span x-show="!loading" x-text="getButtonText()"></span><span x-show="loading" class="material-icons animate-spin text-sm">refresh</span></button>
        </div>
    </div>
    
    <!-- Pay Modal -->
    <div x-show="payModal.open" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
         <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="payModal.open = false"></div>
         <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm relative z-10 p-6 space-y-4">
             <h3 class="font-bold text-lg text-slate-800">Abonar a Deuda</h3>
             <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 text-center"><p class="text-xs text-slate-500">Deuda Restante</p><p class="text-xl font-black text-rose-500" x-text="'Bs ' + calculateDebt(payModal.order || {}, 'Bs')"></p></div>
             <div class="grid grid-cols-2 gap-3"><div><label class="text-[10px] font-bold text-slate-500">Abono Bs</label><input type="number" step="0.01" x-model.number="payModal.amount_bs" class="w-full bg-white border border-slate-200 rounded-lg px-2 py-2 font-bold outline-none focus:border-indigo-500"></div><div><label class="text-[10px] font-bold text-emerald-600">Abono USD</label><input type="number" step="0.01" x-model.number="payModal.amount_usd" class="w-full bg-white border border-emerald-200 rounded-lg px-2 py-2 font-bold outline-none focus:border-emerald-500 text-emerald-600"></div></div>
             <div><label class="text-[10px] font-bold text-slate-500 mb-1 block">Cuenta</label><select x-model="payModal.account_id" class="w-full text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-lg px-2 py-2 outline-none"><?php foreach($accounts as $acc): ?><option value="<?= $acc['id'] ?>"><?= $acc['name'] ?></option><?php endforeach; ?></select></div>
             <button @click="submitPayment()" class="w-full bg-indigo-600 text-white font-bold py-3 rounded-xl hover:bg-indigo-700">Registrar Abono</button>
         </div>
    </div>

    <!-- Edit/Delete/Info Modals (Placeholders) -->
    <div x-show="deleteModal.open" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak><div class="absolute inset-0 bg-slate-900/60" @click="deleteModal.open=false"></div><div class="bg-white rounded-xl p-6 relative z-10 w-full max-w-sm"><h3 class="font-bold text-lg mb-4">Eliminar Orden</h3><div class="flex items-center gap-2 mb-4"><input type="checkbox" x-model="deleteModal.revert" id="revert" class="w-5 h-5"><label for="revert" class="text-sm">Revertir dinero (caja)</label></div><div class="flex gap-2"><button @click="deleteModal.open=false" class="flex-1 py-3 font-bold text-slate-500 bg-slate-100 rounded-xl">Cancelar</button><button @click="deleteOrder()" class="flex-1 py-3 font-bold text-white bg-rose-500 rounded-xl">Eliminar</button></div></div></div>
    
    <div x-show="detailsModal.open" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak><div class="absolute inset-0 bg-slate-900/60" @click="detailsModal.open=false"></div><div class="bg-white rounded-2xl p-6 relative z-10 w-full max-w-md max-h-[80vh] overflow-y-auto"><h3 class="font-bold text-xl mb-2">Detalles</h3><div class="space-y-2 mb-4"><template x-for="line in detailsModal.items"><p class="text-sm text-slate-700 border-b border-slate-50 pb-1" x-text="line"></p></template></div><div x-show="detailsModal.transactions.length"><h4 class="font-bold text-xs text-slate-400 uppercase mt-4 mb-2">Pagos</h4><template x-for="t in detailsModal.transactions"><div class="text-sm flex justify-between bg-slate-50 p-2 rounded mb-1"><span x-text="t.created_at"></span><span x-text="(t.amount > 0 ? 'Bs '+t.amount : '') + (t.amount_usd > 0 ? ' $'+t.amount_usd : '')"></span><button @click="confirmTransDelete(t.id)" class="text-rose-500"><span class="material-icons text-xs">delete</span></button></div></template></div><button @click="detailsModal.open=false" class="w-full bg-slate-100 py-3 rounded-xl font-bold text-slate-600 mt-4">Cerrar</button></div></div>
    
    <div x-show="transDeleteModal.open" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak><div class="absolute inset-0 bg-slate-900/60" @click="transDeleteModal.open=false"></div><div class="bg-white rounded-xl p-6 relative z-10 w-full max-w-sm"><h3 class="font-bold text-lg mb-4">Eliminar Transacción</h3><div class="flex gap-2"><button @click="deleteTransaction(false)" class="flex-1 py-3 font-bold text-slate-500 bg-slate-100 rounded-xl">Solo Registro</button><button @click="deleteTransaction(true)" class="flex-1 py-3 font-bold text-white bg-rose-500 rounded-xl">Revertir Dinero</button></div></div></div>

    <!-- Toast -->
    <div x-show="message" x-transition class="fixed bottom-10 left-1/2 -translate-x-1/2 bg-slate-900 text-white px-6 py-3 rounded-full shadow-xl z-50 text-sm font-bold flex items-center gap-2"><span class="material-icons text-green-400">check_circle</span><span x-text="message"></span></div>

    <script>
        <!-- Alpine Logic Injection Placeholder -->
    </script>
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
                async fetchRate() { try { let res = await fetch('<?= base_url('currency/get-rate') ?>'); let data = await res.json(); if (data.rate > 0) this.exchangeRate = data.rate; } catch(e){} },
                async fetchHistory() { try { let res = await fetch('<?= base_url('printing/history') ?>?t='+Date.now()); let data = await res.json(); if (data.status === 'success') this.orders = data.data; if(this.tab==='history') this.fetchMovements(); } catch(e){} },
                async fetchMovements() { try { let res = await fetch('<?= base_url('printing/movements') ?>?t='+Date.now()); let data = await res.json(); if (data.status==='success') this.movements = data.data; } catch(e){} },
                
                parseDetails(str) { try { return JSON.parse(str); } catch(e) { return [str]; } },
                
                addToCart(product) {
                    let exists = this.cart.find(i => i.id === product.id);
                    if (exists) { exists.quantity++; } 
                    else { this.cart.push({ id: product.id, name: product.name, price_bs: parseFloat(product.price_bs), price_usd: parseFloat(product.price_usd), quantity: 1, note: '' }); }
                    this.updateTotals();
                },
                removeFromCart(index) { this.cart.splice(index, 1); this.updateTotals(); },
                updateTotals() {
                    let bs = 0, usd = 0;
                    this.cart.forEach(i => {
                        let q = parseInt(i.quantity)||0;
                        if(i.price_bs > 0) { bs += i.price_bs*q; usd += (i.price_bs*q)/this.exchangeRate; }
                        else { usd += i.price_usd*q; bs += (i.price_usd*q)*this.exchangeRate; }
                    });
                    this.totalBs = bs; this.totalUsd = usd;
                },
                getLineTotalBs(item) { let q = parseInt(item.quantity)||0; return (item.price_bs > 0 ? item.price_bs*q : item.price_usd*q*this.exchangeRate).toFixed(2); },
                getButtonText() { return this.loading ? 'Procesando...' : ((this.paidBs > 0 || this.paidUsd > 0) ? 'Confirmar Pago' : 'Guardar como Deuda'); },
                toggleDebt() { if(this.paidBs == 0 && this.paidUsd == 0) { this.paidBs = this.totalBs.toFixed(2); this.paidUsd = 0; } else { this.paidBs = 0; this.paidUsd = 0; } },
                
                async checkout() {
                    if (this.cart.length === 0) return;
                    this.loading = true;
                    try {
                        let res = await fetch('<?= base_url('printing/store') ?>', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ items: this.cart, customer_name: this.customer_name, account_id: this.account_id, exchange_rate: this.exchangeRate, paid_bs: parseFloat(this.paidBs||0), paid_usd: parseFloat(this.paidUsd||0) }) });
                        let data = await res.json();
                        if(data.status === 'success') { this.cart = []; this.customer_name = ''; this.paidBs=0; this.paidUsd=0; this.updateTotals(); this.checkoutModal.open = false; this.cartOpen = false; this.fetchHistory(); this.message='Venta Registrada'; setTimeout(()=>this.message='',3000); }
                        else { alert('Error: ' + data.message); }
                    } catch(e) { alert('Error de conexión'); } finally { this.loading = false; }
                },

                openPayModal(order) { this.payModal.order = order; this.payModal.orderId = order.id; this.payModal.customer = order.customer_name; this.payModal.total_bs = parseFloat(order.total_bs); this.payModal.paid_bs = parseFloat(order.paid_bs); this.payModal.paid_usd = parseFloat(order.paid_usd); this.payModal.amount_bs=0; this.payModal.amount_usd=0; this.payModal.open = true; this.fetchPayHistory(order.id); },
                async fetchPayHistory(id) { try { let res = await fetch('<?= base_url('printing/payments') ?>/' + id); let data = await res.json(); if(data.status==='success') this.payModal.history = data.data; } catch(e){} },
                
                async submitPayment() {
                     if ((this.payModal.amount_bs > 0 || this.payModal.amount_usd > 0) && !this.payModal.account_id) { alert('Seleccione cuenta'); return; }
                     try {
                        let res = await fetch('<?= base_url('printing/add-payment') ?>', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ order_id: this.payModal.orderId, account_id: this.payModal.account_id, amount_bs: parseFloat(this.payModal.amount_bs), amount_usd: parseFloat(this.payModal.amount_usd), rate: this.exchangeRate }) });
                        let data = await res.json();
                        if(data.status === 'success') { 
                            this.payModal.open = false; 
                            if(data.order) { let idx = this.orders.findIndex(o => o.id == data.order.id); if(idx !== -1) this.orders[idx] = data.order; }
                            this.fetchHistory(); this.message = 'Abono registrado'; setTimeout(()=>this.message='',3000);
                        } else { alert(data.message); }
                     } catch(e) {}
                },

                confirmDelete(id) { this.deleteModal.orderId = id; this.deleteModal.revert = false; this.deleteModal.open = true; },
                async deleteOrder() { try { await fetch('<?= base_url('printing/delete-order') ?>/'+this.deleteModal.orderId+'?revert='+this.deleteModal.revert, { method: 'POST' }); this.deleteModal.open=false; this.fetchHistory(); this.message='Eliminado'; setTimeout(()=>this.message='',3000); } catch(e){} },
                
                confirmTransDelete(id) { this.transDeleteModal.transId = id; this.transDeleteModal.open = true; },
                async deleteTransaction(revert) { try { let res = await fetch('<?= base_url('printing/delete-transaction') ?>/'+this.transDeleteModal.transId+'?revert='+revert, { method: 'POST' }); let data = await res.json(); if(data.status==='success') { this.transDeleteModal.open=false; if(data.order) { let idx = this.orders.findIndex(o=>o.id==data.order.id); if(idx!==-1) this.orders[idx]=data.order; } this.detailsModal.open=false; this.fetchHistory(); this.message='Eliminado'; setTimeout(()=>this.message='',3000); } } catch(e){} },

                async openOrderDetails(order) { this.detailsModal.order = order; this.detailsModal.items = this.parseDetails(order.details); this.detailsModal.transactions = []; this.detailsModal.open = true; try { let res = await fetch('<?= base_url('printing/payments') ?>/' + order.id); let data = await res.json(); if (data.status === 'success') this.detailsModal.transactions = data.data; } catch(e){} },
                openEditModal(order) { this.editModal = { open: true, id: order.id, customer_name: order.customer_name, status: order.status }; },

                get filteredOrders() { if(this.tab === 'debts') return this.orders.filter(o => o.status !== 'paid' && this.matchSearch(o, this.searchQuery)); return this.orders; },
                get filteredHistory() { let list = this.orders; if(this.historyFilter !== 'all') list = list.filter(o => o.status === this.historyFilter); if(this.historySearch) list = list.filter(o => this.matchSearch(o, this.historySearch)); return list; },
                get debtsCount() { return this.orders.filter(o => o.status !== 'paid').length; },
                get historyMetrics() { let t = new Date().toISOString().split('T')[0], m = { today_bs: 0, debt_bs: 0 }; this.filteredHistory.forEach(o => { if(o.status!=='paid') m.debt_bs += parseFloat(this.calculateDebt(o,'Bs')); if(o.created_at && o.created_at.startsWith(t)) m.today_bs += parseFloat(o.paid_bs) + (parseFloat(o.paid_usd)*this.exchangeRate); }); return m; },
                matchSearch(o, q) { if(!q) return true; let l = q.toLowerCase(); return (o.customer_name||'').toLowerCase().includes(l) || o.id.toString().includes(l) || JSON.stringify(o.details).toLowerCase().includes(l); },
                calculateDebt(o, c) { let dBs = Math.max(0, parseFloat(o.total_bs)-parseFloat(o.paid_bs)-(parseFloat(o.paid_usd)*this.exchangeRate)); let dUsd = Math.max(0, parseFloat(o.total_usd)-parseFloat(o.paid_usd)-(parseFloat(o.paid_bs)/this.exchangeRate)); return c === 'Bs' ? dBs.toFixed(2) : dUsd.toFixed(2); },

                // Customer Search Helpers
                async searchCustomers() { if(this.customer_name.length < 2) { this.customerSuggestions.list = []; return; } try { let res = await fetch('<?= base_url('printing/customers') ?>?term=' + this.customer_name); let data = await res.json(); if(data.status==='success') this.customerSuggestions.list = data.data; } catch(e){} },
                selectCustomer(c) { this.customer_name = c.name; this.isFavorite = (c.is_favorite == 1); this.customerSuggestions.show = false; },
                async toggleFavorite() { this.isFavorite = !this.isFavorite; if(this.customer_name) await fetch('<?= base_url('printing/toggle-favorite') ?>', { method: 'POST', body: JSON.stringify({ name: this.customer_name, favorite: this.isFavorite }) }); }
            }
        }
    </script>
</body>
</html>
