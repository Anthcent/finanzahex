<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen" x-data="itemsApp()">

    <!-- Header -->
    <div class="bg-white/90 backdrop-blur-md border-b border-slate-100 sticky top-0 z-40">
        <div class="max-w-md mx-auto flex items-center justify-between p-4">
            <a href="<?= base_url('inventory') ?>" class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-600 hover:bg-slate-100 transition-colors">
                <span class="material-icons">arrow_back</span>
            </a>
            <h1 class="text-lg font-bold text-slate-800">Productos</h1>
            <button @click="openModal()" class="w-10 h-10 rounded-full bg-purple-50 text-purple-600 flex items-center justify-center hover:bg-purple-100 transition-colors">
                <span class="material-icons">add</span>
            </button>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="max-w-md mx-auto px-4 py-2 sticky top-[73px] z-30 bg-slate-50/95 backdrop-blur">
        <div class="relative">
            <span class="material-icons absolute left-3 top-2.5 text-slate-400 text-sm">search</span>
            <input type="text" x-model="search" class="w-full bg-white border border-slate-200 rounded-xl py-2 pl-9 pr-4 text-sm font-bold text-slate-700 outline-none focus:border-purple-300 transition-colors" placeholder="Buscar producto...">
        </div>
    </div>

    <div class="max-w-md mx-auto p-4 pb-24 space-y-3">
        
        <template x-if="filteredItems.length === 0">
            <div class="text-center py-10 opacity-50">
                <p class="text-sm font-medium text-slate-400">No se encontraron productos</p>
            </div>
        </template>

        <template x-for="item in filteredItems" :key="item.id">
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 flex justify-between items-center group relative overflow-hidden" @click="editItem(item)">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-lg"
                         :class="item.stock < 5 ? 'bg-rose-50 text-rose-500' : 'bg-purple-50 text-purple-600'">
                        <span x-text="item.name.charAt(0)"></span>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800 leading-tight" x-text="item.name"></h3>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">
                            Stock: <span :class="item.stock < 5 ? 'text-rose-500' : 'text-slate-600'" x-text="item.stock + ' ' + item.unit"></span>
                        </p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="font-black text-slate-800" x-text="'$ ' + parseFloat(item.price).toFixed(2)"></p>
                    <p class="text-[10px] text-slate-400 font-medium" x-text="item.category_name || 'Sin Categoría'"></p>
                </div>
            </div>
        </template>

    </div>

    <!-- ADD/EDIT MODAL -->
    <div x-show="showModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:px-4" x-cloak>
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showModal = false"></div>
        
        <div class="bg-white rounded-t-[2rem] sm:rounded-[2rem] p-6 w-full max-w-sm shadow-2xl relative z-10 transform h-[85vh] flex flex-col">
            
            <div class="w-12 h-1.5 bg-slate-200 rounded-full mx-auto mb-6 shrink-0"></div>
            
            <h2 class="text-xl font-bold text-slate-800 mb-4" x-text="form.id ? 'Editar Producto' : 'Nuevo Producto'"></h2>

            <div class="overflow-y-auto flex-1 space-y-4 pr-1">
                <!-- Name -->
                <div>
                     <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Nombre</label>
                     <input type="text" x-model="form.name" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-slate-700 outline-none focus:border-purple-500 transition-colors">
                </div>

                <!-- Category -->
                <div>
                     <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Categoría</label>
                     <div class="flex gap-2">
                         <select x-model="form.category_id" class="flex-1 bg-slate-50 border border-slate-200 rounded-xl p-3 font-medium text-slate-700 outline-none text-xs">
                             <option value="">Seleccionar...</option>
                             <template x-for="cat in categories" :key="cat.id">
                                 <option :value="cat.id" x-text="cat.name"></option>
                             </template>
                         </select>
                         <button @click="addCategory()" class="px-3 bg-purple-50 text-purple-600 rounded-xl"><span class="material-icons text-sm">add</span></button>
                     </div>
                </div>

                <!-- Price & Cost -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                         <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Precio Venta ($)</label>
                         <input type="number" step="0.01" x-model="form.price" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-slate-700 outline-none focus:border-emerald-500 transition-colors">
                    </div>
                    <div>
                         <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Costo ($)</label>
                         <input type="number" step="0.01" x-model="form.cost" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-slate-700 outline-none focus:border-rose-500 transition-colors">
                    </div>
                </div>

                <!-- Initial Stock (Only for New) -->
                <div x-show="!form.id">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Stock Inicial</label>
                    <div class="flex gap-2">
                        <input type="number" x-model="form.stock" class="flex-1 bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-slate-700 outline-none focus:border-purple-500 transition-colors">
                        <select x-model="form.unit" class="w-24 bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-xs text-slate-700 outline-none">
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
                     <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Descripción</label>
                     <textarea x-model="form.description" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-medium text-slate-700 outline-none h-20 resize-none"></textarea>
                </div>

                <!-- CHARACTERISTICS -->
                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Características</label>
                    
                    <div class="space-y-2 mb-2">
                        <template x-for="(value, key) in form.characteristics" :key="key">
                            <div class="flex items-center gap-2 bg-slate-50 border border-slate-100 rounded-lg p-2">
                                <span class="text-xs font-bold text-slate-500 bg-white px-2 py-0.5 rounded shadow-sm" x-text="key"></span>
                                <span class="text-xs font-medium text-slate-700 flex-1" x-text="value"></span>
                                <button @click="delete charTemp[key]; delete form.characteristics[key];" class="text-rose-400 hover:text-rose-600"><span class="material-icons text-sm">close</span></button>
                            </div>
                        </template>
                    </div>

                    <div class="flex gap-2 items-center">
                        <input type="text" x-model="newCharKey" placeholder="Ej. Color" class="w-1/3 bg-slate-50 border border-slate-200 rounded-xl p-2 text-xs font-bold text-slate-700 outline-none">
                        <input type="text" x-model="newCharValue" @keydown.enter="addChar()" placeholder="Ej. Rojo" class="flex-1 bg-slate-50 border border-slate-200 rounded-xl p-2 text-xs font-bold text-slate-700 outline-none">
                        <button @click="addChar()" class="bg-indigo-50 text-indigo-600 rounded-xl p-2"><span class="material-icons text-sm">add</span></button>
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-4 border-t border-slate-100 flex gap-3 shrink-0">
                <button x-show="form.id" @click="deleteItem()" class="px-4 py-3 bg-rose-50 text-rose-600 rounded-xl font-bold"><span class="material-icons">delete</span></button>
                <button @click="save()" class="flex-1 py-3 bg-purple-600 text-white rounded-xl font-bold shadow-lg shadow-purple-200 active:scale-95 transition-all">Guardar</button>
            </div>
        </div>
    </div>

    <!-- CATEGORY MODAL -->
    <!-- CATEGORY MODAL -->
    <div x-show="showCategoryModal" 
         @keydown.escape.window="showCategoryModal = false"
         class="fixed inset-0 z-[60] flex items-center justify-center px-4" x-cloak>
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showCategoryModal = false"></div>
        
        <div x-trap.noscroll="showCategoryModal" 
             class="bg-white rounded-2xl p-6 w-full max-w-xs shadow-2xl relative z-10 transform transition-all scale-100">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Nueva Categoría</h3>
            
            <div class="mb-4">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Nombre</label>
                <input type="text" x-model="categoryName" x-ref="categoryInput" @keydown.enter="saveCategory()" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-slate-700 outline-none focus:border-purple-500 transition-colors" placeholder="Ej. Bebidas">
            </div>

            <div class="flex gap-2">
                <button @click="showCategoryModal = false" class="flex-1 py-2 bg-slate-100 text-slate-600 rounded-xl font-bold text-sm">Cancelar</button>
                <button @click="saveCategory()" class="flex-1 py-2 bg-purple-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-purple-200">Guardar</button>
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
                    let res = await fetch('<?= base_url('inventory/get-items') ?>');
                    let data = await res.json();
                    this.items = data.data.map(i => {
                        // Parse characteristics if string
                        if(typeof i.characteristics === 'string' && i.characteristics) {
                            try { i.characteristics = JSON.parse(i.characteristics); } catch(e) { i.characteristics = {}; }
                        } else if (!i.characteristics) {
                            i.characteristics = {};
                        }
                        return i;
                    });
                },

                async fetchCategories() {
                     let res = await fetch('<?= base_url('inventory/get-categories') ?>');
                     let data = await res.json();
                     this.categories = data.data;
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
                    this.form = JSON.parse(JSON.stringify(item)); // Deep clone
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
                    if(!this.form.name) return alert('Nombre requerido');
                    
                    let res = await fetch('<?= base_url('inventory/save-item') ?>', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(this.form)
                    });
                    let data = await res.json();
                    if(data.status === 'success') {
                        this.showModal = false;
                        this.fetchItems();
                    }
                },

                async deleteItem() {
                    if(!confirm('¿Eliminar producto?')) return;
                    await fetch('<?= base_url('inventory/delete-item/') ?>' + this.form.id);
                    this.showModal = false;
                    this.fetchItems();
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

                    await fetch('<?= base_url('inventory/save-category') ?>', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ name: this.categoryName, type: 'product' })
                    });
                    
                    this.showCategoryModal = false;
                    this.fetchCategories();
                }
            }
        }
    </script>
</body>
</html>
