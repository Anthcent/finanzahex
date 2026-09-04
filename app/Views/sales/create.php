<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Nueva Venta | Fi-Hex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        [x-cloak] { display: none !important; }
        .customize-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
        .customize-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .customize-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
        .safe-bottom { padding-bottom: max(1.5rem, env(safe-area-inset-bottom)); }
        .safe-top { padding-top: max(0.75rem, env(safe-area-inset-top)); }
    </style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800 antialiased" x-data="saleForm()">

    <!-- Executive Top Nav Header -->
    <header class="sticky top-0 z-40 bg-gradient-to-r from-emerald-950 via-slate-900 to-teal-950 text-white shadow-xl border-b border-emerald-800/30 safe-top">
        <div class="max-w-md mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="<?= base_url('sales') ?>" class="w-10 h-10 rounded-2xl bg-white/10 hover:bg-white/20 active:scale-95 flex items-center justify-center text-white transition-all border border-white/10" title="Volver">
                    <span class="material-icons text-xl">arrow_back</span>
                </a>
                <div>
                    <h1 class="text-sm sm:text-base font-black tracking-tight text-white leading-tight">Nueva Venta</h1>
                    <p class="text-[10px] text-emerald-200/70 font-semibold">Salida de mercancía y cobranza</p>
                </div>
            </div>
            <button @click="resetForm()" class="text-xs font-black text-emerald-300 hover:text-white uppercase tracking-wider bg-white/10 hover:bg-white/20 px-3 py-1.5 rounded-xl border border-white/10 transition-all active:scale-95">
                Limpiar
            </button>
        </div>
    </header>

    <main class="max-w-md mx-auto p-4 pb-36 space-y-4 safe-bottom">
        
        <!-- Customer & Date Card -->
        <div class="bg-white rounded-3xl shadow-xs p-5 space-y-4 border border-slate-100">
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Cliente</label>
                <input type="text" x-model="form.customer" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-2.5 text-sm sm:text-base font-black text-slate-900 outline-none focus:border-emerald-500 focus:bg-white transition-all" placeholder="Nombre completo o empresa">
            </div>

            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Fecha de la Venta</label>
                <input type="date" x-model="form.date" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-2.5 text-xs sm:text-sm font-bold text-slate-800 outline-none focus:border-emerald-500 focus:bg-white transition-all">
            </div>
        </div>

        <!-- ITEMS SECTION -->
        <div class="bg-white rounded-3xl shadow-xs p-5 border border-slate-100 space-y-4">
            <div class="flex justify-between items-center">
                <h2 class="text-xs font-black text-slate-400 uppercase tracking-wider">Productos de la Orden</h2>
                <span class="text-[11px] font-black text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-lg border border-emerald-100" x-text="form.items.length + ' ítems'"></span>
            </div>
            
            <div class="space-y-3">
                <template x-for="(item, index) in form.items" :key="index">
                    <div class="bg-slate-50 rounded-2xl p-3.5 border border-slate-200/80 relative">
                        <button @click="removeItem(index)" class="absolute top-2.5 right-2.5 w-7 h-7 bg-rose-50 text-rose-500 hover:bg-rose-100 rounded-xl flex items-center justify-center transition-all" title="Eliminar ítem">
                            <span class="material-icons text-sm">close</span>
                        </button>
                        
                        <div class="pr-8 mb-2">
                             <p class="font-black text-slate-900 text-sm truncate" x-text="item.name"></p>
                             <p class="font-black text-emerald-700 text-xs mt-0.5" x-text="'$ ' + (item.price_usd * item.quantity).toFixed(2)"></p>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-2 text-xs">
                             <div class="flex items-center bg-white rounded-xl border border-slate-200 px-3 py-1.5">
                                 <span class="text-slate-400 font-bold mr-2 text-[10px] uppercase">Cant:</span>
                                 <input type="number" x-model="item.quantity" @input="calculateTotal()" class="w-full font-black text-slate-800 text-center outline-none">
                             </div>
                             <div class="flex items-center bg-white rounded-xl border border-slate-200 px-3 py-1.5">
                                 <span class="text-slate-400 font-bold mr-2 text-[10px] uppercase">Precio $:</span>
                                 <input type="number" step="0.01" x-model="item.price_usd" @input="calculateTotal()" class="w-full font-black text-slate-800 text-right outline-none">
                             </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Add Item Input -->
            <div class="relative pt-1" @click.outside="searchResults = []">
                <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-2xl px-3.5 py-2.5 focus-within:border-emerald-500 focus-within:bg-white transition-all">
                    <span class="material-icons text-slate-400 text-lg">search</span>
                    <input type="text" x-model="searchQuery" @input="searchItems()" @keydown.enter="addItemManual()" placeholder="Buscar producto o agregar manual..." class="w-full bg-transparent outline-none text-xs sm:text-sm font-bold text-slate-800">
                    <button @click="addItemManual()" class="text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-xs font-black uppercase px-2.5 py-1 rounded-xl shrink-0 transition-all" x-show="searchQuery.length > 0">
                        Agregar
                    </button>
                </div>

                <!-- Search Results Dropdown -->
                <div x-show="searchResults.length > 0" class="absolute top-full left-0 right-0 bg-white shadow-xl rounded-2xl mt-2 z-30 border border-slate-100 max-h-52 overflow-y-auto customize-scrollbar">
                    <template x-for="res in searchResults" :key="res.id">
                        <div @click="selectItem(res)" class="p-3 hover:bg-emerald-50/50 cursor-pointer border-b border-slate-100 last:border-0 transition-colors">
                            <p class="font-black text-slate-900 text-xs sm:text-sm truncate" x-text="res.name"></p>
                            <div class="flex justify-between mt-1">
                                <span class="text-[10px] font-bold text-slate-400" x-text="'Stock: ' + res.stock + ' ' + (res.unit || 'und')"></span>
                                <span class="text-[11px] font-black text-emerald-700" x-text="'$ ' + res.price"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Pricing Card -->
        <div class="bg-gradient-to-br from-emerald-50/70 to-teal-50/40 rounded-3xl p-5 space-y-4 border border-emerald-200/70 shadow-2xs">
            
            <!-- Exchange Rate -->
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-black text-emerald-800 uppercase tracking-wider flex items-center gap-1">
                    <span class="material-icons text-xs">currency_exchange</span>
                    <span>Tasa de Cambio</span>
                </span>
                <div class="flex items-center bg-white px-3 py-1.5 rounded-xl border border-emerald-200/80 shadow-2xs">
                    <span class="text-xs font-black text-emerald-700 mr-1.5">Bs.</span>
                    <input type="number" x-model.number="form.exchange_rate" @input="calculateTotal()" class="w-16 text-right bg-transparent text-xs font-black text-slate-800 outline-none p-0 border-none">
                </div>
            </div>

            <div class="h-px bg-emerald-200/40"></div>

            <!-- Amounts -->
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-white/80 p-3.5 rounded-2xl border border-emerald-100">
                    <label class="block text-[10px] font-black text-emerald-700 uppercase tracking-wider mb-0.5">Total Dólares</label>
                    <div class="flex items-baseline gap-1">
                        <span class="text-emerald-700 text-lg font-black">$</span>
                        <input type="number" step="0.01" x-model.number="totals.usd" readonly class="w-full text-2xl font-black text-slate-900 outline-none bg-transparent">
                    </div>
                </div>
                <div class="bg-white/80 p-3.5 rounded-2xl border border-emerald-100">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-0.5">Total Bolívares</label>
                    <div class="flex items-baseline gap-1">
                        <span class="text-slate-400 text-sm font-black">Bs.</span>
                        <input type="number" step="0.01" x-model.number="totals.bs" readonly class="w-full text-xl font-black text-slate-700 outline-none bg-transparent">
                    </div>
                </div>
            </div>
        </div>

        <!-- Income Destination Card -->
        <div class="bg-white rounded-3xl shadow-xs p-5 space-y-4 border border-slate-100">
            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Destino del Ingreso</h3>
            
            <!-- Account Selection -->
            <div>
                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Cuenta de Depósito</label>
                <select x-model="form.account_id" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-2.5 text-xs sm:text-sm font-bold text-slate-800 outline-none focus:border-emerald-500 focus:bg-white transition-all">
                    <option value="" disabled>Seleccione cuenta...</option>
                    <template x-for="acc in accounts" :key="acc.id">
                        <option :value="acc.id" x-text="acc.name + ' (' + acc.currency + ')'"></option>
                    </template>
                </select>
            </div>

            <!-- Category Selection -->
            <div>
                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Categoría</label>
                <select x-model="form.category_id" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-2.5 text-xs sm:text-sm font-bold text-slate-800 outline-none focus:border-emerald-500 focus:bg-white transition-all">
                    <option value="" disabled>Seleccione categoría...</option>
                    <template x-for="cat in categories" :key="cat.id">
                        <option :value="cat.id" x-text="cat.name"></option>
                    </template>
                </select>
            </div>

            <!-- Owner Selection -->
            <div>
                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Asignar a</label>
                <div class="grid grid-cols-3 gap-2">
                    <button @click="form.owner = 'Negocio'" type="button" 
                            class="py-2.5 rounded-xl text-xs font-black border transition-all active:scale-95"
                            :class="form.owner === 'Negocio' ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'">
                        Negocio
                    </button>
                    <button @click="form.owner = 'Anthony'" type="button" 
                            class="py-2.5 rounded-xl text-xs font-black border transition-all active:scale-95"
                            :class="form.owner === 'Anthony' ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'">
                        Anthony
                    </button>
                    <button @click="form.owner = 'Arianny'" type="button" 
                            class="py-2.5 rounded-xl text-xs font-black border transition-all active:scale-95"
                            :class="form.owner === 'Arianny' ? 'bg-teal-700 text-white border-teal-700 shadow-sm' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'">
                        Arianny
                    </button>
                </div>
            </div>
        </div>

        <!-- Payment Status (Paid / Partial) -->
        <div class="bg-white rounded-3xl shadow-xs p-3 border border-slate-100">
            <div class="grid grid-cols-2 gap-2 p-1 bg-slate-100 rounded-2xl">
                <button @click="form.status = 'paid'; form.paid_amount = totals.bs; form.paid_amount_usd = totals.usd" 
                        class="py-2.5 rounded-xl text-xs font-black transition-all flex items-center justify-center gap-1.5"
                        :class="form.status === 'paid' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'">
                    <span class="material-icons text-sm">check_circle</span>
                    <span>Pago Completo</span>
                </button>
                <button @click="form.status = 'partial'; form.paid_amount = ''; form.paid_amount_usd = ''" 
                        class="py-2.5 rounded-xl text-xs font-black transition-all flex items-center justify-center gap-1.5"
                        :class="form.status === 'partial' ? 'bg-amber-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'">
                    <span class="material-icons text-sm">pending</span>
                    <span>Crédito / Abono</span>
                </button>
            </div>

            <!-- Partial Payment Details -->
            <div x-show="form.status === 'partial'" class="mt-4 px-2 pb-2 pt-2 border-t border-slate-100">
                <p class="text-[10px] font-black text-amber-700 uppercase tracking-wider mb-2 text-center">Abono Inicial Recibido</p>
                
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-amber-50/60 border border-amber-200/80 rounded-2xl p-3">
                        <label class="text-[10px] text-amber-800 font-black block mb-0.5">Monto ($)</label>
                        <input type="number" step="0.01" x-model.number="form.paid_amount_usd" @input="calculatePayment('usd')" class="w-full bg-transparent text-lg font-black text-slate-900 outline-none" placeholder="0.00">
                    </div>
                    <div class="bg-amber-50/60 border border-amber-200/80 rounded-2xl p-3">
                        <label class="text-[10px] text-amber-800 font-black block mb-0.5">Monto (Bs)</label>
                        <input type="number" step="0.01" x-model.number="form.paid_amount" @input="calculatePayment('bs')" class="w-full bg-transparent text-lg font-black text-slate-900 outline-none" placeholder="0.00">
                    </div>
                </div>
                
                <div class="text-center mt-3 text-xs font-bold">
                    <span class="text-slate-400">Resta por cobrar:</span>
                    <span class="font-black text-rose-600 ml-1" x-text="formatMoney((totals.usd || 0) - (form.paid_amount_usd || 0), 'USD')"></span>
                </div>
            </div>
        </div>

        <!-- Order Status Pills -->
        <div class="bg-white rounded-3xl shadow-xs p-5 border border-slate-100">
            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-3">Estado Inicial de la Orden</h3>
            <div class="flex flex-wrap gap-2">
                <template x-for="st in statuses" :key="st.id">
                    <button @click="form.order_status_id = st.id" 
                            type="button"
                            class="px-3.5 py-2 rounded-xl text-xs font-black transition-all border active:scale-95"
                            :class="form.order_status_id == st.id ? (st.color + ' border-transparent shadow-sm') : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'">
                        <span x-text="st.name"></span>
                    </button>
                </template>
            </div>
        </div>

        <!-- Note Card -->
        <div class="bg-white rounded-3xl shadow-xs p-5 border border-slate-100">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Nota o Referencia</label>
            <input type="text" x-model="form.reference" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-2.5 text-xs sm:text-sm font-bold text-slate-800 outline-none focus:border-emerald-500 focus:bg-white transition-all" placeholder="Referencia de pago, especificaciones...">
        </div>

    </main>

    <!-- Floating Action Submit Button -->
    <div class="fixed bottom-6 left-0 right-0 px-4 max-w-md mx-auto z-40 safe-bottom">
        <button @click="submit()" :disabled="loading || form.items.length === 0" 
                class="w-full bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white h-14 rounded-2xl font-black text-base shadow-xl shadow-emerald-950/30 active:scale-95 transition-all flex items-center justify-center gap-2.5 disabled:opacity-50 disabled:cursor-not-allowed">
            <span x-show="!loading">Registrar Venta</span>
            <span x-show="!loading" class="material-icons text-xl">arrow_forward</span>
            <span x-show="loading" class="material-icons animate-spin">refresh</span>
        </button>
    </div>

    <!-- Toast Notification -->
    <div x-show="message" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-10 scale-90"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         class="fixed bottom-24 left-1/2 transform -translate-x-1/2 bg-slate-950/90 backdrop-blur-md text-white px-5 py-3.5 rounded-2xl shadow-2xl z-50 flex items-center gap-3 border border-white/10 pointer-events-none min-w-[280px]">
        <div class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
            <span class="material-icons text-base">check</span>
        </div>
        <div class="min-w-0 flex-1">
            <p class="font-black text-xs">Notificación</p>
            <p x-text="message" class="text-[11px] text-slate-300 font-medium truncate"></p>
        </div>
    </div>

    <script>
        function saleForm() {
            return {
                form: {
                    date: new Date().toISOString().split('T')[0],
                    customer: '',
                    exchange_rate: 50,
                    status: 'paid',
                    order_status_id: null,
                    paid_amount: '',
                    paid_amount_usd: '',
                    reference: '',
                    items: [],
                    account_id: '',
                    category_id: '',
                    owner: 'Negocio'
                },
                totals: { usd: 0, bs: 0 },
                searchQuery: '',
                searchResults: [],
                loading: false,
                message: '',

                statuses: [],
                accounts: [],
                categories: [],

                init() {
                    this.fetchRate();
                    this.fetchStatuses();
                    this.fetchAccounts();
                    this.fetchCategories();
                },

                async fetchCategories() {
                     try {
                        let res = await fetch('<?= base_url('sales/get-categories') ?>');
                        let data = await res.json();
                        if(data.status === 'success') {
                             this.categories = data.data;
                             let salesCat = this.categories.find(c => c.name === 'Ventas' || c.name === 'Ingresos');
                             if (salesCat) {
                                 this.form.category_id = salesCat.id;
                             } else if (this.categories.length > 0) {
                                 this.form.category_id = this.categories[0].id;
                             }
                        }
                    } catch(e) {}
                },

                async fetchAccounts() {
                    try {
                        let res = await fetch('<?= base_url('sales/get-accounts') ?>'); 
                        let data = await res.json();
                        if(data.status === 'success') {
                             this.accounts = data.data;
                             if(this.accounts.length > 0) this.form.account_id = this.accounts[0].id;
                        }
                    } catch(e) {}
                },

                async fetchStatuses() {
                    try {
                        let res = await fetch('<?= base_url('sales/get-statuses') ?>');
                        let data = await res.json();
                        if(data.status === 'success') {
                            this.statuses = data.data;
                            if(this.statuses.length > 0 && !this.form.order_status_id) {
                                this.form.order_status_id = this.statuses[0].id;
                            }
                        }
                    } catch(e) {}
                },
                
                resetForm() {
                    const rate = this.form.exchange_rate;
                    const date = this.form.date;
                    const statusId = this.form.order_status_id;
                    const accId = this.form.account_id;
                    const catId = this.form.category_id;
                    this.form = {
                        date: date,
                        customer: '',
                        exchange_rate: rate,
                        status: 'paid',
                        order_status_id: statusId,
                        paid_amount: '',
                        paid_amount_usd: '',
                        reference: '',
                        items: [],
                        account_id: accId,
                        category_id: catId,
                        owner: 'Negocio'
                    };
                    this.calculateTotal();
                },

                async fetchRate() {
                    try {
                        let res = await fetch('<?= base_url('currency/get-rate') ?>');
                        let data = await res.json();
                        if (data.status === 'success' && data.rate > 0) {
                            this.form.exchange_rate = data.rate;
                        } else {
                            const savedRate = localStorage.getItem('exchangeRate');
                            if(savedRate) this.form.exchange_rate = parseFloat(savedRate);
                        }
                    } catch(e) {}
                },

                async searchItems() {
                    if (this.searchQuery.length < 2) {
                        this.searchResults = [];
                        return;
                    }
                    try {
                        let res = await fetch('<?= base_url('inventory/search') ?>?q=' + encodeURIComponent(this.searchQuery));
                        let data = await res.json();
                        this.searchResults = data.data || [];
                    } catch(e) {}
                },

                selectItem(item) {
                    this.form.items.push({
                        id: item.id,
                        name: item.name,
                        quantity: 1,
                        price_usd: item.price,
                        cost: item.cost
                    });
                    this.searchQuery = '';
                    this.searchResults = [];
                    this.calculateTotal();
                },

                addItemManual() {
                    if (!this.searchQuery) return;
                    this.form.items.push({
                        id: null,
                        name: this.searchQuery,
                        quantity: 1,
                        price_usd: 0,
                        cost: 0
                    });
                    this.searchQuery = '';
                    this.searchResults = [];
                    this.calculateTotal();
                },

                removeItem(index) {
                    this.form.items.splice(index, 1);
                    this.calculateTotal();
                },

                calculateTotal() {
                    let totalUsd = 0;
                    this.form.items.forEach(item => {
                        totalUsd += (parseFloat(item.price_usd) || 0) * (parseFloat(item.quantity) || 1);
                    });
                    
                    this.totals.usd = totalUsd.toFixed(2);
                    this.totals.bs = (totalUsd * this.form.exchange_rate).toFixed(2);

                    if (this.form.status === 'paid') {
                        this.form.paid_amount = this.totals.bs;
                        this.form.paid_amount_usd = this.totals.usd;
                    }
                },

                calculatePayment(source) {
                    if (!this.form.exchange_rate) return;

                    if (source === 'usd' && this.form.paid_amount_usd) {
                        this.form.paid_amount = (this.form.paid_amount_usd * this.form.exchange_rate).toFixed(2);
                    } else if (source === 'bs' && this.form.paid_amount) {
                        this.form.paid_amount_usd = (this.form.paid_amount / this.form.exchange_rate).toFixed(2);
                    }
                },

                formatMoney(value, currency = 'Bs') {
                    if (!value) return (0).toFixed(2);
                    if (currency === 'USD') {
                        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 2 }).format(value);
                    }
                    return new Intl.NumberFormat('es-VE', { style: 'currency', currency: 'VES', minimumFractionDigits: 2 }).format(value);
                },

                async submit() {
                    if (this.form.items.length === 0 || !this.form.customer) {
                        this.showMsg('Agregue productos y el nombre del cliente');
                        return;
                    }

                    this.loading = true;
                    try {
                        let res = await fetch('<?= base_url('sales/store') ?>', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(this.form)
                        });
                        let data = await res.json();
                        
                        if (data.status === 'success') {
                            this.showMsg('Venta registrada correctamente');
                            setTimeout(() => {
                                window.location.href = '<?= base_url('sales') ?>';
                            }, 1500);
                        } else {
                            this.showMsg('Error al guardar: ' + (data.message || 'Desconocido'));
                        }
                    } catch (e) {
                        this.showMsg('Error de conexión');
                        console.error(e);
                    } finally {
                        this.loading = false;
                    }
                },

                showMsg(txt) {
                    this.message = txt;
                    setTimeout(() => this.message = '', 3000);
                }
            }
        }
    </script>
</body>
</html>
