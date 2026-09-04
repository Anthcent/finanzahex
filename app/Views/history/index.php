<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Historial - FinazaPersonal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        [x-cloak] { display: none !important; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-gray-50 h-screen flex flex-col text-gray-800 font-sans" x-data="historyApp()">

    <!-- Sticky Header -->
    <div class="flex-none bg-white shadow-sm z-30 sticky top-0">
        <div class="max-w-5xl mx-auto px-4 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <a href="<?= base_url() ?>" class="p-2 rounded-full hover:bg-gray-100 transition text-gray-600">
                    <span class="material-icons">arrow_back</span>
                </a>
                <h1 class="text-xl font-bold text-gray-900 tracking-tight">Historial</h1>
            </div>
            <div class="flex items-center space-x-2">
                <button @click="showFilters = !showFilters" 
                        class="flex items-center space-x-1 px-4 py-2 rounded-full transition"
                        :class="showFilters ? 'bg-emerald-50 text-emerald-700 ring-2 ring-emerald-100' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'">
                    <span class="material-icons text-xl">filter_list</span>
                    <span class="text-sm font-medium hidden sm:inline">Filtros</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Collapsible Filters Section -->
    <div x-show="showFilters" x-collapse x-cloak class="bg-white border-b border-gray-100 shadow-sm z-20">
        <div class="max-w-5xl mx-auto px-4 py-4 space-y-4">
            
            <!-- Top Row: Search & Reset -->
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="material-icons text-gray-400 text-lg">search</span>
                    </span>
                    <input type="text" x-model.debounce.500ms="filters.search" @input="fetchRecords()" 
                           placeholder="Buscar por descripción, monto..." 
                           class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition">
                </div>
                <button @click="resetFilters()" class="self-end sm:self-auto px-4 py-2 bg-white border border-gray-200 text-gray-600 rounded-lg text-sm font-medium hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-200 transition flex items-center shadow-sm">
                    <span class="material-icons text-sm mr-1">restart_alt</span> Limpiar
                </button>
            </div>

            <!-- Type Toggles -->
            <div class="flex overflow-x-auto gap-2 pb-1 no-scrollbar justify-start sm:justify-center">
                <button @click="toggleType('')" :class="!filters.type ? 'bg-gray-800 text-white shadow-md' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'" class="px-4 py-2 rounded-full text-sm font-semibold whitespace-nowrap transition-all">Todos</button>
                <button @click="toggleType('income')" :class="filters.type === 'income' ? 'bg-emerald-600 text-white shadow-md' : 'bg-white border border-gray-200 text-gray-600 hover:bg-emerald-50 hover:text-emerald-700'" class="px-4 py-2 rounded-full text-sm font-semibold whitespace-nowrap transition-all">Ingresos</button>
                <button @click="toggleType('expense')" :class="filters.type === 'expense' ? 'bg-red-600 text-white shadow-md' : 'bg-white border border-gray-200 text-gray-600 hover:bg-red-50 hover:text-red-700'" class="px-4 py-2 rounded-full text-sm font-semibold whitespace-nowrap transition-all">Gastos</button>
                <button @click="toggleType('savings')" :class="filters.type === 'savings' ? 'bg-teal-600 text-white shadow-md' : 'bg-white border border-gray-200 text-gray-600 hover:bg-teal-50 hover:text-teal-700'" class="px-4 py-2 rounded-full text-sm font-semibold whitespace-nowrap transition-all">Ahorros</button>
            </div>

            <!-- Advanced Filters Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                 <select x-model="filters.owner" @change="fetchRecords()" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                    <option value="">Todos los Dueños</option>
                    <?php foreach ($owners as $owner): ?>
                        <option value="<?= $owner ?>"><?= $owner ?></option>
                    <?php endforeach; ?>
                 </select>
                 <select x-model="filters.category_id" @change="fetchRecords()" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                    <option value="">Todas las Categorías</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= $cat['name'] ?></option>
                    <?php endforeach; ?>
                 </select>
                 <input type="date" x-model="filters.date_start" @change="fetchRecords()" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                 <input type="date" x-model="filters.date_end" @change="fetchRecords()" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
            </div>
        </div>
    </div>

    <!-- Content Area -->
    <div class="flex-1 overflow-y-auto px-4 py-6" id="scroll-container">
        <div class="max-w-5xl mx-auto">
            
            <!-- Loading State -->
            <div x-show="loading" class="flex flex-col items-center justify-center py-20 text-gray-400">
                <span class="material-icons animate-spin text-3xl mb-2 text-emerald-600">sync</span>
                <span class="text-sm">Cargando movimientos...</span>
            </div>

            <!-- Empty State -->
            <div x-show="!loading && records.length === 0" class="flex flex-col items-center justify-center py-20 text-center" x-cloak>
                 <div class="bg-emerald-50 p-4 rounded-full mb-4">
                     <span class="material-icons text-emerald-400 text-4xl">receipt_long</span>
                 </div>
                 <h3 class="text-lg font-semibold text-gray-700">No hay movimientos</h3>
                 <p class="text-gray-500 text-sm max-w-xs mx-auto">Intenta ajustar los filtros para encontrar lo que buscas.</p>
                 <button @click="resetFilters()" class="mt-4 px-4 py-2 md:py-1.5 text-emerald-700 font-medium hover:bg-emerald-50 rounded-lg transition">Limpiar filtros</button>
            </div>

            <!-- Transactions Grid -->
            <div x-show="!loading && records.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4">
                <template x-for="item in records" :key="item.id">
                    <div class="group bg-white rounded-xl shadow-[0_2px_8px_rgba(0,0,0,0.04)] hover:shadow-[0_4px_16px_rgba(0,0,0,0.08)] transition-all duration-200 border border-gray-100 overflow-hidden relative">
                        
                        <!-- Left Color Stripe -->
                        <div class="absolute top-0 bottom-0 left-0 w-1.5"
                             :class="{
                                'bg-red-500': item.type === 'expense',
                                'bg-green-500': item.type === 'income',
                                'bg-teal-500': item.type === 'savings',
                                'bg-gray-400': !['expense','income','savings'].includes(item.type)
                             }"></div>
                        
                        <div class="p-4 pl-5">
                            <!-- Header: Category & Date -->
                            <div class="flex justify-between items-start mb-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-600 tracking-wide uppercase" x-text="item.category_name || 'General'"></span>
                                <span class="text-xs text-gray-400 font-medium" x-text="formatDate(item.created_at)"></span>
                            </div>

                            <!-- Main Content: Description & Amount -->
                            <div class="flex justify-between items-baseline mb-1">
                                <h3 class="text-gray-900 font-bold text-sm leading-snug mr-3 flex-1 line-clamp-2" x-text="item.description || 'Sin descripción'"></h3>
                                <div class="text-right whitespace-nowrap">
                                    <span class="block text-lg font-bold tracking-tight"
                                          :class="{
                                              'text-red-600': item.type === 'expense',
                                              'text-green-600': item.type === 'income',
                                              'text-teal-600': item.type === 'savings'
                                          }"
                                          x-text="(item.type === 'expense' ? '-' : '+') + formatMoney(item.amount)"></span>
                                    <span x-show="item.amount_usd > 0" class="block text-xs text-gray-500 font-medium mt-0.5" x-text="formatUsd(item.amount_usd)"></span>
                                </div>
                            </div>

                            <!-- Footer: Account & Owner -->
                            <div class="flex items-center justify-between text-xs text-gray-500 mt-3 pt-3 border-t border-gray-50">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center gap-1">
                                        <span class="material-icons text-[14px] text-gray-300">account_balance_wallet</span>
                                        <span x-text="item.account_name"></span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <span class="material-icons text-[14px] text-gray-300">person</span>
                                        <span x-text="item.owner"></span>
                                    </div>
                                </div>
                                <!-- Actions -->
                                <div class="flex items-center gap-1 transition-opacity">
                                    <button @click="editItem(item)" class="p-1.5 rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 transition" title="Editar">
                                        <span class="material-icons text-base">edit</span>
                                    </button>
                                    <button @click="deleteItem(item.id)" class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition" title="Eliminar">
                                        <span class="material-icons text-base">delete</span>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Editing UI Overlay (Replaces card content when editing) -->
                            <div x-show="editingId === item.id" class="absolute inset-0 bg-white z-10 p-3 flex flex-col justify-between" x-transition>
                                <div class="space-y-3">
                                    <div class="flex gap-2">
                                        <div class="flex-1">
                                            <label class="text-[10px] uppercase font-bold text-gray-400">Monto</label>
                                            <input type="number" x-model="editForm.amount" :disabled="editForm.isComplex" class="w-full border-b border-emerald-300 py-1 text-sm font-bold text-gray-900 focus:border-emerald-600 outline-none">
                                        </div>
                                        <div class="flex-1">
                                            <label class="text-[10px] uppercase font-bold text-gray-400">Fecha</label>
                                            <input type="datetime-local" x-model="editForm.created_at" class="w-full border-b border-gray-300 py-1 text-xs text-gray-700 focus:border-emerald-600 outline-none">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-[10px] uppercase font-bold text-gray-400">Categoría</label>
                                        <select x-model="editForm.category_id" class="w-full border-b border-gray-300 py-1 text-sm text-gray-700 bg-transparent focus:border-emerald-600 outline-none">
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?= $cat['id'] ?>"><?= $cat['name'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-[10px] uppercase font-bold text-gray-400">Descripción</label>
                                        <input type="text" x-model="editForm.description" @keydown.enter="saveEdit()" class="w-full border-b border-gray-300 py-1 text-sm text-gray-700 focus:border-emerald-600 outline-none">
                                    </div>
                                </div>
                                <div class="flex justify-end gap-2 mt-2">
                                    <button @click="cancelEdit()" class="px-3 py-1.5 text-xs font-bold text-gray-500 hover:bg-gray-100 rounded">Cancelar</button>
                                    <button @click="saveEdit()" class="px-3 py-1.5 text-xs font-bold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 rounded shadow-sm">Guardar</button>
                                </div>
                            </div>

                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

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
