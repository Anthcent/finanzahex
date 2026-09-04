<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Historial de Ventas | Fi-Hex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
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
<body class="bg-slate-50 min-h-screen text-slate-800 antialiased" x-data="historyApp()">

    <!-- Executive Top Nav Header -->
    <header class="sticky top-0 z-40 bg-gradient-to-r from-emerald-950 via-slate-900 to-teal-950 text-white shadow-xl border-b border-emerald-800/30 safe-top">
        <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="<?= base_url('sales') ?>" class="w-10 h-10 rounded-2xl bg-white/10 hover:bg-white/20 active:scale-95 flex items-center justify-center text-white transition-all border border-white/10" title="Volver">
                    <span class="material-icons text-xl">arrow_back</span>
                </a>
                <div>
                    <h1 class="text-sm sm:text-base font-black tracking-tight text-white leading-tight">Historial de Ventas</h1>
                    <p class="text-[10px] text-emerald-200/70 font-semibold" x-text="filteredSales.length + ' ventas encontradas'"></p>
                </div>
            </div>
            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-blue-500 to-teal-400 p-[1.5px] shadow-sm">
                <div class="w-full h-full bg-slate-950 rounded-[9px] flex items-center justify-center">
                    <span class="material-icons text-blue-300 text-sm">history</span>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto p-4 pb-24 space-y-4 safe-bottom">
        
        <!-- Search & Filter Controls -->
        <div class="bg-white rounded-3xl p-4 shadow-xs border border-slate-100 flex flex-col gap-3">
            <div class="relative">
                <span class="material-icons absolute left-3.5 top-2.5 text-slate-400 text-lg">search</span>
                <input type="text" x-model="filters.search" placeholder="Buscar por cliente, referencia o #ID..." 
                       class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl text-xs sm:text-sm font-bold text-slate-800 outline-none focus:border-emerald-500 focus:bg-white transition-all">
            </div>
            
            <div class="flex gap-2 text-xs overflow-x-auto pb-1 no-scrollbar">
                <button @click="filters.status = ''" 
                        class="px-3.5 py-1.5 rounded-xl text-xs font-black whitespace-nowrap transition-all border active:scale-95"
                        :class="filters.status === '' ? 'bg-gradient-to-r from-emerald-600 to-teal-700 text-white border-emerald-600 shadow-xs' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'">
                    Todos
                </button>
                <template x-for="st in statuses" :key="st.id">
                    <button @click="filters.status = st.id" 
                            class="px-3.5 py-1.5 rounded-xl text-xs font-black whitespace-nowrap transition-all border active:scale-95"
                            :class="filters.status == st.id ? (st.color + ' border-transparent shadow-xs scale-105') : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'">
                        <span x-text="st.name"></span>
                    </button>
                </template>
            </div>
        </div>

        <!-- Sales Records Container -->
        <div class="bg-white rounded-3xl shadow-xs overflow-hidden border border-slate-100">
            
            <!-- Desktop View: Sleek Table -->
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-400 text-[10px] font-black uppercase tracking-wider border-b border-slate-100">
                            <th class="p-4">Fecha / ID</th>
                            <th class="p-4">Detalle / Cliente</th>
                            <th class="p-4 text-right">Monto</th>
                            <th class="p-4 text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs sm:text-sm">
                        <tr x-show="filteredSales.length === 0">
                            <td colspan="4" class="p-8 text-center text-slate-400 font-bold">
                                No se encontraron registros de ventas.
                            </td>
                        </tr>

                        <template x-for="sale in filteredSales" :key="sale.id">
                            <tr class="hover:bg-slate-50/80 transition-colors group cursor-pointer" @click="openSale(sale.id)">
                                <td class="p-4">
                                    <div class="flex flex-col">
                                        <span class="font-black text-slate-800" x-text="formatDateShort(sale.date)"></span>
                                        <span class="text-[10px] font-bold text-slate-400" x-text="'#' + sale.id"></span>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <p class="font-black text-slate-900 group-hover:text-emerald-700 transition-colors" x-text="sale.customer"></p>
                                    <p class="text-xs text-slate-400 font-medium truncate max-w-xs" x-text="sale.product"></p>
                                </td>
                                <td class="p-4 text-right">
                                    <p class="font-black text-slate-900" x-text="'$' + parseFloat(sale.amount_usd).toFixed(2)"></p>
                                    <p class="text-[10px] text-slate-400 font-bold" x-text="'Bs. ' + parseFloat(sale.amount).toLocaleString('es-VE', {minimumFractionDigits: 2})"></p>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider inline-block"
                                          :class="sale.status_color || 'bg-slate-100 text-slate-600'">
                                        <span x-text="sale.status_name || 'Pendiente'"></span>
                                    </span>
                                    
                                    <div x-show="sale.status == 'paid'" class="flex justify-center items-center mt-1 text-emerald-700">
                                        <span class="material-icons text-xs mr-0.5">check_circle</span> 
                                        <span class="text-[10px] font-black">Pagado</span>
                                    </div>
                                    <div x-show="sale.status == 'partial'" class="flex justify-center items-center mt-1 text-amber-700">
                                        <span class="material-icons text-xs mr-0.5">pending</span> 
                                        <span class="text-[10px] font-black">Abono</span>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Mobile View: Modern Cards (Zero Overflow) -->
            <div class="sm:hidden divide-y divide-slate-100">
                <template x-for="sale in filteredSales" :key="sale.id">
                    <div class="p-4 flex items-center justify-between gap-3 hover:bg-slate-50 transition-colors cursor-pointer active:scale-98" @click="openSale(sale.id)">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-1.5 mb-1">
                                <span class="px-2 py-0.5 rounded-md text-[9px] font-black uppercase"
                                      :class="sale.status_color || 'bg-slate-100 text-slate-600'"
                                      x-text="sale.status_name || 'Pendiente'"></span>
                                <span class="text-[10px] font-bold text-slate-400" x-text="formatDateShort(sale.date) + ' • #' + sale.id"></span>
                            </div>
                            <h4 class="font-black text-slate-900 text-xs truncate" x-text="sale.customer"></h4>
                            <p class="text-[10px] text-slate-500 font-bold truncate mt-0.5" x-text="sale.product"></p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="font-black text-slate-900 text-sm" x-text="'$' + parseFloat(sale.amount_usd).toFixed(2)"></p>
                            <span class="text-[9px] font-black uppercase px-1.5 py-0.5 rounded"
                                  :class="sale.status === 'paid' ? 'text-emerald-700 bg-emerald-50' : 'text-amber-700 bg-amber-50'"
                                  x-text="sale.status === 'paid' ? 'Pagado' : 'Abono'"></span>
                        </div>
                    </div>
                </template>
                <div x-show="filteredSales.length === 0" class="p-8 text-center text-slate-400 font-bold text-xs">
                    No se encontraron ventas con los filtros aplicados.
                </div>
            </div>

        </div>
    </main>
        
    <!-- DETAILS MODAL (Mobile Bottom Sheet) -->
    <div x-show="showModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4" x-cloak>
        <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs transition-opacity" @click="showModal = false"></div>
        
        <div class="bg-white rounded-t-[2.5rem] sm:rounded-3xl w-full sm:max-w-lg shadow-2xl relative z-10 flex flex-col max-h-[90vh] safe-bottom animate-slide-up">
            
            <!-- Modal Header -->
            <div class="p-5 sm:p-6 border-b border-slate-100 flex justify-between items-start shrink-0">
                <div>
                    <h2 class="text-base sm:text-lg font-black text-slate-900">Detalle de Venta</h2>
                    <p class="text-xs text-slate-400 font-bold mt-0.5">
                        #<span x-text="selectedSale?.id"></span> &bull; 
                        <span x-text="formatDate(selectedSale?.date)"></span>
                    </p>
                </div>
                <button @click="showModal = false" class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 hover:text-slate-800 transition-colors">
                    <span class="material-icons text-sm">close</span>
                </button>
            </div>

            <!-- Modal Content -->
            <div class="overflow-y-auto p-5 sm:p-6 space-y-5 customize-scrollbar">
                
                <!-- Status & Customer Card -->
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/70 space-y-3">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-[10px] uppercase font-black text-slate-400 tracking-wider">Cliente</p>
                            <p class="font-black text-slate-900 text-sm sm:text-base" x-text="selectedSale?.customer"></p>
                        </div>
                        <div class="px-2.5 py-1 rounded-lg text-xs font-black uppercase"
                             :class="selectedSale?.status === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'">
                            <span x-text="selectedSale?.status === 'paid' ? 'Pagado' : 'Abono / Crédito'"></span>
                        </div>
                    </div>
                    
                    <!-- Order Status Selector -->
                    <div>
                        <p class="text-[10px] uppercase font-black text-slate-400 tracking-wider mb-2">Actualizar Estado</p>
                        <div class="flex flex-wrap gap-2">
                             <template x-for="st in statuses" :key="st.id">
                                 <button @click="changeStatus(st.id)"
                                         class="px-3 py-1.5 rounded-xl text-xs font-black border transition-all active:scale-95"
                                         :class="selectedSale?.order_status_id == st.id ? (st.color + ' border-transparent shadow-xs scale-105') : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-100'">
                                     <span x-text="st.name"></span>
                                 </button>
                             </template>
                        </div>
                    </div>
                </div>

                <!-- Items Table -->
                <div>
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider mb-2">Productos Comprados</h3>
                    <div class="border border-slate-200/70 rounded-2xl overflow-hidden">
                        <table class="w-full text-left text-xs sm:text-sm">
                            <thead class="bg-slate-50 text-slate-500 text-[10px] font-black uppercase border-b border-slate-100">
                                <tr>
                                    <th class="p-3">Ítem</th>
                                    <th class="p-3 text-center">Cant</th>
                                    <th class="p-3 text-right">Total ($)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="item in items" :key="item.id">
                                    <tr>
                                        <td class="p-3 font-bold text-slate-800" x-text="item.item_name || 'Ítem Manual'"></td>
                                        <td class="p-3 text-center font-bold text-slate-500" x-text="parseFloat(item.quantity) + ' ' + (item.unit || '')"></td>
                                        <td class="p-3 text-right font-black text-emerald-700" x-text="'$' + parseFloat(item.subtotal).toFixed(2)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Financials -->
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/70 space-y-2">
                    <div class="flex justify-between items-center text-xs">
                        <span class="font-bold text-slate-500">Total Dólares</span>
                        <span class="font-black text-slate-900 text-sm" x-text="'$' + parseFloat(selectedSale?.amount_usd || 0).toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="font-bold text-slate-500">Total Bolívares</span>
                        <span class="font-bold text-slate-700" x-text="'Bs. ' + parseFloat(selectedSale?.amount || 0).toLocaleString('es-VE', {minimumFractionDigits: 2})"></span>
                    </div>
                    <div class="h-px bg-slate-200 my-1"></div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="font-bold text-slate-500">Total Pagado</span>
                        <span class="font-black text-emerald-700 text-sm" x-text="'$' + parseFloat(selectedSale?.paid_amount_usd || 0).toFixed(2)"></span>
                    </div>
                </div>

                <!-- Payments History -->
                <div x-show="payments.length > 0">
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider mb-2">Historial de Pagos</h3>
                    <div class="space-y-2">
                        <template x-for="pay in payments" :key="pay.id">
                            <div class="flex justify-between items-center text-xs bg-slate-50 p-2.5 rounded-xl border border-slate-200/70">
                                <span class="text-slate-500 font-bold" x-text="formatDate(pay.date)"></span>
                                <span class="font-black text-emerald-700" x-text="'$' + parseFloat(pay.amount_usd).toFixed(2)"></span>
                            </div>
                        </template>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        function historyApp() {
            return {
                sales: <?= json_encode($sales ?? []) ?>,
                statuses: <?= json_encode($statuses ?? []) ?>,
                filters: {
                    search: '',
                    status: ''
                },
                
                showModal: false,
                selectedSale: null,
                items: [],
                payments: [],

                get filteredSales() {
                    return this.sales.filter(s => {
                        const q = this.filters.search.toLowerCase();
                        const matchSearch = !q || 
                            (s.customer && s.customer.toLowerCase().includes(q)) || 
                            (s.id && s.id.toString().includes(q)) ||
                            (s.product && s.product.toLowerCase().includes(q));
                            
                        const matchStatus = !this.filters.status || s.order_status_id == this.filters.status;
                        return matchSearch && matchStatus;
                    });
                },

                formatDateShort(dateStr) {
                     if(!dateStr) return '';
                     const d = new Date(dateStr);
                     return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' });
                },

                async openSale(id) {
                    this.selectedSale = null;
                    this.items = [];
                    this.payments = [];
                    this.showModal = true;
                    
                    const localSale = this.sales.find(s => s.id == id);
                    if(localSale) {
                         this.selectedSale = {...localSale};
                    }
                    
                    try {
                        let res = await fetch('<?= base_url('sales/get-details/') ?>' + id);
                        let data = await res.json();
                        
                        if(data.status === 'success') {
                            this.selectedSale = data.sale;
                            this.items = data.items || [];
                            this.payments = data.payments || [];
                        }
                    } catch(e) {
                        console.error(e);
                    }
                },
                
                async changeStatus(newStatusId) {
                    if(!this.selectedSale) return;
                    
                    try {
                        let res = await fetch('<?= base_url('sales/update-status') ?>', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                sale_id: this.selectedSale.id,
                                status_id: newStatusId
                            })
                        });
                        let data = await res.json();
                        
                        if(data.status === 'success') {
                            this.selectedSale.order_status_id = newStatusId;
                            
                            const idx = this.sales.findIndex(s => s.id == this.selectedSale.id);
                            if(idx !== -1) {
                                const statusObj = this.statuses.find(st => st.id == newStatusId);
                                if(statusObj) {
                                    this.sales[idx].order_status_id = newStatusId;
                                    this.sales[idx].status_name = statusObj.name;
                                    this.sales[idx].status_color = statusObj.color;
                                }
                            }
                        } else {
                            alert('Error al actualizar estado');
                        }
                    } catch(e) {
                         alert('Error de conexión');
                    }
                },

                formatDate(dateStr) {
                    if(!dateStr) return '';
                    const date = new Date(dateStr);
                    return date.toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' });
                }
            }
        }
    </script>
</body>
</html>
