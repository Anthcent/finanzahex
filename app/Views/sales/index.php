<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Ventas</title>
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
<body class="bg-slate-50 min-h-screen" x-data="dashboardApp()">
    
    <!-- Hero Header -->
    <div class="bg-gradient-to-br from-indigo-900 via-blue-900 to-slate-900 text-white pb-16 rounded-b-[2rem] shadow-xl relative overflow-hidden">
        <!-- Decorative bubbles -->
        <div class="absolute top-0 right-0 w-64 h-64 bg-white opacity-5 rounded-full blur-3xl -mr-20 -mt-20"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-emerald-500 opacity-10 rounded-full blur-2xl -ml-10 -mb-10"></div>

        <div class="p-4 pt-6 max-w-md mx-auto relative z-10">
            <div class="flex items-center justify-between mb-4">
                <a href="<?= base_url() ?>" class="w-8 h-8 rounded-full bg-white/10 backdrop-blur-md flex items-center justify-center text-white hover:bg-white/20 transition-all border border-white/10">
                    <span class="material-icons text-xs">arrow_back</span>
                </a>
                <div class="text-right">
                    <p class="text-[10px] font-medium text-emerald-300 uppercase tracking-widest">Módulo</p>
                    <h1 class="text-xl font-black tracking-tight">Ventas</h1>
                </div>
            </div>

            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/10 shadow-lg relative overflow-hidden group">
                <div class="absolute right-0 top-0 w-24 h-24 bg-indigo-500/20 rounded-bl-full blur-xl transition-transform group-hover:scale-110"></div>
                
                <div class="relative z-10">
                    <p class="text-indigo-200 text-xs font-medium mb-0.5 flex items-center gap-2">
                        Resumen del Mes
                        <span class="bg-white/10 px-1.5 py-0.5 rounded text-[9px] text-white/70"><?= date('F') ?></span>
                    </p>
                    <div class="flex items-end gap-2">
                        <h2 class="text-3xl font-black text-white tracking-tight">$<?= number_format($monthTotal, 2) ?></h2>
                        
                        <?php if($growth != 0): ?>
                        <div class="flex items-center gap-0.5 px-1.5 py-0.5 rounded-lg border backdrop-blur-md mb-1.5 <?= $growth >= 0 ? 'bg-emerald-500/20 border-emerald-500/30 text-emerald-300' : 'bg-rose-500/20 border-rose-500/30 text-rose-300' ?>">
                            <span class="material-icons text-[10px] font-bold"><?= $growth >= 0 ? 'north_east' : 'south_east' ?></span>
                            <span class="text-[10px] font-bold"><?= abs(round($growth)) ?>%</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <p class="text-[10px] text-indigo-300 mt-1 font-medium"><?= $monthCount ?> ventas registradas</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Stats (Horizontal Scroll) -->
    <div class="max-w-md mx-auto px-4 -mt-8 mb-4 relative z-30">
        <div class="flex gap-3 overflow-x-auto pb-2 pt-1 no-scrollbar snap-x">
            <?php foreach($statusStats as $stat): ?>
            <div class="min-w-[130px] bg-white rounded-xl p-3 shadow-md border border-slate-50 snap-center flex flex-col justify-between h-24 relative overflow-hidden group hover:-translate-y-0.5 transition-transform duration-300">
                 
                 <div class="flex items-center gap-2 mb-1 relative z-10">
                     <span class="w-1.5 h-6 rounded-full <?= explode(' ', $stat['color'])[1] // Use text color class for accent bar ?> bg-current opacity-50"></span>
                     <div>
                         <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wider truncate max-w-[90px]"><?= esc($stat['name']) ?></p>
                         <p class="text-2xl font-black text-slate-800 leading-none mt-0.5"><?= $stat['count'] ?></p>
                     </div>
                 </div>
                 
                 <div class="relative z-10 bg-slate-50 rounded-lg p-1.5 flex justify-between items-center group-hover:bg-indigo-50/50 transition-colors">
                     <span class="text-[9px] font-bold text-slate-400 uppercase">Total</span>
                     <p class="text-[10px] font-bold text-slate-600">
                        $<?= number_format($stat['total_usd'], 0) ?>
                     </p>
                 </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Main Menu Cards -->
    <div class="max-w-md mx-auto px-4 space-y-3 pb-20 relative z-20">
        
        <!-- Register Sale -->
        <a href="<?= base_url('sales/create') ?>" class="block bg-white p-4 rounded-2xl shadow-sm hover:shadow-md transition-all active:scale-95 group border border-slate-50 relative overflow-hidden">
            <div class="absolute right-0 top-0 w-24 h-24 bg-indigo-50 rounded-bl-full opacity-50 transition-transform group-hover:scale-110 origin-top-right"></div>
            <div class="relative z-10 flex items-center space-x-4">
                <div class="w-12 h-12 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-md shadow-indigo-200 group-hover:rotate-6 transition-transform">
                    <span class="material-icons text-2xl">point_of_sale</span>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-slate-800 group-hover:text-indigo-700 transition-colors">Nueva Venta</h2>
                    <p class="text-xs text-slate-500 font-medium">Registrar salida de mercancía</p>
                </div>
            </div>
        </a>

        <!-- Debts -->
        <a href="<?= base_url('sales/debts') ?>" class="block bg-white p-4 rounded-2xl shadow-sm hover:shadow-md transition-all active:scale-95 group border border-slate-50 relative overflow-hidden">
            <div class="absolute right-0 top-0 w-24 h-24 bg-amber-50 rounded-bl-full opacity-50 transition-transform group-hover:scale-110 origin-top-right"></div>
            <div class="relative z-10 flex items-center space-x-4">
                <div class="w-12 h-12 rounded-xl bg-amber-500 text-white flex items-center justify-center shadow-md shadow-amber-200 group-hover:-rotate-6 transition-transform">
                    <span class="material-icons text-2xl">pending_actions</span>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-slate-800 group-hover:text-amber-600 transition-colors">Cuentas por Cobrar</h2>
                    <p class="text-xs text-slate-500 font-medium">Gestionar abonos y deudas</p>
                </div>
            </div>
        </a>

        <!-- History -->
        <a href="<?= base_url('sales/history') ?>" class="block bg-white p-4 rounded-2xl shadow-sm hover:shadow-md transition-all active:scale-95 group border border-slate-50 relative overflow-hidden">
            <div class="absolute right-0 top-0 w-24 h-24 bg-blue-50 rounded-bl-full opacity-50 transition-transform group-hover:scale-110 origin-top-right"></div>
            <div class="relative z-10 flex items-center space-x-4">
                <div class="w-12 h-12 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-md shadow-blue-200 group-hover:rotate-3 transition-transform">
                    <span class="material-icons text-2xl">history</span>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-slate-800 group-hover:text-blue-600 transition-colors">Historial</h2>
                    <p class="text-xs text-slate-500 font-medium">Ver todas las transacciones</p>
                </div>
            </div>
        </a>

    </div>

    <!-- Quick Manage FAB -->
    <div class="fixed bottom-6 right-6 z-40">
        <button @click="openQuickManage()" class="w-14 h-14 bg-indigo-600 rounded-full shadow-2xl shadow-indigo-500/40 text-white flex items-center justify-center hover:scale-110 active:scale-95 transition-all">
            <span class="material-icons">playlist_add_check</span>
        </button>
    </div>

    <!-- QUICK MANAGE MODAL -->
    <div x-show="showQuickManage" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center px-4 pb-4 sm:p-0" x-cloak>
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showQuickManage = false"></div>
        
        <div class="bg-white rounded-t-3xl sm:rounded-2xl w-full max-w-lg shadow-2xl relative z-10 transform transition-all scale-100 flex flex-col max-h-[85vh]">
            
            <div class="p-6 border-b border-slate-100 flex justify-between items-center shrink-0">
                <div>
                    <h2 class="text-xl font-bold text-slate-800">Ordenes Activas</h2>
                    <p class="text-xs text-slate-500 font-medium mt-1">Gestión rápida de estados</p>
                </div>
                <button @click="showQuickManage = false" class="w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 hover:bg-slate-100 transition-colors">
                    <span class="material-icons text-sm">close</span>
                </button>
            </div>

            <div class="overflow-y-auto p-4 space-y-4 bg-slate-50/50">
                <template x-for="sale in activeOrders" :key="sale.id">
                    <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-100">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <p class="font-bold text-slate-800" x-text="sale.customer"></p>
                                <p class="text-xs text-slate-500 truncate w-48" x-text="sale.product"></p>
                            </div>
                            <span class="px-2 py-1 rounded-lg text-[10px] font-bold uppercase" 
                                  :class="sale.status_color || 'bg-slate-100 text-slate-500'"
                                  x-text="sale.status_name"></span>
                        </div>
                        
                        <!-- Quick Actions -->
                        <div class="flex gap-2 text-xs overflow-x-auto no-scrollbar pb-1">
                            <template x-for="st in statuses" :key="st.id">
                                <button @click="quickUpdateStatus(sale, st.id)" 
                                        x-show="sale.order_status_id != st.id"
                                        class="px-3 py-1.5 rounded-lg border font-bold whitespace-nowrap transition-colors"
                                        :class="st.name === 'Entregado' ? 'bg-emerald-50 text-emerald-600 border-emerald-100 hover:bg-emerald-100' : 
                                               (st.name === 'Cancelado' ? 'bg-rose-50 text-rose-600 border-rose-100 hover:bg-rose-100' : 
                                               'bg-white text-slate-500 border-slate-200 hover:bg-slate-50')">
                                    <span x-text="st.name"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
                
                <div x-show="activeOrders.length === 0" class="text-center py-10 opacity-50">
                    <span class="material-icons text-4xl text-slate-300 mb-2">assignment_turned_in</span>
                    <p class="text-sm font-bold text-slate-500">¡Todo al día!</p>
                    <p class="text-xs text-slate-400">No hay ordenes pendientes</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Alpine Logic -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        function dashboardApp() {
            return {
                showQuickManage: false,
                activeOrders: [],
                statuses: [],

                init() {
                    // Fetch statuses for the modal buttons
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
                    this.activeOrders = []; // Reset
                    try {
                        let res = await fetch('<?= base_url('sales/get-active-orders') ?>');
                        let data = await res.json();
                        if(data.status === 'success') {
                            this.activeOrders = data.data;
                        }
                    } catch(e) {
                        alert('Error al cargar ordenes');
                    }
                },

                async quickUpdateStatus(sale, newStatusId) {
                    // Optimistic UI Update
                    const originalStatus = sale.order_status_id;
                    sale.order_status_id = newStatusId; // Temporarily hide button if same logic uses it
                    
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
                            // If status is Entregado/Cancelado, remove from list with animation
                            // Check if new status is "Entregado" or "Cancelado"
                            const statusObj = this.statuses.find(s => s.id == newStatusId);
                            if(statusObj && (statusObj.name === 'Entregado' || statusObj.name === 'Cancelado')) {
                                this.activeOrders = this.activeOrders.filter(s => s.id !== sale.id);
                            } else {
                                // Just update visual
                                sale.status_name = statusObj.name;
                                sale.status_color = statusObj.color;
                            }
                        } else {
                            sale.order_status_id = originalStatus; // Revert
                            alert('Error updating');
                        }
                    } catch(e) {
                        sale.order_status_id = originalStatus;
                        alert('Connection error');
                    }
                }
            }
        }
    </script>
</body>
</html>
