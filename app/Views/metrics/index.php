<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Métricas & Reportes | Fi-Hex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        [x-cloak] { display: none !important; }
        .customize-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
        .customize-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .customize-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
        .safe-bottom { padding-bottom: max(1.25rem, env(safe-area-inset-bottom)); }
        .safe-top { padding-top: max(0.75rem, env(safe-area-inset-top)); }
    </style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800 antialiased" x-data="metricsApp()">

    <!-- Executive Top Nav Header -->
    <header class="sticky top-0 z-40 bg-gradient-to-r from-emerald-950 via-slate-900 to-teal-950 text-white shadow-xl border-b border-emerald-800/30 safe-top">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="<?= base_url() ?>" class="w-10 h-10 rounded-2xl bg-white/10 hover:bg-white/20 active:scale-95 flex items-center justify-center transition-all border border-white/10 text-white" title="Volver al Inicio">
                    <span class="material-icons text-xl">arrow_back</span>
                </a>
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-400 p-[1.5px] shadow-sm">
                        <div class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center">
                            <span class="text-xs font-black tracking-tight text-emerald-300">FW</span>
                        </div>
                    </div>
                    <div>
                        <h1 class="text-sm sm:text-base font-black tracking-tight text-white flex items-center gap-1.5">
                            <span>Métricas & Reportes</span>
                            <span class="bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 text-[9px] font-black uppercase px-2 py-0.5 rounded-md">Fintech</span>
                        </h1>
                        <p class="text-[10px] text-emerald-200/70 font-semibold">Análisis consolidado de ingresos y egresos</p>
                    </div>
                </div>
            </div>

            <!-- Export Action -->
            <div>
                <a :href="'<?= base_url('metrics/export') ?>?start=' + startDate + '&end=' + endDate" target="_blank" 
                   class="bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 active:scale-95 text-white px-3.5 py-2 rounded-xl text-xs font-black shadow-lg shadow-emerald-950/30 flex items-center gap-1.5 transition-all border border-emerald-500/30">
                    <span class="material-icons text-sm">download</span>
                    <span class="hidden sm:inline">Exportar Excel</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="max-w-6xl mx-auto px-4 py-6 space-y-6 safe-bottom">

        <!-- Executive Date Range Filter -->
        <div class="bg-white rounded-3xl p-4 sm:p-5 shadow-xs border border-slate-100">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-end gap-3 flex-1">
                    <div class="flex-1">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Fecha Desde</label>
                        <input type="date" x-model="startDate" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm font-bold text-slate-800 outline-none focus:border-emerald-500 focus:bg-white transition-all">
                    </div>
                    <div class="flex-1">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Fecha Hasta</label>
                        <input type="date" x-model="endDate" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm font-bold text-slate-800 outline-none focus:border-emerald-500 focus:bg-white transition-all">
                    </div>
                    <button @click="fetchData()" class="bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 active:scale-95 text-white px-5 py-2.5 rounded-xl font-black text-xs sm:text-sm shadow-md shadow-emerald-900/20 transition-all flex items-center justify-center gap-1.5 shrink-0">
                        <span class="material-icons text-sm">filter_alt</span>
                        <span>Filtrar</span>
                    </button>
                </div>

                <!-- Quick Preset Chips -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 customize-scrollbar shrink-0">
                    <button @click="setRange('thisMonth')" class="px-3 py-1.5 rounded-xl text-xs font-bold border border-slate-200 bg-slate-50 hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-200 transition-all whitespace-nowrap">Este Mes</button>
                    <button @click="setRange('lastMonth')" class="px-3 py-1.5 rounded-xl text-xs font-bold border border-slate-200 bg-slate-50 hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-200 transition-all whitespace-nowrap">Mes Anterior</button>
                    <button @click="setRange('last7Days')" class="px-3 py-1.5 rounded-xl text-xs font-bold border border-slate-200 bg-slate-50 hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-200 transition-all whitespace-nowrap">7 Días</button>
                    <button @click="setRange('thisYear')" class="px-3 py-1.5 rounded-xl text-xs font-bold border border-slate-200 bg-slate-50 hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-200 transition-all whitespace-nowrap">Este Año</button>
                </div>
            </div>
        </div>

        <!-- KPI Cards Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Income Card -->
            <div class="bg-gradient-to-br from-emerald-50/80 to-teal-50/40 rounded-3xl p-5 border border-emerald-200/70 shadow-2xs relative overflow-hidden">
                <div class="flex justify-between items-start">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-wider text-emerald-700 bg-emerald-100/80 px-2 py-0.5 rounded-md">Ingresos Totales</span>
                        <h3 class="text-2xl sm:text-3xl font-black text-slate-900 mt-2 tracking-tight" x-text="formatMoney(totals.income)"></h3>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-600 text-white flex items-center justify-center shadow-md shadow-emerald-900/20 shrink-0">
                        <span class="material-icons text-2xl">trending_up</span>
                    </div>
                </div>
                <div class="mt-3 text-[11px] font-bold text-emerald-700 flex items-center gap-1">
                    <span class="material-icons text-xs">verified</span>
                    <span>Flujo de entrada registrado</span>
                </div>
            </div>

            <!-- Expense Card -->
            <div class="bg-gradient-to-br from-rose-50/80 to-pink-50/40 rounded-3xl p-5 border border-rose-200/70 shadow-2xs relative overflow-hidden">
                <div class="flex justify-between items-start">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-wider text-rose-700 bg-rose-100/80 px-2 py-0.5 rounded-md">Egresos Totales</span>
                        <h3 class="text-2xl sm:text-3xl font-black text-slate-900 mt-2 tracking-tight" x-text="formatMoney(totals.expense)"></h3>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-rose-600 text-white flex items-center justify-center shadow-md shadow-rose-900/20 shrink-0">
                        <span class="material-icons text-2xl">trending_down</span>
                    </div>
                </div>
                <div class="mt-3 text-[11px] font-bold text-rose-700 flex items-center gap-1">
                    <span class="material-icons text-xs">money_off</span>
                    <span>Gastos y pagos contabilizados</span>
                </div>
            </div>

            <!-- Net Balance Card -->
            <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-2xs relative overflow-hidden"
                 :class="(totals.income - totals.expense) >= 0 ? 'bg-gradient-to-br from-teal-50/40 to-emerald-50/60' : 'bg-gradient-to-br from-rose-50/40 to-orange-50/60'">
                <div class="flex justify-between items-start">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-md"
                              :class="(totals.income - totals.expense) >= 0 ? 'text-teal-800 bg-teal-100/80' : 'text-rose-800 bg-rose-100/80'">
                            Balance Neto
                        </span>
                        <h3 class="text-2xl sm:text-3xl font-black mt-2 tracking-tight"
                            :class="(totals.income - totals.expense) >= 0 ? 'text-emerald-700' : 'text-rose-600'"
                            x-text="formatMoney(totals.income - totals.expense)"></h3>
                    </div>
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center shadow-md shrink-0 text-white"
                         :class="(totals.income - totals.expense) >= 0 ? 'bg-teal-600 shadow-teal-900/20' : 'bg-rose-600 shadow-rose-900/20'">
                        <span class="material-icons text-2xl" x-text="(totals.income - totals.expense) >= 0 ? 'account_balance_wallet' : 'warning'"></span>
                    </div>
                </div>
                <div class="mt-3 text-[11px] font-bold flex items-center gap-1"
                     :class="(totals.income - totals.expense) >= 0 ? 'text-emerald-700' : 'text-rose-600'">
                    <span class="material-icons text-xs" x-text="(totals.income - totals.expense) >= 0 ? 'check_circle' : 'error'"></span>
                    <span x-text="(totals.income - totals.expense) >= 0 ? 'Superávit en el período' : 'Déficit en el período'"></span>
                </div>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Trend Chart -->
            <div class="bg-white p-5 rounded-3xl shadow-xs border border-slate-100">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h3 class="font-black text-slate-800 text-sm sm:text-base">Ingresos vs Egresos (Diario)</h3>
                        <p class="text-[10px] text-slate-400 font-bold">Comportamiento diario de flujo de caja</p>
                    </div>
                    <div class="flex items-center gap-3 text-[11px] font-bold">
                        <span class="flex items-center gap-1 text-emerald-700"><span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span> Ingresos</span>
                        <span class="flex items-center gap-1 text-rose-600"><span class="w-2.5 h-2.5 rounded-full bg-rose-600"></span> Egresos</span>
                    </div>
                </div>
                <div class="h-64 sm:h-72">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>

            <!-- Category Chart -->
            <div class="bg-white p-5 rounded-3xl shadow-xs border border-slate-100">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h3 class="font-black text-slate-800 text-sm sm:text-base">Gastos por Categoría</h3>
                        <p class="text-[10px] text-slate-400 font-bold">Distribución proporcional de egresos</p>
                    </div>
                    <span class="material-icons text-slate-400 text-lg">pie_chart</span>
                </div>
                <div class="h-64 sm:h-72 flex justify-center items-center">
                    <canvas id="catChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Detailed Transactions List / Table -->
        <div class="bg-white rounded-3xl shadow-xs border border-slate-100 overflow-hidden">
            <div class="p-4 sm:p-5 border-b border-slate-100 flex justify-between items-center">
                <div>
                    <h3 class="font-black text-slate-900 text-sm sm:text-base">Detalle de Transacciones</h3>
                    <p class="text-[10px] text-slate-400 font-bold">Desglose de movimientos del período seleccionado</p>
                </div>
                <span class="text-xs font-black text-slate-500 bg-slate-100 px-2.5 py-1 rounded-lg" x-text="history.length + ' movimientos'"></span>
            </div>

            <!-- Desktop View: Sleek Table -->
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-wider border-b border-slate-100">
                            <th class="p-4">Fecha</th>
                            <th class="p-4">Descripción</th>
                            <th class="p-4">Categoría</th>
                            <th class="p-4">Cuenta</th>
                            <th class="p-4 text-right">Monto (Bs)</th>
                            <th class="p-4 text-right">Monto ($)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs sm:text-sm">
                        <template x-for="row in history" :key="row.id">
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="p-4 font-bold text-slate-500 whitespace-nowrap" x-text="formatDate(row.created_at)"></td>
                                <td class="p-4">
                                    <p class="font-black text-slate-800" x-text="row.description"></p>
                                </td>
                                <td class="p-4">
                                    <span class="text-[11px] font-bold bg-slate-100 text-slate-600 px-2 py-0.5 rounded-md" x-text="row.category_name || 'Sin Categoría'"></span>
                                </td>
                                <td class="p-4 font-bold text-slate-600" x-text="row.account_name || '-'"></td>
                                <td class="p-4 text-right font-black" :class="row.type === 'income' ? 'text-emerald-700' : 'text-rose-600'">
                                    <span x-text="row.type === 'income' ? '+' : '-'"></span>
                                    <span x-text="'Bs. ' + parseFloat(row.amount).toLocaleString('es-VE', {minimumFractionDigits: 2})"></span>
                                </td>
                                <td class="p-4 text-right font-black text-slate-500 whitespace-nowrap" x-text="'$' + parseFloat(row.amount_usd).toLocaleString('en-US', {minimumFractionDigits: 2})"></td>
                            </tr>
                        </template>
                        <tr x-show="history.length === 0">
                            <td colspan="6" class="p-8 text-center text-slate-400 font-bold">
                                No se encontraron registros en el período seleccionado.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile View: Modern Cards (Zero Horizontal Overflow) -->
            <div class="sm:hidden divide-y divide-slate-100">
                <template x-for="row in history" :key="row.id">
                    <div class="p-4 flex items-center justify-between gap-3 hover:bg-slate-50 transition-colors">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-1.5 mb-1">
                                <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-md"
                                      :class="row.type === 'income' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                                      x-text="row.type === 'income' ? 'Ingreso' : 'Egreso'"></span>
                                <span class="text-[10px] font-bold text-slate-400" x-text="formatDate(row.created_at)"></span>
                            </div>
                            <h4 class="font-black text-slate-800 text-xs truncate" x-text="row.description"></h4>
                            <div class="flex items-center gap-2 mt-1 text-[10px] text-slate-500 font-bold">
                                <span x-text="row.category_name || 'General'"></span>
                                <span>•</span>
                                <span x-text="row.account_name || 'Cuenta'"></span>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-xs font-black" :class="row.type === 'income' ? 'text-emerald-700' : 'text-rose-600'">
                                <span x-text="row.type === 'income' ? '+' : '-'"></span>
                                <span x-text="'Bs. ' + parseFloat(row.amount).toLocaleString('es-VE', {minimumFractionDigits: 2})"></span>
                            </p>
                            <p class="text-[10px] font-bold text-slate-400" x-text="'$' + parseFloat(row.amount_usd).toLocaleString('en-US', {minimumFractionDigits: 2})"></p>
                        </div>
                    </div>
                </template>
                <div x-show="history.length === 0" class="p-8 text-center text-slate-400 font-bold text-xs">
                    No se encontraron transacciones en este período.
                </div>
            </div>
        </div>

    </main>

    <script>
        function metricsApp() {
            return {
                startDate: '<?= date('Y-m-01') ?>',
                endDate: '<?= date('Y-m-t') ?>',
                totals: { income: 0, expense: 0 },
                history: [],
                charts: { trend: null, cat: null },

                init() {
                    this.fetchData();
                },

                setRange(type) {
                    const now = new Date();
                    if (type === 'thisMonth') {
                        const y = now.getFullYear();
                        const m = String(now.getMonth() + 1).padStart(2, '0');
                        const lastDay = new Date(y, now.getMonth() + 1, 0).getDate();
                        this.startDate = `${y}-${m}-01`;
                        this.endDate = `${y}-${m}-${lastDay}`;
                    } else if (type === 'lastMonth') {
                        const prevMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                        const y = prevMonth.getFullYear();
                        const m = String(prevMonth.getMonth() + 1).padStart(2, '0');
                        const lastDay = new Date(y, prevMonth.getMonth() + 1, 0).getDate();
                        this.startDate = `${y}-${m}-01`;
                        this.endDate = `${y}-${m}-${lastDay}`;
                    } else if (type === 'last7Days') {
                        const past = new Date();
                        past.setDate(now.getDate() - 7);
                        this.startDate = past.toISOString().split('T')[0];
                        this.endDate = now.toISOString().split('T')[0];
                    } else if (type === 'thisYear') {
                        const y = now.getFullYear();
                        this.startDate = `${y}-01-01`;
                        this.endDate = `${y}-12-31`;
                    }
                    this.fetchData();
                },

                async fetchData() {
                    try {
                        let res = await fetch('<?= base_url('metrics/fetch') ?>', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ start: this.startDate, end: this.endDate })
                        });
                        let payload = await res.json();
                        
                        if(payload.status === 'success') {
                            this.totals = payload.data.totals;
                            this.history = payload.history || [];
                            this.updateCharts(payload.data);
                        }
                    } catch(e) { console.error(e); }
                },

                updateCharts(data) {
                    // Trend Chart
                    const ctxTrend = document.getElementById('trendChart');
                    if(this.charts.trend) this.charts.trend.destroy();
                    
                    this.charts.trend = new Chart(ctxTrend, {
                        type: 'bar',
                        data: {
                            labels: data.trends.map(t => t.date),
                            datasets: [
                                { 
                                    label: 'Ingresos', 
                                    data: data.trends.map(t => t.income), 
                                    backgroundColor: '#059669', 
                                    borderRadius: 6 
                                },
                                { 
                                    label: 'Egresos', 
                                    data: data.trends.map(t => t.expense), 
                                    backgroundColor: '#e11d48', 
                                    borderRadius: 6 
                                }
                            ]
                        },
                        options: { 
                            responsive: true, 
                            maintainAspectRatio: false, 
                            plugins: {
                                legend: { display: false }
                            },
                            scales: { 
                                x: { grid: { display: false } },
                                y: { beginAtZero: true, grid: { color: '#f1f5f9' } } 
                            } 
                        }
                    });

                    // Category Chart
                    const ctxCat = document.getElementById('catChart');
                    if(this.charts.cat) this.charts.cat.destroy();

                    const categoryColors = ['#059669', '#2563eb', '#d97706', '#7c3aed', '#e11d48', '#0d9488', '#64748b'];

                    this.charts.cat = new Chart(ctxCat, {
                        type: 'doughnut',
                        data: {
                            labels: data.by_category.map(c => c.name || 'Otros'),
                            datasets: [{
                                data: data.by_category.map(c => c.total),
                                backgroundColor: categoryColors,
                                borderWidth: 2,
                                borderColor: '#ffffff'
                            }]
                        },
                        options: { 
                            responsive: true, 
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        boxWidth: 12,
                                        font: { family: 'Plus Jakarta Sans', size: 11, weight: 'bold' }
                                    }
                                }
                            },
                            cutout: '68%'
                        }
                    });
                },

                formatMoney(amount) {
                    return 'Bs. ' + parseFloat(amount || 0).toLocaleString('es-VE', { minimumFractionDigits: 2 });
                },

                formatDate(dateStr) {
                    if(!dateStr) return '-';
                    return new Date(dateStr).toLocaleDateString('es-VE', { day: '2-digit', month: '2-digit', year: 'numeric' });
                }
            }
        }
    </script>
</body>
</html>
