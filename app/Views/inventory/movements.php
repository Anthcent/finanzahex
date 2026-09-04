<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Movimientos de Inventario - Fi-Hex Wallet</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        [x-cloak] { display: none !important; }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
        .safe-top { padding-top: max(12px, env(safe-area-inset-top)); }
        .safe-bottom { padding-bottom: max(16px, env(safe-area-inset-bottom)); }
        .customize-scrollbar::-webkit-scrollbar { width: 4px; height: 4px; }
        .customize-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .customize-scrollbar::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, 0.3); border-radius: 9999px; }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex flex-col antialiased selection:bg-emerald-500 selection:text-white" x-data="movementsApp()">

    <!-- Executive Header -->
    <header class="bg-gradient-to-r from-emerald-950 via-slate-900 to-teal-950 border-b border-emerald-500/20 sticky top-0 z-30 backdrop-blur-xl bg-opacity-95 safe-top shadow-lg shadow-black/20">
        <div class="max-w-4xl mx-auto px-4 py-3 sm:py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="<?= base_url('inventory') ?>" class="w-10 h-10 rounded-xl bg-white/10 hover:bg-white/15 active:scale-95 border border-white/10 flex items-center justify-center text-slate-200 hover:text-white transition-all shadow-inner">
                    <span class="material-icons text-xl">arrow_back</span>
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="font-extrabold text-base sm:text-lg text-white tracking-tight leading-none">Movimientos</h1>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Kárdex</span>
                    </div>
                    <p class="text-[11px] text-slate-400 font-medium mt-0.5" x-text="filteredMovements.length + ' de ' + movements.length + ' registros'"></p>
                </div>
            </div>

            <!-- Quick Filter Pill Toggle -->
            <div class="flex items-center bg-slate-800/80 p-1 rounded-xl border border-white/10 text-xs font-bold">
                <button @click="filterType = 'all'" :class="filterType === 'all' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-400 hover:text-white'" class="px-2.5 py-1 rounded-lg transition-all text-[11px]">
                    Todos
                </button>
                <button @click="filterType = 'in'" :class="filterType === 'in' ? 'bg-emerald-700 text-white shadow-sm' : 'text-slate-400 hover:text-emerald-400'" class="px-2.5 py-1 rounded-lg transition-all text-[11px] flex items-center gap-0.5">
                    <span class="material-icons text-[13px]">arrow_downward</span> Entradas
                </button>
                <button @click="filterType = 'out'" :class="filterType === 'out' ? 'bg-rose-600 text-white shadow-sm' : 'text-slate-400 hover:text-rose-400'" class="px-2.5 py-1 rounded-lg transition-all text-[11px] flex items-center gap-0.5">
                    <span class="material-icons text-[13px]">arrow_upward</span> Salidas
                </button>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 max-w-4xl mx-auto w-full px-4 py-5 space-y-4 safe-bottom">

        <!-- Search Bar -->
        <div class="relative">
            <span class="material-icons text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 text-lg pointer-events-none">search</span>
            <input type="text" x-model="search" placeholder="Buscar por producto, lote o referencia..." 
                   class="w-full pl-10 pr-10 py-2.5 bg-slate-800/90 border border-slate-700/80 rounded-2xl text-sm font-semibold text-white placeholder-slate-400 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-all shadow-inner">
            <button x-show="search.length > 0" @click="search = ''" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white">
                <span class="material-icons text-sm">clear</span>
            </button>
        </div>

        <!-- Summary KPI Cards -->
        <div class="grid grid-cols-2 gap-3">
            <!-- Entradas KPI -->
            <div class="bg-gradient-to-br from-slate-800/90 to-slate-800/40 p-3.5 rounded-2xl border border-emerald-500/20 shadow-md flex items-center justify-between backdrop-blur-md">
                <div>
                    <div class="flex items-center gap-1.5 text-emerald-400">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <p class="text-[10px] font-black uppercase tracking-wider">Entradas</p>
                    </div>
                    <p class="text-xl sm:text-2xl font-black text-emerald-400 font-mono mt-0.5" x-text="'+' + totalIn"></p>
                    <p class="text-[10px] text-slate-400">Unidades ingresadas</p>
                </div>
                <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 shadow-inner">
                    <span class="material-icons text-lg">arrow_downward</span>
                </div>
            </div>

            <!-- Salidas KPI -->
            <div class="bg-gradient-to-br from-slate-800/90 to-slate-800/40 p-3.5 rounded-2xl border border-rose-500/20 shadow-md flex items-center justify-between backdrop-blur-md">
                <div>
                    <div class="flex items-center gap-1.5 text-rose-400">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                        <p class="text-[10px] font-black uppercase tracking-wider">Salidas</p>
                    </div>
                    <p class="text-xl sm:text-2xl font-black text-rose-400 font-mono mt-0.5" x-text="'-' + totalOut"></p>
                    <p class="text-[10px] text-slate-400">Unidades despachadas</p>
                </div>
                <div class="w-10 h-10 rounded-2xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-400 shadow-inner">
                    <span class="material-icons text-lg">arrow_upward</span>
                </div>
            </div>
        </div>

        <!-- Movements List -->
        <div class="space-y-2.5">
            <template x-if="filteredMovements.length === 0">
                <div class="py-14 text-center text-slate-400 bg-slate-800/50 rounded-2xl border border-slate-700/60 p-6">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-slate-800 border border-slate-700 flex items-center justify-center text-slate-400 mb-3">
                        <span class="material-icons text-3xl">inventory_2</span>
                    </div>
                    <p class="font-bold text-sm text-slate-200">No hay movimientos registrados</p>
                    <p class="text-xs text-slate-400 mt-1">Los ajustes, compras y ventas de inventario aparecerán aquí.</p>
                </div>
            </template>

            <template x-for="mov in filteredMovements" :key="mov.id">
                <div @click="openDetail(mov)" 
                     class="bg-slate-800/90 hover:bg-slate-800 border border-slate-700/70 hover:border-slate-600 p-3.5 rounded-2xl shadow-sm flex items-center justify-between cursor-pointer active:scale-[0.99] transition-all relative overflow-hidden group">
                    <!-- Color Accent Bar -->
                    <div class="absolute left-0 top-0 bottom-0 w-1.5" 
                         :class="isInput(mov.type) ? 'bg-emerald-500' : 'bg-rose-500'"></div>

                    <div class="flex items-center gap-3 pl-2 min-w-0">
                        <!-- Icon Avatar -->
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 border"
                             :class="isInput(mov.type) 
                                ? 'bg-emerald-500/15 border-emerald-500/30 text-emerald-400' 
                                : 'bg-rose-500/15 border-rose-500/30 text-rose-400'">
                            <span class="material-icons text-xl" x-text="getIcon(mov.type)"></span>
                        </div>

                        <!-- Product Info -->
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-white text-sm leading-tight truncate group-hover:text-emerald-300 transition-colors" x-text="mov.item_name"></p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-md border"
                                      :class="isInput(mov.type) 
                                        ? 'bg-emerald-500/10 text-emerald-300 border-emerald-500/20' 
                                        : 'bg-rose-500/10 text-rose-300 border-rose-500/20'"
                                      x-text="getTypeLabel(mov.type)">
                                </span>
                                <span class="text-[11px] text-slate-400 font-medium" x-text="formatDateShort(mov.date)"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Right Quantity & Details -->
                    <div class="text-right shrink-0 ml-3">
                        <p class="text-base sm:text-lg font-black font-mono tracking-tight" 
                           :class="isInput(mov.type) ? 'text-emerald-400' : 'text-rose-400'"
                           x-text="(isInput(mov.type) ? '+' : '-') + mov.quantity"></p>
                        <span class="text-[10px] font-medium text-slate-400 bg-slate-900/80 px-2 py-0.5 rounded-md border border-slate-700/60" 
                              x-text="formatTime(mov.date)"></span>
                    </div>
                </div>
            </template>
        </div>

    </main>

    <!-- Detail Bottom Sheet Modal -->
    <div x-show="selectedMov" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <!-- Backdrop -->
        <div x-show="selectedMov" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-950/80 backdrop-blur-md" 
             @click="selectedMov = null"></div>

        <!-- Sheet Card -->
        <div x-show="selectedMov" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-y-full sm:translate-y-4 sm:scale-95 opacity-0"
             x-transition:enter-end="translate-y-0 sm:scale-100 opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-y-0 sm:scale-100 opacity-100"
             x-transition:leave-end="translate-y-full sm:translate-y-4 sm:scale-95 opacity-0"
             class="relative bg-slate-900 border border-slate-800 w-full sm:max-w-md rounded-t-[2.5rem] sm:rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] safe-bottom z-10 text-slate-100">
            
            <!-- Mobile Drag Indicator -->
            <div class="w-12 h-1.5 bg-slate-700 rounded-full mx-auto my-3 sm:hidden shrink-0"></div>

            <!-- Header Badge -->
            <div class="p-4 sm:p-5 flex items-center justify-between border-b border-slate-800"
                 :class="selectedMov && isInput(selectedMov.type) ? 'bg-emerald-950/60' : 'bg-rose-950/60'">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center"
                         :class="selectedMov && isInput(selectedMov.type) ? 'bg-emerald-500/20 text-emerald-400' : 'bg-rose-500/20 text-rose-400'">
                        <span class="material-icons text-base" x-text="selectedMov ? getIcon(selectedMov.type) : ''"></span>
                    </div>
                    <div>
                        <span class="font-extrabold uppercase tracking-wider text-xs" 
                              :class="selectedMov && isInput(selectedMov.type) ? 'text-emerald-400' : 'text-rose-400'"
                              x-text="selectedMov ? getTypeLabel(selectedMov.type) : ''"></span>
                        <p class="text-[10px] text-slate-400">Detalle de movimiento</p>
                    </div>
                </div>
                <button @click="selectedMov = null" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-slate-300 hover:text-white flex items-center justify-center transition-colors">
                    <span class="material-icons text-sm">close</span>
                </button>
            </div>

            <!-- Content Area -->
            <div class="p-5 sm:p-6 space-y-5 overflow-y-auto customize-scrollbar" x-if="selectedMov">
                <!-- Main Quantity Card -->
                <div class="text-center bg-slate-800/60 p-4 rounded-2xl border border-slate-700/60">
                    <p class="text-[10px] uppercase font-bold text-slate-400 tracking-widest">Variación de Stock</p>
                    <p class="text-4xl sm:text-5xl font-black font-mono my-2 tracking-tight" 
                       :class="isInput(selectedMov.type) ? 'text-emerald-400' : 'text-rose-400'"
                       x-text="(isInput(selectedMov.type) ? '+' : '-') + selectedMov.quantity"></p>
                    <p class="text-sm font-bold text-slate-200" x-text="selectedMov.item_name"></p>
                </div>

                <!-- Details Grid -->
                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div class="bg-slate-800/40 p-3 rounded-xl border border-slate-700/50">
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Fecha</p>
                        <p class="text-sm font-bold text-slate-200 mt-0.5" x-text="formatDate(selectedMov.date)"></p>
                    </div>
                    <div class="bg-slate-800/40 p-3 rounded-xl border border-slate-700/50">
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Hora</p>
                        <p class="text-sm font-bold text-slate-200 mt-0.5" x-text="formatTime(selectedMov.date)"></p>
                    </div>
                    <div class="col-span-2 bg-slate-800/40 p-3.5 rounded-xl border border-slate-700/50">
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-1">Referencia / Motivo</p>
                        <div class="flex items-start gap-2 text-slate-300 italic text-sm">
                            <span class="material-icons text-base text-slate-500">format_quote</span>
                            <span x-text="selectedMov.reference || 'Sin referencia adicional registrada.'"></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="p-4 border-t border-slate-800 bg-slate-900/90 flex justify-end">
                <button @click="selectedMov = null" class="w-full sm:w-auto px-6 py-2.5 bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-xl text-xs font-bold text-white transition-colors active:scale-95">
                    Entendido
                </button>
            </div>
        </div>
    </div>

    <!-- Alpine.js Application Logic -->
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
                    let q = this.search.toLowerCase().trim();
                    return this.movements.filter(m => {
                        // Type Filter
                        let typeMatch = true;
                        if(this.filterType === 'in') typeMatch = this.isInput(m.type);
                        if(this.filterType === 'out') typeMatch = !this.isInput(m.type);
                        
                        // Search Filter
                        let searchMatch = !q || 
                            (m.item_name && m.item_name.toLowerCase().includes(q)) || 
                            (m.reference && m.reference.toLowerCase().includes(q));
                            
                        return typeMatch && searchMatch;
                    });
                },
                
                get totalIn() {
                    return this.filteredMovements
                        .filter(m => this.isInput(m.type))
                        .reduce((sum, m) => sum + parseInt(m.quantity || 0), 0);
                },
                
                get totalOut() {
                    return this.filteredMovements
                        .filter(m => !this.isInput(m.type))
                        .reduce((sum, m) => sum + parseInt(m.quantity || 0), 0);
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
