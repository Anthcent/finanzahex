<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Productos | Fi-Hex</title>
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
<body class="bg-slate-50 min-h-screen text-slate-800 antialiased" x-data="itemsApp()">

    <!-- Executive Top Nav Header -->
    <header class="sticky top-0 z-40 bg-gradient-to-r from-emerald-950 via-slate-900 to-teal-950 text-white shadow-xl border-b border-emerald-800/30 safe-top">
        <div class="max-w-md mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="<?= base_url('inventory') ?>" class="w-10 h-10 rounded-2xl bg-white/10 hover:bg-white/20 active:scale-95 flex items-center justify-center text-white transition-all border border-white/10" title="Volver">
                    <span class="material-icons text-xl">arrow_back</span>
                </a>
                <div>
                    <h1 class="text-sm sm:text-base font-black tracking-tight text-white leading-tight">Catálogo de Productos</h1>
                    <p class="text-[10px] text-emerald-200/70 font-semibold" x-text="filteredItems.length + ' ítems listados'"></p>
                </div>
            </div>
            <button @click="openModal()" class="w-10 h-10 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white flex items-center justify-center shadow-md shadow-emerald-950/20 active:scale-95 transition-all border border-emerald-500/30" title="Nuevo Producto">
                <span class="material-icons text-xl">add</span>
            </button>
        </div>
    </header>

    <!-- Search & Filter Sticky Bar -->
    <div class="max-w-md mx-auto px-4 py-2.5 sticky top-[60px] z-30 bg-slate-50/95 backdrop-blur-xs">
        <div class="relative">
            <span class="material-icons absolute left-3.5 top-2.5 text-slate-400 text-lg">search</span>
            <input type="text" x-model="search" class="w-full bg-white border border-slate-200 rounded-2xl py-2.5 pl-10 pr-4 text-xs sm:text-sm font-bold text-slate-800 outline-none focus:border-emerald-500 shadow-2xs transition-all" placeholder="Buscar por nombre...">
        </div>
    </div>

    <!-- Items List -->
    <main class="max-w-md mx-auto p-4 pb-28 space-y-3 safe-bottom">
        
        <div x-show="filteredItems.length === 0" class="text-center py-16 bg-white rounded-3xl border border-dashed border-slate-200 p-8">
            <span class="material-icons text-4xl text-slate-300 mb-2">inventory_2</span>
            <p class="text-sm font-bold text-slate-500">No se encontraron productos</p>
            <p class="text-xs text-slate-400 mt-0.5">Usa el botón '+' para agregar el primero.</p>
        </div>

        <template x-for="item in filteredItems" :key="item.id">
            <div class="bg-white rounded-3xl p-4 shadow-xs border border-slate-100 flex justify-between items-center group cursor-pointer hover:border-emerald-200 active:scale-98 transition-all" @click="editItem(item)">
                <div class="flex items-center gap-3 min-w-0 flex-1 pr-3">
                    <div class="w-11 h-11 rounded-2xl flex items-center justify-center font-black text-sm shrink-0 shadow-2xs"
                         :class="item.stock < 5 ? 'bg-rose-50 text-rose-600 border border-rose-100' : 'bg-emerald-50 text-emerald-700 border border-emerald-100'">
                        <span x-text="item.name.charAt(0).toUpperCase()"></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="font-black text-slate-900 text-sm leading-tight truncate" x-text="item.name"></h3>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-md"
                                  :class="item.stock < 5 ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-600'"
                                  x-text="'Stock: ' + item.stock + ' ' + (item.unit || 'und')"></span>
                            <span class="text-[10px] font-bold text-slate-400 truncate" x-text="item.category_name || 'Sin Categoría'"></span>
                        </div>
                    </div>
                </div>
                <div class="text-right shrink-0">
                    <p class="font-black text-slate-900 text-base" x-text="'$ ' + parseFloat(item.price).toFixed(2)"></p>
                    <span class="text-[10px] font-bold text-slate-400" x-text="'Costo: $' + parseFloat(item.cost || 0).toFixed(2)"></span>
                </div>
            </div>
        </template>

    </main>

    <!-- ADD/EDIT PRODUCT MODAL (Mobile Bottom Sheet) -->
    <div x-show="showModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4" x-cloak>
        <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs transition-opacity" @click="showModal = false"></div>
        
        <div class="bg-white rounded-t-[2.5rem] sm:rounded-3xl p-5 sm:p-6 w-full sm:max-w-md shadow-2xl relative z-10 flex flex-col max-h-[90vh] safe-bottom animate-slide-up">
            
            <div class="flex justify-between items-center border-b border-slate-100 pb-3 mb-4 shrink-0">
                <h2 class="text-base sm:text-lg font-black text-slate-900" x-text="form.id ? 'Editar Producto' : 'Nuevo Producto'"></h2>
                <button @click="showModal = false" class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 hover:text-slate-800 transition-colors">
                    <span class="material-icons text-sm">close</span>
                </button>
            </div>

            <div class="overflow-y-auto flex-1 space-y-4 customize-scrollbar pr-1">
                <!-- Name -->
                <div>
                     <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block mb-1">Nombre del Producto</label>
                     <input type="text" x-model="form.name" class="w-full bg-slate-50 border border-slate-200 rounded-2xl p-3 text-xs sm:text-sm font-black text-slate-900 outline-none focus:border-emerald-500 focus:bg-white transition-all" placeholder="Ej. Taza Personalizada">
                </div>

                <!-- Category -->
                <div>
                     <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block mb-1">Categoría</label>
                     <div class="flex gap-2">
                         <select x-model="form.category_id" class="flex-1 bg-slate-50 border border-slate-200 rounded-2xl p-3 font-bold text-slate-800 outline-none text-xs focus:border-emerald-500 focus:bg-white transition-all">
                             <option value="">Seleccionar Categoría...</option>
                             <template x-for="cat in categories" :key="cat.id">
                                 <option :value="cat.id" x-text="cat.name"></option>
                             </template>
                         </select>
                         <button @click="addCategory()" type="button" class="px-3.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-2xl hover:bg-emerald-100 transition-all flex items-center justify-center">
                             <span class="material-icons text-base">add</span>
                         </button>
                     </div>
                </div>

                <!-- Price & Cost -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                         <label class="text-[10px] font-black text-emerald-700 uppercase tracking-wider block mb-1">Precio Venta ($)</label>
                         <input type="number" step="0.01" x-model="form.price" class="w-full bg-slate-50 border border-slate-200 rounded-2xl p-3 font-black text-emerald-700 outline-none focus:border-emerald-500 focus:bg-white transition-all" placeholder="0.00">
                    </div>
                    <div>
                         <label class="text-[10px] font-black text-rose-700 uppercase tracking-wider block mb-1">Costo ($)</label>
                         <input type="number" step="0.01" x-model="form.cost" class="w-full bg-slate-50 border border-slate-200 rounded-2xl p-3 font-black text-rose-600 outline-none focus:border-rose-500 focus:bg-white transition-all" placeholder="0.00">
                    </div>
                </div>

                <!-- Initial Stock (Only for New) -->
                <div x-show="!form.id">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block mb-1">Stock Inicial</label>
                    <div class="flex gap-2">
                        <input type="number" x-model="form.stock" class="flex-1 bg-slate-50 border border-slate-200 rounded-2xl p-3 font-black text-slate-900 outline-none focus:border-emerald-500 focus:bg-white transition-all" placeholder="0">
                        <select x-model="form.unit" class="w-24 bg-slate-50 border border-slate-200 rounded-2xl p-3 font-black text-xs text-slate-700 outline-none focus:border-emerald-500">
                            <option value="unid">Unid</option>
                            <option value="kg">Kg</option>
                            <option value="g">g</option>
                            <option value="lt">Lt</option>
                            <option value="m">m</option>
                            <option value="cm">cm</option>
                        </select>
                    </div>
                </div>
                
                <!-- Description -->
                <div>
                     <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block mb-1">Descripción / Notas</label>
                     <textarea x-model="form.description" class="w-full bg-slate-50 border border-slate-200 rounded-2xl p-3 text-xs font-bold text-slate-800 outline-none h-18 resize-none focus:border-emerald-500 focus:bg-white transition-all" placeholder="Detalles del producto..."></textarea>
                </div>

                <!-- Characteristics Tags -->
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block mb-1">Características Dinámicas</label>
                    
                    <div class="space-y-1.5 mb-2">
                        <template x-for="(value, key) in form.characteristics" :key="key">
                            <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl p-2">
                                <span class="text-[10px] font-black text-slate-700 bg-white px-2 py-0.5 rounded-lg border border-slate-100" x-text="key"></span>
                                <span class="text-xs font-bold text-slate-800 flex-1 truncate" x-text="value"></span>
                                <button @click="delete form.characteristics[key];" class="text-rose-400 hover:text-rose-600 p-1">
                                    <span class="material-icons text-xs">close</span>
                                </button>
                            </div>
                        </template>
                    </div>

                    <div class="flex gap-2 items-center">
                        <input type="text" x-model="newCharKey" placeholder="Propiedad (ej. Color)" class="w-1/3 bg-slate-50 border border-slate-200 rounded-xl p-2 text-xs font-bold text-slate-800 outline-none focus:border-emerald-500">
                        <input type="text" x-model="newCharValue" @keydown.enter="addChar()" placeholder="Valor (ej. Azul)" class="flex-1 bg-slate-50 border border-slate-200 rounded-xl p-2 text-xs font-bold text-slate-800 outline-none focus:border-emerald-500">
                        <button @click="addChar()" type="button" class="bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-xl p-2 hover:bg-emerald-100">
                            <span class="material-icons text-sm">add</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 flex gap-2.5 shrink-0">
                <button x-show="form.id" @click="deleteItem()" class="px-4 py-3 bg-rose-50 text-rose-600 hover:bg-rose-100 rounded-2xl font-bold transition-all" title="Eliminar Producto">
                    <span class="material-icons text-base">delete</span>
                </button>
                <button @click="save()" class="flex-1 py-3.5 bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white rounded-2xl font-black text-xs sm:text-sm shadow-xl shadow-emerald-950/20 active:scale-95 transition-all">
                    Guardar Producto
                </button>
            </div>
        </div>
    </div>

    <!-- CATEGORY MODAL (Mobile Bottom Sheet) -->
    <div x-show="showCategoryModal" 
         @keydown.escape.window="showCategoryModal = false"
         class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center p-0 sm:p-4" x-cloak>
        <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs transition-opacity" @click="showCategoryModal = false"></div>
        
        <div class="bg-white rounded-t-[2.5rem] sm:rounded-3xl p-6 w-full sm:max-w-xs shadow-2xl relative z-10 safe-bottom animate-slide-up space-y-4">
            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                <h3 class="text-base font-black text-slate-900">Nueva Categoría</h3>
                <button @click="showCategoryModal = false" class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 hover:text-slate-800 transition-colors">
                    <span class="material-icons text-sm">close</span>
                </button>
            </div>
            
            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block mb-1">Nombre</label>
                <input type="text" x-model="categoryName" x-ref="categoryInput" @keydown.enter="saveCategory()" class="w-full bg-slate-50 border border-slate-200 rounded-2xl p-3 font-bold text-slate-800 outline-none focus:border-emerald-500 focus:bg-white transition-all" placeholder="Ej. Accesorios">
            </div>

            <div class="flex gap-2.5 pt-2">
                <button @click="showCategoryModal = false" class="flex-1 py-3 bg-slate-100 text-slate-600 hover:bg-slate-200 rounded-2xl font-bold text-xs sm:text-sm transition-colors">Cancelar</button>
                <button @click="saveCategory()" class="flex-1 py-3 bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white rounded-2xl font-black text-xs sm:text-sm shadow-md transition-all">Guardar</button>
            </div>
        </div>
    </div>

    <script>
        function itemsApp() {
            return {
                items: [],
                categories: [],
                search: '',
                showModal: false,
                showCategoryModal: false,
                categoryName: '',
                newCharKey: '',
                newCharValue: '',
                form: {
                    id: null,
                    name: '',
                    category_id: '',
                    price: '',
                    cost: '',
                    stock: '',
                    unit: 'unid',
                    description: '',
                    characteristics: {}
                },

                init() {
                    this.fetchItems();
                    this.fetchCategories();
                },

                async fetchItems() {
                    try {
                        let res = await fetch('<?= base_url('inventory/get-items') ?>');
                        let data = await res.json();
                        this.items = (data.data || []).map(i => {
                            if(typeof i.characteristics === 'string' && i.characteristics) {
                                try { i.characteristics = JSON.parse(i.characteristics); } catch(e) { i.characteristics = {}; }
                            } else if (!i.characteristics) {
                                i.characteristics = {};
                            }
                            return i;
                        });
                    } catch(e) {}
                },

                async fetchCategories() {
                    try {
                        let res = await fetch('<?= base_url('inventory/get-categories') ?>');
                        let data = await res.json();
                        this.categories = data.data || [];
                    } catch(e) {}
                },

                get filteredItems() {
                    if(!this.search) return this.items;
                    const q = this.search.toLowerCase();
                    return this.items.filter(i => i.name.toLowerCase().includes(q));
                },

                openModal() {
                    this.form = { id: null, name: '', category_id: '', price: '', cost: '', stock: '', unit: 'unid', description: '', characteristics: {} };
                    this.newCharKey = '';
                    this.newCharValue = '';
                    this.showModal = true;
                },

                editItem(item) {
                    this.form = JSON.parse(JSON.stringify(item));
                    if(!this.form.characteristics) this.form.characteristics = {};
                    this.showModal = true;
                },

                addChar() {
                    if(!this.newCharKey || !this.newCharValue) return;
                    this.form.characteristics[this.newCharKey] = this.newCharValue;
                    this.newCharKey = '';
                    this.newCharValue = '';
                },

                async save() {
                    if(!this.form.name) return alert('El nombre del producto es requerido');
                    
                    try {
                        let res = await fetch('<?= base_url('inventory/save-item') ?>', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify(this.form)
                        });
                        let data = await res.json();
                        if(data.status === 'success') {
                            this.showModal = false;
                            this.fetchItems();
                        } else {
                            alert('Error al guardar: ' + (data.message || 'Error desconocido'));
                        }
                    } catch(e) {
                        alert('Error de conexión');
                    }
                },

                async deleteItem() {
                    if(!confirm('¿Eliminar producto? Esta acción no se puede deshacer.')) return;
                    try {
                        await fetch('<?= base_url('inventory/delete-item/') ?>' + this.form.id);
                        this.showModal = false;
                        this.fetchItems();
                    } catch(e) {
                        alert('Error de conexión');
                    }
                },

                addCategory() {
                    this.categoryName = '';
                    this.showCategoryModal = true;
                    this.$nextTick(() => {
                        this.$refs.categoryInput.focus();
                    });
                },

                async saveCategory() {
                    if (!this.categoryName) return;

                    try {
                        await fetch('<?= base_url('inventory/save-category') ?>', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({ name: this.categoryName, type: 'product' })
                        });
                        
                        this.showCategoryModal = false;
                        this.fetchCategories();
                    } catch(e) {
                        alert('Error de conexión');
                    }
                }
            }
        }
    </script>
</body>
</html>
