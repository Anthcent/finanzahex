<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Métricas & Reportes Financieros | Finanzahex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <meta name="theme-color" content="#064e3b">
    <style>
        body { font-family: 'Plus Jakarta Sans', 'Outfit', sans-serif; }
        [x-cloak] { display: none !important; }
        .customize-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
        .customize-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .customize-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
        .safe-bottom { padding-bottom: max(1.5rem, env(safe-area-inset-bottom)); }
        .safe-top { padding-top: max(0.75rem, env(safe-area-inset-top)); }
    </style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800 antialiased" x-data="metricsApp()">

    <!-- Executive Top Nav Header -->
    <header class="sticky top-0 z-40 bg-gradient-to-r from-emerald-950 via-slate-900 to-teal-950 text-white shadow-xl border-b border-emerald-800/30 safe-top">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <a href="<?= base_url() ?>" class="w-10 h-10 rounded-2xl bg-white/10 hover:bg-white/20 active:scale-95 flex items-center justify-center transition-all border border-white/10 text-white shrink-0" title="Volver al Inicio">
                    <span class="material-icons text-xl">arrow_back</span>
                </a>
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-500 via-teal-400 to-cyan-400 p-[1.5px] shadow-sm shrink-0">
                        <div class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center">
                            <span class="material-icons text-emerald-400 text-lg">insights</span>
                        </div>
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-sm sm:text-base font-black tracking-tight text-white flex items-center gap-1.5 truncate">
                            <span>Métricas & Reportes</span>
                            <span class="bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 text-[9px] font-black uppercase px-2 py-0.5 rounded-md hidden sm:inline-block">Fintech</span>
                        </h1>
                        <p class="text-[10px] text-emerald-200/70 font-semibold truncate">Ingresos, Gastos, Ahorros y Flujos de Caja</p>
                    </div>
                </div>
            </div>

            <!-- Header Actions: BCV rate badge, WhatsApp Summary & Export -->
            <div class="flex items-center gap-2 shrink-0">
                <div class="hidden lg:flex items-center gap-1.5 px-3 py-1.5 bg-white/10 border border-white/10 rounded-xl text-xs font-black text-emerald-300">
                    <span class="material-icons text-xs">currency_exchange</span>
                    <span>BCV: Bs. <?= number_format($exchangeRate, 2, ',', '.') ?></span>
                </div>

                <!-- Copy WhatsApp Summary -->
                <button @click="copyWhatsAppSummary()" 
                        class="bg-emerald-600/90 hover:bg-emerald-600 active:scale-95 text-white px-3 py-2 rounded-xl text-xs font-black shadow-md flex items-center gap-1.5 transition-all border border-emerald-400/30"
                        title="Copiar balance financiero para compartir">
                    <span class="material-icons text-sm">content_copy</span>
                    <span class="hidden sm:inline">Copiar Resumen</span>
                </button>

                <!-- Export CSV -->
                <a :href="'<?= base_url('metrics/export') ?>?start=' + startDate + '&end=' + endDate" target="_blank" 
                   class="bg-gradient-to-r from-teal-600 to-cyan-700 hover:from-teal-700 hover:to-cyan-800 active:scale-95 text-white px-3 py-2 rounded-xl text-xs font-black shadow-lg shadow-teal-950/30 flex items-center gap-1.5 transition-all border border-teal-400/30"
                   title="Descargar reporte en formato Excel / CSV">
                    <span class="material-icons text-sm">download</span>
                    <span class="hidden sm:inline">Exportar Excel</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="max-w-7xl mx-auto px-4 py-6 space-y-6 safe-bottom">

        <!-- Controls Toolbar: Date Ranges & Currency Selector -->
        <div class="bg-white rounded-3xl p-4 sm:p-5 shadow-xs border border-slate-200/80 space-y-4">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                
                <!-- Quick Date Presets -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 customize-scrollbar flex-wrap sm:flex-nowrap">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 mr-1 hidden sm:inline">Período:</span>
                    <button @click="setRange('today')" :class="activePreset === 'today' ? 'bg-emerald-700 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap">Hoy</button>
                    <button @click="setRange('last7Days')" :class="activePreset === 'last7Days' ? 'bg-emerald-700 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap">7 Días</button>
                    <button @click="setRange('thisMonth')" :class="activePreset === 'thisMonth' ? 'bg-emerald-700 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap">Este Mes</button>
                    <button @click="setRange('lastMonth')" :class="activePreset === 'lastMonth' ? 'bg-emerald-700 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap">Mes Anterior</button>
                    <button @click="setRange('thisYear')" :class="activePreset === 'thisYear' ? 'bg-emerald-700 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap">Este Año</button>
                    <button @click="setRange('all')" :class="activePreset === 'all' ? 'bg-emerald-700 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap">Todo</button>
                </div>

                <!-- Currency Display Switcher -->
                <div class="flex items-center gap-1.5 bg-slate-100 p-1 rounded-2xl shrink-0 self-start sm:self-auto border border-slate-200/60">
                    <span class="text-[10px] font-black uppercase text-slate-400 px-2">Moneda:</span>
                    <button @click="currencyMode = 'both'" :class="currencyMode === 'both' ? 'bg-white text-emerald-800 shadow-xs font-black' : 'text-slate-600 font-bold hover:text-slate-900'" class="px-2.5 py-1 rounded-xl text-xs transition-all">Ambas (Bs / $)</button>
                    <button @click="currencyMode = 'bs'" :class="currencyMode === 'bs' ? 'bg-white text-emerald-800 shadow-xs font-black' : 'text-slate-600 font-bold hover:text-slate-900'" class="px-2.5 py-1 rounded-xl text-xs transition-all">Solo Bs.</button>
                    <button @click="currencyMode = 'usd'" :class="currencyMode === 'usd' ? 'bg-white text-emerald-800 shadow-xs font-black' : 'text-slate-600 font-bold hover:text-slate-900'" class="px-2.5 py-1 rounded-xl text-xs transition-all">Solo $ USD</button>
                </div>
            </div>

            <!-- Custom Date Inputs -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-end gap-3 pt-2 border-t border-slate-100">
                <div class="flex-1">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Fecha Desde</label>
                    <input type="date" x-model="startDate" @change="activePreset = 'custom'" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs sm:text-sm font-bold text-slate-800 outline-none focus:border-emerald-500 focus:bg-white transition-all">
                </div>
                <div class="flex-1">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Fecha Hasta</label>
                    <input type="date" x-model="endDate" @change="activePreset = 'custom'" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs sm:text-sm font-bold text-slate-800 outline-none focus:border-emerald-500 focus:bg-white transition-all">
                </div>
                <button @click="fetchData()" :disabled="isLoading" class="bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 active:scale-95 text-white px-5 py-2.5 rounded-xl font-black text-xs sm:text-sm shadow-md shadow-emerald-900/20 transition-all flex items-center justify-center gap-1.5 shrink-0 disabled:opacity-50">
                    <span class="material-icons text-sm" :class="{'animate-spin': isLoading}">refresh</span>
                    <span>Actualizar</span>
                </button>
            </div>
        </div>

        <!-- KPI Cards Grid (Including Explicit Lo Ahorrado / Savings!) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
            
            <!-- 1. Ingresos Totales -->
            <div class="bg-gradient-to-br from-emerald-500/10 via-emerald-500/5 to-white rounded-3xl p-4 border border-emerald-200/80 shadow-xs relative overflow-hidden flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-[9px] font-black uppercase tracking-wider text-emerald-800 bg-emerald-100/90 px-2 py-0.5 rounded-md">Ingresos</span>
                        <span class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center shadow-xs">
                            <span class="material-icons text-base">trending_up</span>
                        </span>
                    </div>
                    <div class="mt-2.5">
                        <div x-show="currencyMode !== 'usd'" class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight" x-text="formatBs(totals.income)"></div>
                        <div x-show="currencyMode !== 'bs'" class="text-xs sm:text-sm font-black text-emerald-700" x-text="formatUsd(totals.income_usd)"></div>
                    </div>
                </div>
                <div class="mt-3 text-[10px] font-bold text-emerald-700 flex items-center justify-between border-t border-emerald-100 pt-2">
                    <span x-text="(totals.count_income || 0) + ' entradas'"></span>
                    <span class="material-icons text-xs">arrow_upward</span>
                </div>
            </div>

            <!-- 2. Egresos Totales -->
            <div class="bg-gradient-to-br from-rose-500/10 via-rose-500/5 to-white rounded-3xl p-4 border border-rose-200/80 shadow-xs relative overflow-hidden flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-[9px] font-black uppercase tracking-wider text-rose-800 bg-rose-100/90 px-2 py-0.5 rounded-md">Egresos</span>
                        <span class="w-8 h-8 rounded-xl bg-rose-600 text-white flex items-center justify-center shadow-xs">
                            <span class="material-icons text-base">trending_down</span>
                        </span>
                    </div>
                    <div class="mt-2.5">
                        <div x-show="currencyMode !== 'usd'" class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight" x-text="formatBs(totals.expense)"></div>
                        <div x-show="currencyMode !== 'bs'" class="text-xs sm:text-sm font-black text-rose-600" x-text="formatUsd(totals.expense_usd)"></div>
                    </div>
                </div>
                <div class="mt-3 text-[10px] font-bold text-rose-700 flex items-center justify-between border-t border-rose-100 pt-2">
                    <span x-text="(totals.count_expense || 0) + ' gastos'"></span>
                    <span class="material-icons text-xs">arrow_downward</span>
                </div>
            </div>

            <!-- 3. LO AHORRADO (SAVINGS - Fully Highlighted!) -->
            <div class="bg-gradient-to-br from-indigo-500/15 via-blue-500/5 to-white rounded-3xl p-4 border-2 border-indigo-300 shadow-sm relative overflow-hidden flex flex-col justify-between">
                <div class="absolute -right-2 -bottom-2 opacity-10 pointer-events-none text-indigo-900">
                    <span class="material-icons text-7xl">savings</span>
                </div>
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-[9px] font-black uppercase tracking-wider text-indigo-900 bg-indigo-100 px-2 py-0.5 rounded-md flex items-center gap-1">
                            <span class="material-icons text-[10px]">savings</span>
                            <span>Lo Ahorrado</span>
                        </span>
                        <span class="w-8 h-8 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-xs">
                            <span class="material-icons text-base">savings</span>
                        </span>
                    </div>
                    <div class="mt-2.5">
                        <div x-show="currencyMode !== 'usd'" class="text-xl sm:text-2xl font-black text-indigo-950 tracking-tight" x-text="formatBs(totals.savings)"></div>
                        <div x-show="currencyMode !== 'bs'" class="text-xs sm:text-sm font-black text-indigo-600" x-text="formatUsd(totals.savings_usd)"></div>
                    </div>
                </div>
                <div class="mt-3 text-[10px] font-bold text-indigo-800 flex items-center justify-between border-t border-indigo-100 pt-2">
                    <span x-text="(totals.count_savings || 0) + ' aportes a fondo'"></span>
                    <span class="bg-indigo-600 text-white text-[9px] font-black px-1.5 py-0.2 rounded">Reserva</span>
                </div>
            </div>

            <!-- 4. Balance Neto (Superávit / Déficit) -->
            <div class="rounded-3xl p-4 border shadow-xs relative overflow-hidden flex flex-col justify-between"
                 :class="(totals.income - totals.expense) >= 0 ? 'bg-gradient-to-br from-teal-500/10 to-white border-teal-200/80' : 'bg-gradient-to-br from-amber-500/10 to-white border-amber-200/80'">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-md"
                              :class="(totals.income - totals.expense) >= 0 ? 'text-teal-800 bg-teal-100' : 'text-amber-800 bg-amber-100'">
                            Balance Neto
                        </span>
                        <span class="w-8 h-8 rounded-xl text-white flex items-center justify-center shadow-xs"
                              :class="(totals.income - totals.expense) >= 0 ? 'bg-teal-600' : 'bg-amber-600'">
                            <span class="material-icons text-base" x-text="(totals.income - totals.expense) >= 0 ? 'account_balance_wallet' : 'warning'"></span>
                        </span>
                    </div>
                    <div class="mt-2.5">
                        <div x-show="currencyMode !== 'usd'" class="text-xl sm:text-2xl font-black tracking-tight"
                             :class="(totals.income - totals.expense) >= 0 ? 'text-teal-900' : 'text-amber-900'"
                             x-text="formatBs(totals.income - totals.expense)"></div>
                        <div x-show="currencyMode !== 'bs'" class="text-xs sm:text-sm font-black"
                             :class="(totals.income - totals.expense) >= 0 ? 'text-teal-700' : 'text-amber-700'"
                             x-text="formatUsd(totals.income_usd - totals.expense_usd)"></div>
                    </div>
                </div>
                <div class="mt-3 text-[10px] font-bold flex items-center justify-between border-t pt-2"
                     :class="(totals.income - totals.expense) >= 0 ? 'text-teal-800 border-teal-100' : 'text-amber-800 border-amber-100'">
                    <span x-text="(totals.income - totals.expense) >= 0 ? 'Superávit en período' : 'Déficit en período'"></span>
                    <span class="material-icons text-xs" x-text="(totals.income - totals.expense) >= 0 ? 'check_circle' : 'error'"></span>
                </div>
            </div>

            <!-- 5. Tasa de Ahorro (%) -->
            <div class="bg-gradient-to-br from-violet-500/10 via-purple-500/5 to-white rounded-3xl p-4 border border-violet-200/80 shadow-xs relative overflow-hidden flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-[9px] font-black uppercase tracking-wider text-violet-800 bg-violet-100 px-2 py-0.5 rounded-md">Tasa de Ahorro</span>
                        <span class="w-8 h-8 rounded-xl bg-violet-600 text-white flex items-center justify-center shadow-xs">
                            <span class="material-icons text-base">percent</span>
                        </span>
                    </div>
                    <div class="mt-2.5">
                        <div class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight" x-text="savingsRate() + '%'"></div>
                        <div class="text-xs sm:text-sm font-black text-violet-700">del ingreso guardado</div>
                    </div>
                </div>
                <div class="mt-3 text-[10px] font-bold text-violet-800 flex items-center justify-between border-t border-violet-100 pt-2">
                    <span x-text="savingsRate() >= 20 ? 'Excelente ahorro' : (savingsRate() > 0 ? 'Ahorro positivo' : 'Sin ahorros')"></span>
                    <span class="material-icons text-xs">workspace_premium</span>
                </div>
            </div>

            <!-- 6. Patrimonio Total en Cuentas -->
            <div class="bg-gradient-to-br from-slate-900 to-slate-800 text-white rounded-3xl p-4 shadow-sm relative overflow-hidden flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-[9px] font-black uppercase tracking-wider text-emerald-300 bg-emerald-950/60 border border-emerald-500/30 px-2 py-0.5 rounded-md">Cuentas Activas</span>
                        <span class="w-8 h-8 rounded-xl bg-white/10 text-white flex items-center justify-center">
                            <span class="material-icons text-base">account_balance</span>
                        </span>
                    </div>
                    <div class="mt-2.5">
                        <div x-show="currencyMode !== 'usd'" class="text-xl sm:text-2xl font-black text-white tracking-tight" x-text="formatBs(accountsTotalBs())"></div>
                        <div x-show="currencyMode !== 'bs'" class="text-xs sm:text-sm font-black text-emerald-400" x-text="formatUsd(accountsTotalUsd())"></div>
                    </div>
                </div>
                <div class="mt-3 text-[10px] font-bold text-slate-300 flex items-center justify-between border-t border-white/10 pt-2">
                    <span x-text="accountsSnapshot.length + ' cuentas'"></span>
                    <span class="text-[9px] text-slate-400">Saldo actual</span>
                </div>
            </div>

        </div>

        <!-- Navigation Tabs for Detailed Modules -->
        <div class="border-b border-slate-200">
            <nav class="flex space-x-2 sm:space-x-4 overflow-x-auto pb-1 customize-scrollbar" aria-label="Tabs">
                <button @click="activeTab = 'charts'; $nextTick(() => renderAllCharts())"
                        :class="activeTab === 'charts' ? 'border-emerald-600 text-emerald-700 bg-emerald-50/50' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
                        class="whitespace-nowrap py-2.5 px-4 border-b-2 font-black text-xs sm:text-sm rounded-t-2xl transition-all flex items-center gap-1.5">
                    <span class="material-icons text-base">bar_chart</span>
                    <span>Gráficos & Tendencias</span>
                </button>
                <button @click="activeTab = 'breakdown'"
                        :class="activeTab === 'breakdown' ? 'border-emerald-600 text-emerald-700 bg-emerald-50/50' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
                        class="whitespace-nowrap py-2.5 px-4 border-b-2 font-black text-xs sm:text-sm rounded-t-2xl transition-all flex items-center gap-1.5">
                    <span class="material-icons text-base">pie_chart</span>
                    <span>Categorías & Cuentas</span>
                </button>
                <button @click="activeTab = 'business'"
                        :class="activeTab === 'business' ? 'border-emerald-600 text-emerald-700 bg-emerald-50/50' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
                        class="whitespace-nowrap py-2.5 px-4 border-b-2 font-black text-xs sm:text-sm rounded-t-2xl transition-all flex items-center gap-1.5">
                    <span class="material-icons text-base">business_center</span>
                    <span>Negocio & Impresiones</span>
                </button>
                <button @click="activeTab = 'history'"
                        :class="activeTab === 'history' ? 'border-emerald-600 text-emerald-700 bg-emerald-50/50' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
                        class="whitespace-nowrap py-2.5 px-4 border-b-2 font-black text-xs sm:text-sm rounded-t-2xl transition-all flex items-center gap-1.5">
                    <span class="material-icons text-base">receipt_long</span>
                    <span>Transacciones</span>
                    <span class="bg-slate-200 text-slate-700 text-[10px] font-black px-1.5 py-0.5 rounded-full" x-text="filteredHistory.length"></span>
                </button>
            </nav>
        </div>

        <!-- ================= TAB 1: GRÁFICOS & TENDENCIAS ================= -->
        <div x-show="activeTab === 'charts'" class="space-y-6">
            
            <!-- Main Trend Chart: Income, Expense AND SAVINGS -->
            <div class="bg-white p-5 rounded-3xl shadow-xs border border-slate-100">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                    <div>
                        <h3 class="font-black text-slate-800 text-sm sm:text-base flex items-center gap-2">
                            <span>Tendencia Diaria: Ingresos, Egresos y Ahorros</span>
                            <span class="bg-indigo-100 text-indigo-800 text-[9px] font-black uppercase px-2 py-0.5 rounded-md">+ Lo Ahorrado</span>
                        </h3>
                        <p class="text-[10px] text-slate-400 font-bold">Comportamiento diario de flujo operativo y resguardo de capital</p>
                    </div>
                    <div class="flex items-center gap-3 text-[11px] font-bold flex-wrap">
                        <span class="flex items-center gap-1 text-emerald-700"><span class="w-3 h-3 rounded-md bg-emerald-600"></span> Ingresos</span>
                        <span class="flex items-center gap-1 text-rose-600"><span class="w-3 h-3 rounded-md bg-rose-600"></span> Egresos</span>
                        <span class="flex items-center gap-1 text-indigo-700"><span class="w-3 h-3 rounded-md bg-indigo-600"></span> Ahorros</span>
                    </div>
                </div>
                <div class="h-72 sm:h-80 w-full">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>

            <!-- Row of 3 Doughnut / Breakdown Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- 1. Cashflow Global Ratio -->
                <div class="bg-white p-5 rounded-3xl shadow-xs border border-slate-100 flex flex-col justify-between">
                    <div class="flex justify-between items-center mb-2">
                        <div>
                            <h3 class="font-black text-slate-800 text-sm">Distribución de Flujo</h3>
                            <p class="text-[10px] text-slate-400 font-bold">Ingresos vs Gastos vs Ahorros</p>
                        </div>
                        <span class="material-icons text-slate-400 text-lg">donut_large</span>
                    </div>
                    <div class="h-60 flex justify-center items-center relative">
                        <canvas id="flowRatioChart"></canvas>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-center pt-3 border-t border-slate-100 text-[10px] font-black">
                        <div class="text-emerald-700">
                            <div>Ingresos</div>
                            <div class="text-xs" x-text="formatCurrencyShort(totals.income, totals.income_usd)"></div>
                        </div>
                        <div class="text-rose-600">
                            <div>Gastos</div>
                            <div class="text-xs" x-text="formatCurrencyShort(totals.expense, totals.expense_usd)"></div>
                        </div>
                        <div class="text-indigo-600">
                            <div>Ahorros</div>
                            <div class="text-xs" x-text="formatCurrencyShort(totals.savings, totals.savings_usd)"></div>
                        </div>
                    </div>
                </div>

                <!-- 2. Expenses by Category -->
                <div class="bg-white p-5 rounded-3xl shadow-xs border border-slate-100 flex flex-col justify-between">
                    <div class="flex justify-between items-center mb-2">
                        <div>
                            <h3 class="font-black text-slate-800 text-sm">Gastos por Categoría</h3>
                            <p class="text-[10px] text-slate-400 font-bold">Principales rubros de egreso</p>
                        </div>
                        <span class="material-icons text-slate-400 text-lg">pie_chart</span>
                    </div>
                    <div class="h-60 flex justify-center items-center relative">
                        <canvas id="catChart"></canvas>
                    </div>
                    <div class="text-center pt-3 border-t border-slate-100 text-[10px] font-bold text-slate-400">
                        <span x-text="byCategory.length + ' categorías con egresos'"></span>
                    </div>
                </div>

                <!-- 3. Savings Distribution (Lo Ahorrado por Fondos) -->
                <div class="bg-white p-5 rounded-3xl shadow-xs border border-slate-100 flex flex-col justify-between">
                    <div class="flex justify-between items-center mb-2">
                        <div>
                            <h3 class="font-black text-slate-800 text-sm flex items-center gap-1.5">
                                <span>Destino de Ahorros</span>
                                <span class="bg-indigo-100 text-indigo-700 text-[9px] font-black px-1.5 py-0.2 rounded">Fondos</span>
                            </h3>
                            <p class="text-[10px] text-slate-400 font-bold">Distribución del capital guardado</p>
                        </div>
                        <span class="material-icons text-indigo-400 text-lg">savings</span>
                    </div>
                    <div class="h-60 flex justify-center items-center relative">
                        <canvas id="savingsChart"></canvas>
                    </div>
                    <div class="text-center pt-3 border-t border-slate-100 text-[10px] font-bold text-indigo-700">
                        <span x-text="savingsByCategory.length > 0 ? (savingsByCategory.length + ' fondos de reserva registrados') : 'Sin aportes registrados en el período'"></span>
                    </div>
                </div>

            </div>

        </div>

        <!-- ================= TAB 2: CATEGORÍAS & CUENTAS ================= -->
        <div x-show="activeTab === 'breakdown'" class="space-y-6">
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Expenses by Category Column -->
                <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center">
                                <span class="material-icons text-sm">trending_down</span>
                            </div>
                            <h3 class="font-black text-slate-800 text-sm">Egresos por Categoría</h3>
                        </div>
                        <span class="text-xs font-black text-rose-600" x-text="formatCurrencyShort(totals.expense, totals.expense_usd)"></span>
                    </div>

                    <div class="space-y-3 max-h-96 overflow-y-auto customize-scrollbar pr-1">
                        <template x-for="cat in byCategory" :key="'exp-' + cat.id">
                            <div class="p-2.5 rounded-2xl bg-slate-50 border border-slate-100/80">
                                <div class="flex items-center justify-between text-xs font-bold mb-1.5">
                                    <span class="text-slate-800 flex items-center gap-1.5 truncate">
                                        <span class="material-icons text-xs text-rose-500" x-text="cat.icon || 'label'"></span>
                                        <span x-text="cat.name"></span>
                                    </span>
                                    <span class="font-black text-rose-600 shrink-0" x-text="formatCurrencyShort(cat.total, cat.total_usd)"></span>
                                </div>
                                <div class="w-full bg-slate-200 h-1.5 rounded-full overflow-hidden">
                                    <div class="bg-rose-500 h-1.5 rounded-full" :style="'width: ' + ((totals.expense > 0 ? (cat.total / totals.expense) * 100 : 0)) + '%'"></div>
                                </div>
                                <div class="flex justify-between items-center mt-1 text-[9px] text-slate-400 font-bold">
                                    <span x-text="cat.count + ' transacciones'"></span>
                                    <span x-text="(totals.expense > 0 ? ((cat.total / totals.expense) * 100).toFixed(1) : 0) + '%'"></span>
                                </div>
                            </div>
                        </template>
                        <div x-show="byCategory.length === 0" class="p-8 text-center text-slate-400 font-bold text-xs">
                            No hay egresos en este período.
                        </div>
                    </div>
                </div>

                <!-- Incomes by Category Column -->
                <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
                                <span class="material-icons text-sm">trending_up</span>
                            </div>
                            <h3 class="font-black text-slate-800 text-sm">Ingresos por Categoría</h3>
                        </div>
                        <span class="text-xs font-black text-emerald-700" x-text="formatCurrencyShort(totals.income, totals.income_usd)"></span>
                    </div>

                    <div class="space-y-3 max-h-96 overflow-y-auto customize-scrollbar pr-1">
                        <template x-for="cat in incomeByCategory" :key="'inc-' + cat.id">
                            <div class="p-2.5 rounded-2xl bg-slate-50 border border-slate-100/80">
                                <div class="flex items-center justify-between text-xs font-bold mb-1.5">
                                    <span class="text-slate-800 flex items-center gap-1.5 truncate">
                                        <span class="material-icons text-xs text-emerald-600" x-text="cat.icon || 'label'"></span>
                                        <span x-text="cat.name"></span>
                                    </span>
                                    <span class="font-black text-emerald-700 shrink-0" x-text="formatCurrencyShort(cat.total, cat.total_usd)"></span>
                                </div>
                                <div class="w-full bg-slate-200 h-1.5 rounded-full overflow-hidden">
                                    <div class="bg-emerald-600 h-1.5 rounded-full" :style="'width: ' + ((totals.income > 0 ? (cat.total / totals.income) * 100 : 0)) + '%'"></div>
                                </div>
                                <div class="flex justify-between items-center mt-1 text-[9px] text-slate-400 font-bold">
                                    <span x-text="cat.count + ' entradas'"></span>
                                    <span x-text="(totals.income > 0 ? ((cat.total / totals.income) * 100).toFixed(1) : 0) + '%'"></span>
                                </div>
                            </div>
                        </template>
                        <div x-show="incomeByCategory.length === 0" class="p-8 text-center text-slate-400 font-bold text-xs">
                            No hay ingresos en este período.
                        </div>
                    </div>
                </div>

                <!-- Lo Ahorrado (Savings) by Category / Reserve Column -->
                <div class="bg-white rounded-3xl p-5 border-2 border-indigo-200 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-indigo-100">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center">
                                <span class="material-icons text-sm">savings</span>
                            </div>
                            <div>
                                <h3 class="font-black text-slate-800 text-sm">Lo Ahorrado</h3>
                                <p class="text-[9px] font-bold text-indigo-700">Fondos y reservas</p>
                            </div>
                        </div>
                        <span class="text-xs font-black text-indigo-700" x-text="formatCurrencyShort(totals.savings, totals.savings_usd)"></span>
                    </div>

                    <div class="space-y-3 max-h-96 overflow-y-auto customize-scrollbar pr-1">
                        <template x-for="cat in savingsByCategory" :key="'sav-' + cat.id">
                            <div class="p-2.5 rounded-2xl bg-indigo-50/50 border border-indigo-100">
                                <div class="flex items-center justify-between text-xs font-bold mb-1.5">
                                    <span class="text-slate-800 flex items-center gap-1.5 truncate">
                                        <span class="material-icons text-xs text-indigo-600" x-text="cat.icon || 'savings'"></span>
                                        <span x-text="cat.name"></span>
                                    </span>
                                    <span class="font-black text-indigo-700 shrink-0" x-text="formatCurrencyShort(cat.total, cat.total_usd)"></span>
                                </div>
                                <div class="w-full bg-slate-200 h-1.5 rounded-full overflow-hidden">
                                    <div class="bg-indigo-600 h-1.5 rounded-full" :style="'width: ' + ((totals.savings > 0 ? (cat.total / totals.savings) * 100 : 0)) + '%'"></div>
                                </div>
                                <div class="flex justify-between items-center mt-1 text-[9px] text-slate-400 font-bold">
                                    <span x-text="cat.count + ' movimientos'"></span>
                                    <span x-text="(totals.savings > 0 ? ((cat.total / totals.savings) * 100).toFixed(1) : 0) + '%'"></span>
                                </div>
                            </div>
                        </template>
                        <div x-show="savingsByCategory.length === 0" class="p-8 text-center text-slate-400 font-bold text-xs">
                            No hay ahorros registrados en este período.
                        </div>
                    </div>
                </div>

            </div>

            <!-- Bank Accounts Movement Breakdown -->
            <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="font-black text-slate-800 text-sm sm:text-base">Flujo y Saldos por Cuenta Bancaria</h3>
                        <p class="text-[10px] text-slate-400 font-bold">Entradas, salidas y aportes de ahorro por cada cuenta en el período</p>
                    </div>
                    <span class="text-xs font-black text-slate-500 bg-slate-100 px-3 py-1 rounded-xl" x-text="byAccount.length + ' cuentas'"></span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <template x-for="acc in byAccount" :key="'acc-' + acc.id">
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/70 space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="w-8 h-8 rounded-xl bg-slate-200 text-slate-700 flex items-center justify-center font-black text-xs">
                                        <span class="material-icons text-sm">account_balance</span>
                                    </span>
                                    <div>
                                        <h4 class="font-black text-slate-900 text-xs sm:text-sm" x-text="acc.name"></h4>
                                        <span class="text-[9px] font-bold text-slate-400 uppercase" x-text="acc.currency"></span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-[9px] font-bold text-slate-400 block">Saldo Actual</span>
                                    <span class="font-black text-xs text-slate-800" x-text="acc.currency === 'USD' ? formatUsd(acc.current_balance) : formatBs(acc.current_balance)"></span>
                                </div>
                            </div>

                            <!-- Financial Movements in Period -->
                            <div class="grid grid-cols-3 gap-2 bg-white p-2.5 rounded-xl border border-slate-100 text-center">
                                <div>
                                    <span class="text-[9px] font-bold text-emerald-700 block">Entradas</span>
                                    <span class="text-[11px] font-black text-emerald-700" x-text="formatCurrencyShort(acc.income, acc.income_usd)"></span>
                                </div>
                                <div>
                                    <span class="text-[9px] font-bold text-rose-600 block">Salidas</span>
                                    <span class="text-[11px] font-black text-rose-600" x-text="formatCurrencyShort(acc.expense, acc.expense_usd)"></span>
                                </div>
                                <div>
                                    <span class="text-[9px] font-bold text-indigo-700 block">Ahorros</span>
                                    <span class="text-[11px] font-black text-indigo-700" x-text="formatCurrencyShort(acc.savings, acc.savings_usd)"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

        </div>

        <!-- ================= TAB 3: NEGOCIO & IMPRESIONES ================= -->
        <div x-show="activeTab === 'business'" class="space-y-6">
            
            <!-- Printing KPI Cards -->
            <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-2xl bg-gradient-to-tr from-amber-500 to-orange-400 text-white flex items-center justify-center shadow-xs">
                            <span class="material-icons text-lg">print</span>
                        </div>
                        <div>
                            <h3 class="font-black text-slate-800 text-sm sm:text-base">Módulo de Impresiones (Printing)</h3>
                            <p class="text-[10px] text-slate-400 font-bold">Rendimiento comercial y cobranza en el período seleccionado</p>
                        </div>
                    </div>
                    <a href="<?= base_url('printing') ?>" class="text-xs font-black text-emerald-700 hover:text-emerald-800 flex items-center gap-1">
                        <span>Ir a Printing</span>
                        <span class="material-icons text-sm">arrow_forward</span>
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="p-4 rounded-2xl bg-amber-50/60 border border-amber-200/80">
                        <span class="text-[9px] font-black text-amber-800 uppercase">Órdenes Generadas</span>
                        <div class="text-2xl font-black text-slate-900 mt-1" x-text="printStats.order_count || 0"></div>
                        <span class="text-[10px] text-amber-700 font-bold" x-text="(printStats.open_count || 0) + ' órdenes con saldo pendiente'"></span>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
                        <span class="text-[9px] font-black text-slate-500 uppercase">Total Facturado</span>
                        <div class="text-lg font-black text-slate-900 mt-1" x-text="formatBs(printStats.total_bs || 0)"></div>
                        <div class="text-xs font-black text-slate-600" x-text="formatUsd(printStats.total_usd || 0)"></div>
                    </div>

                    <div class="p-4 rounded-2xl bg-emerald-50/60 border border-emerald-200">
                        <span class="text-[9px] font-black text-emerald-800 uppercase">Total Cobrado</span>
                        <div class="text-lg font-black text-emerald-900 mt-1" x-text="formatBs(printStats.paid_bs || 0)"></div>
                        <div class="text-xs font-black text-emerald-700" x-text="formatUsd(printStats.paid_usd || 0)"></div>
                    </div>

                    <div class="p-4 rounded-2xl bg-rose-50/60 border border-rose-200">
                        <span class="text-[9px] font-black text-rose-800 uppercase">Saldo Pendiente</span>
                        <div class="text-lg font-black text-rose-900 mt-1" x-text="formatBs((printStats.total_bs || 0) - (printStats.paid_bs || 0))"></div>
                        <div class="text-xs font-black text-rose-700" x-text="formatUsd((printStats.total_usd || 0) - (printStats.paid_usd || 0))"></div>
                    </div>
                </div>
            </div>

            <!-- Negocio vs Personal Breakdown -->
            <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="font-black text-slate-800 text-sm sm:text-base">Distribución por Propietario (Negocio vs Personal)</h3>
                        <p class="text-[10px] text-slate-400 font-bold">Segregación de finanzas operativas del negocio y gastos personales</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <template x-for="own in byOwner" :key="'own-' + own.owner">
                        <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200 space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="px-3 py-1 rounded-xl text-xs font-black uppercase tracking-wider"
                                      :class="own.owner === 'Negocio' ? 'bg-emerald-100 text-emerald-800' : (own.owner === 'Personal' ? 'bg-blue-100 text-blue-800' : 'bg-slate-200 text-slate-800')"
                                      x-text="own.owner"></span>
                                <span class="text-xs font-bold text-slate-400" x-text="own.count + ' movimientos'"></span>
                            </div>

                            <div class="grid grid-cols-3 gap-2">
                                <div class="bg-white p-3 rounded-xl border border-slate-100">
                                    <span class="text-[9px] font-black text-emerald-700 block">Ingresos</span>
                                    <div class="text-xs font-black text-slate-900 mt-1" x-text="formatCurrencyShort(own.income, own.income_usd)"></div>
                                </div>
                                <div class="bg-white p-3 rounded-xl border border-slate-100">
                                    <span class="text-[9px] font-black text-rose-600 block">Egresos</span>
                                    <div class="text-xs font-black text-slate-900 mt-1" x-text="formatCurrencyShort(own.expense, own.expense_usd)"></div>
                                </div>
                                <div class="bg-white p-3 rounded-xl border border-slate-100">
                                    <span class="text-[9px] font-black text-indigo-700 block">Ahorros</span>
                                    <div class="text-xs font-black text-slate-900 mt-1" x-text="formatCurrencyShort(own.savings, own.savings_usd)"></div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

        </div>

        <!-- ================= TAB 4: HISTORIAL & BUSCADOR AVANZADO ================= -->
        <div x-show="activeTab === 'history'" class="space-y-4">
            
            <!-- Search and Filter Bar -->
            <div class="bg-white rounded-3xl p-4 sm:p-5 shadow-xs border border-slate-100 space-y-4">
                <div class="flex flex-col md:flex-row gap-3">
                    
                    <!-- Search Input -->
                    <div class="relative flex-1">
                        <span class="material-icons absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
                        <input type="text" x-model="searchQuery" placeholder="Buscar por descripción, categoría, cuenta o propietario..." 
                               class="w-full pl-10 pr-4 py-2.5 rounded-2xl bg-slate-50 border border-slate-200 text-xs sm:text-sm font-bold text-slate-800 placeholder-slate-400 outline-none focus:border-emerald-500 focus:bg-white transition-all">
                        <button x-show="searchQuery" @click="searchQuery = ''" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                            <span class="material-icons text-sm">close</span>
                        </button>
                    </div>

                    <!-- Type Filter Pills -->
                    <div class="flex items-center gap-1 overflow-x-auto pb-1 customize-scrollbar shrink-0">
                        <button @click="typeFilter = 'all'" :class="typeFilter === 'all' ? 'bg-slate-900 text-white font-black' : 'bg-slate-100 text-slate-600 font-bold hover:bg-slate-200'" class="px-3 py-2 rounded-xl text-xs transition-all whitespace-nowrap">Todos</button>
                        <button @click="typeFilter = 'income'" :class="typeFilter === 'income' ? 'bg-emerald-600 text-white font-black' : 'bg-slate-100 text-slate-600 font-bold hover:bg-slate-200'" class="px-3 py-2 rounded-xl text-xs transition-all whitespace-nowrap">Ingresos</button>
                        <button @click="typeFilter = 'expense'" :class="typeFilter === 'expense' ? 'bg-rose-600 text-white font-black' : 'bg-slate-100 text-slate-600 font-bold hover:bg-slate-200'" class="px-3 py-2 rounded-xl text-xs transition-all whitespace-nowrap">Egresos</button>
                        <button @click="typeFilter = 'savings'" :class="typeFilter === 'savings' ? 'bg-indigo-600 text-white font-black' : 'bg-slate-100 text-slate-600 font-bold hover:bg-slate-200'" class="px-3 py-2 rounded-xl text-xs transition-all whitespace-nowrap flex items-center gap-1">
                            <span class="material-icons text-xs">savings</span>
                            <span>Ahorros</span>
                        </button>
                    </div>

                    <!-- Category & Account Selects -->
                    <div class="flex items-center gap-2">
                        <select x-model="categoryFilter" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 outline-none focus:border-emerald-500">
                            <option value="">Todas las categorías</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= esc($cat['name']) ?>"><?= esc($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <select x-model="accountFilter" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 outline-none focus:border-emerald-500">
                            <option value="">Todas las cuentas</option>
                            <?php foreach ($accounts as $acc): ?>
                                <option value="<?= esc($acc['name']) ?>"><?= esc($acc['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Subtotals Counter Pill -->
                <div class="flex flex-wrap items-center justify-between text-[11px] font-bold text-slate-500 pt-2 border-t border-slate-100">
                    <span>Mostrando <span class="font-black text-slate-900" x-text="filteredHistory.length"></span> de <span x-text="history.length"></span> transacciones</span>
                    <div class="flex items-center gap-2">
                        <span>Total Filtrado:</span>
                        <span class="font-black text-slate-900" x-text="formatBs(filteredTotalBs)"></span>
                        <span>/</span>
                        <span class="font-black text-emerald-700" x-text="formatUsd(filteredTotalUsd)"></span>
                    </div>
                </div>
            </div>

            <!-- Detailed Table Container -->
            <div class="bg-white rounded-3xl shadow-xs border border-slate-100 overflow-hidden">
                
                <!-- Desktop View: Sleek Table -->
                <div class="hidden sm:block overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-wider border-b border-slate-100">
                                <th class="p-4">Fecha</th>
                                <th class="p-4">Tipo</th>
                                <th class="p-4">Descripción</th>
                                <th class="p-4">Categoría</th>
                                <th class="p-4">Cuenta</th>
                                <th class="p-4">Propietario</th>
                                <th class="p-4 text-right">Monto (Bs)</th>
                                <th class="p-4 text-right">Monto ($)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs sm:text-sm">
                            <template x-for="row in filteredHistory" :key="row.id">
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="p-4 font-bold text-slate-500 whitespace-nowrap" x-text="formatDate(row.created_at)"></td>
                                    <td class="p-4 whitespace-nowrap">
                                        <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-md inline-flex items-center gap-1"
                                              :class="row.type === 'income' ? 'bg-emerald-100 text-emerald-800' : (row.type === 'savings' ? 'bg-indigo-100 text-indigo-800' : 'bg-rose-100 text-rose-800')">
                                            <span class="material-icons text-[11px]" x-text="row.type === 'income' ? 'arrow_upward' : (row.type === 'savings' ? 'savings' : 'arrow_downward')"></span>
                                            <span x-text="row.type === 'income' ? 'Ingreso' : (row.type === 'savings' ? 'Ahorro' : 'Egreso')"></span>
                                        </span>
                                    </td>
                                    <td class="p-4">
                                        <p class="font-black text-slate-800" x-text="row.description"></p>
                                    </td>
                                    <td class="p-4">
                                        <span class="text-[11px] font-bold bg-slate-100 text-slate-700 px-2 py-0.5 rounded-md inline-flex items-center gap-1">
                                            <span class="material-icons text-xs text-slate-400" x-text="row.category_icon || 'label'"></span>
                                            <span x-text="row.category_name || 'Sin Categoría'"></span>
                                        </span>
                                    </td>
                                    <td class="p-4 font-bold text-slate-600 whitespace-nowrap" x-text="row.account_name || '-'"></td>
                                    <td class="p-4 whitespace-nowrap">
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-md"
                                              :class="row.owner === 'Negocio' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : (row.owner === 'Personal' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-slate-100 text-slate-600')"
                                              x-text="row.owner || 'General'"></span>
                                    </td>
                                    <td class="p-4 text-right font-black whitespace-nowrap" 
                                        :class="row.type === 'income' ? 'text-emerald-700' : (row.type === 'savings' ? 'text-indigo-700' : 'text-rose-600')">
                                        <span x-text="row.type === 'income' ? '+' : (row.type === 'savings' ? '★' : '-')"></span>
                                        <span x-text="formatBs(row.amount)"></span>
                                    </td>
                                    <td class="p-4 text-right font-black whitespace-nowrap text-slate-600" x-text="formatUsd(row.amount_usd)"></td>
                                </tr>
                            </template>
                            <tr x-show="filteredHistory.length === 0">
                                <td colspan="8" class="p-12 text-center text-slate-400 font-bold">
                                    No se encontraron transacciones con los criterios seleccionados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile View: Modern Cards (Zero Horizontal Overflow) -->
                <div class="sm:hidden divide-y divide-slate-100">
                    <template x-for="row in filteredHistory" :key="row.id">
                        <div class="p-4 flex items-center justify-between gap-3 hover:bg-slate-50 transition-colors">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5 mb-1">
                                    <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-md inline-flex items-center gap-1"
                                          :class="row.type === 'income' ? 'bg-emerald-100 text-emerald-800' : (row.type === 'savings' ? 'bg-indigo-100 text-indigo-800' : 'bg-rose-100 text-rose-800')">
                                        <span class="material-icons text-[10px]" x-text="row.type === 'income' ? 'arrow_upward' : (row.type === 'savings' ? 'savings' : 'arrow_downward')"></span>
                                        <span x-text="row.type === 'income' ? 'Ingreso' : (row.type === 'savings' ? 'Ahorro' : 'Egreso')"></span>
                                    </span>
                                    <span class="text-[10px] font-bold text-slate-400" x-text="formatDate(row.created_at)"></span>
                                    <span class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-slate-100 text-slate-500" x-text="row.owner || 'General'"></span>
                                </div>
                                <h4 class="font-black text-slate-800 text-xs truncate" x-text="row.description"></h4>
                                <div class="flex items-center gap-2 mt-1 text-[10px] text-slate-500 font-bold truncate">
                                    <span x-text="row.category_name || 'General'"></span>
                                    <span>•</span>
                                    <span x-text="row.account_name || 'Cuenta'"></span>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-xs font-black" :class="row.type === 'income' ? 'text-emerald-700' : (row.type === 'savings' ? 'text-indigo-700' : 'text-rose-600')">
                                    <span x-text="row.type === 'income' ? '+' : (row.type === 'savings' ? '★' : '-')"></span>
                                    <span x-text="formatBs(row.amount)"></span>
                                </p>
                                <p class="text-[10px] font-bold text-slate-400" x-text="formatUsd(row.amount_usd)"></p>
                            </div>
                        </div>
                    </template>
                    <div x-show="filteredHistory.length === 0" class="p-8 text-center text-slate-400 font-bold text-xs">
                        No se encontraron transacciones.
                    </div>
                </div>

            </div>

        </div>

    </main>

    <!-- Toast Notification -->
    <div x-show="toast.show" x-transition x-cloak
         class="fixed bottom-6 right-6 z-50 bg-slate-900 text-white px-4 py-3 rounded-2xl shadow-2xl flex items-center gap-2 border border-slate-700 text-xs font-bold">
        <span class="material-icons text-emerald-400 text-sm">check_circle</span>
        <span x-text="toast.message"></span>
    </div>

    <!-- Application Script -->
    <script>
        function metricsApp() {
            return {
                startDate: '<?= date('Y-m-01') ?>',
                endDate: '<?= date('Y-m-t') ?>',
                activePreset: 'thisMonth',
                activeTab: 'charts',
                currencyMode: 'both', // 'both' | 'bs' | 'usd'
                isLoading: false,

                // Filters for transactions tab
                searchQuery: '',
                typeFilter: 'all',
                categoryFilter: '',
                accountFilter: '',

                // Data sets
                totals: { 
                    income: 0, income_usd: 0, 
                    expense: 0, expense_usd: 0, 
                    savings: 0, savings_usd: 0,
                    total_transactions: 0,
                    count_income: 0, count_expense: 0, count_savings: 0
                },
                byCategory: [],
                incomeByCategory: [],
                savingsByCategory: [],
                byAccount: [],
                byOwner: [],
                trends: [],
                printStats: {},
                accountsSnapshot: [],
                history: [],

                // Toast
                toast: { show: false, message: '' },

                // Chart instances
                charts: { trend: null, flowRatio: null, cat: null, savings: null },

                init() {
                    this.fetchData();
                },

                setRange(type) {
                    this.activePreset = type;
                    const now = new Date();
                    if (type === 'today') {
                        const y = now.getFullYear();
                        const m = String(now.getMonth() + 1).padStart(2, '0');
                        const d = String(now.getDate()).padStart(2, '0');
                        this.startDate = `${y}-${m}-${d}`;
                        this.endDate = `${y}-${m}-${d}`;
                    } else if (type === 'last7Days') {
                        const past = new Date();
                        past.setDate(now.getDate() - 7);
                        this.startDate = past.toISOString().split('T')[0];
                        this.endDate = now.toISOString().split('T')[0];
                    } else if (type === 'thisMonth') {
                        const y = now.getFullYear();
                        const m = String(now.getMonth() + 1).padStart(2, '0');
                        const lastDay = new Date(y, now.getMonth() + 1, 0).getDate();
                        this.startDate = `${y}-${m}-01`;
                        this.endDate = `${y}-${m}-${String(lastDay).padStart(2, '0')}`;
                    } else if (type === 'lastMonth') {
                        const prevMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                        const y = prevMonth.getFullYear();
                        const m = String(prevMonth.getMonth() + 1).padStart(2, '0');
                        const lastDay = new Date(y, prevMonth.getMonth() + 1, 0).getDate();
                        this.startDate = `${y}-${m}-01`;
                        this.endDate = `${y}-${m}-${String(lastDay).padStart(2, '0')}`;
                    } else if (type === 'thisYear') {
                        const y = now.getFullYear();
                        this.startDate = `${y}-01-01`;
                        this.endDate = `${y}-12-31`;
                    } else if (type === 'all') {
                        this.startDate = '2020-01-01';
                        this.endDate = now.toISOString().split('T')[0];
                    }
                    this.fetchData();
                },

                async fetchData() {
                    this.isLoading = true;
                    try {
                        let res = await fetch('<?= base_url('metrics/fetch') ?>', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ start: this.startDate, end: this.endDate })
                        });
                        let payload = await res.json();
                        
                        if (payload.status === 'success') {
                            const d = payload.data || {};
                            this.totals = d.totals || { income: 0, income_usd: 0, expense: 0, expense_usd: 0, savings: 0, savings_usd: 0 };
                            this.byCategory = d.by_category || [];
                            this.incomeByCategory = d.income_by_category || [];
                            this.savingsByCategory = d.savings_by_category || [];
                            this.byAccount = d.by_account || [];
                            this.byOwner = d.by_owner || [];
                            this.trends = d.trends || [];
                            this.printStats = d.print_stats || {};
                            this.accountsSnapshot = d.accounts_snapshot || [];
                            this.history = payload.history || [];

                            this.$nextTick(() => {
                                this.renderAllCharts();
                            });
                        }
                    } catch (e) {
                        console.error('Error fetching metrics:', e);
                    } finally {
                        this.isLoading = false;
                    }
                },

                renderAllCharts() {
                    this.renderTrendChart();
                    this.renderFlowRatioChart();
                    this.renderCatChart();
                    this.renderSavingsChart();
                },

                renderTrendChart() {
                    const ctx = document.getElementById('trendChart');
                    if (!ctx) return;
                    if (this.charts.trend) this.charts.trend.destroy();

                    // Choose Bs or USD according to currencyMode
                    const useUsd = (this.currencyMode === 'usd');
                    const labels = this.trends.map(t => {
                        const parts = t.date.split('-');
                        return parts.length === 3 ? `${parts[2]}/${parts[1]}` : t.date;
                    });
                    
                    const incomeData = this.trends.map(t => useUsd ? parseFloat(t.income_usd || 0) : parseFloat(t.income || 0));
                    const expenseData = this.trends.map(t => useUsd ? parseFloat(t.expense_usd || 0) : parseFloat(t.expense || 0));
                    const savingsData = this.trends.map(t => useUsd ? parseFloat(t.savings_usd || 0) : parseFloat(t.savings || 0));

                    this.charts.trend = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: 'Ingresos',
                                    data: incomeData,
                                    backgroundColor: '#059669',
                                    borderRadius: 6,
                                    barPercentage: 0.8
                                },
                                {
                                    label: 'Egresos',
                                    data: expenseData,
                                    backgroundColor: '#e11d48',
                                    borderRadius: 6,
                                    barPercentage: 0.8
                                },
                                {
                                    label: 'Lo Ahorrado',
                                    data: savingsData,
                                    backgroundColor: '#4f46e5',
                                    borderRadius: 6,
                                    barPercentage: 0.8
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: { mode: 'index', intersect: false },
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: (context) => {
                                            const val = context.parsed.y;
                                            return `${context.dataset.label}: ${useUsd ? '$' + val.toFixed(2) : 'Bs. ' + val.toLocaleString('es-VE', {minimumFractionDigits: 2})}`;
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: { grid: { display: false } },
                                y: { 
                                    beginAtZero: true, 
                                    grid: { color: '#f1f5f9' },
                                    ticks: {
                                        callback: (value) => useUsd ? '$' + value : 'Bs ' + value
                                    }
                                }
                            }
                        }
                    });
                },

                renderFlowRatioChart() {
                    const ctx = document.getElementById('flowRatioChart');
                    if (!ctx) return;
                    if (this.charts.flowRatio) this.charts.flowRatio.destroy();

                    const inc = parseFloat(this.totals.income || 0);
                    const exp = parseFloat(this.totals.expense || 0);
                    const sav = parseFloat(this.totals.savings || 0);

                    const dataVals = (inc + exp + sav === 0) ? [1] : [inc, exp, sav];
                    const bgColors = (inc + exp + sav === 0) ? ['#cbd5e1'] : ['#059669', '#e11d48', '#4f46e5'];
                    const labels = (inc + exp + sav === 0) ? ['Sin Datos'] : ['Ingresos', 'Egresos', 'Ahorros'];

                    this.charts.flowRatio = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: labels,
                            datasets: [{
                                data: dataVals,
                                backgroundColor: bgColors,
                                borderWidth: 2,
                                borderColor: '#ffffff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '72%',
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { boxWidth: 10, font: { family: 'Plus Jakarta Sans', size: 10, weight: 'bold' } }
                                }
                            }
                        }
                    });
                },

                renderCatChart() {
                    const ctx = document.getElementById('catChart');
                    if (!ctx) return;
                    if (this.charts.cat) this.charts.cat.destroy();

                    const categoryColors = ['#e11d48', '#d97706', '#7c3aed', '#0d9488', '#2563eb', '#64748b', '#059669'];
                    const labels = this.byCategory.length > 0 ? this.byCategory.map(c => c.name || 'Otros') : ['Sin egresos'];
                    const data = this.byCategory.length > 0 ? this.byCategory.map(c => parseFloat(c.total || 0)) : [1];
                    const colors = this.byCategory.length > 0 ? categoryColors.slice(0, this.byCategory.length) : ['#e2e8f0'];

                    this.charts.cat = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: labels,
                            datasets: [{
                                data: data,
                                backgroundColor: colors,
                                borderWidth: 2,
                                borderColor: '#ffffff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '68%',
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { boxWidth: 10, font: { family: 'Plus Jakarta Sans', size: 10, weight: 'bold' } }
                                }
                            }
                        }
                    });
                },

                renderSavingsChart() {
                    const ctx = document.getElementById('savingsChart');
                    if (!ctx) return;
                    if (this.charts.savings) this.charts.savings.destroy();

                    const savingsColors = ['#4f46e5', '#3b82f6', '#06b6d4', '#8b5cf6', '#6366f1', '#64748b'];
                    const labels = this.savingsByCategory.length > 0 ? this.savingsByCategory.map(s => s.name || 'Fondo General') : ['Sin aportes'];
                    const data = this.savingsByCategory.length > 0 ? this.savingsByCategory.map(s => parseFloat(s.total || 0)) : [1];
                    const colors = this.savingsByCategory.length > 0 ? savingsColors.slice(0, this.savingsByCategory.length) : ['#e2e8f0'];

                    this.charts.savings = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: labels,
                            datasets: [{
                                data: data,
                                backgroundColor: colors,
                                borderWidth: 2,
                                borderColor: '#ffffff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '68%',
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { boxWidth: 10, font: { family: 'Plus Jakarta Sans', size: 10, weight: 'bold' } }
                                }
                            }
                        }
                    });
                },

                // Filtered History for Transactions Tab
                get filteredHistory() {
                    return this.history.filter(item => {
                        // Type filter
                        if (this.typeFilter !== 'all' && item.type !== this.typeFilter) return false;

                        // Category filter
                        if (this.categoryFilter && (item.category_name || '') !== this.categoryFilter) return false;

                        // Account filter
                        if (this.accountFilter && (item.account_name || '') !== this.accountFilter) return false;

                        // Text search
                        if (this.searchQuery.trim() !== '') {
                            const q = this.searchQuery.toLowerCase();
                            const desc = (item.description || '').toLowerCase();
                            const cat = (item.category_name || '').toLowerCase();
                            const acc = (item.account_name || '').toLowerCase();
                            const owner = (item.owner || '').toLowerCase();
                            return desc.includes(q) || cat.includes(q) || acc.includes(q) || owner.includes(q);
                        }

                        return true;
                    });
                },

                get filteredTotalBs() {
                    return this.filteredHistory.reduce((sum, item) => sum + parseFloat(item.amount || 0), 0);
                },

                get filteredTotalUsd() {
                    return this.filteredHistory.reduce((sum, item) => sum + parseFloat(item.amount_usd || 0), 0);
                },

                savingsRate() {
                    const inc = parseFloat(this.totals.income || 0);
                    const sav = parseFloat(this.totals.savings || 0);
                    if (inc <= 0) return 0;
                    return ((sav / inc) * 100).toFixed(1);
                },

                accountsTotalBs() {
                    return this.accountsSnapshot.reduce((acc, a) => {
                        if (a.currency === 'USD') {
                            return acc + (parseFloat(a.balance || 0) * <?= $exchangeRate ?>);
                        }
                        return acc + parseFloat(a.balance || 0);
                    }, 0);
                },

                accountsTotalUsd() {
                    return this.accountsSnapshot.reduce((acc, a) => {
                        if (a.currency === 'USD') {
                            return acc + parseFloat(a.balance || 0);
                        }
                        return acc + (parseFloat(a.balance || 0) / <?= $exchangeRate ?>);
                    }, 0);
                },

                formatBs(val) {
                    return 'Bs. ' + parseFloat(val || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },

                formatUsd(val) {
                    return '$ ' + parseFloat(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },

                formatCurrencyShort(bsVal, usdVal) {
                    if (this.currencyMode === 'usd') {
                        return this.formatUsd(usdVal);
                    } else if (this.currencyMode === 'bs') {
                        return this.formatBs(bsVal);
                    } else {
                        return this.formatBs(bsVal) + ' / ' + this.formatUsd(usdVal);
                    }
                },

                formatDate(dateStr) {
                    if (!dateStr) return '-';
                    const d = new Date(dateStr);
                    return d.toLocaleDateString('es-VE', { day: '2-digit', month: '2-digit', year: 'numeric' });
                },

                copyWhatsAppSummary() {
                    const netBs = parseFloat(this.totals.income || 0) - parseFloat(this.totals.expense || 0);
                    const netUsd = parseFloat(this.totals.income_usd || 0) - parseFloat(this.totals.expense_usd || 0);

                    let msg = `📊 *RESUMEN FINANCIERO - FINANZAHEX*\n`;
                    msg += `📅 *Período:* ${this.startDate} al ${this.endDate}\n`;
                    msg += `💵 *Tasa BCV:* Bs. <?= number_format($exchangeRate, 2, ',', '.') ?>\n\n`;
                    msg += `📈 *Ingresos Totales:* ${this.formatBs(this.totals.income)} (${this.formatUsd(this.totals.income_usd)})\n`;
                    msg += `📉 *Egresos Totales:* ${this.formatBs(this.totals.expense)} (${this.formatUsd(this.totals.expense_usd)})\n`;
                    msg += `🏛️ *Lo Ahorrado:* ${this.formatBs(this.totals.savings)} (${this.formatUsd(this.totals.savings_usd)})\n`;
                    msg += `⚖️ *Balance Neto:* ${this.formatBs(netBs)} (${this.formatUsd(netUsd)})\n`;
                    msg += `💎 *Tasa de Ahorro:* ${this.savingsRate()}%\n\n`;

                    if (this.printStats && this.printStats.order_count > 0) {
                        msg += `🖨️ *Impresiones:* ${this.printStats.order_count} órdenes | Facturado: ${this.formatBs(this.printStats.total_bs)} | Cobrado: ${this.formatBs(this.printStats.paid_bs)}\n\n`;
                    }

                    msg += `Generado automáticamente desde Finanzahex.`;

                    navigator.clipboard.writeText(msg).then(() => {
                        this.showToast('¡Resumen financiero copiado al portapapeles!');
                    }).catch(err => {
                        console.error('Error al copiar:', err);
                    });
                },

                showToast(message) {
                    this.toast.message = message;
                    this.toast.show = true;
                    setTimeout(() => {
                        this.toast.show = false;
                    }, 3500);
                }
            }
        }
    </script>
</body>
</html>

