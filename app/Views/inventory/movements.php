<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movimientos de Inventario</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body class="bg-slate-50 text-slate-800" x-data="movementsApp()">

    <!-- Header -->
    <div class="bg-white border-b border-slate-200 sticky top-0 z-10 shadow-sm">
        <div class="max-w-4xl mx-auto px-4 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="<?= base_url('inventory') ?>" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100 transition-colors">
                    <span class="material-icons text-slate-500">arrow_back</span>
                </a>
                <div>
                    <h1 class="font-bold text-lg text-slate-800 leading-tight">Movimientos</h1>
                    <p class="text-[10px] text-slate-400 font-medium" x-text="movements.length + ' registros'"></p>
                </div>
            </div>
            
            <!-- Export/Action (Placeholder) -->
            <button class="w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 hover:text-indigo-600 transition-colors">
                <span class="material-icons text-sm">download</span>
            </button>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 py-6 space-y-6">

        <!-- Filters -->
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <span class="material-icons text-slate-400 absolute left-3 top-2.5 text-sm">search</span>
                <input type="text" x-model="search" placeholder="Buscar producto o referencia..." class="w-full pl-9 pr-4 py-2 bg-white border border-slate-200 rounded-lg text-sm font-bold text-slate-700 outline-none focus:border-indigo-500 transition-colors shadow-sm">
            </div>
            <select x-model="filterType" class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm font-bold text-slate-600 outline-none shadow-sm cursor-pointer hover:bg-slate-50">
                <option value="all">Todos</option>
                <option value="in">Entradas</option>
                <option value="out">Salidas</option>
            </select>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-2 gap-3">
            <div class="bg-white p-3 rounded-xl border border-slate-100 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Entradas</p>
                    <p class="text-lg font-black text-emerald-600" x-text="'+' + totalIn"></p>
                </div>
                <div class="w-8 h-8 rounded-full bg-emerald-50 flex items-center justify-center">
                    <span class="material-icons text-emerald-500 text-sm">arrow_downward</span>
                </div>
            </div>
            <div class="bg-white p-3 rounded-xl border border-slate-100 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Salidas</p>
                    <p class="text-lg font-black text-rose-600" x-text="'-' + totalOut"></p>
                </div>
                <div class="w-8 h-8 rounded-full bg-rose-50 flex items-center justify-center">
                    <span class="material-icons text-rose-500 text-sm">arrow_upward</span>
                </div>
            </div>
        </div>
        
        <!-- List View (Cards) -->
        <div class="space-y-3">
            <template x-if="filteredMovements.length === 0">
                <div class="py-12 text-center text-slate-400 bg-white rounded-xl border border-slate-100">
                    <span class="material-icons text-4xl mb-2 block text-slate-300">search_off</span>
                    <p class="font-medium text-sm">No se encontraron movimientos</p>
                </div>
            </template>

            <template x-for="mov in filteredMovements" :key="mov.id">
                <div @click="openDetail(mov)" class="bg-white p-3 rounded-xl border border-slate-100 shadow-sm flex items-center justify-between cursor-pointer active:scale-[0.98] transition-all hover:bg-slate-50 relative overflow-hidden group">
                    <!-- Decorator Line -->
                    <div class="absolute left-0 top-0 bottom-0 w-1" :class="isInput(mov.type) ? 'bg-emerald-500' : 'bg-rose-500'"></div>
                    
                    <div class="flex items-center gap-3 pl-2">
                        <!-- Icon -->
                        <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0"
                             :class="isInput(mov.type) ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'">
                            <span class="material-icons text-xl" x-text="getIcon(mov.type)"></span>
                        </div>
                        
                        <!-- Info -->
                        <div>
                            <p class="font-bold text-slate-700 text-sm leading-tight" x-text="mov.item_name"></p>
                            <p class="text-[10px] text-slate-400 font-medium mt-0.5" x-text="getTypeLabel(mov.type) + ' • ' + formatDateShort(mov.date)"></p>
                        </div>
                    </div>

                    <!-- Right Stats -->
                    <div class="text-right">
                        <p class="text-lg font-black font-mono tracking-tight" 
                           :class="isInput(mov.type) ? 'text-emerald-600' : 'text-rose-600'"
                           x-text="(isInput(mov.type) ? '+' : '-') + mov.quantity"></p>
                        <span class="text-[9px] text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded-md" x-text="formatTime(mov.date)"></span>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Detail Modal -->
    <div x-show="selectedMov" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div x-show="selectedMov" x-transition.opacity class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="selectedMov = null"></div>

        <!-- Card -->
        <div x-show="selectedMov" x-transition.scale.origin.bottom 
             class="relative bg-white w-full max-w-sm rounded-2xl shadow-xl overflow-hidden flex flex-col max-h-[90vh]">
            
            <!-- Header (Color Coded) -->
            <div class="p-4 flex items-center justify-between text-white shadow-inner"
                 :class="selectedMov && isInput(selectedMov.type) ? 'bg-emerald-500' : 'bg-rose-500'">
                <div class="flex items-center gap-2">
                    <span class="material-icons opacity-80" x-text="selectedMov ? getIcon(selectedMov.type) : ''"></span>
                    <span class="font-bold uppercase tracking-wider text-xs" x-text="selectedMov ? getTypeLabel(selectedMov.type) : ''"></span>
                </div>
                <button @click="selectedMov = null" class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center hover:bg-white/30 transition-colors">
                    <span class="material-icons text-sm">close</span>
                </button>
            </div>

            <!-- Content -->
            <div class="p-6 space-y-4 overflow-y-auto" x-if="selectedMov">
                <!-- Main Qty -->
                <div class="text-center">
                    <p class="text-[10px] uppercase font-bold text-slate-400 tracking-widest">Cantidad</p>
                    <p class="text-5xl font-black text-slate-800 font-mono my-1" 
                       :class="isInput(selectedMov.type) ? 'text-emerald-600' : 'text-rose-600'"
                       x-text="(isInput(selectedMov.type) ? '+' : '-') + selectedMov.quantity"></p>
                    <p class="text-sm font-bold text-slate-600" x-text="selectedMov.item_name"></p>
                </div>

                <hr class="border-slate-100">

                <!-- Details Grid -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-[10px] text-slate-400 font-bold uppercase">Fecha</p>
                        <p class="text-sm font-bold text-slate-700" x-text="formatDate(selectedMov.date)"></p>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-400 font-bold uppercase">Hora</p>
                        <p class="text-sm font-bold text-slate-700" x-text="formatTime(selectedMov.date)"></p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-[10px] text-slate-400 font-bold uppercase">Referencia / Detalle</p>
                        <div class="bg-slate-50 p-3 rounded-lg border border-slate-100 text-sm text-slate-600 font-medium italic">
                            <span class="material-icons text-[14px] align-text-bottom text-slate-400 mr-1">format_quote</span>
                            <span x-text="selectedMov.reference || 'Sin referencia'"></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Actions (Optional) -->
            <div class="p-4 border-t border-slate-100 bg-slate-50 flex justify-end">
                <button @click="selectedMov = null" class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-600 hover:bg-slate-100">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    <!-- Alpine.js Logic -->
    <script src="//unpkg.com/alpinejs" defer></script>
    <script>
        function movementsApp() {
            return {
                movements: <?= json_encode($movements ?? []) ?>,
                search: '',
                filterType: 'all',
                selectedMov: null,
                
                openDetail(mov) {
                    this.selectedMov = mov;
                },

                get filteredMovements() {
                    let q = this.search.toLowerCase();
                    return this.movements.filter(m => {
                        // Type Filter
                        let typeMatch = true;
                        if(this.filterType === 'in') typeMatch = this.isInput(m.type);
                        if(this.filterType === 'out') typeMatch = !this.isInput(m.type);
                        
                        // Search Filter
                        let searchMatch = !q || 
                            m.item_name.toLowerCase().includes(q) || 
                            m.reference.toLowerCase().includes(q);
                            
                        return typeMatch && searchMatch;
                    });
                },
                
                get totalIn() {
                    return this.filteredMovements
                        .filter(m => this.isInput(m.type))
                        .reduce((sum, m) => sum + parseInt(m.quantity), 0);
                },
                
                get totalOut() {
                    return this.filteredMovements
                        .filter(m => !this.isInput(m.type))
                        .reduce((sum, m) => sum + parseInt(m.quantity), 0);
                },

                isInput(type) {
                    return ['in', 'purchase', 'return'].includes(type);
                },
                
                getIcon(type) {
                    const icons = {
                        'in': 'login',
                        'purchase': 'shopping_bag',
                        'return': 'assignment_return',
                        'out': 'logout',
                        'sale': 'sell',
                        'adjustment': 'tune'
                    };
                    return icons[type] || 'sync_alt';
                },
                
                getTypeLabel(type) {
                    const labels = {
                        'in': 'Entrada Manual',
                        'purchase': 'Compra Stock',
                        'return': 'Devolución',
                        'out': 'Salida Manual',
                        'sale': 'Venta',
                        'adjustment': 'Ajuste'
                    };
                    return labels[type] || type;
                },
                
                formatDate(dateStr) {
                    if(!dateStr) return '';
                    const d = new Date(dateStr);
                    return d.toLocaleDateString('es-ES', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' });
                },

                formatDateShort(dateStr) {
                     if(!dateStr) return '';
                     const d = new Date(dateStr);
                     return d.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit' });
                },
                
                formatTime(dateStr) {
                    if(!dateStr) return '';
                    const d = new Date(dateStr);
                    return d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
                }
            }
        }
    </script>

</body>
</html>
