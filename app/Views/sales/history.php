<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Ventas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen" x-data="historyApp()">

    <!-- Header -->
    <div class="bg-white/90 backdrop-blur-md border-b border-slate-100 sticky top-0 z-40">
        <div class="max-w-md mx-auto flex items-center justify-between p-4">
            <a href="<?= base_url('sales') ?>" class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-600 hover:bg-slate-100 transition-colors">
                <span class="material-icons">arrow_back</span>
            </a>
            <h1 class="text-lg font-bold text-slate-800">Historial</h1>
            <div class="w-10"></div> 
        </div>
    </div>

    <div class="max-w-md mx-auto p-4 pb-20">
        
        <div class="space-y-4">
        
            <!-- Filters -->
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-50 flex flex-col gap-3">
                <div class="relative">
                    <span class="material-icons absolute left-3 top-2.5 text-slate-400 text-sm">search</span>
                    <input type="text" x-model="filters.search" placeholder="Buscar cliente, referencia o ID..." 
                           class="w-full pl-9 pr-4 py-2 bg-slate-50 border-none rounded-xl text-sm font-bold text-slate-700 outline-none focus:ring-2 ring-indigo-100 transition-all">
                </div>
                
                <div class="flex gap-2 text-xs overflow-x-auto pb-1 no-scrollbar">
                    <button @click="filters.status = ''" 
                            class="px-3 py-1.5 rounded-lg font-bold whitespace-nowrap transition-colors border"
                            :class="filters.status === '' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-500 border-slate-200'">
                        Todos
                    </button>
                    <template x-for="st in statuses" :key="st.id">
                        <button @click="filters.status = st.id" 
                                class="px-3 py-1.5 rounded-lg font-bold whitespace-nowrap transition-colors border"
                                :class="filters.status == st.id ? (st.color + ' border-transparent ring-2 ring-indigo-100') : 'bg-white text-slate-500 border-slate-200'">
                            <span x-text="st.name"></span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- List -->
            <div class="bg-white rounded-[1.5rem] shadow-xl shadow-slate-200/50 overflow-hidden border border-slate-50">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-slate-400 text-[10px] uppercase tracking-widest border-b border-slate-100">
                                <th class="p-4 font-bold">Fecha</th>
                                <th class="p-4 font-bold">Detalle</th>
                                <th class="p-4 font-bold text-right">Monto</th>
                                <th class="p-4 font-bold text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <!-- Empty State -->
                            <tr x-show="filteredSales.length === 0">
                                <td colspan="4" class="p-8 text-center text-slate-400 text-sm">
                                    No se encontraron ventas
                                </td>
                            </tr>

                            <template x-for="sale in filteredSales" :key="sale.id">
                                <tr class="hover:bg-indigo-50/30 transition-colors group cursor-pointer" @click="openSale(sale.id)">
                                    <td class="p-4">
                                        <div class="flex flex-col">
                                            <span class="text-xs font-bold text-slate-700" x-text="formatDateShort(sale.date)"></span>
                                            <span class="text-[10px] text-slate-400" x-text="'#' + sale.id"></span>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <p class="text-sm font-bold text-slate-800 group-hover:text-indigo-900 transition-colors" x-text="sale.customer"></p>
                                        <p class="text-xs text-slate-500 truncate w-32" x-text="sale.product"></p>
                                    </td>
                                    <td class="p-4 text-right">
                                        <p class="text-sm font-black text-slate-800" x-text="'$' + parseFloat(sale.amount_usd).toFixed(2)"></p>
                                        <p class="text-[10px] text-slate-400 font-medium" x-text="'Bs ' + parseFloat(sale.amount).toFixed(2)"></p>
                                    </td>
                                    <td class="p-4 text-center">
                                        <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider block w-max mx-auto"
                                              :class="sale.status_color || 'bg-slate-100 text-slate-500'">
                                            <span x-text="sale.status_name || 'Pendiente'"></span>
                                        </span>
                                        
                                        <div x-show="sale.status == 'paid'" class="flex justify-center items-center mt-1">
                                            <span class="material-icons text-[10px] text-emerald-500 mr-0.5">paid</span> 
                                            <span class="text-[9px] text-emerald-500 font-bold">Pagado</span>
                                        </div>
                                         <div x-show="sale.status == 'partial'" class="flex justify-center items-center mt-1">
                                            <span class="material-icons text-[10px] text-amber-500 mr-0.5">pending</span> 
                                            <span class="text-[9px] text-amber-500 font-bold">Parcial</span>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
    <!-- DETAILS MODAL -->
    <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center px-4" x-cloak>
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showModal = false"></div>
        
        <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl relative z-10 transform transition-all scale-100 flex flex-col max-h-[90vh]">
            
            <!-- Modal Header -->
            <div class="p-6 border-b border-slate-100 flex justify-between items-start shrink-0">
                <div>
                    <h2 class="text-xl font-bold text-slate-800">Detalle de Venta</h2>
                    <p class="text-xs text-slate-500 font-medium mt-1">
                        #<span x-text="selectedSale?.id"></span> &bull; 
                        <span x-text="formatDate(selectedSale?.date)"></span>
                    </p>
                </div>
                <button @click="showModal = false" class="w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 hover:bg-slate-100 transition-colors">
                    <span class="material-icons text-sm">close</span>
                </button>
            </div>

            <!-- Modal Content -->
            <div class="overflow-y-auto p-6 space-y-6">
                
                <!-- Status & Customer -->
                <div class="flex flex-col gap-4 bg-slate-50 p-4 rounded-xl">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Cliente</p>
                            <p class="font-bold text-slate-700" x-text="selectedSale?.customer"></p>
                        </div>
                        <div class="px-3 py-1 rounded-full text-xs font-bold capitalize"
                             :class="selectedSale?.status === 'paid' ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600'">
                            <span x-text="selectedSale?.status === 'paid' ? 'Pagado' : 'Pendiente Pago'"></span>
                        </div>
                    </div>
                    
                    <!-- Order Status Selector -->
                    <div>
                        <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider mb-2">Estado del Pedido</p>
                        <div class="flex flex-wrap gap-2">
                             <template x-for="st in statuses" :key="st.id">
                                 <button @click="changeStatus(st.id)"
                                         class="px-3 py-1.5 rounded-lg text-xs font-bold border transition-all"
                                         :class="selectedSale?.order_status_id == st.id ? (st.color + ' border-transparent ring-2 ring-offset-1 ring-slate-200 shadow-sm') : 'bg-white text-slate-500 border-slate-200 hover:border-slate-300'">
                                     <span x-text="st.name"></span>
                                 </button>
                             </template>
                        </div>
                    </div>
                </div>

                <!-- Items Table -->
                <div>
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Productos</h3>
                    <div class="border border-slate-100 rounded-xl overflow-hidden">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-slate-500 text-[10px] font-bold uppercase">
                                <tr>
                                    <th class="p-3">Item</th>
                                    <th class="p-3 text-center">Cant</th>
                                    <th class="p-3 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <template x-for="item in items" :key="item.id">
                                    <tr>
                                        <td class="p-3 font-medium text-slate-700" x-text="item.item_name || 'Item Manual'"></td>
                                        <td class="p-3 text-center text-slate-500" x-text="parseFloat(item.quantity) + ' ' + (item.unit || '')"></td>
                                        <td class="p-3 text-right font-bold text-slate-700" x-text="'$' + parseFloat(item.subtotal).toFixed(2)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Financials -->
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-slate-500">Total USD</span>
                        <span class="text-lg font-black text-slate-800" x-text="'$' + parseFloat(selectedSale?.amount_usd || 0).toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-slate-500">Total BS</span>
                        <span class="text-sm font-bold text-slate-600" x-text="'Bs ' + parseFloat(selectedSale?.amount || 0).toFixed(2)"></span>
                    </div>
                    <div class="h-px bg-slate-100 my-2"></div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-slate-500">Pagado</span>
                        <span class="text-sm font-bold text-emerald-600" x-text="'$' + parseFloat(selectedSale?.paid_amount_usd || 0).toFixed(2)"></span>
                    </div>
                </div>

                <!-- Payments History -->
                <div x-show="payments.length > 0">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Historial de Pagos</h3>
                    <div class="space-y-2">
                        <template x-for="pay in payments" :key="pay.id">
                            <div class="flex justify-between items-center text-xs bg-slate-50 p-2 rounded-lg border border-slate-100">
                                <span class="text-slate-500" x-text="formatDate(pay.date)"></span>
                                <span class="font-bold text-slate-700" x-text="'$' + parseFloat(pay.amount_usd).toFixed(2)"></span>
                            </div>
                        </template>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
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
                        // 1. Search
                        const q = this.filters.search.toLowerCase();
                        const matchSearch = !q || 
                            (s.customer && s.customer.toLowerCase().includes(q)) || 
                            (s.id && s.id.toString().includes(q)) ||
                            (s.product && s.product.toLowerCase().includes(q));
                            
                        // 2. Status
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
                    
                    // Optimistic Load from local list first for speed
                    const localSale = this.sales.find(s => s.id == id);
                    if(localSale) {
                         this.selectedSale = {...localSale}; // Clone
                    }
                    
                    try {
                        let res = await fetch('<?= base_url('sales/get-details/') ?>' + id);
                        let data = await res.json();
                        
                        if(data.status === 'success') {
                            this.selectedSale = data.sale; // Full details
                            this.items = data.items;
                            this.payments = data.payments;
                        } else {
                            // If fail, we still have local data, maybe show subtle error
                        }
                    } catch(e) {
                        console.error(e);
                        // Network error, keep showing local data
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
                            // Update UI
                            this.selectedSale.order_status_id = newStatusId;
                            
                            // Also update local list!
                            const idx = this.sales.findIndex(s => s.id == this.selectedSale.id);
                            if(idx !== -1) {
                                // Find status object to get name/color
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
