<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Mis Cuentas - FinazaPersonal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col" x-data="accountsApp()">

    <!-- Header -->
    <div class="bg-white p-4 shadow-sm z-10 flex items-center justify-between sticky top-0">
        <div class="flex items-center">
            <a href="<?= base_url() ?>" class="mr-3 text-gray-500 hover:text-gray-800">
                <span class="material-icons">arrow_back</span>
            </a>
            <h1 class="text-xl font-bold text-gray-800">Mis Cuentas</h1>
        </div>
        <div></div>
    </div>

    <!-- Content -->
    <div class="flex-1 p-4 space-y-4 pb-20">
        
        <!-- Total Wealth Card -->
        <div class="bg-gradient-to-br from-slate-950 via-emerald-950 to-slate-900 border border-emerald-500/20 rounded-2xl p-5 text-white shadow-lg relative overflow-hidden">
            <div class="absolute -right-6 -bottom-6 w-28 h-28 bg-emerald-500/10 rounded-full blur-xl pointer-events-none"></div>
            <p class="text-emerald-300 text-xs font-semibold uppercase tracking-wider mb-1">Patrimonio Total</p>
            <div class="flex flex-col">
                <span class="text-3xl font-bold" x-text="formatMoney(totalBs)"></span>
                <span class="text-sm text-emerald-200/70" x-text="'≈ ' + formatUsd(totalUsd)"></span>
            </div>
            <div class="mt-4 flex items-center text-xs text-slate-400">
                <span class="material-icons text-[12px] mr-1 text-emerald-400">trending_up</span>
                <span>Calculado a Tasa BCV: <span class="text-emerald-400 font-bold" x-text="exchangeRate"></span></span>
            </div>
        </div>

        <!-- Main Accounts List -->
        <h2 class="font-bold text-gray-700 text-sm uppercase tracking-wide mt-2">Cuentas Principales</h2>
        <div class="grid gap-4">
            <template x-for="acc in accounts.filter(a => a.type !== 'temporary')" :key="acc.id">
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 relative">
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600">
                                <span class="material-icons">account_balance_wallet</span>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-800" x-text="acc.name"></h3>
                                <p class="text-xs text-gray-400">Cuenta Principal</p>
                            </div>
                        </div>
                        <button @click="confirmDelete(acc)" class="text-gray-300 hover:text-red-500 transition-colors">
                            <span class="material-icons text-sm">close</span>
                        </button>
                    </div>
                    
                    <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                        <p class="text-xs text-gray-500 mb-1">Balance Actual</p>
                        <div class="flex items-center">
                            <span class="font-bold text-xl text-gray-800" x-text="formatMoney(acc.balance, acc.currency)"></span>
                        </div>
                        <p class="text-xs text-emerald-600 font-medium mt-1" x-show="acc.currency !== 'USD'" x-text="'$ ' + formatValUsd(acc.balance)"></p>
                        <p class="text-xs text-emerald-600 font-medium mt-1" x-show="acc.currency === 'USD'" x-text="acc.tenure_type === 'digital' ? 'Digital' : 'Efectivo'"></p>
                        <p class="text-xs text-gray-400 mt-2 italic">
                            <span class="material-icons text-[10px]">info</span>
                            El balance se actualiza automáticamente con cada transacción
                        </p>
                    </div>
                </div>
            </template>
        </div>

        <!-- Temporary Funds List -->
        <div class="mt-6">
            <div class="flex justify-between items-center mb-3">
                <h2 class="font-bold text-orange-600 text-sm uppercase tracking-wide flex items-center">
                    <span class="material-icons text-base mr-1">savings</span>
                    Fondos Temporales
                </h2>
                
                <!-- Filter Toggle -->
                <button @click="showClosed = !showClosed" class="flex items-center text-xs font-medium text-gray-500 hover:text-orange-600 transition">
                    <span class="material-icons text-[16px] mr-1" :class="showClosed ? 'text-orange-600' : 'text-gray-400'">
                        list_alt
                    </span>
                    <span x-text="showClosed ? 'Ocultar Cerrados' : 'Ver Historial'"></span>
                </button>
            </div>

            <div class="grid gap-4">
                <template x-for="acc in accounts.filter(a => a.type === 'temporary' && (a.status === 'active' || showClosed))" :key="acc.id">
                    <div :class="acc.status === 'closed' ? 'bg-gray-100 border-gray-200 opacity-75' : 'bg-gradient-to-br from-orange-50 to-amber-50 border-orange-100'" 
                         class="rounded-xl p-4 shadow-sm border relative overflow-hidden transition-all duration-300">
                        
                        <!-- Decorative bg icon -->
                        <span class="material-icons absolute -right-4 -bottom-4 text-8xl opacity-50 pointer-events-none"
                              :class="acc.status === 'closed' ? 'text-gray-200' : 'text-orange-100'">savings</span>
                        
                        <div class="relative z-10 flex justify-between items-start mb-2">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center shadow-sm"
                                     :class="acc.status === 'closed' ? 'bg-gray-200 text-gray-500' : 'bg-orange-100 text-orange-600'">
                                    <span class="material-icons" x-text="acc.status === 'closed' ? 'lock' : 'work_history'"></span>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-800" x-text="acc.name"></h3>
                                    <p class="text-xs font-bold" 
                                       :class="acc.status === 'closed' ? 'text-gray-500' : 'text-orange-600'"
                                       x-text="acc.status === 'closed' ? 'Fondo Cerrado' : 'Fondo Activo'">
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Liquidar Button (Only for Active) -->
                            <button x-show="acc.status === 'active'" 
                                    @click="confirmLiquidation(acc)" 
                                    class="bg-orange-600 text-white text-xs px-3 py-1.5 rounded-lg shadow hover:bg-orange-700 transition flex items-center">
                                <span class="material-icons text-[14px] mr-1">published_with_changes</span>
                                Liquidar
                            </button>
                            
                             <!-- Static Badge (For Closed) -->
                             <span x-show="acc.status === 'closed'" class="text-xs font-bold text-gray-400 bg-gray-200 px-2 py-1 rounded">
                                Liquidado
                            </span>
                        </div>
                        
                        <div class="relative z-10 mt-3 text-right">
                             <span class="text-2xl font-bold" 
                                   :class="acc.status === 'closed' ? 'text-gray-500 line-through' : 'text-gray-800'"
                                   x-text="formatMoney(acc.balance)"></span>
                             <p class="text-xs font-medium" 
                                :class="acc.status === 'closed' ? 'text-gray-400' : 'text-orange-600'"
                                x-text="'$ ' + formatValUsd(acc.balance)"></p>
                        </div>
                    </div>
                </template>
                
                <!-- Empty State -->
                <div x-show="accounts.filter(a => a.type === 'temporary').length === 0" class="text-center p-6 text-gray-400 bg-white rounded-xl border border-dashed border-gray-300">
                    <p class="text-sm">No hay fondos temporales creados.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Actions -->
     <div class="fixed bottom-6 right-6 flex flex-col space-y-3 z-30">
        <button @click="showTempModal = true" class="bg-orange-600 text-white p-3 rounded-full shadow-lg hover:bg-orange-700 transition flex items-center justify-center transform hover:scale-110" title="Crear Fondo Temporal">
            <span class="material-icons">savings</span>
        </button>
        <button @click="showTransferModal = true" class="bg-blue-600 text-white p-3 rounded-full shadow-lg hover:bg-blue-700 transition flex items-center justify-center transform hover:scale-110" title="Transferir entre Cuentas">
            <span class="material-icons">sync_alt</span>
        </button>
        <button @click="showAddModal = true" class="bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white p-3 rounded-full shadow-lg shadow-emerald-500/30 transition flex items-center justify-center transform hover:scale-110" title="Nueva Cuenta">
            <span class="material-icons">add</span>
        </button>
    </div>

    <!-- Confirmation Modal (Liquidation) -->
    <div x-show="showConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center px-4" x-cloak>
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-60 transition-opacity" x-show="showConfirmModal" x-transition.opacity @click="showConfirmModal = false"></div>
        
        <!-- Panel -->
        <div class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-2xl transform transition-all relative z-10" x-show="showConfirmModal" x-transition>
            <div class="text-center mb-4">
                <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <span class="material-icons text-3xl text-orange-600">published_with_changes</span>
                </div>
                <h2 class="text-xl font-bold text-gray-800">¿Liquidar Fondo?</h2>
                <p class="text-sm text-gray-500 mt-2">
                    El saldo restante de <span class="font-bold text-gray-800" x-text="selectedAccount?.name"></span> 
                    (<span class="font-bold text-green-600" x-text="formatMoney(selectedAccount?.balance || 0)"></span>)
                    será devuelto a su origen.
                </p>
            </div>
            
            <div class="flex space-x-3">
                <button @click="showConfirmModal = false" class="flex-1 py-3 rounded-xl text-gray-600 font-bold bg-gray-100 hover:bg-gray-200 transition">Cancelar</button>
                <button @click="executeLiquidation()" class="flex-1 py-3 rounded-xl text-white font-bold bg-orange-600 hover:bg-orange-700 shadow-lg shadow-orange-200 transition">Sí, Liquidar</button>
            </div>
        </div>
    </div>

    <!-- Delete Modal (Red) -->
    <div x-show="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center px-4" x-cloak>
         <!-- Backdrop -->
         <div class="fixed inset-0 bg-black bg-opacity-60 transition-opacity" x-show="showDeleteModal" x-transition.opacity @click="showDeleteModal = false"></div>
        
         <!-- Panel -->
        <div class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-2xl transform transition-all relative z-10" x-show="showDeleteModal" x-transition>
            <div class="text-center mb-4">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <span class="material-icons text-3xl text-red-600">delete_forever</span>
                </div>
                <h2 class="text-xl font-bold text-gray-800">¿Eliminar Cuenta?</h2>
                <p class="text-sm text-gray-500 mt-2">
                    Se eliminará la cuenta <span class="font-bold text-gray-800" x-text="selectedAccount?.name"></span> y todo su historial de transacciones.
                    <span class="block mt-1 font-bold text-red-500">Esta acción no se puede deshacer.</span>
                </p>
            </div>
            
            <div class="flex space-x-3">
                <button @click="showDeleteModal = false" class="flex-1 py-3 rounded-xl text-gray-600 font-bold bg-gray-100 hover:bg-gray-200 transition">Cancelar</button>
                <button @click="executeDelete()" class="flex-1 py-3 rounded-xl text-white font-bold bg-red-600 hover:bg-red-700 shadow-lg shadow-red-200 transition">Sí, Eliminar</button>
            </div>
        </div>
    </div>

    <!-- Add Account Modal -->
    <div x-show="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center px-4" x-cloak>
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" x-show="showAddModal" x-transition.opacity @click="showAddModal = false"></div>
        
        <!-- Panel -->
        <div class="bg-white rounded-xl p-6 w-full max-w-sm transform transition-all relative z-10" x-show="showAddModal" x-transition>
            <h2 class="text-lg font-bold text-gray-800 mb-4">Nueva Cuenta Principal</h2>
            <input type="text" x-model="newAccountName" placeholder="Nombre (ej. Banesco)" class="w-full border rounded-lg p-3 mb-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
            <input type="number" x-model="newAccountBalance" placeholder="Monto Inicial" class="w-full border rounded-lg p-3 mb-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
            
            <div class="flex gap-2 mb-4">
                <select x-model="newAccountCurrency" class="flex-1 border rounded-lg p-3 bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
                    <option value="Bs">Bolívares (Bs)</option>
                    <option value="USD">Dólares ($)</option>
                </select>
                <select x-model="newAccountTenure" x-show="newAccountCurrency === 'USD'" class="flex-1 border rounded-lg p-3 bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
                    <option value="none" disabled selected>Tipo de Tenencia</option>
                    <option value="digital">Digital (Zelle/Binance)</option>
                    <option value="physical">Efectivo</option>
                </select>
            </div>
            <div class="flex space-x-3">
                <button @click="showAddModal = false" class="flex-1 py-3 rounded-lg text-gray-600 font-bold bg-gray-100">Cancelar</button>
                <button @click="addAccount()" class="flex-1 py-3 rounded-lg text-white font-bold bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 shadow-md shadow-emerald-500/20">Crear</button>
            </div>
        </div>
    </div>

    <!-- Create Temp Fund Modal -->
    <div x-show="showTempModal" class="fixed inset-0 z-50 flex items-center justify-center px-4" x-cloak>
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" x-show="showTempModal" x-transition.opacity @click="showTempModal = false"></div>
        
        <!-- Panel -->
        <div class="bg-white rounded-xl p-6 w-full max-w-sm transform transition-all relative z-10" x-show="showTempModal" x-transition>
            <h2 class="text-lg font-bold text-gray-800 mb-2">Nuevo Fondo Temporal</h2>
            <p class="text-xs text-gray-500 mb-4">Dinero apartado para un propósito específico.</p>
            
            <input type="text" x-model="tempName" placeholder="Nombre (ej. Compras Negocio)" class="w-full border rounded-lg p-3 mb-3 focus:ring-2 focus:ring-orange-500 outline-none">
            
            <label class="block text-xs font-bold text-gray-500 mb-1">Tomar dinero de:</label>
            <select x-model="tempSource" class="w-full border rounded-lg p-3 mb-3 bg-white focus:ring-2 focus:ring-orange-500">
                <option value="">Seleccione Cuenta Origen</option>
                <template x-for="a in accounts.filter(x => x.type !== 'temporary')" :key="a.id">
                    <option :value="a.id" x-text="a.name + ' (' + formatMoney(a.balance) + ')'"></option>
                </template>
            </select>

            <label class="block text-xs font-bold text-gray-500 mb-1">Monto a Asignar:</label>
            <input type="number" x-model="tempAmount" placeholder="0.00" class="w-full border rounded-lg p-3 mb-4 focus:ring-2 focus:ring-orange-500 outline-none">
            
            <div class="flex space-x-3">
                <button @click="showTempModal = false" class="flex-1 py-3 rounded-lg text-gray-600 font-bold bg-gray-100">Cancelar</button>
                <button @click="createTempFund()" class="flex-1 py-3 rounded-lg text-white font-bold bg-orange-600 md:hover:bg-orange-700">Crear Fondo</button>
            </div>
        </div>
    </div>

    <!-- Transfer Modal -->
    <div x-show="showTransferModal" class="fixed inset-0 z-50 flex items-center justify-center px-4" x-cloak>
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" x-show="showTransferModal" x-transition.opacity @click="showTransferModal = false"></div>
        
        <!-- Panel -->
        <div class="bg-white rounded-xl p-6 w-full max-w-sm transform transition-all relative z-10" x-show="showTransferModal" x-transition>
            <h2 class="text-lg font-bold text-gray-800 mb-2 flex items-center">
                <span class="material-icons text-blue-600 mr-2">sync_alt</span>
                Transferencia Rápida
            </h2>
            <p class="text-xs text-gray-500 mb-4">Mueve dinero entre tus cuentas principales.</p>
            
            <!-- Source -->
            <label class="block text-xs font-bold text-gray-500 mb-1">Desde (Origen):</label>
            <select x-model="transferSource" class="w-full border rounded-lg p-3 mb-3 bg-white focus:ring-2 focus:ring-blue-500">
                <option value="">Seleccione Cuenta</option>
                <template x-for="acc in accounts.filter(a => a.type !== 'temporary')" :key="acc.id">
                    <option :value="acc.id" x-text="acc.name + ' (' + formatMoney(acc.balance, acc.currency) + ')'"></option>
                </template>
            </select>

            <!-- Destination -->
            <label class="block text-xs font-bold text-gray-500 mb-1">Para (Destino):</label>
            <select x-model="transferDest" class="w-full border rounded-lg p-3 mb-3 bg-white focus:ring-2 focus:ring-blue-500">
                <option value="">Seleccione Cuenta</option>
                <template x-for="acc in availableDestinations" :key="acc.id">
                    <option :value="acc.id" x-text="acc.name + ' (' + acc.currency + ')'"></option>
                </template>
            </select>

            <!-- Category -->
            <label class="block text-xs font-bold text-gray-500 mb-1">Categoría:</label>
            <select x-model="transferCategory" class="w-full border rounded-lg p-3 mb-3 bg-white focus:ring-2 focus:ring-blue-500">
                <option value="">Seleccione Categoría</option>
                <template x-for="cat in categories" :key="cat.id">
                    <option :value="cat.id" x-text="cat.name"></option>
                </template>
            </select>

            <label class="block text-xs font-bold text-gray-500 mb-1">Monto:</label>
            <input type="number" x-model="transferAmount" placeholder="0.00" class="w-full border rounded-lg p-3 mb-3 focus:ring-2 focus:ring-blue-500 outline-none">

            <label class="block text-xs font-bold text-gray-500 mb-1">Nota (Opcional):</label>
            <input type="text" x-model="transferNote" placeholder="Ej. Pago de deuda" class="w-full border rounded-lg p-3 mb-4 focus:ring-2 focus:ring-blue-500 outline-none">
            
            <div class="flex space-x-3">
                <button @click="showTransferModal = false" class="flex-1 py-3 rounded-lg text-gray-600 font-bold bg-gray-100">Cancelar</button>
                <button @click="executeTransfer()" class="flex-1 py-3 rounded-lg text-white font-bold bg-blue-600 md:hover:bg-blue-700 shadow-lg shadow-blue-200">Transferir</button>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div x-show="showSuccessModal" class="fixed inset-0 z-[60] flex items-center justify-center px-4" x-cloak>
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" x-show="showSuccessModal" x-transition.opacity @click="showSuccessModal = false"></div>
        <div class="bg-white rounded-2xl p-6 w-full max-w-sm transform transition-all relative z-10 text-center" x-show="showSuccessModal" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="material-icons text-green-600 text-3xl">check_circle</span>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">¡Transferencia Exitosa!</h3>
            <p class="text-gray-500 text-sm mb-6">El dinero se ha movido correctamente entre tus cuentas.</p>
            <button @click="showSuccessModal = false" class="w-full py-3 rounded-xl text-white font-bold bg-green-600 hover:bg-green-700 shadow-lg shadow-green-200 transition-all">
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

                // ... keep createTempFund and others ...

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
