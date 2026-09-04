<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Configuración - Fi-Hex Wallet</title>
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
<body class="bg-slate-900 text-slate-100 min-h-screen flex flex-col antialiased selection:bg-emerald-500 selection:text-white" x-data="configApp()">

    <!-- Executive Header -->
    <header class="bg-gradient-to-r from-emerald-950 via-slate-900 to-teal-950 border-b border-emerald-500/20 sticky top-0 z-30 backdrop-blur-xl bg-opacity-95 safe-top shadow-lg shadow-black/20">
        <div class="max-w-2xl mx-auto px-4 py-3 sm:py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="<?= base_url() ?>" class="w-10 h-10 rounded-xl bg-white/10 hover:bg-white/15 active:scale-95 border border-white/10 flex items-center justify-center text-slate-200 hover:text-white transition-all shadow-inner">
                    <span class="material-icons text-xl">arrow_back</span>
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="font-extrabold text-base sm:text-lg text-white tracking-tight leading-none">
                            Configuración
                        </h1>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                            Ajustes
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-400 font-medium mt-0.5">Gestión de cuentas, categorías y respaldo de datos</p>
                </div>
            </div>
            
            <div class="w-9 h-9 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                <span class="material-icons text-lg">tune</span>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-2xl mx-auto w-full px-4 py-5 space-y-5 safe-bottom">

        <!-- Accounts & Balance Management -->
        <section class="bg-slate-800/80 border border-slate-700/70 rounded-3xl p-5 shadow-md backdrop-blur-md">
            <div class="flex items-center justify-between pb-3 border-b border-slate-700/60 mb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center">
                        <span class="material-icons text-base">account_balance</span>
                    </div>
                    <div>
                        <h2 class="text-sm sm:text-base font-extrabold text-white">Cuentas y Fondos</h2>
                        <p class="text-[10px] text-slate-400">Ajusta los saldos iniciales o agrega nuevas entidades</p>
                    </div>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-900/80 text-slate-400 border border-slate-700/60" x-text="accounts.length + ' cuentas'"></span>
            </div>

            <!-- Accounts List -->
            <div class="space-y-2.5 max-h-72 overflow-y-auto customize-scrollbar pr-1">
                <template x-for="acc in accounts" :key="acc.id">
                    <div class="flex items-center justify-between bg-slate-900/80 border border-slate-700/60 hover:border-slate-600 p-3 rounded-2xl transition-all">
                        <div class="flex items-center gap-2.5 min-w-0 pr-2">
                            <div class="w-8 h-8 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center text-slate-300 shrink-0">
                                <span class="material-icons text-sm">payments</span>
                            </div>
                            <span class="font-bold text-xs sm:text-sm text-slate-200 truncate" x-text="acc.name"></span>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <!-- Balance Direct Editor -->
                            <div class="flex items-center bg-slate-800 border border-slate-700 rounded-xl px-2.5 py-1 focus-within:border-emerald-500 focus-within:ring-2 focus-within:ring-emerald-500/20 transition-all">
                                <span class="text-[10px] font-bold text-slate-400 mr-1.5">Bs.</span>
                                <input type="number" step="0.01" x-model="acc.balance" @change="updateBalance(acc)" 
                                       class="w-24 sm:w-28 text-right text-xs sm:text-sm font-black font-mono text-emerald-400 bg-transparent focus:outline-none">
                            </div>

                            <!-- Delete Button -->
                            <button @click="deleteAccount(acc.id)" class="w-8 h-8 rounded-xl bg-slate-800/80 hover:bg-rose-950/60 border border-slate-700/60 hover:border-rose-500/30 text-slate-400 hover:text-rose-400 flex items-center justify-center transition-colors active:scale-95" title="Eliminar cuenta">
                                <span class="material-icons text-base">delete</span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Add Account Bar -->
            <div class="mt-4 pt-3 border-t border-slate-700/60 flex gap-2">
                <input type="text" x-model="newAccount" placeholder="Nombre de cuenta (ej. Banesco, Binance)..." 
                       class="flex-1 bg-slate-900 border border-slate-700/80 rounded-2xl px-4 py-2.5 text-xs text-white placeholder-slate-500 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-all shadow-inner"
                       @keyup.enter="addAccount()">
                <button @click="addAccount()" class="px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-500 hover:to-teal-600 text-white rounded-2xl font-extrabold text-xs shadow-lg shadow-emerald-900/30 active:scale-95 transition flex items-center gap-1 shrink-0">
                    <span class="material-icons text-sm">add</span>
                    <span class="hidden sm:inline">Agregar</span>
                </button>
            </div>
        </section>

        <!-- Categories Section -->
        <section class="bg-slate-800/80 border border-slate-700/70 rounded-3xl p-5 shadow-md backdrop-blur-md">
            <div class="flex items-center justify-between pb-3 border-b border-slate-700/60 mb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-blue-500/20 text-blue-400 flex items-center justify-center">
                        <span class="material-icons text-base">label</span>
                    </div>
                    <div>
                        <h2 class="text-sm sm:text-base font-extrabold text-white">Categorías de Operación</h2>
                        <p class="text-[10px] text-slate-400">Clasificación para gastos, compras e ingresos</p>
                    </div>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-900/80 text-slate-400 border border-slate-700/60" x-text="categories.length + ' categorías'"></span>
            </div>

            <!-- Categories List -->
            <div class="space-y-2 max-h-60 overflow-y-auto customize-scrollbar pr-1">
                <template x-for="cat in categories" :key="cat.id">
                    <div class="flex justify-between items-center bg-slate-900/80 border border-slate-700/60 hover:border-slate-600 p-2.5 sm:p-3 rounded-2xl transition-all">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                            <span class="text-xs sm:text-sm font-semibold text-slate-200" x-text="cat.name"></span>
                        </div>
                        <button @click="deleteCategory(cat.id)" class="w-7 h-7 rounded-lg bg-slate-800 hover:bg-rose-950/60 text-slate-400 hover:text-rose-400 flex items-center justify-center transition-colors active:scale-95" title="Eliminar categoría">
                            <span class="material-icons text-sm">delete</span>
                        </button>
                    </div>
                </template>
            </div>

            <!-- Add Category Bar -->
            <div class="mt-4 pt-3 border-t border-slate-700/60 flex gap-2">
                <input type="text" x-model="newCategory" placeholder="Nueva categoría (ej. Alimentación, Servicios)..." 
                       class="flex-1 bg-slate-900 border border-slate-700/80 rounded-2xl px-4 py-2.5 text-xs text-white placeholder-slate-500 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all shadow-inner"
                       @keyup.enter="addCategory()">
                <button @click="addCategory()" class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-500 hover:to-indigo-600 text-white rounded-2xl font-extrabold text-xs shadow-lg shadow-blue-900/30 active:scale-95 transition flex items-center gap-1 shrink-0">
                    <span class="material-icons text-sm">add</span>
                    <span class="hidden sm:inline">Crear</span>
                </button>
            </div>
        </section>

        <!-- Data Export & Backups -->
        <section class="bg-slate-800/80 border border-slate-700/70 rounded-3xl p-5 shadow-md backdrop-blur-md">
            <div class="flex items-center gap-2.5 pb-3 border-b border-slate-700/60 mb-4">
                <div class="w-8 h-8 rounded-xl bg-teal-500/20 text-teal-400 flex items-center justify-center">
                    <span class="material-icons text-base">cloud_download</span>
                </div>
                <div>
                    <h2 class="text-sm sm:text-base font-extrabold text-white">Respaldo y Gestión de Datos</h2>
                    <p class="text-[10px] text-slate-400">Descarga tu información en hojas de cálculo</p>
                </div>
            </div>

            <div class="space-y-3">
                <a href="<?= base_url('config/export') ?>" target="_blank" 
                   class="flex items-center justify-between p-4 bg-slate-900/80 hover:bg-slate-900 border border-slate-700/60 hover:border-emerald-500/40 rounded-2xl transition-all cursor-pointer group active:scale-[0.99] shadow-inner">
                    <div class="flex items-center gap-3.5">
                        <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 group-hover:scale-105 transition-transform">
                            <span class="material-icons text-xl">table_view</span>
                        </div>
                        <div>
                            <h3 class="text-xs sm:text-sm font-extrabold text-white group-hover:text-emerald-300 transition-colors">Exportar Todas las Transacciones</h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">Descarga el historial completo en formato compatible CSV/Excel</p>
                        </div>
                    </div>
                    <div class="w-8 h-8 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center text-slate-400 group-hover:text-emerald-400 group-hover:border-emerald-500/30 transition-colors">
                        <span class="material-icons text-base">download</span>
                    </div>
                </a>
            </div>
        </section>

    </main>

    <script>
        function configApp() {
            return {
                accounts: [],
                categories: [],
                newAccount: '',
                newCategory: '',

                init() {
                    this.fetchData();
                },

                async fetchData() {
                    try {
                        let res = await fetch('<?= base_url('config/get-data') ?>');
                        let data = await res.json();
                        this.accounts = data.accounts || [];
                        this.categories = data.categories || [];
                    } catch(e) {
                        console.error(e);
                    }
                },

                async addAccount() {
                    if(!this.newAccount.trim()) return;
                    await fetch('<?= base_url('config/add-account') ?>', {
                        method: 'POST',
                        body: JSON.stringify({ name: this.newAccount.trim() })
                    });
                    this.newAccount = '';
                    this.fetchData();
                },

                async deleteAccount(id) {
                    if(!confirm('¿Estás seguro de eliminar esta cuenta?')) return;
                    await fetch('<?= base_url('config/delete-account/') ?>' + id);
                    this.fetchData();
                },

                async updateBalance(acc) {
                    await fetch('<?= base_url('config/update-balance') ?>', {
                        method: 'POST',
                        body: JSON.stringify({ id: acc.id, balance: acc.balance })
                    });
                },

                async addCategory() {
                    if(!this.newCategory.trim()) return;
                    await fetch('<?= base_url('config/add-category') ?>', {
                        method: 'POST',
                        body: JSON.stringify({ name: this.newCategory.trim() })
                    });
                    this.newCategory = '';
                    this.fetchData();
                },
                
                async deleteCategory(id) {
                    if(!confirm('¿Estás seguro de eliminar esta categoría?')) return;
                    await fetch('<?= base_url('config/delete-category/') ?>' + id);
                    this.fetchData();
                }
            }
        }
    </script>
</body>
</html>
