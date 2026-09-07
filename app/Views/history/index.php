<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Historial Financiero - Fi-Hex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Outfit:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;600;800&display=swap" rel="stylesheet">
    <meta name="theme-color" content="#047857">
    <style>
        body { font-family: 'Plus Jakarta Sans', 'Outfit', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        [x-cloak] { display: none !important; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .customize-scrollbar::-webkit-scrollbar { width: 6px; }
        .customize-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .customize-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
        .safe-bottom { padding-bottom: env(safe-area-inset-bottom, 1.5rem); }
        .thermal-paper {
            background: linear-gradient(to bottom, #fffdf8, #fffdf0);
            box-shadow: 0 4px 20px rgba(0,0,0,0.06), inset 0 0 15px rgba(0,0,0,0.02);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-emerald-50/60 via-slate-50 to-teal-50/40 h-[100dvh] min-h-0 overflow-hidden flex flex-col text-slate-800 antialiased selection:bg-emerald-500 selection:text-white" x-data="historyApp()">

    <!-- ========================================== -->
    <!-- STICKY EXECUTIVE HEADER                    -->
    <!-- ========================================== -->
    <header class="flex-none bg-white/95 backdrop-blur-xl border-b border-slate-200/80 z-30 sticky top-0 shadow-xs">
        <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5 min-w-0">
                <a href="<?= base_url() ?>" class="w-9 h-9 flex items-center justify-center rounded-2xl bg-slate-100/90 hover:bg-emerald-50 text-slate-600 hover:text-emerald-700 transition-colors border border-slate-200/70 active:scale-95 shrink-0 shadow-2xs" title="Volver al Dashboard">
                    <span class="material-icons text-xl">arrow_back</span>
                </a>
                <div class="w-9 h-9 rounded-2xl bg-gradient-to-br from-emerald-600 to-teal-700 text-white flex items-center justify-center shadow-md shadow-emerald-950/20 ring-1 ring-emerald-400/40 shrink-0">
                    <span class="material-icons text-lg">receipt_long</span>
                </div>
                <div class="leading-tight min-w-0">
                    <div class="flex items-center gap-1.5">
                        <h1 class="font-black text-slate-900 tracking-tight text-sm sm:text-base truncate">
                            Historial <span class="bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent">Financiero</span>
                        </h1>
                        <span class="hidden md:inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-200/80" x-text="records.length + ' regs'"></span>
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 truncate hidden sm:block">Libro mayor, ingresos, gastos y facturación fiscal</p>
                </div>
            </div>
            
            <!-- Quick Actions Bar -->
            <div class="flex items-center gap-1.5 sm:gap-2">
                <!-- Quick OCR Scanner Shortcut -->
                <a href="<?= base_url('ocr') ?>" 
                   class="flex items-center gap-1.5 px-3 py-2 rounded-2xl bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white font-black text-xs shadow-sm shadow-orange-950/20 active:scale-95 transition-all"
                   title="Escanear nueva factura">
                    <span class="material-icons text-base">photo_camera</span>
                    <span class="hidden sm:inline">Escanear</span>
                </a>

                <!-- Export CSV Button -->
                <button @click="exportCsv()" 
                        :disabled="records.length === 0"
                        class="flex items-center gap-1.5 px-3 py-2 rounded-2xl bg-white border border-slate-200/90 text-slate-700 hover:bg-slate-50 font-black text-xs shadow-2xs active:scale-95 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                        title="Exportar registros filtrados a CSV">
                    <span class="material-icons text-base text-emerald-600">download</span>
                    <span class="hidden md:inline">CSV</span>
                </button>

                <!-- Filter Toggle Button -->
                <button @click="showFilters = !showFilters" 
                        class="flex items-center gap-1.5 px-3.5 py-2 rounded-2xl transition-all font-black text-xs shadow-2xs active:scale-95 relative"
                        :class="showFilters ? 'bg-slate-900 text-white shadow-md' : 'bg-white border border-slate-200/90 text-slate-700 hover:bg-slate-50'">
                    <span class="material-icons text-base">filter_list</span>
                    <span class="hidden sm:inline">Filtros</span>
                    <span x-show="activeFiltersCount > 0" 
                          class="w-5 h-5 rounded-full bg-emerald-500 text-white text-[10px] font-black flex items-center justify-center -mr-1 shadow-2xs animate-pulse" 
                          x-text="activeFiltersCount"></span>
                </button>
            </div>
        </div>
    </header>

    <!-- ========================================== -->
    <!-- COLLAPSIBLE ADVANCED FILTER PANEL          -->
    <!-- ========================================== -->
    <div x-show="showFilters" x-transition.opacity.duration.200ms x-cloak class="bg-white/95 backdrop-blur-xl border-b border-slate-200/80 shadow-md z-20">
        <div class="max-w-6xl mx-auto px-4 py-4 space-y-3.5">
            
            <!-- Search & Presets Bar -->
            <div class="flex flex-col md:flex-row gap-2.5 items-stretch md:items-center justify-between">
                <!-- Search Input -->
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <span class="material-icons text-base">search</span>
                    </span>
                    <input type="text" x-model.debounce.350ms="filters.search" @input="fetchRecords()" 
                           placeholder="Buscar por descripción, comercio, RIF, Nro. factura, cuenta..." 
                           class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200/90 rounded-2xl text-xs sm:text-sm font-bold text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-emerald-500 outline-none transition shadow-2xs">
                    <button x-show="filters.search" @click="filters.search = ''; fetchRecords()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                        <span class="material-icons text-sm">cancel</span>
                    </button>
                </div>

                <!-- Quick Date Presets -->
                <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar py-0.5">
                    <button @click="setDatePreset('today')" class="px-2.5 py-1.5 rounded-xl text-[11px] font-black transition-all whitespace-nowrap" :class="activePreset === 'today' ? 'bg-emerald-700 text-white shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">Hoy</button>
                    <button @click="setDatePreset('this_week')" class="px-2.5 py-1.5 rounded-xl text-[11px] font-black transition-all whitespace-nowrap" :class="activePreset === 'this_week' ? 'bg-emerald-700 text-white shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">Esta semana</button>
                    <button @click="setDatePreset('this_month')" class="px-2.5 py-1.5 rounded-xl text-[11px] font-black transition-all whitespace-nowrap" :class="activePreset === 'this_month' ? 'bg-emerald-700 text-white shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">Este mes</button>
                    <button @click="setDatePreset('last_month')" class="px-2.5 py-1.5 rounded-xl text-[11px] font-black transition-all whitespace-nowrap" :class="activePreset === 'last_month' ? 'bg-emerald-700 text-white shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">Mes pasado</button>
                    <button @click="setDatePreset('all')" class="px-2.5 py-1.5 rounded-xl text-[11px] font-black transition-all whitespace-nowrap" :class="activePreset === 'all' ? 'bg-emerald-700 text-white shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">Todo</button>
                </div>
            </div>

            <!-- Type Filter Pills (Fintech Badges) -->
            <div class="flex overflow-x-auto gap-2 pb-1 no-scrollbar justify-start md:justify-center items-center">
                <button @click="toggleType('')" 
                        :class="!filters.type ? 'bg-slate-900 text-white shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" 
                        class="px-3.5 py-1.5 rounded-full text-xs font-black whitespace-nowrap transition-all flex items-center gap-1">
                    <span>Todos</span>
                    <span class="text-[10px] opacity-80" x-text="'(' + (summary?.total_records || 0) + ')'"></span>
                </button>

                <button @click="toggleType('income')" 
                        :class="filters.type === 'income' ? 'bg-emerald-600 text-white shadow-md' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200/60'" 
                        class="px-3.5 py-1.5 rounded-full text-xs font-black whitespace-nowrap transition-all flex items-center gap-1">
                    <span class="material-icons text-sm">arrow_downward</span>
                    <span>Ingresos</span>
                </button>

                <button @click="toggleType('expense')" 
                        :class="filters.type === 'expense' ? 'bg-rose-600 text-white shadow-md' : 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200/60'" 
                        class="px-3.5 py-1.5 rounded-full text-xs font-black whitespace-nowrap transition-all flex items-center gap-1">
                    <span class="material-icons text-sm">arrow_upward</span>
                    <span>Gastos</span>
                </button>

                <!-- High-Impact Facturas OCR Pill -->
                <button @click="toggleType('invoice')" 
                        :class="filters.type === 'invoice' ? 'bg-gradient-to-r from-amber-500 to-orange-600 text-white shadow-md ring-2 ring-orange-400/50' : 'bg-amber-50 text-amber-900 hover:bg-amber-100 border border-amber-300/80'" 
                        class="px-3.5 py-1.5 rounded-full text-xs font-black whitespace-nowrap transition-all flex items-center gap-1.5">
                    <span class="material-icons text-sm text-amber-300">receipt</span>
                    <span>⚡ Facturas OCR</span>
                    <span class="px-1.5 py-0.2 rounded-md bg-amber-950/20 text-[10px] font-black" x-text="summary?.invoices_count || 0"></span>
                </button>

                <button @click="toggleType('savings')" 
                        :class="filters.type === 'savings' ? 'bg-blue-600 text-white shadow-md' : 'bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200/60'" 
                        class="px-3.5 py-1.5 rounded-full text-xs font-black whitespace-nowrap transition-all flex items-center gap-1">
                    <span class="material-icons text-sm">savings</span>
                    <span>Ahorros</span>
                </button>
            </div>

            <!-- Deep Dropdown Filters Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2.5 pt-1">
                <!-- Account Selector -->
                <div>
                    <label class="text-[9px] uppercase font-black text-slate-400 tracking-wider block mb-1">Cuenta</label>
                    <select x-model="filters.account_id" @change="fetchRecords()" class="w-full p-2 bg-slate-50 border border-slate-200/90 rounded-xl text-xs font-bold text-slate-700 outline-none focus:border-emerald-500">
                        <option value="">Todas las cuentas</option>
                        <?php foreach ($accounts as $acc): ?>
                            <option value="<?= $acc['id'] ?>"><?= esc($acc['name']) ?> (<?= esc($acc['currency']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Category Selector -->
                <div>
                    <label class="text-[9px] uppercase font-black text-slate-400 tracking-wider block mb-1">Categoría</label>
                    <select x-model="filters.category_id" @change="fetchRecords()" class="w-full p-2 bg-slate-50 border border-slate-200/90 rounded-xl text-xs font-bold text-slate-700 outline-none focus:border-emerald-500">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= esc($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Owner Selector -->
                <div>
                    <label class="text-[9px] uppercase font-black text-slate-400 tracking-wider block mb-1">Dueño / Entidad</label>
                    <select x-model="filters.owner" @change="fetchRecords()" class="w-full p-2 bg-slate-50 border border-slate-200/90 rounded-xl text-xs font-bold text-slate-700 outline-none focus:border-emerald-500">
                        <option value="">Todos los dueños</option>
                        <?php foreach ($owners as $owner): ?>
                            <option value="<?= $owner ?>"><?= $owner ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Sort Selector -->
                <div>
                    <label class="text-[9px] uppercase font-black text-slate-400 tracking-wider block mb-1">Ordenar por</label>
                    <select x-model="filters.sort" @change="fetchRecords()" class="w-full p-2 bg-slate-50 border border-slate-200/90 rounded-xl text-xs font-bold text-slate-700 outline-none focus:border-emerald-500">
                        <option value="date_desc">Más recientes</option>
                        <option value="date_asc">Más antiguos</option>
                        <option value="amount_desc">Mayor monto</option>
                        <option value="amount_asc">Menor monto</option>
                    </select>
                </div>

                <!-- Date Start -->
                <div>
                    <label class="text-[9px] uppercase font-black text-slate-400 tracking-wider block mb-1">Desde</label>
                    <input type="date" x-model="filters.date_start" @change="activePreset=''; fetchRecords()" class="w-full p-2 bg-slate-50 border border-slate-200/90 rounded-xl text-xs font-bold text-slate-700 outline-none focus:border-emerald-500">
                </div>

                <!-- Date End -->
                <div>
                    <label class="text-[9px] uppercase font-black text-slate-400 tracking-wider block mb-1">Hasta</label>
                    <input type="date" x-model="filters.date_end" @change="activePreset=''; fetchRecords()" class="w-full p-2 bg-slate-50 border border-slate-200/90 rounded-xl text-xs font-bold text-slate-700 outline-none focus:border-emerald-500">
                </div>
            </div>

            <!-- Reset Button -->
            <div class="flex justify-end pt-1">
                <button @click="resetFilters()" class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5 active:scale-95">
                    <span class="material-icons text-sm">restart_alt</span>
                    <span>Restablecer filtros</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MAIN SCROLL AREA: summary + feed unified   -->
    <!-- ========================================== -->
    <main class="flex-1 min-h-0 overflow-y-auto px-3 sm:px-4 pt-3 sm:pt-4 pb-0 customize-scrollbar" id="scroll-container">
        <div class="max-w-6xl mx-auto pb-24 safe-bottom space-y-3 sm:space-y-4">

            <!-- ========================================== -->
            <!-- EXECUTIVE MINI-DASHBOARD (SUMMARY METRICS) -->
            <!-- ========================================== -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <!-- Total Income Card -->
            <div class="bg-white/90 backdrop-blur-md rounded-2xl p-3 sm:p-4 border border-emerald-100 shadow-2xs hover:shadow-sm transition-all relative overflow-hidden group">
                <div class="absolute -right-3 -top-3 w-14 h-14 bg-emerald-50 rounded-full group-hover:scale-110 transition-transform"></div>
                <div class="flex items-center justify-between mb-1.5 relative">
                    <span class="text-[10px] sm:text-xs font-black uppercase tracking-wider text-emerald-800">Ingresos</span>
                    <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                        <span class="material-icons text-sm">arrow_downward</span>
                    </span>
                </div>
                <div class="text-base sm:text-lg font-black text-emerald-700 tracking-tight" x-text="formatMoney(summary?.total_income || 0)"></div>
                <div class="text-[10px] font-bold text-slate-400 mt-0.5" x-text="'Filtrado en ' + (records.length) + ' registros'"></div>
            </div>

            <!-- Total Expense Card -->
            <div class="bg-white/90 backdrop-blur-md rounded-2xl p-3 sm:p-4 border border-rose-100 shadow-2xs hover:shadow-sm transition-all relative overflow-hidden group">
                <div class="absolute -right-3 -top-3 w-14 h-14 bg-rose-50 rounded-full group-hover:scale-110 transition-transform"></div>
                <div class="flex items-center justify-between mb-1.5 relative">
                    <span class="text-[10px] sm:text-xs font-black uppercase tracking-wider text-rose-800">Gastos</span>
                    <span class="w-6 h-6 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center">
                        <span class="material-icons text-sm">arrow_upward</span>
                    </span>
                </div>
                <div class="text-base sm:text-lg font-black text-rose-600 tracking-tight" x-text="formatMoney(summary?.total_expense || 0)"></div>
                <div class="text-[10px] font-bold text-slate-400 mt-0.5">Egresos totales período</div>
            </div>

            <!-- Total OCR Invoices Card -->
            <div @click="toggleType('invoice')" class="bg-gradient-to-br from-amber-50 to-orange-50/70 rounded-2xl p-3 sm:p-4 border border-amber-200/90 shadow-2xs hover:shadow-md transition-all relative overflow-hidden group cursor-pointer" title="Filtrar solo Facturas OCR">
                <div class="absolute -right-3 -top-3 w-14 h-14 bg-amber-100/50 rounded-full group-hover:scale-110 transition-transform"></div>
                <div class="flex items-center justify-between mb-1.5 relative">
                    <span class="text-[10px] sm:text-xs font-black uppercase tracking-wider text-amber-900 flex items-center gap-1">
                        <span>⚡ Facturas OCR</span>
                    </span>
                    <span class="px-1.5 py-0.5 rounded-lg bg-amber-200 text-amber-900 text-[10px] font-black" x-text="(summary?.invoices_count || 0) + ' fac'"></span>
                </div>
                <div class="text-base sm:text-lg font-black text-amber-950 tracking-tight" x-text="formatMoney(summary?.total_invoices_bs || 0)"></div>
                <div class="text-[10px] font-bold text-amber-700 mt-0.5" x-show="(summary?.total_invoices_usd || 0) > 0" x-text="'≈ ' + formatUsd(summary?.total_invoices_usd || 0)"></div>
                <div class="text-[10px] font-bold text-amber-700/80 mt-0.5" x-show="!(summary?.total_invoices_usd > 0)">Click para aislar facturas</div>
            </div>

            <!-- Net Balance Card -->
            <div class="bg-white/90 backdrop-blur-md rounded-2xl p-3 sm:p-4 border border-slate-200/80 shadow-2xs hover:shadow-sm transition-all relative overflow-hidden group">
                <div class="flex items-center justify-between mb-1.5 relative">
                    <span class="text-[10px] sm:text-xs font-black uppercase tracking-wider text-slate-600">Balance Neto</span>
                    <span class="w-6 h-6 rounded-lg flex items-center justify-center text-xs font-black"
                          :class="(summary?.net_balance || 0) >= 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
                        <span class="material-icons text-sm" x-text="(summary?.net_balance || 0) >= 0 ? 'trending_up' : 'trending_down'"></span>
                    </span>
                </div>
                <div class="text-base sm:text-lg font-black tracking-tight"
                     :class="(summary?.net_balance || 0) >= 0 ? 'text-emerald-700' : 'text-rose-600'"
                     x-text="formatMoney(summary?.net_balance || 0)"></div>
                <div class="text-[10px] font-bold text-slate-400 mt-0.5">Ingresos vs. Gastos</div>
            </div>
        </div>

            <!-- CHRONOLOGICAL FEED -->
            
            <!-- Loading Indicator -->
            <div x-show="loading" class="flex flex-col items-center justify-center py-20 text-slate-400">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-3 shadow-2xs ring-1 ring-emerald-200">
                    <span class="material-icons animate-spin text-2xl">sync</span>
                </div>
                <span class="text-xs font-black tracking-wide text-slate-600">Actualizando libro de movimientos...</span>
            </div>

            <!-- Empty State -->
            <div x-show="!loading && records.length === 0" class="flex flex-col items-center justify-center py-20 text-center" x-cloak>
                 <div class="w-20 h-20 rounded-3xl bg-slate-100 text-slate-400 flex items-center justify-center mb-3 shadow-2xs border border-slate-200/70">
                     <span class="material-icons text-4xl">search_off</span>
                 </div>
                 <h3 class="text-base font-black text-slate-800">No se encontraron movimientos</h3>
                 <p class="text-slate-400 text-xs max-w-sm mx-auto mt-1">No hay registros que coincidan con los filtros seleccionados o el rango de fecha.</p>
                 <div class="flex gap-2 mt-4">
                     <button @click="resetFilters()" class="px-4 py-2 text-emerald-700 bg-emerald-50 hover:bg-emerald-100 text-xs font-black rounded-xl transition">Limpiar filtros</button>
                     <a href="<?= base_url('ocr') ?>" class="px-4 py-2 text-white bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-xs font-black rounded-xl shadow-xs transition">Escanear Factura</a>
                 </div>
            </div>

            <!-- Grouped Chronological List -->
            <div x-show="!loading && records.length > 0" class="space-y-6">
                <template x-for="dayGroup in groupedRecords" :key="dayGroup.date">
                    <div class="space-y-2.5">
                        
                        <!-- Day Group Sticky Header -->
                        <div class="sticky top-0 z-10 py-1.5 px-3 bg-slate-100/90 backdrop-blur-md rounded-2xl border border-slate-200/70 flex flex-wrap items-center justify-between gap-x-3 gap-y-1 shadow-2xs">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                                <h3 class="text-xs font-black text-slate-800 tracking-tight truncate" x-text="dayGroup.label"></h3>
                                <span class="px-1.5 py-0.5 rounded-md bg-white border border-slate-200/80 text-[10px] font-black text-slate-500 shrink-0" x-text="dayGroup.items.length + ' mov'"></span>
                            </div>

                            <!-- Day Totals Mini Pills -->
                            <div class="flex items-center flex-wrap gap-1.5 text-[10px] font-black">
                                <span x-show="dayGroup.expenseTotal > 0" class="text-rose-600 bg-rose-50 px-2 py-0.5 rounded-lg border border-rose-200/60" x-text="'-' + formatMoney(dayGroup.expenseTotal)"></span>
                                <span x-show="dayGroup.incomeTotal > 0" class="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-lg border border-emerald-200/60" x-text="'+' + formatMoney(dayGroup.incomeTotal)"></span>
                                <span x-show="dayGroup.invoicesCount > 0" class="text-amber-900 bg-amber-100/80 px-2 py-0.5 rounded-lg border border-amber-300/80 flex items-center gap-0.5">
                                    <span class="material-icons text-[11px]">receipt</span>
                                    <span x-text="dayGroup.invoicesCount + ' fac'"></span>
                                </span>
                            </div>
                        </div>

                        <!-- Transaction Cards Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <template x-for="item in dayGroup.items" :key="item.id">
                                <div class="group bg-white/95 backdrop-blur-md rounded-2xl shadow-2xs hover:shadow-md transition-all duration-200 border border-slate-200/80 overflow-hidden relative flex flex-col justify-between"
                                     :class="item.has_invoice ? 'ring-1 ring-amber-400/40 bg-gradient-to-r from-amber-50/15 to-white' : ''">
                                    
                                    <!-- Left Accent Stripe -->
                                    <div class="absolute top-0 bottom-0 left-0 w-1.5"
                                         :class="{
                                            'bg-gradient-to-b from-amber-500 to-orange-600': item.has_invoice,
                                            'bg-rose-500': item.type === 'expense' && !item.has_invoice,
                                            'bg-emerald-500': item.type === 'income',
                                            'bg-blue-600': item.type === 'savings',
                                            'bg-slate-400': !['expense','income','savings'].includes(item.type) && !item.has_invoice
                                         }"></div>
                                    
                                    <div class="p-3.5 pl-5">
                                        <!-- Header Row: Badges, Time & OCR Status -->
                                        <div class="flex items-center justify-between gap-2 mb-2">
                                            <div class="flex items-center gap-1.5 flex-wrap min-w-0">
                                                <!-- Category Badge -->
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-black tracking-wider uppercase"
                                                      :class="item.has_invoice ? 'bg-amber-100/80 text-amber-900 border border-amber-200/80' : 'bg-slate-100 text-slate-700'">
                                                    <span class="material-icons text-[11px]" x-text="item.category_icon || 'category'"></span>
                                                    <span class="truncate max-w-[80px]" x-text="item.category_name || 'General'"></span>
                                                </span>

                                                <!-- OCR Fiscal Invoice Badge -->
                                                <template x-if="item.has_invoice">
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-black bg-gradient-to-r from-amber-500 to-orange-500 text-white shadow-2xs">
                                                        <span class="material-icons text-[11px]">receipt</span>
                                                        <span>Factura Fiscal OCR</span>
                                                    </span>
                                                </template>

                                                <!-- Items Count Pill -->
                                                <template x-if="item.items_count > 0">
                                                    <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-lg text-[10px] font-black bg-slate-100 text-slate-600 border border-slate-200/80">
                                                        <span class="material-icons text-[11px]">inventory_2</span>
                                                        <span x-text="item.items_count + ' items'"></span>
                                                    </span>
                                                </template>
                                            </div>

                                            <!-- Timestamp -->
                                            <div class="text-[11px] text-slate-400 font-bold shrink-0 flex items-center gap-1">
                                                <span class="material-icons text-[12px]">schedule</span>
                                                <span x-text="item.time_hi || formatDateOnlyTime(item.created_at)"></span>
                                            </div>
                                        </div>

                                        <!-- Main Description & Merchant Info -->
                                        <div class="flex justify-between items-start gap-2 mb-2.5">
                                            <div class="min-w-0 flex-1">
                                                <!-- If OCR Invoice, show merchant prominence -->
                                                <template x-if="item.has_invoice && item.invoice_merchant">
                                                    <div class="mb-1">
                                                        <div class="font-black text-slate-900 text-sm leading-tight truncate flex items-center gap-1">
                                                            <span class="material-icons text-amber-600 text-sm shrink-0">storefront</span>
                                                            <span class="truncate" x-text="item.invoice_merchant"></span>
                                                        </div>
                                                        <div class="flex items-center flex-wrap gap-2 text-[10px] font-bold text-slate-500 mt-0.5">
                                                            <span x-show="item.invoice_rif" x-text="'RIF: ' + item.invoice_rif"></span>
                                                            <span x-show="item.invoice_number" class="text-slate-700 bg-slate-100 px-1 rounded font-mono" x-text="'Fac: #' + item.invoice_number"></span>
                                                        </div>
                                                    </div>
                                                </template>

                                                <!-- Standard Description -->
                                                <h4 class="text-slate-800 font-extrabold text-xs sm:text-sm leading-snug line-clamp-2" 
                                                    x-text="item.description || 'Sin descripción'"></h4>
                                            </div>

                                            <!-- Amount Column -->
                                            <div class="text-right shrink-0">
                                                <span class="block text-base sm:text-lg font-black tracking-tight"
                                                      :class="{
                                                          'text-rose-600': item.type === 'expense',
                                                          'text-emerald-700': item.type === 'income',
                                                          'text-blue-600': item.type === 'savings'
                                                      }"
                                                      x-text="(item.type === 'expense' ? '-' : '+') + formatMoney(item.amount)"></span>
                                                
                                                <div class="flex items-center justify-end gap-1 mt-0.5">
                                                    <span x-show="item.amount_usd > 0" class="text-[11px] text-slate-400 font-bold" x-text="'≈ ' + formatUsd(item.amount_usd)"></span>
                                                    <span x-show="item.exchange_rate > 0" class="text-[9px] text-slate-400 font-mono bg-slate-100 px-1 rounded" x-text="'@' + parseFloat(item.exchange_rate).toFixed(2)"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Footer: Account, Owner & Interactive Buttons -->
                                        <div class="flex flex-wrap items-center justify-between gap-x-2 gap-y-2 text-[11px] text-slate-500 pt-2 border-t border-slate-100 font-bold">
                                            <div class="flex items-center gap-2 min-w-0 flex-wrap">
                                                <!-- Account -->
                                                <div class="flex items-center gap-1 min-w-0" title="Cuenta Bancaria">
                                                    <span class="material-icons text-[13px] text-slate-400 shrink-0">account_balance_wallet</span>
                                                    <span class="truncate max-w-[90px] sm:max-w-[140px]" x-text="item.account_name || 'Sin cuenta'"></span>
                                                </div>
                                                <!-- Owner -->
                                                <div class="flex items-center gap-1 shrink-0" title="Dueño / Titular">
                                                    <span class="material-icons text-[13px] text-slate-400">person</span>
                                                    <span x-text="item.owner"></span>
                                                </div>
                                            </div>

                                            <!-- Actions Toolbar -->
                                            <div class="flex items-center gap-1 shrink-0 ml-auto">
                                                <!-- View Detail / Fiscal Ticket Modal -->
                                                <button @click="openDetail(item)" 
                                                        class="px-2 py-1 rounded-xl text-[11px] font-black transition-all flex items-center gap-1 shadow-2xs active:scale-95"
                                                        :class="item.has_invoice ? 'bg-amber-100 text-amber-900 hover:bg-amber-200 border border-amber-300/80' : 'bg-slate-100 text-slate-700 hover:bg-emerald-50 hover:text-emerald-700'">
                                                    <span class="material-icons text-xs" x-text="item.has_invoice ? 'receipt' : 'visibility'"></span>
                                                    <span x-text="item.has_invoice ? 'Factura' : 'Detalle'"></span>
                                                </button>

                                                <!-- Quick Edit Button -->
                                                <button @click="editItem(item)" class="w-7 h-7 rounded-xl text-slate-400 hover:text-emerald-700 hover:bg-emerald-50 flex items-center justify-center transition-colors" title="Editar registro">
                                                    <span class="material-icons text-sm">edit</span>
                                                </button>

                                                <!-- Delete Button -->
                                                <button @click="deleteItem(item.id, item.has_invoice)" class="w-7 h-7 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 flex items-center justify-center transition-colors" title="Eliminar y revertir saldo">
                                                    <span class="material-icons text-sm">delete</span>
                                                </button>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </template>
                        </div>

                    </div>
                </template>
            </div>

        </div>
    </main>

    <!-- ========================================== -->
    <!-- COMPREHENSIVE DETAIL & INVOICE MODAL       -->
    <!-- ========================================== -->
    <div x-show="showDetailModal" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-3 sm:p-6" 
         x-cloak>
        
        <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full overflow-hidden flex flex-col max-h-[92vh] border border-slate-200"
             @click.away="showDetailModal = false">
            
            <!-- Modal Header -->
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/80">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-2xl flex items-center justify-center shadow-2xs"
                         :class="selectedItem?.has_invoice ? 'bg-amber-500 text-white' : 'bg-emerald-600 text-white'">
                        <span class="material-icons text-lg" x-text="selectedItem?.has_invoice ? 'receipt' : 'info'"></span>
                    </div>
                    <div>
                        <h3 class="font-black text-slate-900 text-sm sm:text-base leading-tight"
                            x-text="selectedItem?.has_invoice ? (detailInvoice?.merchant || selectedItem?.invoice_merchant || 'Factura Fiscal OCR') : 'Detalle de Movimiento'"></h3>
                        <p class="text-[10px] font-bold text-slate-400" 
                           x-text="'Registro #' + (selectedItem?.id || '') + ' • ' + formatDate(selectedItem?.created_at)"></p>
                    </div>
                </div>

                <button @click="showDetailModal = false" class="w-8 h-8 rounded-full bg-slate-200/70 hover:bg-slate-300 text-slate-600 flex items-center justify-center transition active:scale-95">
                    <span class="material-icons text-base">close</span>
                </button>
            </div>

            <!-- Modal Content (Scrollable) -->
            <div class="p-5 overflow-y-auto customize-scrollbar space-y-4">
                
                <!-- Loading Detail State -->
                <div x-show="detailLoading" class="py-12 flex flex-col items-center justify-center text-slate-400">
                    <span class="material-icons animate-spin text-3xl mb-2 text-emerald-600">sync</span>
                    <span class="text-xs font-bold">Cargando desglose completo...</span>
                </div>

                <div x-show="!detailLoading" class="space-y-4">
                    
                    <!-- Top Info Cards Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                        <div class="bg-slate-50 p-2.5 rounded-2xl border border-slate-100">
                            <span class="text-[9px] font-black uppercase text-slate-400 block">Tipo</span>
                            <span class="text-xs font-black capitalize"
                                  :class="{
                                      'text-rose-600': selectedItem?.type === 'expense',
                                      'text-emerald-700': selectedItem?.type === 'income',
                                      'text-blue-600': selectedItem?.type === 'savings'
                                  }"
                                  x-text="selectedItem?.type"></span>
                        </div>

                        <div class="bg-slate-50 p-2.5 rounded-2xl border border-slate-100">
                            <span class="text-[9px] font-black uppercase text-slate-400 block">Cuenta</span>
                            <span class="text-xs font-black text-slate-800 truncate block" x-text="selectedItem?.account_name || 'N/A'"></span>
                        </div>

                        <div class="bg-slate-50 p-2.5 rounded-2xl border border-slate-100">
                            <span class="text-[9px] font-black uppercase text-slate-400 block">Dueño</span>
                            <span class="text-xs font-black text-slate-800" x-text="selectedItem?.owner || 'N/A'"></span>
                        </div>

                        <div class="bg-slate-50 p-2.5 rounded-2xl border border-slate-100">
                            <span class="text-[9px] font-black uppercase text-slate-400 block">Tasa de Cambio</span>
                            <span class="text-xs font-black text-slate-800 font-mono" x-text="(selectedItem?.exchange_rate > 0 ? formatMoney(selectedItem?.exchange_rate) + '/$' : 'N/A')"></span>
                        </div>
                    </div>

                    <!-- Fiscal Header Details (if OCR Invoice) -->
                    <template x-if="selectedItem?.has_invoice || detailInvoice">
                        <div class="bg-gradient-to-br from-amber-50 to-orange-50/50 rounded-2xl p-4 border border-amber-200/90 shadow-2xs space-y-3">
                            <div class="flex items-center justify-between border-b border-amber-200/70 pb-2">
                                <span class="text-xs font-black text-amber-950 flex items-center gap-1.5">
                                    <span class="material-icons text-base text-amber-600">verified</span>
                                    <span>Datos Fiscales del Emisor</span>
                                </span>
                                <span class="px-2 py-0.5 rounded-lg bg-amber-200/80 text-amber-900 text-[10px] font-black" 
                                      x-text="detailInvoice?.model_label || 'SENIAT Ticket'"></span>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 text-xs">
                                <div>
                                    <span class="text-[9px] font-black uppercase text-amber-800/70 block">Razón Social</span>
                                    <span class="font-black text-slate-900" x-text="detailInvoice?.merchant || selectedItem?.invoice_merchant || 'N/A'"></span>
                                </div>
                                <div>
                                    <span class="text-[9px] font-black uppercase text-amber-800/70 block">RIF</span>
                                    <span class="font-black text-slate-900 font-mono" x-text="detailInvoice?.rif || selectedItem?.invoice_rif || 'N/A'"></span>
                                </div>
                                <div>
                                    <span class="text-[9px] font-black uppercase text-amber-800/70 block">Nro. Factura / Control</span>
                                    <span class="font-black text-slate-900 font-mono" x-text="detailInvoice?.invoice_number || selectedItem?.invoice_number || 'S/N'"></span>
                                </div>
                                <div>
                                    <span class="text-[9px] font-black uppercase text-amber-800/70 block">Fecha y Hora Factura</span>
                                    <span class="font-bold text-slate-700" x-text="(detailInvoice?.invoice_date || selectedItem?.invoice_date || '') + ' ' + (detailInvoice?.invoice_time || selectedItem?.invoice_time || '')"></span>
                                </div>
                                <div>
                                    <span class="text-[9px] font-black uppercase text-amber-800/70 block">Forma de Pago</span>
                                    <span class="font-bold text-slate-700" x-text="detailInvoice?.payment_method || 'Punto / Débito'"></span>
                                </div>
                                <div x-show="detailInvoice?.cashea_amount > 0">
                                    <span class="text-[9px] font-black uppercase text-amber-800/70 block">Monto Cashea</span>
                                    <span class="font-black text-indigo-700" x-text="formatMoney(detailInvoice?.cashea_amount)"></span>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Products / Line Items Breakdown -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-2xs">
                        <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200/80 flex items-center justify-between">
                            <h4 class="text-xs font-black text-slate-800 flex items-center gap-1.5">
                                <span class="material-icons text-sm text-emerald-600">inventory</span>
                                <span>Productos y Servicios Facturados</span>
                            </h4>
                            <span class="text-[10px] font-black text-slate-400" x-text="detailItems.length + ' renglones'"></span>
                        </div>

                        <!-- Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="border-b border-slate-100 bg-slate-50/50 text-[9px] font-black uppercase text-slate-400">
                                        <th class="py-2 px-3">Cant</th>
                                        <th class="py-2 px-3">Descripción</th>
                                        <th class="py-2 px-3 text-right">Precio Bs</th>
                                        <th class="py-2 px-3 text-right">Total Bs</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="(it, idx) in detailItems" :key="idx">
                                        <tr class="hover:bg-slate-50/80 transition-colors">
                                            <td class="py-2 px-3 font-mono font-bold text-slate-600 text-[11px]" x-text="it.quantity + 'x'"></td>
                                            <td class="py-2 px-3">
                                                <div class="font-bold text-slate-800" x-text="it.name"></div>
                                                <div x-show="it.description" class="text-[10px] text-slate-400 font-mono" x-text="it.description"></div>
                                            </td>
                                            <td class="py-2 px-3 text-right font-mono text-slate-600 text-[11px]" x-text="formatMoney(it.price)"></td>
                                            <td class="py-2 px-3 text-right font-mono font-black text-slate-900" x-text="formatMoney(it.price * it.quantity)"></td>
                                        </tr>
                                    </template>
                                    <template x-if="detailItems.length === 0">
                                        <tr>
                                            <td colspan="4" class="py-4 px-3 text-center text-slate-400 text-xs font-bold">
                                                No hay ítems desglosados registrados para este movimiento.
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Fiscal Summary (Subtotal, Exento, Base, IVA, Total) -->
                    <div class="bg-slate-50/90 rounded-2xl p-4 border border-slate-200/80 space-y-2">
                        <template x-if="detailInvoice && (detailInvoice.subtotal > 0 || detailInvoice.iva_amount > 0 || detailInvoice.exento > 0)">
                            <div class="space-y-1.5 text-xs text-slate-600 border-b border-slate-200/80 pb-2">
                                <div class="flex justify-between" x-show="detailInvoice.subtotal > 0">
                                    <span>Subtotal:</span>
                                    <span class="font-mono font-bold text-slate-800" x-text="formatMoney(detailInvoice.subtotal)"></span>
                                </div>
                                <div class="flex justify-between" x-show="detailInvoice.exento > 0">
                                    <span>Monto Exento (E):</span>
                                    <span class="font-mono font-bold text-slate-800" x-text="formatMoney(detailInvoice.exento)"></span>
                                </div>
                                <div class="flex justify-between" x-show="detailInvoice.base_imponible > 0">
                                    <span>Base Imponible (G 16%):</span>
                                    <span class="font-mono font-bold text-slate-800" x-text="formatMoney(detailInvoice.base_imponible)"></span>
                                </div>
                                <div class="flex justify-between" x-show="detailInvoice.iva_amount > 0">
                                    <span>IVA (16,00%):</span>
                                    <span class="font-mono font-bold text-rose-600" x-text="formatMoney(detailInvoice.iva_amount)"></span>
                                </div>
                                <div class="flex justify-between" x-show="detailInvoice.igtf_amount > 0">
                                    <span>IGTF (3,00%):</span>
                                    <span class="font-mono font-bold text-rose-600" x-text="formatMoney(detailInvoice.igtf_amount)"></span>
                                </div>
                            </div>
                        </template>

                        <div class="flex items-baseline justify-between pt-1">
                            <span class="text-sm font-black text-slate-900 uppercase">Monto Total:</span>
                            <div class="text-right">
                                <span class="text-lg font-black text-slate-900 tracking-tight" x-text="formatMoney(selectedItem?.amount || detailInvoice?.total_bs || 0)"></span>
                                <div x-show="selectedItem?.amount_usd > 0 || detailInvoice?.total_usd > 0" class="text-xs font-bold text-slate-400" x-text="'≈ ' + formatUsd(selectedItem?.amount_usd || detailInvoice?.total_usd || 0)"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Simulator of Thermal Ticket (Collapsible / Toggle) -->
                    <div class="border border-slate-200/90 rounded-2xl overflow-hidden bg-white">
                        <button @click="showThermalPreview = !showThermalPreview" 
                                class="w-full px-4 py-3 bg-slate-50 hover:bg-slate-100 flex items-center justify-between text-xs font-black text-slate-700 transition">
                            <span class="flex items-center gap-1.5">
                                <span class="material-icons text-base text-amber-600">receipt</span>
                                <span>Simulador de Ticket Térmico Fiscal</span>
                            </span>
                            <span class="material-icons text-sm transition-transform" :class="showThermalPreview ? 'rotate-180' : ''">expand_more</span>
                        </button>

                        <div x-show="showThermalPreview" x-cloak class="p-4 bg-slate-100/60 border-t border-slate-200">
                            <!-- Monospace Thermal Receipt Box -->
                            <div class="thermal-paper max-w-sm mx-auto p-4 rounded-xl border border-slate-300 font-mono text-[11px] text-slate-800 shadow-sm leading-relaxed">
                                <div class="text-center border-b border-dashed border-slate-400 pb-2 mb-2">
                                    <div class="font-black text-xs uppercase" x-text="detailInvoice?.merchant || selectedItem?.invoice_merchant || selectedItem?.description || 'FACTURA FISCAL'"></div>
                                    <div class="text-[10px] text-slate-600" x-show="detailInvoice?.rif || selectedItem?.invoice_rif" x-text="'RIF: ' + (detailInvoice?.rif || selectedItem?.invoice_rif)"></div>
                                    <div class="text-[9px] text-slate-500 mt-0.5" x-text="detailInvoice?.model_label || 'COMPROBANTE FISCAL'"></div>
                                    <div class="text-[9px] text-slate-600 mt-1 flex justify-between px-1">
                                        <span x-text="'FAC: #' + (detailInvoice?.invoice_number || selectedItem?.invoice_number || 'S/N')"></span>
                                        <span x-text="(detailInvoice?.invoice_date || selectedItem?.date_ymd || '') + ' ' + (detailInvoice?.invoice_time || selectedItem?.time_hi || '')"></span>
                                    </div>
                                </div>

                                <div class="space-y-1 my-2 border-b border-dashed border-slate-400 pb-2">
                                    <template x-for="(it, i) in detailItems" :key="i">
                                        <div class="flex justify-between items-start gap-1">
                                            <span class="truncate flex-1" x-text="(it.quantity || 1) + 'x ' + (it.name || 'Item')"></span>
                                            <span class="shrink-0 font-bold" x-text="formatMoney(it.price * it.quantity)"></span>
                                        </div>
                                    </template>
                                </div>

                                <div class="space-y-0.5 text-[10px] border-b border-dashed border-slate-400 pb-2 mb-2">
                                    <div class="flex justify-between" x-show="detailInvoice?.subtotal > 0">
                                        <span>SUBTOTAL:</span>
                                        <span x-text="formatMoney(detailInvoice?.subtotal)"></span>
                                    </div>
                                    <div class="flex justify-between" x-show="detailInvoice?.exento > 0">
                                        <span>EXENTO (E):</span>
                                        <span x-text="formatMoney(detailInvoice?.exento)"></span>
                                    </div>
                                    <div class="flex justify-between" x-show="detailInvoice?.base_imponible > 0">
                                        <span>BASE (G 16%):</span>
                                        <span x-text="formatMoney(detailInvoice?.base_imponible)"></span>
                                    </div>
                                    <div class="flex justify-between" x-show="detailInvoice?.iva_amount > 0">
                                        <span>IVA (16%):</span>
                                        <span x-text="formatMoney(detailInvoice?.iva_amount)"></span>
                                    </div>
                                    <div class="flex justify-between font-black text-xs pt-1 border-t border-slate-300">
                                        <span>TOTAL:</span>
                                        <span x-text="formatMoney(selectedItem?.amount || detailInvoice?.total_bs || 0)"></span>
                                    </div>
                                    <div class="flex justify-between text-slate-600" x-show="selectedItem?.amount_usd > 0 || detailInvoice?.total_usd > 0">
                                        <span>TOTAL USD:</span>
                                        <span x-text="formatUsd(selectedItem?.amount_usd || detailInvoice?.total_usd || 0)"></span>
                                    </div>
                                </div>

                                <div class="text-center text-[10px] text-slate-500 font-bold uppercase tracking-wider">
                                    ¡Gracias por su compra!
                                </div>
                            </div>

                            <!-- Copy Ticket Action -->
                            <div class="flex justify-center mt-3">
                                <button @click="copyThermalTicket()" 
                                        class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-black flex items-center gap-1.5 shadow-sm active:scale-95 transition">
                                    <span class="material-icons text-sm" x-text="copiedTicket ? 'check' : 'content_copy'"></span>
                                    <span x-text="copiedTicket ? '¡Copiado al portapapeles!' : 'Copiar Ticket como Texto'"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Raw OCR Text Toggle (Inspection) -->
                    <template x-if="detailInvoice?.raw_text || selectedItem?.invoice_raw_text">
                        <div class="border border-slate-200/90 rounded-2xl overflow-hidden bg-white">
                            <button @click="showRawOcr = !showRawOcr" 
                                    class="w-full px-4 py-2.5 bg-slate-50 hover:bg-slate-100 flex items-center justify-between text-xs font-black text-slate-600 transition">
                                <span class="flex items-center gap-1.5">
                                    <span class="material-icons text-sm text-slate-400">text_snippet</span>
                                    <span>Texto Original OCR Extraído</span>
                                </span>
                                <span class="material-icons text-sm transition-transform" :class="showRawOcr ? 'rotate-180' : ''">expand_more</span>
                            </button>
                            <div x-show="showRawOcr" x-cloak class="p-3 bg-slate-900 text-slate-200 font-mono text-[10px] overflow-x-auto max-h-48 customize-scrollbar leading-relaxed">
                                <pre x-text="detailInvoice?.raw_text || selectedItem?.invoice_raw_text"></pre>
                            </div>
                        </div>
                    </template>

                </div>

            </div>

            <!-- Modal Footer -->
            <div class="px-5 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                <button @click="deleteItem(selectedItem?.id, selectedItem?.has_invoice)" class="px-3.5 py-2 text-rose-600 hover:bg-rose-50 text-xs font-black rounded-xl transition flex items-center gap-1">
                    <span class="material-icons text-sm">delete</span>
                    <span>Eliminar Registro</span>
                </button>

                <div class="flex items-center gap-2">
                    <button @click="showDetailModal = false; editItem(selectedItem)" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-black rounded-xl transition flex items-center gap-1">
                        <span class="material-icons text-sm">edit</span>
                        <span>Editar</span>
                    </button>
                    <button @click="showDetailModal = false" class="px-4 py-2 bg-slate-900 text-white hover:bg-slate-800 text-xs font-black rounded-xl transition">
                        Cerrar
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- ========================================== -->
    <!-- QUICK EDIT MODAL                           -->
    <!-- ========================================== -->
    <div x-show="editingId !== null" 
         x-transition.opacity.duration.200ms
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4" 
         x-cloak>
        
        <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-200"
             @click.away="cancelEdit()">
            
            <div class="px-5 py-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
                        <span class="material-icons text-base">edit</span>
                    </span>
                    <h3 class="font-black text-slate-900 text-sm sm:text-base">Editar Transacción</h3>
                </div>
                <button @click="cancelEdit()" class="w-7 h-7 rounded-full bg-slate-200/70 hover:bg-slate-300 text-slate-600 flex items-center justify-center">
                    <span class="material-icons text-sm">close</span>
                </button>
            </div>

            <div class="p-5 space-y-3.5">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-[9px] uppercase font-black text-slate-400 tracking-wider block mb-1">Monto (Bs)</label>
                        <input type="number" step="0.01" x-model="editForm.amount" :disabled="editForm.isComplex" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm font-black text-slate-900 focus:bg-white focus:border-emerald-500 outline-none">
                    </div>
                    <div>
                        <label class="text-[9px] uppercase font-black text-slate-400 tracking-wider block mb-1">Fecha y Hora</label>
                        <input type="datetime-local" x-model="editForm.created_at" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 focus:bg-white focus:border-emerald-500 outline-none">
                    </div>
                </div>

                <div>
                    <label class="text-[9px] uppercase font-black text-slate-400 tracking-wider block mb-1">Categoría</label>
                    <select x-model="editForm.category_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 focus:bg-white focus:border-emerald-500 outline-none">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= esc($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="text-[9px] uppercase font-black text-slate-400 tracking-wider block mb-1">Descripción</label>
                    <input type="text" x-model="editForm.description" @keydown.enter="saveEdit()" 
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:bg-white focus:border-emerald-500 outline-none">
                </div>

                <div class="p-3 bg-amber-50 border border-amber-200/80 rounded-xl text-[11px] font-bold text-amber-900 flex items-start gap-2">
                    <span class="material-icons text-amber-600 text-sm shrink-0 mt-0.5">info</span>
                    <span>Al modificar el monto, el saldo de la cuenta bancaria asociada se reajustará automáticamente.</span>
                </div>
            </div>

            <div class="px-5 py-3.5 bg-slate-50 border-t border-slate-100 flex justify-end gap-2">
                <button @click="cancelEdit()" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-200 rounded-xl transition">Cancelar</button>
                <button @click="saveEdit()" :disabled="savingEdit" class="px-5 py-2 text-xs font-black text-white bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 rounded-xl shadow-xs transition flex items-center gap-1.5">
                    <span x-show="savingEdit" class="material-icons text-xs animate-spin">sync</span>
                    <span x-text="savingEdit ? 'Guardando...' : 'Guardar Cambios'"></span>
                </button>
            </div>

        </div>
    </div>

    <!-- ========================================== -->
    <!-- ALPINE APP LOGIC                           -->
    <!-- ========================================== -->
    <script>
        function historyApp() {
            return {
                showFilters: false,
                loading: false,
                savingEdit: false,
                records: [],
                summary: null,
                activePreset: 'all',
                
                // Detail Modal State
                showDetailModal: false,
                detailLoading: false,
                selectedItem: null,
                detailItems: [],
                detailInvoice: null,
                showThermalPreview: false,
                showRawOcr: false,
                copiedTicket: false,

                // Editing State
                editingId: null,
                editForm: { id: null, amount: 0, description: '', created_at: '', category_id: '', isComplex: false },

                // Filters
                filters: {
                    date_start: '',
                    date_end: '',
                    type: '',
                    owner: '',
                    category_id: '',
                    account_id: '',
                    search: '',
                    sort: 'date_desc'
                },

                init() {
                    this.fetchRecords();
                },

                get activeFiltersCount() {
                    let count = 0;
                    if (this.filters.search) count++;
                    if (this.filters.type) count++;
                    if (this.filters.owner) count++;
                    if (this.filters.category_id) count++;
                    if (this.filters.account_id) count++;
                    if (this.filters.date_start) count++;
                    if (this.filters.date_end) count++;
                    return count;
                },

                get groupedRecords() {
                    const groups = {};
                    for (const item of this.records) {
                        const key = item.date_ymd || (item.created_at ? item.created_at.substring(0, 10) : 'Sin fecha');
                        if (!groups[key]) {
                            groups[key] = {
                                date: key,
                                label: this.formatDayHeader(key),
                                items: [],
                                incomeTotal: 0,
                                expenseTotal: 0,
                                invoicesCount: 0
                            };
                        }
                        groups[key].items.push(item);
                        const amt = parseFloat(item.amount || 0);
                        if (['income', 'return', 'exchange_in', 'transfer_in'].includes(item.type)) {
                            groups[key].incomeTotal += amt;
                        } else if (['expense', 'exchange_out', 'transfer_out'].includes(item.type)) {
                            groups[key].expenseTotal += amt;
                        }
                        if (item.has_invoice) {
                            groups[key].invoicesCount++;
                        }
                    }
                    return Object.values(groups);
                },

                formatDayHeader(dateStr) {
                    if (!dateStr || dateStr === 'Sin fecha') return 'Fecha no especificada';
                    const parts = dateStr.split('-');
                    if (parts.length !== 3) return dateStr;
                    const d = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
                    
                    const today = new Date();
                    const todayStr = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');
                    
                    const yest = new Date();
                    yest.setDate(yest.getDate() - 1);
                    const yestStr = yest.getFullYear() + '-' + String(yest.getMonth() + 1).padStart(2, '0') + '-' + String(yest.getDate()).padStart(2, '0');
                    
                    const options = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
                    const formatted = d.toLocaleDateString('es-VE', options);
                    const capitalized = formatted.charAt(0).toUpperCase() + formatted.slice(1);
                    
                    if (dateStr === todayStr) return 'Hoy • ' + capitalized;
                    if (dateStr === yestStr) return 'Ayer • ' + capitalized;
                    return capitalized;
                },

                setDatePreset(preset) {
                    this.activePreset = preset;
                    const now = new Date();

                    if (preset === 'today') {
                        const s = now.toISOString().slice(0, 10);
                        this.filters.date_start = s;
                        this.filters.date_end = s;
                    } else if (preset === 'this_week') {
                        const day = now.getDay();
                        const diffToMon = now.getDate() - (day === 0 ? 6 : day - 1);
                        const mon = new Date(now.setDate(diffToMon));
                        const sun = new Date(mon);
                        sun.setDate(mon.getDate() + 6);
                        this.filters.date_start = mon.toISOString().slice(0, 10);
                        this.filters.date_end = sun.toISOString().slice(0, 10);
                    } else if (preset === 'this_month') {
                        const y = now.getFullYear();
                        const m = now.getMonth();
                        const first = new Date(y, m, 1);
                        const last = new Date(y, m + 1, 0);
                        this.filters.date_start = first.toISOString().slice(0, 10);
                        this.filters.date_end = last.toISOString().slice(0, 10);
                    } else if (preset === 'last_month') {
                        const y = now.getFullYear();
                        const m = now.getMonth() - 1;
                        const first = new Date(y, m, 1);
                        const last = new Date(y, m + 1, 0);
                        this.filters.date_start = first.toISOString().slice(0, 10);
                        this.filters.date_end = last.toISOString().slice(0, 10);
                    } else if (preset === 'all') {
                        this.filters.date_start = '';
                        this.filters.date_end = '';
                    }
                    this.fetchRecords();
                },

                toggleType(t) {
                    this.filters.type = this.filters.type === t ? '' : t;
                    this.fetchRecords();
                },

                resetFilters() {
                    this.activePreset = 'all';
                    this.filters = {
                        date_start: '',
                        date_end: '',
                        type: '',
                        owner: '',
                        category_id: '',
                        account_id: '',
                        search: '',
                        sort: 'date_desc'
                    };
                    this.fetchRecords();
                },

                formatMoney(value) {
                    if (isNaN(value) || value === null || value === undefined) return 'Bs. 0,00';
                    return new Intl.NumberFormat('es-VE', { style: 'currency', currency: 'VES' }).format(value);
                },

                formatUsd(value) {
                    if (isNaN(value) || value === null || value === undefined) return '$0.00';
                    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
                },

                formatDate(dateStr) {
                    if(!dateStr) return '';
                    const d = new Date(dateStr);
                    if(isNaN(d.getTime())) return dateStr;
                    return d.toLocaleDateString('es-VE', {day:'2-digit', month:'2-digit', year:'numeric'}) + ' ' + d.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                },

                formatDateOnlyTime(dateStr) {
                    if(!dateStr) return '';
                    const d = new Date(dateStr);
                    if(isNaN(d.getTime())) return '';
                    return d.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
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
                            this.records = data.data || [];
                            this.summary = data.summary || null;
                        }
                    } catch(e) { 
                        console.error('Error fetching records:', e); 
                    } finally { 
                        this.loading = false; 
                    }
                },

                async openDetail(item) {
                    this.selectedItem = item;
                    this.detailItems = [];
                    this.detailInvoice = null;
                    this.showThermalPreview = item.has_invoice;
                    this.showRawOcr = false;
                    this.copiedTicket = false;
                    this.showDetailModal = true;
                    this.detailLoading = true;

                    try {
                        const res = await fetch('<?= base_url('history/items/') ?>' + item.id);
                        const data = await res.json();
                        if (data.status === 'success') {
                            this.detailItems = data.items || [];
                            this.detailInvoice = data.invoice || null;
                        }
                    } catch (e) {
                        console.error('Error loading items:', e);
                    } finally {
                        this.detailLoading = false;
                    }
                },

                copyThermalTicket() {
                    let t = '';
                    const inv = this.detailInvoice || {};
                    const item = this.selectedItem || {};
                    const merchant = inv.merchant || item.invoice_merchant || item.description || 'FACTURA FISCAL';
                    const rif = inv.rif || item.invoice_rif || '';
                    const num = inv.invoice_number || item.invoice_number || 'S/N';
                    const date = (inv.invoice_date || item.date_ymd || '') + ' ' + (inv.invoice_time || item.time_hi || '');
                    
                    t += '========================================\n';
                    t += '           ' + merchant.toUpperCase() + '\n';
                    if (rif) t += '           RIF: ' + rif + '\n';
                    t += '========================================\n';
                    t += 'FACTURA: ' + num + '\n';
                    t += 'FECHA:   ' + date + '\n';
                    t += '----------------------------------------\n';
                    t += 'CANT  DESCRIPCION                 TOTAL \n';
                    t += '----------------------------------------\n';
                    if (this.detailItems && this.detailItems.length > 0) {
                        for (const it of this.detailItems) {
                            const qty = (it.quantity || 1) + 'x';
                            const name = (it.name || '').substring(0, 22).padEnd(22, ' ');
                            const price = this.formatMoney((it.price || 0) * (it.quantity || 1));
                            t += qty.padEnd(5, ' ') + name + price.padStart(13, ' ') + '\n';
                        }
                    } else {
                        t += '1x   ' + merchant.substring(0, 22).padEnd(22, ' ') + this.formatMoney(item.amount || 0).padStart(13, ' ') + '\n';
                    }
                    t += '----------------------------------------\n';
                    if (inv.subtotal > 0)        t += 'SUBTOTAL:        ' + this.formatMoney(inv.subtotal).padStart(23, ' ') + '\n';
                    if (inv.exento > 0)          t += 'EXENTO (E):      ' + this.formatMoney(inv.exento).padStart(23, ' ') + '\n';
                    if (inv.base_imponible > 0)  t += 'BASE IMPONIBLE:  ' + this.formatMoney(inv.base_imponible).padStart(23, ' ') + '\n';
                    if (inv.iva_amount > 0)      t += 'IVA (16%):       ' + this.formatMoney(inv.iva_amount).padStart(23, ' ') + '\n';
                    if (inv.igtf_amount > 0)     t += 'IGTF (3%):       ' + this.formatMoney(inv.igtf_amount).padStart(23, ' ') + '\n';
                    t += 'TOTAL:           ' + this.formatMoney(item.amount || inv.total_bs || 0).padStart(23, ' ') + '\n';
                    if (item.amount_usd > 0 || inv.total_usd > 0) {
                        t += 'TOTAL USD:       ' + this.formatUsd(item.amount_usd || inv.total_usd || 0).padStart(23, ' ') + '\n';
                    }
                    if (inv.payment_method) {
                        t += 'PAGO:            ' + inv.payment_method.padStart(23, ' ') + '\n';
                    }
                    t += '========================================\n';
                    t += '       ¡GRACIAS POR SU COMPRA!          \n';
                    t += '========================================\n';
                    
                    navigator.clipboard.writeText(t).then(() => {
                        this.copiedTicket = true;
                        setTimeout(() => this.copiedTicket = false, 2500);
                    }).catch(() => {
                        alert('No se pudo copiar automáticamente al portapapeles.');
                    });
                },

                exportCsv() {
                    if (!this.records || this.records.length === 0) {
                        alert('No hay registros para exportar.');
                        return;
                    }
                    const headers = [
                        'ID', 'Fecha', 'Hora', 'Tipo', 'Dueño', 'Cuenta', 'Categoría', 
                        'Descripción', 'Monto Bs', 'Monto USD', 'Tasa BCV',
                        'Es Factura OCR', 'Comercio Factura', 'RIF', 'Nro Factura',
                        'Subtotal Factura', 'Exento Factura', 'Base Imponible', 'IVA Factura', 'Método Pago'
                    ];
                    
                    const rows = this.records.map(r => [
                        r.id,
                        r.date_ymd || '',
                        r.time_hi || '',
                        r.type,
                        r.owner,
                        '"' + (r.account_name || '').replace(/"/g, '""') + '"',
                        '"' + (r.category_name || '').replace(/"/g, '""') + '"',
                        '"' + (r.description || '').replace(/"/g, '""') + '"',
                        r.amount || 0,
                        r.amount_usd || 0,
                        r.exchange_rate || '',
                        r.has_invoice ? 'SI' : 'NO',
                        '"' + (r.invoice_merchant || '').replace(/"/g, '""') + '"',
                        '"' + (r.invoice_rif || '').replace(/"/g, '""') + '"',
                        '"' + (r.invoice_number || '').replace(/"/g, '""') + '"',
                        r.invoice_subtotal || 0,
                        r.invoice_exento || 0,
                        r.invoice_base_imponible || 0,
                        r.invoice_iva_amount || 0,
                        '"' + (r.invoice_payment_method || '').replace(/"/g, '""') + '"'
                    ]);

                    const csvContent = '\uFEFF' + [headers.join(','), ...rows.map(e => e.join(','))].join('\r\n');
                    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.setAttribute('href', url);
                    link.setAttribute('download', `historial_financiero_${new Date().toISOString().slice(0,10)}.csv`);
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                },
                
                async deleteItem(id, hasInvoice) {
                    const extraMsg = hasInvoice ? ' Esta factura OCR quedará marcada como anulada.' : '';
                    if(!confirm('¿Eliminar este registro? Se revertirá el monto debitado/acreditado en la cuenta bancaria.' + extraMsg)) return;
                    
                    try {
                        const res = await fetch('<?= base_url('history/delete/') ?>' + id);
                        const data = await res.json();
                        if(data.status === 'success') {
                            if (this.showDetailModal) this.showDetailModal = false;
                            this.fetchRecords();
                        } else {
                            alert(data.message || 'Error al eliminar el registro');
                        }
                    } catch(e) { 
                        console.error('Error al eliminar:', e); 
                    }
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
                    this.savingEdit = true;
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
                            alert('Error: ' + (data.message || 'No se pudo actualizar'));
                        }
                    } catch(e) { 
                        console.error('Error guardando edición:', e); 
                    } finally {
                        this.savingEdit = false;
                    }
                }
            }
        }
    </script>
</body>
</html>
