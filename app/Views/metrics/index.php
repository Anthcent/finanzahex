<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Métricas y Reportes</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800" x-data="metricsApp()">

    <!-- Header -->
    <div class="fixed top-0 inset-x-0 bg-white/80 backdrop-blur-md z-40 border-b border-slate-100 h-16 flex items-center justify-between px-4">
        <div class="flex items-center gap-3">
            <a href="<?= base_url() ?>" class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-slate-100 text-slate-500 transition-colors">
                <span class="material-icons">arrow_back</span>
            </a>
            <h1 class="text-lg font-bold text-slate-800">Métricas y Reportes</h1>
        </div>
        <div class="flex items-center gap-2">
            <a :href="'<?= base_url('metrics/export') ?>?start=' + startDate + '&end=' + endDate" target="_blank" class="bg-emerald-600 text-white px-3 py-2 rounded-xl text-xs font-bold shadow-lg shadow-emerald-200 flex items-center gap-2 hover:bg-emerald-700 transition-all">
                <span class="material-icons text-sm">download</span>
                <span class="hidden sm:inline">Exportar Excel</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="pt-24 pb-12 px-4 max-w-6xl mx-auto space-y-6">

        <!-- Filters -->
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Desde</label>
                <input type="date" x-model="startDate" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm font-bold text-slate-700 outline-none focus:border-emerald-500">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Hasta</label>
                <input type="date" x-model="endDate" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm font-bold text-slate-700 outline-none focus:border-emerald-500">
            </div>
            <button @click="fetchData()" class="bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-md shadow-emerald-500/20 transition-all">
                Filtrar
            </button>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Income -->
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 relative overflow-hidden">
                <div class="absolute right-[-10px] top-[-10px] bg-emerald-50 w-24 h-24 rounded-full flex items-center justify-center opacity-50"></div>
                <p class="text-[10px] font-bold text-slate-400 uppercase relative z-10">Ingresos Totales</p>
                <h3 class="text-2xl font-black text-slate-800 mt-1 relative z-10" x-text="formatMoney(totals.income)"></h3>
            </div>
            <!-- Expense -->
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 relative overflow-hidden">
                <div class="absolute right-[-10px] top-[-10px] bg-rose-50 w-24 h-24 rounded-full flex items-center justify-center opacity-50"></div>
                <p class="text-[10px] font-bold text-slate-400 uppercase relative z-10">Egresos Totales</p>
                <h3 class="text-2xl font-black text-slate-800 mt-1 relative z-10" x-text="formatMoney(totals.expense)"></h3>
            </div>
             <!-- Balance -->
             <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 relative overflow-hidden">
                <div class="absolute right-[-10px] top-[-10px] bg-emerald-50 w-24 h-24 rounded-full flex items-center justify-center opacity-70"></div>
                <p class="text-[10px] font-bold text-slate-400 uppercase relative z-10">Balance Neto</p>
                <h3 class="text-2xl font-black text-emerald-600 mt-1 relative z-10" x-text="formatMoney(totals.income - totals.expense)"></h3>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Trend Chart -->
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100">
                <h3 class="font-bold text-slate-700 mb-4">Ingresos vs Egresos (Diario)</h3>
                <div class="h-64">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
            <!-- Category Chart -->
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100">
                <h3 class="font-bold text-slate-700 mb-4">Gastos por Categoría</h3>
                <div class="h-64 flex justify-center">
                    <canvas id="catChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Detailed Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-5 border-b border-slate-100">
                <h3 class="font-bold text-slate-800">Detalle de Transacciones</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                            <th class="p-4">Fecha</th>
                            <th class="p-4">Descripción</th>
                            <th class="p-4 hidden sm:table-cell">Categoría</th>
                            <th class="p-4 hidden md:table-cell">Cuenta</th>
                            <th class="p-4 text-right">Monto (Bs)</th>
                            <th class="p-4 text-right hidden sm:table-cell">Monto ($)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        <template x-for="row in history" :key="row.id">
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="p-4 font-medium text-slate-500 whitespace-nowrap" x-text="formatDate(row.created_at)"></td>
                                <td class="p-4">
                                    <p class="font-bold text-slate-700" x-text="row.description"></p>
                                    <div class="sm:hidden flex gap-2 mt-1">
                                        <span class="text-[10px] bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded" x-text="row.category_name"></span>
                                    </div>
                                </td>
                                <td class="p-4 hidden sm:table-cell text-slate-500" x-text="row.category_name || '-'"></td>
                                <td class="p-4 hidden md:table-cell text-slate-500" x-text="row.account_name || '-'"></td>
                                <td class="p-4 text-right font-bold" :class="row.type === 'income' ? 'text-emerald-600' : 'text-rose-600'">
                                    <span x-text="row.type === 'income' ? '+' : '-'"></span>
                                    <span x-text="'Bs. ' + parseFloat(row.amount).toLocaleString('es-VE', {minimumFractionDigits: 2})"></span>
                                </td>
                                <td class="p-4 text-right text-slate-400 font-medium hidden sm:table-cell" x-text="'$' + parseFloat(row.amount_usd).toLocaleString('en-US', {minimumFractionDigits: 2})"></td>
                            </tr>
                        </template>
                        <tr x-show="history.length === 0">
                            <td colspan="6" class="p-8 text-center text-slate-400">
                                Sin registros en este período
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

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
                            this.history = payload.history;
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
                                { label: 'Ingresos', data: data.trends.map(t => t.income), backgroundColor: '#10b981', borderRadius: 4 },
                                { label: 'Egresos', data: data.trends.map(t => t.expense), backgroundColor: '#f43f5e', borderRadius: 4 }
                            ]
                        },
                        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
                    });

                    // Category Chart
                    const ctxCat = document.getElementById('catChart');
                    if(this.charts.cat) this.charts.cat.destroy();

                    this.charts.cat = new Chart(ctxCat, {
                        type: 'doughnut',
                        data: {
                            labels: data.by_category.map(c => c.name || 'Otros'),
                            datasets: [{
                                data: data.by_category.map(c => c.total),
                                backgroundColor: ['#6366f1', '#ec4899', '#f59e0b', '#3b82f6', '#10b981', '#64748b'],
                                borderWidth: 0
                            }]
                        },
                        options: { responsive: true, maintainAspectRatio: false }
                    });
                },

                formatMoney(amount) {
                    return 'Bs. ' + parseFloat(amount || 0).toLocaleString('es-VE', { minimumFractionDigits: 2 });
                },

                formatDate(dateStr) {
                    if(!dateStr) return '-';
                    return new Date(dateStr).toLocaleDateString();
                }
            }
        }
    </script>
</body>
</html>
