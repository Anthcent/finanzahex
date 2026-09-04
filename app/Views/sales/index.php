<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Gestión de Ventas | Fi-Hex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
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
        [x-cloak] { display: none !important; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .customize-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
        .customize-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .customize-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
        .safe-bottom { padding-bottom: max(1.5rem, env(safe-area-inset-bottom)); }
        .safe-top { padding-top: max(0.75rem, env(safe-area-inset-top)); }
    </style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800 antialiased" x-data="dashboardApp()">
    
    <!-- Hero Executive Header -->
    <div class="bg-gradient-to-r from-emerald-950 via-slate-900 to-teal-950 text-white pb-14 rounded-b-[2.5rem] shadow-xl relative overflow-hidden safe-top">
        <!-- Subtle Glow Effects -->
        <div class="absolute top-0 right-0 w-72 h-72 bg-emerald-500/10 rounded-full blur-3xl -mr-20 -mt-20 pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 w-60 h-60 bg-teal-500/10 rounded-full blur-3xl -ml-20 -mb-20 pointer-events-none"></div>

        <div class="p-4 pt-4 max-w-md mx-auto relative z-10">
            <!-- Header Bar -->
            <div class="flex items-center justify-between mb-4">
                <a href="<?= base_url() ?>" class="w-10 h-10 rounded-2xl bg-white/10 hover:bg-white/20 active:scale-95 flex items-center justify-center text-white transition-all border border-white/10" title="Volver">
                    <span class="material-icons text-lg">arrow_back</span>
                </a>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-400 p-[1.5px]">
                        <div class="w-full h-full bg-slate-950 rounded-[9px] flex items-center justify-center">
                            <span class="text-[10px] font-black text-emerald-300">FW</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-[9px] font-black text-emerald-300 uppercase tracking-widest block">Módulo</span>
                        <h1 class="text-base font-black tracking-tight text-white leading-tight">Ventas & Pedidos</h1>
                    </div>
                </div>
            </div>

            <!-- Month Summary Card -->
            <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 sm:p-5 border border-white/15 shadow-xl relative overflow-hidden">
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-emerald-200 text-xs font-bold flex items-center gap-1.5">
                            <span class="material-icons text-sm">calendar_month</span>
                            <span>Resumen del Mes</span>
                        </p>
                        <span class="bg-white/15 text-white/90 text-[10px] font-black uppercase px-2 py-0.5 rounded-md border border-white/10"><?= date('F') ?></span>
                    </div>

                    <div class="flex items-end justify-between mt-2">
                        <div>
                            <h2 class="text-3xl font-black text-white tracking-tight">$<?= number_format($monthTotal, 2) ?></h2>
                            <p class="text-[11px] text-emerald-200/80 font-bold mt-0.5"><?= $monthCount ?> ventas registradas</p>
                        </div>
                        
                        <?php if($growth != 0): ?>
                        <div class="flex items-center gap-1 px-2 py-1 rounded-xl border backdrop-blur-md <?= $growth >= 0 ? 'bg-emerald-500/20 border-emerald-400/30 text-emerald-300' : 'bg-rose-500/20 border-rose-400/30 text-rose-300' ?>">
                            <span class="material-icons text-xs font-black"><?= $growth >= 0 ? 'trending_up' : 'trending_down' ?></span>
                            <span class="text-xs font-black"><?= abs(round($growth)) ?>%</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Stats (Horizontal Scroll Carousel) -->
    <div class="max-w-md mx-auto px-4 -mt-7 mb-4 relative z-30">
        <div class="flex gap-2.5 overflow-x-auto pb-2 pt-1 no-scrollbar snap-x">
            <?php foreach($statusStats as $stat): ?>
            <div class="min-w-[135px] bg-white rounded-2xl p-3.5 shadow-sm border border-slate-100 snap-center flex flex-col justify-between h-24 hover:-translate-y-0.5 transition-all">
                 <div class="flex items-center gap-2 mb-1">
                     <span class="w-1.5 h-6 rounded-full <?= explode(' ', $stat['color'])[1] ?? 'text-emerald-600' ?> bg-current opacity-70"></span>
                     <div class="min-w-0 flex-1">
                         <p class="text-[9px] font-black text-slate-400 uppercase tracking-wider truncate"><?= esc($stat['name']) ?></p>
                         <p class="text-xl font-black text-slate-900 leading-none mt-0.5"><?= $stat['count'] ?></p>
                     </div>
                 </div>
                 
                 <div class="bg-slate-50 rounded-xl p-1.5 px-2 flex justify-between items-center">
                     <span class="text-[9px] font-black text-slate-400 uppercase">Total</span>
                     <p class="text-[11px] font-black text-slate-700">
                        $<?= number_format($stat['total_usd'], 0) ?>
                     </p>
                 </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Main Navigation Actions -->
    <div class="max-w-md mx-auto px-4 space-y-3 pb-24 relative z-20 safe-bottom">
        
        <!-- Nueva Venta -->
        <a href="<?= base_url('sales/create') ?>" class="block bg-white p-4 sm:p-5 rounded-3xl shadow-xs hover:shadow-md transition-all active:scale-98 group border border-slate-100 relative overflow-hidden">
            <div class="flex items-center space-x-4">
                <div class="w-13 h-13 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-700 text-white flex items-center justify-center shadow-md shadow-emerald-950/20 group-hover:scale-105 transition-transform shrink-0">
                    <span class="material-icons text-2xl">point_of_sale</span>
                </div>
                <div class="min-w-0 flex-1">
                    <h2 class="text-base font-black text-slate-900 group-hover:text-emerald-700 transition-colors">Nueva Venta</h2>
                    <p class="text-xs text-slate-500 font-bold">Registrar salida de mercancía y cobro</p>
                </div>
                <span class="material-icons text-slate-300 group-hover:text-emerald-600 transition-colors">chevron_right</span>
            </div>
        </a>

        <!-- Cuentas por Cobrar -->
        <a href="<?= base_url('sales/debts') ?>" class="block bg-white p-4 sm:p-5 rounded-3xl shadow-xs hover:shadow-md transition-all active:scale-98 group border border-slate-100 relative overflow-hidden">
            <div class="flex items-center space-x-4">
                <div class="w-13 h-13 rounded-2xl bg-gradient-to-tr from-rose-600 to-rose-700 text-white flex items-center justify-center shadow-md shadow-rose-950/20 group-hover:scale-105 transition-transform shrink-0">
                    <span class="material-icons text-2xl">pending_actions</span>
                </div>
                <div class="min-w-0 flex-1">
                    <h2 class="text-base font-black text-slate-900 group-hover:text-rose-600 transition-colors">Cuentas por Cobrar</h2>
                    <p class="text-xs text-slate-500 font-bold">Gestionar abonos, saldos y deudas</p>
                </div>
                <span class="material-icons text-slate-300 group-hover:text-rose-600 transition-colors">chevron_right</span>
            </div>
        </a>

        <!-- Historial -->
        <a href="<?= base_url('sales/history') ?>" class="block bg-white p-4 sm:p-5 rounded-3xl shadow-xs hover:shadow-md transition-all active:scale-98 group border border-slate-100 relative overflow-hidden">
            <div class="flex items-center space-x-4">
                <div class="w-13 h-13 rounded-2xl bg-gradient-to-tr from-blue-600 to-blue-700 text-white flex items-center justify-center shadow-md shadow-blue-950/20 group-hover:scale-105 transition-transform shrink-0">
                    <span class="material-icons text-2xl">history</span>
                </div>
                <div class="min-w-0 flex-1">
                    <h2 class="text-base font-black text-slate-900 group-hover:text-blue-600 transition-colors">Historial de Ventas</h2>
                    <p class="text-xs text-slate-500 font-bold">Consultar listado global y filtros</p>
                </div>
                <span class="material-icons text-slate-300 group-hover:text-blue-600 transition-colors">chevron_right</span>
            </div>
        </a>

    </div>

    <!-- Quick Manage FAB -->
    <div class="fixed bottom-6 right-6 z-40 safe-bottom">
        <button @click="openQuickManage()" class="w-14 h-14 bg-gradient-to-tr from-emerald-600 to-teal-700 rounded-2xl shadow-xl shadow-emerald-950/30 text-white flex items-center justify-center hover:scale-105 active:scale-95 transition-all" title="Gestión Rápida de Estados">
            <span class="material-icons text-2xl">playlist_add_check</span>
        </button>
    </div>

    <!-- QUICK MANAGE MODAL (Bottom sheet) -->
    <div x-show="showQuickManage" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4" x-cloak>
        <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs" @click="showQuickManage = false"></div>
        
        <div class="bg-white rounded-t-[2.5rem] sm:rounded-3xl w-full sm:max-w-lg shadow-2xl relative z-10 flex flex-col max-h-[85vh] safe-bottom animate-slide-up">
            
            <div class="p-5 sm:p-6 border-b border-slate-100 flex justify-between items-center shrink-0">
                <div>
                    <h2 class="text-base sm:text-lg font-black text-slate-900">Órdenes Activas</h2>
                    <p class="text-[10px] sm:text-xs text-slate-400 font-bold">Actualización instantánea de estado</p>
                </div>
                <button @click="showQuickManage = false" class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 hover:text-slate-800 transition-colors">
                    <span class="material-icons text-sm">close</span>
                </button>
            </div>

            <div class="overflow-y-auto p-4 space-y-3 customize-scrollbar bg-slate-50/60">
                <template x-for="sale in activeOrders" :key="sale.id">
                    <div class="bg-white p-4 rounded-2xl shadow-2xs border border-slate-100">
                        <div class="flex justify-between items-start mb-2.5">
                            <div class="min-w-0 flex-1 pr-2">
                                <p class="font-black text-slate-800 text-sm truncate" x-text="sale.customer"></p>
                                <p class="text-xs text-slate-500 font-bold truncate" x-text="sale.product"></p>
                            </div>
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase shrink-0" 
                                  :class="sale.status_color || 'bg-slate-100 text-slate-600'"
                                  x-text="sale.status_name"></span>
                        </div>
                        
                        <!-- Quick Actions -->
                        <div class="flex gap-2 text-xs overflow-x-auto no-scrollbar pt-1">
                            <template x-for="st in statuses" :key="st.id">
                                <button @click="quickUpdateStatus(sale, st.id)" 
                                        x-show="sale.order_status_id != st.id"
                                        class="px-3 py-1.5 rounded-xl border text-[11px] font-black whitespace-nowrap transition-all active:scale-95"
                                        :class="st.name === 'Entregado' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 
                                               (st.name === 'Cancelado' ? 'bg-rose-50 text-rose-600 border-rose-200 hover:bg-rose-100' : 
                                               'bg-white text-slate-600 border-slate-200 hover:bg-slate-50')">
                                    <span x-text="st.name"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
                
                <div x-show="activeOrders.length === 0" class="text-center py-12 text-slate-400">
                    <span class="material-icons text-4xl text-emerald-500 mb-1">check_circle</span>
                    <p class="text-sm font-black text-slate-700">¡Todo al día!</p>
                    <p class="text-xs text-slate-400 font-medium">No hay órdenes pendientes en este momento.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Alpine Logic -->
    <script>
        function dashboardApp() {
            return {
                showQuickManage: false,
                activeOrders: [],
                statuses: [],

                init() {
                    this.fetchStatuses();
                },

                async fetchStatuses() {
                    try {
                        let res = await fetch('<?= base_url('sales/get-statuses') ?>');
                        let data = await res.json();
                        if(data.status === 'success') this.statuses = data.data;
                    } catch(e) {}
                },

                async openQuickManage() {
                    this.showQuickManage = true;
                    this.activeOrders = [];
                    try {
                        let res = await fetch('<?= base_url('sales/get-active-orders') ?>');
                        let data = await res.json();
                        if(data.status === 'success') {
                            this.activeOrders = data.data;
                        }
                    } catch(e) {
                        alert('Error al cargar órdenes');
                    }
                },

                async quickUpdateStatus(sale, newStatusId) {
                    const originalStatus = sale.order_status_id;
                    sale.order_status_id = newStatusId;
                    
                    try {
                         let res = await fetch('<?= base_url('sales/update-status') ?>', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                sale_id: sale.id,
                                status_id: newStatusId
                            })
                        });
                        let data = await res.json();
                        
                        if(data.status === 'success') {
                            const statusObj = this.statuses.find(s => s.id == newStatusId);
                            if(statusObj && (statusObj.name === 'Entregado' || statusObj.name === 'Cancelado')) {
                                this.activeOrders = this.activeOrders.filter(s => s.id !== sale.id);
                            } else if (statusObj) {
                                sale.status_name = statusObj.name;
                                sale.status_color = statusObj.color;
                            }
                        } else {
                            sale.order_status_id = originalStatus;
                            alert('Error al actualizar estado');
                        }
                    } catch(e) {
                        sale.order_status_id = originalStatus;
                        alert('Error de conexión');
                    }
                }
            }
        }
    </script>
</body>
</html>
