<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Venta</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .input-group:focus-within label { color: #4f46e5; }
        .input-group:focus-within input { border-color: #4f46e5; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen" x-data="saleForm()">

    <!-- Header -->
    <div class="bg-white/80 backdrop-blur-md sticky top-0 z-50 border-b border-slate-100">
        <div class="max-w-md mx-auto flex items-center justify-between p-4">
            <a href="<?= base_url('sales') ?>" class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-600 hover:bg-slate-100 transition-colors">
                <span class="material-icons">arrow_back</span>
            </a>
            <h1 class="text-lg font-bold text-slate-800">Nueva Venta</h1>
            <button @click="resetForm()" class="text-xs font-bold text-slate-400 hover:text-indigo-600 uppercase tracking-wide">Limpiar</button>
        </div>
    </div>

    <div class="max-w-md mx-auto p-5 pb-32 space-y-6">
        
        <!-- Customer & Date -->
        <div class="bg-white rounded-[2rem] shadow-xl shadow-slate-200/50 p-6 space-y-5 border border-slate-50 relative overflow-hidden">
            <!-- Customer -->
            <div class="input-group transition-colors">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Cliente</label>
                <input type="text" x-model="form.customer" class="w-full text-lg font-bold text-slate-800 placeholder-slate-300 border-b-2 border-slate-100 py-2 outline-none bg-transparent transition-all" placeholder="Nombre completo">
            </div>

            <!-- Date -->
            <div class="input-group transition-colors">
                 <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Fecha de Venta</label>
                 <input type="date" x-model="form.date" class="w-full font-bold text-slate-600 border-b-2 border-slate-100 py-2 outline-none bg-transparent transition-all">
            </div>
        </div>

        <!-- ITEMS SECTION -->
        <div class="bg-white rounded-[2rem] shadow-xl shadow-slate-200/50 p-6 border border-slate-50 relative">
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Productos</h2>
            
            <div class="space-y-4">
                <template x-for="(item, index) in form.items" :key="index">
                    <div class="bg-slate-50 rounded-xl p-3 border border-slate-100 relative group">
                        <button @click="removeItem(index)" class="absolute -top-2 -right-2 w-6 h-6 bg-rose-50 text-rose-500 rounded-full flex items-center justify-center shadow-sm opacity-0 group-hover:opacity-100 transition-opacity">
                            <span class="material-icons text-xs">close</span>
                        </button>
                        
                        <div class="flex justify-between items-start mb-2">
                             <!-- Item Name (Editable/Searchable handled by main input below, here just display) -->
                             <p class="font-bold text-slate-800" x-text="item.name"></p>
                             <p class="font-black text-slate-800" x-text="'$ ' + (item.price_usd * item.quantity).toFixed(2)"></p>
                        </div>
                        
                        <div class="flex gap-3 text-xs">
                             <div class="flex items-center bg-white rounded-lg border border-slate-200 px-2 py-1">
                                 <span class="text-slate-400 mr-2">Cant:</span>
                                 <input type="number" x-model="item.quantity" @input="calculateTotal()" class="w-12 font-bold text-center outline-none">
                             </div>
                             <div class="flex items-center bg-white rounded-lg border border-slate-200 px-2 py-1 flex-1">
                                 <span class="text-slate-400 mr-2">Precio $:</span>
                                 <input type="number" step="0.01" x-model="item.price_usd" @input="calculateTotal()" class="w-full font-bold outline-none">
                             </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Add Item Input -->
            <div class="mt-4 relative" @click.outside="searchResults = []">
                <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus-within:ring-2 ring-indigo-100 transition-all">
                    <span class="material-icons text-slate-400">search</span>
                    <input type="text" x-model="searchQuery" @input="searchItems()" @keydown.enter="addItemManual()" placeholder="Buscar o agregar manual..." class="w-full bg-transparent outline-none text-sm font-bold text-slate-700">
                    <button @click="addItemManual()" class="text-indigo-600 text-xs font-bold uppercase" x-show="searchQuery.length > 0">Agregar</button>
                </div>

                <!-- Search Results Dropdown -->
                <div x-show="searchResults.length > 0" class="absolute top-full left-0 right-0 bg-white shadow-xl rounded-xl mt-2 z-20 border border-slate-100 max-h-48 overflow-y-auto">
                    <template x-for="res in searchResults" :key="res.id">
                        <div @click="selectItem(res)" class="p-3 hover:bg-slate-50 cursor-pointer border-b border-slate-50 last:border-0">
                            <p class="font-bold text-slate-800 text-sm" x-text="res.name"></p>
                            <div class="flex justify-between mt-1">
                                <span class="text-[10px] text-slate-400" x-text="'Stock: ' + res.stock + ' ' + res.unit"></span>
                                <span class="text-[10px] font-bold text-emerald-600" x-text="'$ ' + res.price"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Pricing Card -->
        <div class="bg-white rounded-[2rem] shadow-xl shadow-slate-200/50 p-6 space-y-5 border border-slate-50">
            
            <!-- Exchange Rate -->
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Tasa de Cambio</span>
                <div class="flex items-center bg-indigo-50 px-3 py-1.5 rounded-full">
                    <span class="text-xs font-bold text-indigo-600 mr-2">Bs.</span>
                    <input type="number" x-model.number="form.exchange_rate" @input="calculateTotal()" class="w-16 text-right bg-transparent text-sm font-bold text-indigo-800 outline-none p-0 border-none">
                </div>
            </div>

            <div class="h-px bg-slate-100"></div>

            <!-- Amounts -->
            <div class="grid grid-cols-2 gap-8">
                <div>
                    <label class="block text-[10px] font-bold text-emerald-500 uppercase tracking-widest mb-1">Total ($)</label>
                    <div class="flex items-center">
                        <span class="text-emerald-500 text-xl font-medium mr-1">$</span>
                        <input type="number" step="0.01" x-model.number="totals.usd" readonly class="w-full text-3xl font-black text-slate-800 placeholder-slate-200 outline-none bg-transparent">
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Total (Bs)</label>
                    <div class="flex items-center">
                        <span class="text-slate-400 text-xl font-medium mr-1">Bs</span>
                        <input type="number" step="0.01" x-model.number="totals.bs" readonly class="w-full text-3xl font-black text-slate-800 placeholder-slate-200 outline-none bg-transparent">
                    </div>
                </div>
            </div>
        </div>

        <!-- Income Destination -->
        <div class="bg-white rounded-[2rem] shadow-xl shadow-slate-200/50 p-6 space-y-5 border border-slate-50">
            <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Destino del Ingreso</h3>
            
            <!-- Account Selection -->
            <div class="space-y-2">
                <label class="block text-xs font-bold text-slate-600">Cuenta de Depósito</label>
                <div class="relative">
                    <select x-model="form.account_id" class="w-full appearance-none bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 font-bold text-slate-700 outline-none focus:border-indigo-500 transition-colors">
                        <option value="" disabled>Seleccione cuenta...</option>
                        <template x-for="acc in accounts" :key="acc.id">
                            <option :value="acc.id" x-text="acc.name + ' (' + acc.currency + ')'"></option>
                        </template>
                    </select>
                    <span class="material-icons absolute right-4 top-3.5 text-slate-400 pointer-events-none text-lg">expand_more</span>
                </div>
            </div>

            <!-- Category Selection -->
            <div class="space-y-2">
                <label class="block text-xs font-bold text-slate-600">Categoría</label>
                <div class="relative">
                    <select x-model="form.category_id" class="w-full appearance-none bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 font-bold text-slate-700 outline-none focus:border-indigo-500 transition-colors">
                        <option value="" disabled>Seleccione categoría...</option>
                        <template x-for="cat in categories" :key="cat.id">
                            <option :value="cat.id" x-text="cat.name"></option>
                        </template>
                    </select>
                    <span class="material-icons absolute right-4 top-3.5 text-slate-400 pointer-events-none text-lg">expand_more</span>
                </div>
            </div>

            <!-- Owner Selection -->
             <div class="space-y-2">
                <label class="block text-xs font-bold text-slate-600">Asignar a</label>
                <div class="grid grid-cols-3 gap-2">
                    <button @click="form.owner = 'Negocio'" type="button" 
                            class="py-2 rounded-xl text-xs font-bold border transition-all"
                            :class="form.owner === 'Negocio' ? 'bg-indigo-50 text-indigo-600 border-indigo-200 shadow-sm' : 'bg-white text-slate-500 border-slate-100 hover:bg-slate-50'">
                        Negocio
                    </button>
                    <button @click="form.owner = 'Anthony'" type="button" 
                            class="py-2 rounded-xl text-xs font-bold border transition-all"
                            :class="form.owner === 'Anthony' ? 'bg-blue-50 text-blue-600 border-blue-200 shadow-sm' : 'bg-white text-slate-500 border-slate-100 hover:bg-slate-50'">
                        Anthony
                    </button>
                    <button @click="form.owner = 'Arianny'" type="button" 
                            class="py-2 rounded-xl text-xs font-bold border transition-all"
                            :class="form.owner === 'Arianny' ? 'bg-pink-50 text-pink-600 border-pink-200 shadow-sm' : 'bg-white text-slate-500 border-slate-100 hover:bg-slate-50'">
                        Arianny
                    </button>
                </div>
             </div>
        </div>

        <!-- Payment Status -->
        <div class="bg-white rounded-[2rem] shadow-xl shadow-slate-200/50 p-2 border border-slate-50">
            <div class="flex relative bg-slate-100 rounded-xl p-1">
                <div class="absolute inset-y-1 w-[48%] bg-white rounded-lg shadow-sm transition-all duration-300 ease-out"
                     :class="form.status === 'paid' ? 'left-1' : 'left-[51%]'"></div>
                
                <button @click="form.status = 'paid'; form.paid_amount = totals.bs; form.paid_amount_usd = totals.usd" 
                        class="flex-1 relative z-10 py-3 text-sm font-bold transition-colors"
                        :class="form.status === 'paid' ? 'text-emerald-600' : 'text-slate-400'">
                    Pago Completo
                </button>
                <button @click="form.status = 'partial'; form.paid_amount = ''; form.paid_amount_usd = ''" 
                        class="flex-1 relative z-10 py-3 text-sm font-bold transition-colors"
                        :class="form.status === 'partial' ? 'text-amber-600' : 'text-slate-400'">
                    Crédito / Parcial
                </button>
            </div>

            <!-- Partial Payment Details -->
            <div x-show="form.status === 'partial'" x-transition:enter="transition ease-out duration-300" 
                 class="mt-4 px-4 pb-4 border-t border-slate-100 pt-4">
                <p class="text-xs font-bold text-slate-400 uppercase mb-3 text-center">Abono Inicial</p>
                
                <div class="flex items-center justify-center gap-4">
                     <div class="bg-amber-50 rounded-xl p-3 w-1/2">
                        <label class="text-[10px] text-amber-700 font-bold block mb-1">Monto ($)</label>
                        <input type="number" step="0.01" x-model.number="form.paid_amount_usd" @input="calculatePayment('usd')" class="w-full bg-transparent text-xl font-bold text-amber-900 outline-none" placeholder="0.00">
                    </div>
                    <div class="bg-amber-50 rounded-xl p-3 w-1/2">
                        <label class="text-[10px] text-amber-700 font-bold block mb-1">Monto (Bs)</label>
                        <input type="number" step="0.01" x-model.number="form.paid_amount" @input="calculatePayment('bs')" class="w-full bg-transparent text-xl font-bold text-amber-900 outline-none" placeholder="0.00">
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <span class="text-xs text-slate-400">Restante por cobrar:</span>
                    <span class="text-sm font-bold text-rose-500" x-text="formatMoney((totals.usd || 0) - (form.paid_amount_usd || 0), 'USD')"></span>
                </div>
            </div>
        </div>


        <!-- Order Status -->
        <div class="bg-white rounded-[2rem] shadow-xl shadow-slate-200/50 p-6 border border-slate-50">
            <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Estado del Pedido</h3>
            <div class="flex flex-wrap gap-2">
                <template x-for="st in statuses" :key="st.id">
                    <button @click="form.order_status_id = st.id" 
                            type="button"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition-all border"
                            :class="form.order_status_id == st.id ? (st.color + ' border-transparent shadow-md transform scale-105') : 'bg-slate-50 text-slate-500 border-slate-100 hover:bg-slate-100'">
                        <span x-text="st.name"></span>
                    </button>
                </template>
            </div>
        </div>

        <!-- Note -->
        <div class="bg-white rounded-[2rem] shadow-xl shadow-slate-200/50 p-6 border border-slate-50">
            <div class="input-group">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Nota Adicional</label>
                <input type="text" x-model="form.reference" class="w-full font-bold text-slate-600 placeholder-slate-300 border-none outline-none bg-transparent" placeholder="Referencia o detalles...">
            </div>
        </div>

    </div>

    <!-- Floating Action Button -->
    <div class="fixed bottom-6 left-0 right-0 px-6 max-w-md mx-auto z-40">
        <button @click="submit()" :disabled="loading || form.items.length === 0" class="w-full bg-indigo-900 text-white h-16 rounded-[2rem] font-bold text-lg shadow-2xl shadow-indigo-900/40 active:scale-95 transition-all flex items-center justify-center gap-3 disabled:opacity-70 disabled:cursor-not-allowed group">
            <span x-show="!loading" class="group-hover:-translate-y-0.5 transition-transform">Registrar Venta</span>
            <span x-show="!loading" class="material-icons text-indigo-300 group-hover:rotate-45 transition-transform">arrow_forward</span>
            <span x-show="loading" class="material-icons animate-spin">refresh</span>
        </button>
    </div>

    <!-- Toast Notification -->
    <div x-show="message" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-10 scale-90"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         class="fixed bottom-24 left-1/2 transform -translate-x-1/2 bg-slate-900/90 backdrop-blur-md text-white px-6 py-4 rounded-2xl shadow-2xl z-[100] flex items-center gap-4 border border-white/10 pointer-events-none min-w-[300px]">
        <div class="w-8 h-8 rounded-full bg-emerald-500/20 flex items-center justify-center">
            <span class="material-icons text-emerald-400 text-sm">check</span>
        </div>
        <div>
            <p class="font-bold text-sm">Notificación</p>
            <p x-text="message" class="text-xs text-slate-300"></p>
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
                             // Default to 'Ventas' if exists (usually ID 2 or name 'Ventas')
                             // Or just select the first one
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
                             // Auto select first active
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
                            // Set default to first one if exists and not set
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
                    this.form = {
                        date: date,
                        customer: '',
                        exchange_rate: rate,
                        status: 'paid',
                        order_status_id: statusId,
                        paid_amount: '',
                        paid_amount_usd: '',
                        reference: '',
                        items: []
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
                        this.searchResults = data.data;
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

                    // Update payment fields if set to full payment
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
                        this.showMsg('Agregue productos y cliente');
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
