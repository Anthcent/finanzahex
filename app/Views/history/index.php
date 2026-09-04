<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Historial Financiero - Fi-Hex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <meta name="theme-color" content="#047857">
    <style>
        body { font-family: 'Plus Jakarta Sans', 'Outfit', sans-serif; }
        [x-cloak] { display: none !important; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .customize-scrollbar::-webkit-scrollbar { width: 5px; }
        .customize-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .customize-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
        .safe-bottom { padding-bottom: env(safe-area-inset-bottom, 1rem); }
    </style>
</head>
<body class="bg-gradient-to-br from-emerald-50/60 via-slate-50 to-teal-50/40 h-screen flex flex-col text-slate-800 antialiased" x-data="historyApp()">

    <!-- Sticky Executive Top Header -->
    <header class="flex-none bg-white/90 backdrop-blur-xl border-b border-slate-200/80 z-30 sticky top-0 shadow-xs">
        <div class="max-w-5xl mx-auto px-4 h-16 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5 min-w-0">
                <a href="<?= base_url() ?>" class="w-9 h-9 flex items-center justify-center rounded-2xl bg-slate-100/80 hover:bg-emerald-50 text-slate-600 hover:text-emerald-700 transition-colors border border-slate-200/60 active:scale-95 shrink-0" title="Volver al inicio">
                    <span class="material-icons text-xl">arrow_back</span>
                </a>
                <div class="w-9 h-9 rounded-2xl bg-gradient-to-br from-emerald-600 to-teal-700 text-white flex items-center justify-center shadow-md shadow-emerald-950/20 ring-1 ring-emerald-400/40 shrink-0">
                    <span class="material-icons text-lg">receipt_long</span>
                </div>
                <div class="leading-tight min-w-0">
                    <h1 class="font-black text-slate-900 tracking-tight text-sm sm:text-base truncate">
                        Historial <span class="bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent">Financiero</span>
                    </h1>
                    <p class="text-[9px] font-bold text-slate-400 hidden sm:block">Movimientos, ingresos y gastos</p>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <button @click="showFilters = !showFilters" 
                        class="flex items-center gap-1.5 px-3.5 py-2 rounded-2xl transition-all font-black text-xs shadow-2xs active:scale-95"
                        :class="showFilters ? 'bg-gradient-to-r from-emerald-600 to-teal-700 text-white shadow-md shadow-emerald-950/20' : 'bg-white border border-slate-200/80 text-slate-700 hover:bg-slate-50'">
                    <span class="material-icons text-base">filter_list</span>
                    <span>Filtros</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Collapsible Filters Section -->
    <div x-show="showFilters" x-collapse x-cloak class="bg-white/95 backdrop-blur-xl border-b border-slate-200/80 shadow-sm z-20">
        <div class="max-w-5xl mx-auto px-4 py-4 space-y-3.5">
            
            <!-- Search & Reset Bar -->
            <div class="flex flex-col sm:flex-row gap-2.5">
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <span class="material-icons text-slate-400 text-base">search</span>
                    </span>
                    <input type="text" x-model.debounce.400ms="filters.search" @input="fetchRecords()" 
                           placeholder="Buscar por descripción, monto, beneficiario..." 
                           class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200/90 rounded-2xl text-xs sm:text-sm font-bold text-slate-800 focus:bg-white focus:border-emerald-500 outline-none transition shadow-2xs">
                </div>
                <button @click="resetFilters()" class="self-end sm:self-auto px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-2xl text-xs font-bold transition flex items-center gap-1.5 active:scale-95">
                    <span class="material-icons text-sm">restart_alt</span>
                    <span>Limpiar</span>
                </button>
            </div>

            <!-- Type Toggles (Executive Fintech Pills) -->
            <div class="flex overflow-x-auto gap-2 pb-1 no-scrollbar justify-start sm:justify-center">
                <button @click="toggleType('')" :class="!filters.type ? 'bg-slate-900 text-white shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-4 py-1.5 rounded-full text-xs font-black whitespace-nowrap transition-all">Todos</button>
                <button @click="toggleType('income')" :class="filters.type === 'income' ? 'bg-emerald-600 text-white shadow-md' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200/60'" class="px-4 py-1.5 rounded-full text-xs font-black whitespace-nowrap transition-all">Ingresos</button>
                <button @click="toggleType('expense')" :class="filters.type === 'expense' ? 'bg-rose-600 text-white shadow-md' : 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200/60'" class="px-4 py-1.5 rounded-full text-xs font-black whitespace-nowrap transition-all">Gastos</button>
                <button @click="toggleType('savings')" :class="filters.type === 'savings' ? 'bg-blue-600 text-white shadow-md' : 'bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200/60'" class="px-4 py-1.5 rounded-full text-xs font-black whitespace-nowrap transition-all">Ahorros</button>
            </div>

            <!-- Advanced Filters Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2.5">
                 <select x-model="filters.owner" @change="fetchRecords()" class="w-full p-2.5 bg-slate-50 border border-slate-200/90 rounded-2xl text-xs font-bold text-slate-700 outline-none focus:border-emerald-500">
                    <option value="">Todos los Dueños</option>
                    <?php foreach ($owners as $owner): ?>
                        <option value="<?= $owner ?>"><?= $owner ?></option>
                    <?php endforeach; ?>
                 </select>
                 <select x-model="filters.category_id" @change="fetchRecords()" class="w-full p-2.5 bg-slate-50 border border-slate-200/90 rounded-2xl text-xs font-bold text-slate-700 outline-none focus:border-emerald-500">
                    <option value="">Todas las Categorías</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= $cat['name'] ?></option>
                    <?php endforeach; ?>
                 </select>
                 <input type="date" x-model="filters.date_start" @change="fetchRecords()" class="w-full p-2.5 bg-slate-50 border border-slate-200/90 rounded-2xl text-xs font-bold text-slate-700 outline-none focus:border-emerald-500">
                 <input type="date" x-model="filters.date_end" @change="fetchRecords()" class="w-full p-2.5 bg-slate-50 border border-slate-200/90 rounded-2xl text-xs font-bold text-slate-700 outline-none focus:border-emerald-500">
            </div>
        </div>
    </div>

    <!-- Content Area -->
    <main class="flex-1 overflow-y-auto px-4 py-6 customize-scrollbar" id="scroll-container">
        <div class="max-w-5xl mx-auto pb-24">
            
            <!-- Loading State -->
            <div x-show="loading" class="flex flex-col items-center justify-center py-20 text-slate-400">
                <span class="material-icons animate-spin text-3xl mb-2 text-emerald-600">sync</span>
                <span class="text-xs font-bold">Cargando movimientos...</span>
            </div>

            <!-- Empty State -->
            <div x-show="!loading && records.length === 0" class="flex flex-col items-center justify-center py-20 text-center" x-cloak>
                 <div class="w-16 h-16 rounded-3xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-3 shadow-2xs">
                     <span class="material-icons text-3xl">receipt_long</span>
                 </div>
                 <h3 class="text-base font-black text-slate-800">No hay movimientos</h3>
                 <p class="text-slate-400 text-xs max-w-xs mx-auto mt-1">Intenta ajustar los filtros para encontrar lo que buscas.</p>
                 <button @click="resetFilters()" class="mt-4 px-4 py-2 text-emerald-700 bg-emerald-50 hover:bg-emerald-100 text-xs font-bold rounded-xl transition">Limpiar filtros</button>
            </div>

            <!-- Transactions Grid -->
            <div x-show="!loading && records.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-3.5">
                <template x-for="item in records" :key="item.id">
                    <div class="group bg-white/95 backdrop-blur-md rounded-2xl shadow-2xs hover:shadow-md transition-all duration-200 border border-slate-200/80 overflow-hidden relative">
                        
                        <!-- Left Accent Stripe (Standardized Colors) -->
                        <div class="absolute top-0 bottom-0 left-0 w-1.5"
                             :class="{
                                'bg-rose-500': item.type === 'expense',
                                'bg-emerald-500': item.type === 'income',
                                'bg-blue-600': item.type === 'savings',
                                'bg-slate-400': !['expense','income','savings'].includes(item.type)
                             }"></div>
                        
                        <div class="p-4 pl-5">
                            <!-- Header: Category & Date -->
                            <div class="flex justify-between items-start mb-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-black bg-slate-100 text-slate-700 tracking-wider uppercase" x-text="item.category_name || 'General'"></span>
                                <span class="text-[11px] text-slate-400 font-bold" x-text="formatDate(item.created_at)"></span>
                            </div>

                            <!-- Main Content: Description & Amount -->
                            <div class="flex justify-between items-baseline mb-2 gap-2">
                                <h3 class="text-slate-900 font-black text-sm leading-snug flex-1 line-clamp-2" x-text="item.description || 'Sin descripción'"></h3>
                                <div class="text-right shrink-0">
                                    <span class="block text-base sm:text-lg font-black tracking-tight"
                                          :class="{
                                              'text-rose-600': item.type === 'expense',
                                              'text-emerald-700': item.type === 'income',
                                              'text-blue-600': item.type === 'savings'
                                          }"
                                          x-text="(item.type === 'expense' ? '-' : '+') + formatMoney(item.amount)"></span>
                                    <span x-show="item.amount_usd > 0" class="block text-[11px] text-slate-400 font-bold mt-0.5" x-text="formatUsd(item.amount_usd)"></span>
                                </div>
                            </div>

                            <!-- Footer: Account & Owner -->
                            <div class="flex items-center justify-between text-[11px] text-slate-500 pt-2.5 border-t border-slate-100 font-bold">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center gap-1">
                                        <span class="material-icons text-[13px] text-slate-400">account_balance_wallet</span>
                                        <span x-text="item.account_name"></span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <span class="material-icons text-[13px] text-slate-400">person</span>
                                        <span x-text="item.owner"></span>
                                    </div>
                                </div>
                                <!-- Actions -->
                                <div class="flex items-center gap-1">
                                    <button @click="editItem(item)" class="w-7 h-7 rounded-lg text-slate-400 hover:text-emerald-700 hover:bg-emerald-50 flex items-center justify-center transition-colors" title="Editar">
                                        <span class="material-icons text-sm">edit</span>
                                    </button>
                                    <button @click="deleteItem(item.id)" class="w-7 h-7 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 flex items-center justify-center transition-colors" title="Eliminar">
                                        <span class="material-icons text-sm">delete</span>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Editing UI Overlay -->
                            <div x-show="editingId === item.id" class="absolute inset-0 bg-white/95 backdrop-blur-md z-10 p-4 flex flex-col justify-between" x-transition>
                                <div class="space-y-2.5">
                                    <div class="flex gap-2">
                                        <div class="flex-1">
                                            <label class="text-[9px] uppercase font-black text-slate-400 tracking-wider">Monto</label>
                                            <input type="number" step="0.01" x-model="editForm.amount" :disabled="editForm.isComplex" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs font-black text-slate-900 focus:border-emerald-500 outline-none">
                                        </div>
                                        <div class="flex-1">
                                            <label class="text-[9px] uppercase font-black text-slate-400 tracking-wider">Fecha</label>
                                            <input type="datetime-local" x-model="editForm.created_at" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs font-bold text-slate-700 focus:border-emerald-500 outline-none">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-[9px] uppercase font-black text-slate-400 tracking-wider">Categoría</label>
                                        <select x-model="editForm.category_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs font-bold text-slate-700 focus:border-emerald-500 outline-none">
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?= $cat['id'] ?>"><?= $cat['name'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-[9px] uppercase font-black text-slate-400 tracking-wider">Descripción</label>
                                        <input type="text" x-model="editForm.description" @keydown.enter="saveEdit()" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs font-bold text-slate-800 focus:border-emerald-500 outline-none">
                                    </div>
                                </div>
                                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                                    <button @click="cancelEdit()" class="px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Cancelar</button>
                                    <button @click="saveEdit()" class="px-3.5 py-1.5 text-xs font-black text-white bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 rounded-xl shadow-xs">Guardar</button>
                                </div>
                            </div>

                        </div>
                    </div>
                </template>
            </div>
        </div>
    </main>

    <script>
        function historyApp() {
            return {
                showFilters: false,
                loading: false,
                records: [],
                expandedItems: {},
                itemsCache: {},
                editingId: null,
                editForm: { id: null, amount: 0, description: '', created_at: '', category_id: '', isComplex: false },
                filters: {
                    date_start: '',
                    date_end: '',
                    type: '',
                    owner: '',
                    category_id: '',
                    search: ''
                },

                init() {
                    this.fetchRecords();
                },
                
                resetFilters() {
                    this.filters = { date_start: '', date_end: '', type: '', owner: '', category_id: '', search: '' };
                    this.fetchRecords();
                },

                toggleType(t) {
                    this.filters.type = this.filters.type === t ? '' : t;
                    this.fetchRecords();
                },

                formatMoney(value) {
                    return new Intl.NumberFormat('es-VE', { style: 'currency', currency: 'VES' }).format(value);
                },
                formatUsd(value) {
                    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
                },
                formatDate(dateStr) {
                    if(!dateStr) return '';
                    const d = new Date(dateStr);
                    if(isNaN(d.getTime())) return dateStr;
                    return d.toLocaleDateString('es-VE', {day:'2-digit', month:'2-digit', year:'2-digit'}) + ' ' + d.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                },

                async fetchRecords() {
                    this.loading = true;
                    try {
                        const res = await fetch('<?= base_url('history/fetch') ?>', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify(this.filters)
                        });
                        const data = await res.json();
                        if(data.status === 'success') {
                            this.records = data.data;
                        }
                    } catch(e) { console.error(e); } finally { this.loading = false; }
                },
                
                async deleteItem(id) {
                    if(!confirm('¿Eliminar registro? Esta acción revertirá el saldo.')) return;
                    try {
                         const res = await fetch('<?= base_url('history/delete/') ?>' + id);
                         const data = await res.json();
                         if(data.status === 'success') {
                             this.fetchRecords();
                         } else {
                             alert(data.message || 'Error al eliminar');
                         }
                    } catch(e) { console.error(e); }
                },

                editItem(item) {
                     this.editingId = item.id;
                     let editDate = '';
                     if (item.created_at && item.created_at !== '0000-00-00 00:00:00') {
                         const d = new Date(item.created_at);
                         if (!isNaN(d.getTime()) && d.getFullYear() > 2000) {
                             editDate = item.created_at.replace(' ', 'T').substring(0, 16);
                         }
                     }
                     if (!editDate) {
                         const now = new Date();
                         // Adjust for timezone offset if needed or use local ISO
                         const offsetMs = now.getTimezoneOffset() * 60 * 1000; 
                         const msLocal = now.getTime() - offsetMs;
                         editDate = new Date(msLocal).toISOString().slice(0, 16);
                     }
                     this.editForm = {
                         id: item.id,
                         amount: item.amount,
                         description: item.description,
                         created_at: editDate,
                         category_id: item.category_id,
                         isComplex: ['exchange_out', 'exchange_in', 'transfer_out', 'transfer_in'].includes(item.type)
                     };
                },
                
                cancelEdit() {
                    this.editingId = null;
                    this.editForm = { id: null, amount: 0, description: '', created_at: '', category_id: '', isComplex: false };
                },

                async saveEdit() {
                    try {
                        const payload = { ...this.editForm };
                        if (payload.created_at) {
                            payload.created_at = payload.created_at.replace('T', ' ');
                            if (payload.created_at.length === 16) payload.created_at += ':00';
                        }
                        const res = await fetch('<?= base_url('transaction/update/') ?>' + this.editForm.id, {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify(payload)
                        });
                        const data = await res.json();
                        if(data.status === 'success') {
                            this.editingId = null;
                            this.fetchRecords();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    } catch(e) { console.error(e); }
                }
            }
        }
    </script>
</body>
</html>
