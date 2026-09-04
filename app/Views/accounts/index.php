<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Mis Cuentas - Fi-Hex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <meta name="theme-color" content="#047857">
    <style>
        body { font-family: 'Plus Jakarta Sans', 'Outfit', sans-serif; }
        [x-cloak] { display: none !important; }
        .customize-scrollbar::-webkit-scrollbar { width: 5px; }
        .customize-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .customize-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
        @keyframes slide-up { from { transform: translateY(100%); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .animate-slide-up { animation: slide-up 0.28s cubic-bezier(0.16, 1, 0.3, 1); }
        .safe-bottom { padding-bottom: env(safe-area-inset-bottom, 1rem); }
    </style>
</head>
<body class="bg-gradient-to-br from-emerald-50/60 via-slate-50 to-teal-50/40 min-h-screen flex flex-col text-slate-800 antialiased" x-data="accountsApp()">

    <!-- Header -->
    <header class="bg-white/90 backdrop-blur-xl border-b border-slate-200/80 sticky top-0 z-30 shadow-xs">
        <div class="max-w-5xl mx-auto px-4 h-16 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5 min-w-0">
                <a href="<?= base_url() ?>" class="w-9 h-9 flex items-center justify-center rounded-2xl bg-slate-100/80 hover:bg-emerald-50 text-slate-600 hover:text-emerald-700 transition-colors border border-slate-200/60 active:scale-95 shrink-0" title="Volver al inicio">
                    <span class="material-icons text-xl">arrow_back</span>
                </a>
                <div class="w-9 h-9 rounded-2xl bg-gradient-to-br from-emerald-600 to-teal-700 text-white flex items-center justify-center shadow-md shadow-emerald-950/20 ring-1 ring-emerald-400/40 shrink-0">
                    <span class="material-icons text-lg">account_balance_wallet</span>
                </div>
                <div class="leading-tight min-w-0">
                    <h1 class="font-black text-slate-900 tracking-tight text-sm sm:text-base truncate">
                        Mis <span class="bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent">Cuentas</span>
                    </h1>
                    <p class="text-[9px] font-bold text-slate-400 hidden sm:block">Saldos, fondos y transferencias</p>
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <button @click="showTransferModal = true" class="w-9 h-9 flex items-center justify-center rounded-2xl bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200/60 transition-all active:scale-95" title="Transferir entre Cuentas">
                    <span class="material-icons text-base">sync_alt</span>
                </button>
                <button @click="showAddModal = true" class="bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white px-3 sm:px-4 py-2 rounded-xl text-xs sm:text-sm font-black shadow-md shadow-emerald-950/20 transition-all flex items-center gap-1 active:scale-95">
                    <span class="material-icons text-base">add</span>
                    <span class="hidden sm:inline">Nueva Cuenta</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 max-w-5xl mx-auto w-full p-4 sm:p-6 space-y-6 pb-28">
        
        <!-- Total Wealth Card (Executive Satin Dark Gradient) -->
        <div class="bg-gradient-to-br from-slate-950 via-emerald-950 to-slate-900 border border-emerald-500/25 rounded-3xl p-5 sm:p-6 text-white shadow-xl relative overflow-hidden">
            <div class="absolute -right-8 -bottom-8 w-36 h-36 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>
            
            <div class="flex justify-between items-start mb-2">
                <div>
                    <span class="text-[10px] font-black text-emerald-300 uppercase tracking-widest block mb-1">Patrimonio Total Estimado</span>
                    <span class="text-3xl sm:text-4xl font-black tracking-tight" x-text="formatMoney(totalBs)"></span>
                    <p class="text-xs sm:text-sm text-emerald-200/80 font-bold mt-0.5" x-text="'≈ ' + formatUsd(totalUsd)"></p>
                </div>
                <div class="w-10 h-10 rounded-2xl bg-emerald-500/20 border border-emerald-400/30 flex items-center justify-center text-emerald-300">
                    <span class="material-icons text-xl">savings</span>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-white/10 flex items-center justify-between text-[11px] text-slate-300">
                <div class="flex items-center gap-1 text-emerald-400 font-bold">
                    <span class="material-icons text-xs">sync</span>
                    <span>Tasa BCV Oficial:</span>
                    <span class="text-white font-mono font-black" x-text="exchangeRate"></span>
                </div>
                <span class="text-[10px] text-slate-400">Consolidado automático</span>
            </div>
        </div>

        <!-- Main Accounts Section -->
        <div>
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-black text-slate-900 text-xs sm:text-sm uppercase tracking-wider flex items-center gap-2">
                    <span class="material-icons text-emerald-600 text-base">account_balance</span>
                    <span>Cuentas Principales</span>
                </h2>
                <span class="text-xs font-bold text-slate-400" x-text="accounts.filter(a => a.type !== 'temporary').length + ' cuentas'"></span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5">
                <template x-for="acc in accounts.filter(a => a.type !== 'temporary')" :key="acc.id">
                    <div class="bg-white/95 backdrop-blur-md rounded-2xl p-4 shadow-2xs hover:shadow-md border border-slate-200/80 relative group transition-all">
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex items-center gap-2.5 min-w-0 flex-1 pr-2">
                                <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0">
                                    <span class="material-icons text-lg">account_balance_wallet</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3 class="font-black text-slate-800 text-sm leading-tight truncate" x-text="acc.name"></h3>
                                    <span class="inline-block bg-slate-100 text-slate-500 text-[10px] font-black uppercase px-2 py-0.5 rounded-lg mt-0.5" x-text="acc.currency"></span>
                                </div>
                            </div>
                            <button @click="confirmDelete(acc)" class="w-7 h-7 rounded-lg text-slate-300 hover:text-rose-600 hover:bg-rose-50 flex items-center justify-center transition-colors" title="Eliminar cuenta">
                                <span class="material-icons text-sm">close</span>
                            </button>
                        </div>
                        
                        <div class="p-3 bg-slate-50/80 rounded-xl border border-slate-100">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-0.5">Saldo Disponible</p>
                            <span class="font-black text-lg sm:text-xl text-slate-900 tracking-tight" x-text="formatMoney(acc.balance, acc.currency)"></span>
                            <div class="flex items-center justify-between mt-1 text-[11px] font-bold">
                                <span class="text-emerald-700" x-show="acc.currency !== 'USD'" x-text="'≈ $' + formatValUsd(acc.balance)"></span>
                                <span class="text-emerald-700" x-show="acc.currency === 'USD'" x-text="acc.tenure_type === 'digital' ? 'Digital (Zelle/Binance)' : 'Efectivo Físico'"></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Temporary Funds Section -->
        <div class="pt-2">
            <div class="flex justify-between items-center mb-3">
                <h2 class="font-black text-amber-700 text-xs sm:text-sm uppercase tracking-wider flex items-center gap-1.5">
                    <span class="material-icons text-amber-600 text-base">savings</span>
                    <span>Fondos Temporales & Apartados</span>
                </h2>
                
                <!-- Filter Toggle -->
                <button @click="showClosed = !showClosed" class="flex items-center gap-1 text-xs font-bold text-slate-500 hover:text-amber-700 transition">
                    <span class="material-icons text-sm" :class="showClosed ? 'text-amber-600' : 'text-slate-400'">list_alt</span>
                    <span x-text="showClosed ? 'Ocultar Cerrados' : 'Ver Historial'"></span>
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5">
                <template x-for="acc in accounts.filter(a => a.type === 'temporary' && (a.status === 'active' || showClosed))" :key="acc.id">
                    <div :class="acc.status === 'closed' ? 'bg-slate-100/90 border-slate-200 opacity-75' : 'bg-gradient-to-br from-amber-50/60 to-orange-50/40 border-amber-200/70 shadow-2xs'" 
                         class="rounded-2xl p-4 border relative overflow-hidden transition-all duration-300">
                        
                        <div class="relative z-10 flex justify-between items-start mb-2.5">
                            <div class="flex items-center gap-2.5 min-w-0 flex-1 pr-2">
                                <div class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 shadow-2xs"
                                     :class="acc.status === 'closed' ? 'bg-slate-200 text-slate-500' : 'bg-amber-100 text-amber-700'">
                                    <span class="material-icons text-lg" x-text="acc.status === 'closed' ? 'lock' : 'work_history'"></span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3 class="font-black text-slate-800 text-sm leading-tight truncate" x-text="acc.name"></h3>
                                    <p class="text-[10px] font-black uppercase tracking-wider mt-0.5" 
                                       :class="acc.status === 'closed' ? 'text-slate-400' : 'text-amber-700'"
                                       x-text="acc.status === 'closed' ? 'Fondo Cerrado' : 'Fondo Activo'">
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Liquidar Button (Only for Active) -->
                            <button x-show="acc.status === 'active'" 
                                    @click="confirmLiquidation(acc)" 
                                    class="bg-amber-600 hover:bg-amber-700 text-white text-[11px] font-black px-3 py-1.5 rounded-xl shadow-xs transition-all flex items-center gap-1 active:scale-95">
                                <span class="material-icons text-xs">published_with_changes</span>
                                <span>Liquidar</span>
                            </button>
                            
                            <!-- Static Badge (For Closed) -->
                            <span x-show="acc.status === 'closed'" class="text-[10px] font-black text-slate-500 bg-slate-200 px-2.5 py-1 rounded-lg uppercase">
                                Liquidado
                            </span>
                        </div>
                        
                        <div class="relative z-10 mt-3 p-3 bg-white/80 rounded-xl border border-amber-100 text-right">
                             <span class="text-xl font-black tracking-tight" 
                                   :class="acc.status === 'closed' ? 'text-slate-400 line-through' : 'text-slate-900'"
                                   x-text="formatMoney(acc.balance)"></span>
                             <p class="text-[11px] font-bold" 
                                :class="acc.status === 'closed' ? 'text-slate-400' : 'text-amber-700'"
                                x-text="'≈ $' + formatValUsd(acc.balance)"></p>
                        </div>
                    </div>
                </template>
                
                <!-- Empty State -->
                <div x-show="accounts.filter(a => a.type === 'temporary').length === 0" class="sm:col-span-2 lg:col-span-3 text-center py-12 text-slate-400 bg-white/60 rounded-3xl border border-dashed border-slate-200">
                    <span class="material-icons text-3xl text-slate-300 mb-1">savings</span>
                    <p class="text-xs font-bold">No hay fondos temporales creados.</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Floating Action Speed-Dial Button -->
    <div class="fixed bottom-6 right-6 flex flex-col space-y-2.5 z-30 safe-bottom">
        <button @click="showTempModal = true" class="w-12 h-12 rounded-2xl bg-amber-600 text-white shadow-lg shadow-amber-900/20 hover:bg-amber-700 transition-all flex items-center justify-center active:scale-95" title="Crear Fondo Temporal">
            <span class="material-icons text-xl">savings</span>
        </button>
        <button @click="showTransferModal = true" class="w-12 h-12 rounded-2xl bg-blue-600 text-white shadow-lg shadow-blue-900/20 hover:bg-blue-700 transition-all flex items-center justify-center active:scale-95" title="Transferir entre Cuentas">
            <span class="material-icons text-xl">sync_alt</span>
        </button>
        <button @click="showAddModal = true" class="w-13 h-13 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-700 text-white shadow-xl shadow-emerald-950/30 hover:from-emerald-700 hover:to-teal-800 transition-all flex items-center justify-center active:scale-95" title="Nueva Cuenta">
            <span class="material-icons text-2xl">add</span>
        </button>
    </div>

    <!-- ==================== RESPONSIVE MODALS ==================== -->

    <!-- Confirmation Modal (Liquidation) -->
    <div x-show="showConfirmModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4" x-cloak>
        <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-xs" @click="showConfirmModal = false"></div>
        <div class="bg-white rounded-t-[2.5rem] sm:rounded-3xl shadow-2xl w-full sm:max-w-sm relative z-10 p-6 space-y-4 animate-slide-up text-center safe-bottom">
            <div class="w-14 h-14 bg-amber-100 text-amber-700 rounded-2xl flex items-center justify-center mx-auto">
                <span class="material-icons text-3xl">published_with_changes</span>
            </div>
            <div>
                <h2 class="text-lg font-black text-slate-900">¿Liquidar Fondo?</h2>
                <p class="text-xs text-slate-500 mt-1">
                    El saldo de <span class="font-bold text-slate-800" x-text="selectedAccount?.name"></span> 
                    (<span class="font-black text-emerald-700" x-text="formatMoney(selectedAccount?.balance || 0)"></span>)
                    será devuelto a su cuenta de origen.
                </p>
            </div>
            <div class="flex gap-2.5 pt-2">
                <button @click="showConfirmModal = false" class="flex-1 py-3 rounded-xl text-slate-600 font-bold bg-slate-100 hover:bg-slate-200 transition-colors text-xs sm:text-sm">Cancelar</button>
                <button @click="executeLiquidation()" class="flex-1 py-3 rounded-xl text-white font-black bg-amber-600 hover:bg-amber-700 shadow-md transition-all text-xs sm:text-sm">Sí, Liquidar</button>
            </div>
        </div>
    </div>

    <!-- Delete Account Modal -->
    <div x-show="showDeleteModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4" x-cloak>
        <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-xs" @click="showDeleteModal = false"></div>
        <div class="bg-white rounded-t-[2.5rem] sm:rounded-3xl shadow-2xl w-full sm:max-w-sm relative z-10 p-6 space-y-4 animate-slide-up text-center safe-bottom">
            <div class="w-14 h-14 bg-rose-100 text-rose-600 rounded-2xl flex items-center justify-center mx-auto">
                <span class="material-icons text-3xl">delete_forever</span>
            </div>
            <div>
                <h2 class="text-lg font-black text-slate-900">¿Eliminar Cuenta?</h2>
                <p class="text-xs text-slate-500 mt-1">
                    Se eliminará la cuenta <span class="font-black text-slate-800" x-text="selectedAccount?.name"></span>.
                    <span class="block mt-1 font-bold text-rose-600">Esta acción es irreversible.</span>
                </p>
            </div>
            <div class="flex gap-2.5 pt-2">
                <button @click="showDeleteModal = false" class="flex-1 py-3 rounded-xl text-slate-600 font-bold bg-slate-100 hover:bg-slate-200 transition-colors text-xs sm:text-sm">Cancelar</button>
                <button @click="executeDelete()" class="flex-1 py-3 rounded-xl text-white font-black bg-rose-600 hover:bg-rose-700 shadow-md transition-all text-xs sm:text-sm">Sí, Eliminar</button>
            </div>
        </div>
    </div>

    <!-- Add Account Modal -->
    <div x-show="showAddModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4" x-cloak>
        <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-xs" @click="showAddModal = false"></div>
        <div class="bg-white rounded-t-[2.5rem] sm:rounded-3xl shadow-2xl w-full sm:max-w-sm relative z-10 p-6 space-y-4 animate-slide-up safe-bottom">
            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                <h2 class="text-base font-black text-slate-900">Nueva Cuenta Principal</h2>
                <button @click="showAddModal = false" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 flex items-center justify-center">
                    <span class="material-icons text-base">close</span>
                </button>
            </div>

            <div class="space-y-3">
                <input type="text" x-model="newAccountName" placeholder="Nombre (ej. Banesco, Efectivo)" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm font-bold text-slate-800 focus:border-emerald-500 outline-none">
                <input type="number" step="0.01" x-model="newAccountBalance" placeholder="Monto Inicial (0.00)" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm font-black text-slate-800 focus:border-emerald-500 outline-none">
                
                <div class="grid grid-cols-2 gap-2">
                    <select x-model="newAccountCurrency" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-xs font-bold text-slate-800 focus:border-emerald-500 outline-none">
                        <option value="Bs">Bolívares (Bs)</option>
                        <option value="USD">Dólares ($)</option>
                    </select>
                    <select x-model="newAccountTenure" x-show="newAccountCurrency === 'USD'" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-xs font-bold text-slate-800 focus:border-emerald-500 outline-none">
                        <option value="none" disabled selected>Tenencia</option>
                        <option value="digital">Digital</option>
                        <option value="physical">Efectivo</option>
                    </select>
                </div>
            </div>

            <div class="flex gap-2.5 pt-2">
                <button @click="showAddModal = false" class="flex-1 py-3 rounded-xl text-slate-600 font-bold bg-slate-100 hover:bg-slate-200 transition-colors text-xs sm:text-sm">Cancelar</button>
                <button @click="addAccount()" class="flex-1 py-3 rounded-xl text-white font-black bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 shadow-md transition-all text-xs sm:text-sm">Crear Cuenta</button>
            </div>
        </div>
    </div>

    <!-- Create Temp Fund Modal -->
    <div x-show="showTempModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4" x-cloak>
        <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-xs" @click="showTempModal = false"></div>
        <div class="bg-white rounded-t-[2.5rem] sm:rounded-3xl shadow-2xl w-full sm:max-w-sm relative z-10 p-6 space-y-4 animate-slide-up safe-bottom">
            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-base font-black text-slate-900">Nuevo Fondo Temporal</h2>
                    <p class="text-[10px] text-slate-400">Dinero apartado para compras o propósitos específicos.</p>
                </div>
                <button @click="showTempModal = false" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 flex items-center justify-center">
                    <span class="material-icons text-base">close</span>
                </button>
            </div>
            
            <div class="space-y-3">
                <input type="text" x-model="tempName" placeholder="Nombre (ej. Compras de Inventario)" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm font-bold text-slate-800 focus:border-amber-500 outline-none">
                
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Tomar dinero de:</label>
                    <select x-model="tempSource" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-xs font-bold text-slate-800 focus:border-amber-500 outline-none">
                        <option value="">Seleccione Cuenta Origen</option>
                        <template x-for="a in accounts.filter(x => x.type !== 'temporary')" :key="a.id">
                            <option :value="a.id" x-text="a.name + ' (' + formatMoney(a.balance, a.currency) + ')'"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Monto a Asignar:</label>
                    <input type="number" step="0.01" x-model="tempAmount" placeholder="0.00" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm font-black text-slate-800 focus:border-amber-500 outline-none">
                </div>
            </div>
            
            <div class="flex gap-2.5 pt-2">
                <button @click="showTempModal = false" class="flex-1 py-3 rounded-xl text-slate-600 font-bold bg-slate-100 hover:bg-slate-200 transition-colors text-xs sm:text-sm">Cancelar</button>
                <button @click="createTempFund()" class="flex-1 py-3 rounded-xl text-white font-black bg-amber-600 hover:bg-amber-700 shadow-md transition-all text-xs sm:text-sm">Crear Fondo</button>
            </div>
        </div>
    </div>

    <!-- Transfer Modal -->
    <div x-show="showTransferModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4" x-cloak>
        <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-xs" @click="showTransferModal = false"></div>
        <div class="bg-white rounded-t-[2.5rem] sm:rounded-3xl shadow-2xl w-full sm:max-w-sm relative z-10 p-6 space-y-4 max-h-[92vh] overflow-y-auto customize-scrollbar animate-slide-up safe-bottom">
            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-base font-black text-slate-900 flex items-center gap-1.5">
                        <span class="material-icons text-blue-600 text-lg">sync_alt</span>
                        <span>Transferencia Rápida</span>
                    </h2>
                    <p class="text-[10px] text-slate-400">Mueve fondos entre tus cuentas principales.</p>
                </div>
                <button @click="showTransferModal = false" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 flex items-center justify-center">
                    <span class="material-icons text-base">close</span>
                </button>
            </div>
            
            <div class="space-y-3">
                <!-- Source -->
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Desde (Origen):</label>
                    <select x-model="transferSource" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-xs font-bold text-slate-800 focus:border-blue-500 outline-none">
                        <option value="">Seleccione Cuenta Origen</option>
                        <template x-for="acc in accounts.filter(a => a.type !== 'temporary')" :key="acc.id">
                            <option :value="acc.id" x-text="acc.name + ' (' + formatMoney(acc.balance, acc.currency) + ')'"></option>
                        </template>
                    </select>
                </div>

                <!-- Destination -->
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Hacia (Destino):</label>
                    <select x-model="transferDest" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-xs font-bold text-slate-800 focus:border-blue-500 outline-none">
                        <option value="">Seleccione Cuenta Destino</option>
                        <template x-for="acc in availableDestinations" :key="acc.id">
                            <option :value="acc.id" x-text="acc.name + ' (' + acc.currency + ')'"></option>
                        </template>
                    </select>
                </div>

                <!-- Category -->
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Categoría:</label>
                    <select x-model="transferCategory" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-xs font-bold text-slate-800 focus:border-blue-500 outline-none">
                        <option value="">Seleccione Categoría</option>
                        <template x-for="cat in categories" :key="cat.id">
                            <option :value="cat.id" x-text="cat.name"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Monto a Transferir:</label>
                    <input type="number" step="0.01" x-model="transferAmount" placeholder="0.00" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm font-black text-slate-900 focus:border-blue-500 outline-none">
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Nota (Opcional):</label>
                    <input type="text" x-model="transferNote" placeholder="Ej. Cambio de divisa, pase de saldo" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-800 focus:border-blue-500 outline-none">
                </div>
            </div>
            
            <div class="flex gap-2.5 pt-2">
                <button @click="showTransferModal = false" class="flex-1 py-3 rounded-xl text-slate-600 font-bold bg-slate-100 hover:bg-slate-200 transition-colors text-xs sm:text-sm">Cancelar</button>
                <button @click="executeTransfer()" class="flex-1 py-3 rounded-xl text-white font-black bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-900/20 active:scale-98 transition-all text-xs sm:text-sm">Transferir</button>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div x-show="showSuccessModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4" x-cloak>
        <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-xs" @click="showSuccessModal = false"></div>
        <div class="bg-white rounded-3xl p-6 w-full max-w-sm relative z-10 text-center shadow-2xl space-y-4 animate-slide-up">
            <div class="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center mx-auto">
                <span class="material-icons text-3xl">check_circle</span>
            </div>
            <div>
                <h3 class="text-lg font-black text-slate-900">¡Transferencia Exitosa!</h3>
                <p class="text-xs text-slate-500 mt-1">El dinero se ha movido correctamente entre tus cuentas.</p>
            </div>
            <button @click="showSuccessModal = false" class="w-full py-3 rounded-xl text-white font-black bg-emerald-600 hover:bg-emerald-700 shadow-md transition-all text-xs sm:text-sm">
                Entendido
            </button>
        </div>
    </div>

    <script>
        function accountsApp() {
            return {
                accounts: [],
                categories: [],
                showAddModal: false,
                newAccountName: '',
                newAccountBalance: '',
                newAccountCurrency: 'Bs',
                newAccountTenure: 'none',
                
                // Modals
                showTempModal: false,
                showTransferModal: false,
                showConfirmModal: false,
                showDeleteModal: false,
                showSuccessModal: false,
                showClosed: false,

                // Temp Funds
                selectedAccount: null,
                tempName: '',
                tempAmount: '',
                tempSource: '',

                // Transfer
                transferSource: '',
                transferDest: '',
                transferCategory: '',
                transferAmount: '',
                transferNote: '',
                
                exchangeRate: 50, // Default fallback

                get totalBs() {
                    return this.accounts.reduce((sum, acc) => {
                        return sum + (acc.currency === 'Bs' ? parseFloat(acc.balance || 0) : 0);
                    }, 0);
                },

                get totalUsd() {
                     return this.totalBs / (this.exchangeRate || 1) + this.accounts.reduce((sum, acc) => {
                        return sum + (acc.currency === 'USD' ? parseFloat(acc.balance || 0) : 0);
                    }, 0);
                },
                
                get availableDestinations() {
                    if (!this.transferSource) return [];
                    const sourceAcc = this.accounts.find(a => a.id == this.transferSource);
                    if (!sourceAcc) return [];
                    return this.accounts.filter(a => a.id != this.transferSource && a.type !== 'temporary' && a.currency === sourceAcc.currency);
                },

                init() {
                    this.fetchRate();
                    this.fetchAccounts();
                    this.fetchCategories();
                },

                async fetchCategories() {
                    try {
                        let res = await fetch('<?= base_url('config/get-data') ?>');
                        let data = await res.json();
                        this.categories = data.categories || [];
                    } catch(e) {
                        console.error('Error fetching categories:', e);
                    }
                },

                async fetchRate() {
                    try {
                        const savedRate = localStorage.getItem('exchangeRate');
                        if(savedRate) this.exchangeRate = parseFloat(savedRate);
                        
                        let res = await fetch('<?= base_url('currency/get-rate') ?>');
                        let data = await res.json();
                        if(data.status === 'success' && data.rate > 0) {
                            this.exchangeRate = data.rate;
                            localStorage.setItem('exchangeRate', data.rate);
                        }
                    } catch(e) {}
                },

                async fetchAccounts() {
                    try {
                        let res = await fetch('<?= base_url('accounts/fetch') ?>');
                        let data = await res.json();
                        if(data.status === 'success') {
                            this.accounts = data.data;
                        }
                    } catch(e) {}
                },

                async addAccount() {
                    if(!this.newAccountName) return;
                    
                    try {
                        const balance = this.newAccountBalance === '' ? 0 : this.newAccountBalance;
                        
                        let res = await fetch('<?= base_url('accounts/add') ?>', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({ 
                                name: this.newAccountName, 
                                balance: balance,
                                currency: this.newAccountCurrency,
                                tenure_type: this.newAccountTenure
                            })
                        });
                        
                        const contentType = res.headers.get("content-type");
                        if (!contentType || !contentType.includes("application/json")) {
                            const text = await res.text();
                            alert('Error del Servidor (no JSON): ' + text.substring(0, 200));
                            return;
                        }

                        let data = await res.json();
                        
                        if (data.status === 'success') {
                            this.newAccountName = '';
                            this.newAccountBalance = '';
                            this.newAccountCurrency = 'Bs';
                            this.newAccountTenure = 'none';
                            this.showAddModal = false;
                            this.fetchAccounts();
                        } else {
                            alert('Error al crear la cuenta: ' + (data.message || 'Error desconocido'));
                        }
                    } catch(e) {
                         alert('Error crítico: ' + e.message);
                    }
                },

                async executeTransfer() {
                    if (!this.transferSource || !this.transferDest || !this.transferAmount || !this.transferCategory) {
                         alert('Por favor complete todos los campos requeridos (incluyendo categoría)');
                         return;
                    }

                    try {
                        let res = await fetch('<?= base_url('accounts/transfer') ?>', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({
                                source_id: this.transferSource,
                                dest_id: this.transferDest,
                                category_id: this.transferCategory,
                                amount: this.transferAmount,
                                note: this.transferNote
                            })
                        });

                        const contentType = res.headers.get("content-type");
                         if (!contentType || !contentType.includes("application/json")) {
                            const text = await res.text();
                            alert('Error del Servidor: ' + text.substring(0, 200));
                            return;
                        }

                        let data = await res.json();
                         if (data.status === 'success') {
                            this.transferAmount = '';
                            this.transferNote = '';
                            this.transferSource = '';
                            this.transferDest = '';
                            this.transferCategory = '';
                            this.showTransferModal = false;
                            this.fetchAccounts();
                            this.showSuccessModal = true; // Show success modal
                            // Auto close after 3 seconds
                            setTimeout(() => this.showSuccessModal = false, 3000);
                        } else {
                            alert('Error: ' + data.message);
                        }

                    } catch(e) {
                         alert('Error de conexión: ' + e.message);
                    }
                },

                confirmLiquidation(acc) {
                    this.selectedAccount = acc;
                    this.showConfirmModal = true;
                },

                async executeLiquidation() {
                    if (!this.selectedAccount) return;
                    try {
                        let res = await fetch(`<?= base_url('accounts/close-temp') ?>/${this.selectedAccount.id}`);
                        let data = await res.json();
                        if (data.status === 'success') {
                            this.showConfirmModal = false;
                            this.selectedAccount = null;
                            this.fetchAccounts();
                        } else {
                            alert('Error al liquidar: ' + (data.message || 'Error desconocido'));
                        }
                    } catch(e) {
                        alert('Error de conexión: ' + e.message);
                    }
                },

                confirmDelete(acc) {
                    this.selectedAccount = acc;
                    this.showDeleteModal = true;
                },

                async executeDelete() {
                    if (!this.selectedAccount) return;
                    try {
                        let res = await fetch(`<?= base_url('accounts/delete') ?>/${this.selectedAccount.id}`);
                        let data = await res.json();
                        if (data.status === 'success') {
                            this.showDeleteModal = false;
                            this.selectedAccount = null;
                            this.fetchAccounts();
                        } else {
                            alert('Error al eliminar: ' + (data.message || 'Error desconocido'));
                        }
                    } catch(e) {
                        alert('Error de conexión: ' + e.message);
                    }
                },

                async createTempFund() {
                    if (!this.tempName || !this.tempAmount || !this.tempSource) {
                        alert('Por favor complete todos los campos requeridos para crear el fondo.');
                        return;
                    }

                    try {
                        let res = await fetch('<?= base_url('accounts/create-temp') ?>', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({
                                name: this.tempName,
                                amount: this.tempAmount,
                                source_id: this.tempSource
                            })
                        });

                        let data = await res.json();
                        if (data.status === 'success') {
                            this.tempName = '';
                            this.tempAmount = '';
                            this.tempSource = '';
                            this.showTempModal = false;
                            this.fetchAccounts();
                        } else {
                            alert('Error al crear fondo: ' + (data.message || 'Error desconocido'));
                        }
                    } catch(e) {
                        alert('Error de conexión: ' + e.message);
                    }
                },

                formatMoney(value, currency = 'Bs') {
                    if (currency === 'USD') {
                        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
                    }
                    return new Intl.NumberFormat('es-VE', { style: 'currency', currency: 'VES' }).format(value);
                },
                formatUsd(value) {
                    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
                },
                formatValUsd(bsValue) {
                    if(!this.exchangeRate) return '0.00';
                    return (bsValue / this.exchangeRate).toFixed(2);
                }
            }
        }
    </script>
</body>
</html>
