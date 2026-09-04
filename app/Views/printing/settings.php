<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - Impresiones</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen" x-data="settingsApp()">

    <!-- Header -->
    <div class="bg-white border-b border-slate-100 sticky top-0 z-30">
        <div class="max-w-4xl mx-auto px-4 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="<?= base_url('printing') ?>" class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-slate-50 text-slate-500 transition-colors">
                    <span class="material-icons">arrow_back</span>
                </a>
                <h1 class="text-xl font-bold text-slate-800">Gestionar Productos</h1>
            </div>
            <button @click="openModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-bold shadow-lg shadow-indigo-200 transition-all flex items-center gap-2">
                <span class="material-icons text-sm">add</span>
                Nuevo Producto
            </button>
        </div>
    </div>

    <!-- Content -->
    <div class="max-w-4xl mx-auto p-4 md:p-8">
        <!-- General Config Section -->
        <div class="bg-indigo-600 rounded-2xl shadow-lg shadow-indigo-200 p-6 mb-8 text-white">
            <h2 class="font-bold text-lg mb-4 flex items-center gap-2">
                <span class="material-icons">settings</span>
                Configuración General
            </h2>
            <div class="space-y-4">
                <div>
                     <label class="block text-xs font-bold text-indigo-100 uppercase mb-2">Cuenta Predeterminada (Ventas)</label>
                     <select x-model="defaultAccount" class="w-full p-3 bg-white/10 backdrop-blur rounded-xl font-bold text-white outline-none border border-white/20 focus:bg-white/20 transition-colors">
                         <?php foreach($accounts as $acc): ?>
                         <option value="<?= $acc['id'] ?>" class="text-slate-800">
                             <?= $acc['name'] ?> (<?= $acc['currency'] ?? 'Bs' ?>)
                         </option>
                         <?php endforeach; ?>
                     </select>
                     <p class="text-[10px] text-indigo-200 mt-1">Cuenta seleccionada autom. al abrir el módulo.</p>
                </div>

                <div>
                     <label class="block text-xs font-bold text-indigo-100 uppercase mb-2">Categoría (Impresiones)</label>
                     <select x-model="defaultCategory" class="w-full p-3 bg-white/10 backdrop-blur rounded-xl font-bold text-white outline-none border border-white/20 focus:bg-white/20 transition-colors">
                         <?php foreach($categories as $cat): ?>
                         <option value="<?= $cat['id'] ?>" class="text-slate-800">
                             <?= $cat['name'] ?>
                         </option>
                         <?php endforeach; ?>
                     </select>
                </div>

                <div class="pt-2">
                    <button @click="saveGlobalSettings()" class="w-full bg-white text-indigo-600 font-bold py-3 rounded-xl shadow-lg hover:bg-indigo-50 active:scale-95 transition-all flex items-center justify-center gap-2">
                        <span class="material-icons">save</span>
                        Guardar Configuración
                    </button>
                </div>
            </div>
        </div>

        <!-- Products Grid (Mobile Friendly) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach($products as $p): ?>
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between group">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-<?= $p['color'] ?>-50 text-<?= $p['color'] ?>-600 flex items-center justify-center">
                        <span class="material-icons"><?= $p['icon'] ?></span>
                    </div>
                    <div>
                         <h3 class="font-bold text-slate-800 leading-tight"><?= $p['name'] ?></h3>
                         <p class="text-xs text-slate-400"><?= $p['category'] ?></p>
                         <div class="flex gap-2 mt-1">
                             <span class="text-xs font-bold text-slate-600">Bs. <?= $p['price_bs'] ?></span>
                             <span class="text-xs font-bold text-emerald-600">$<?= $p['price_usd'] ?></span>
                         </div>
                    </div>
                </div>
                <div class="flex flex-col gap-2">
                    <button @click='edit(<?= json_encode($p) ?>)' class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <span class="material-icons text-sm">edit</span>
                    </button>
                    <button @click="remove(<?= $p['id'] ?>)" class="w-8 h-8 rounded-lg bg-rose-50 text-rose-500 flex items-center justify-center">
                        <span class="material-icons text-sm">delete</span>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if(empty($products)): ?>
        <div class="text-center py-12 text-slate-400">
            <span class="material-icons text-4xl mb-2">inventory_2</span>
            <p>No hay productos registrados.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal -->
    <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="showModal = false"></div>
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md relative z-10 p-6 space-y-4">
            <h2 class="text-lg font-bold text-slate-800" x-text="form.id ? 'Editar Producto' : 'Nuevo Producto'"></h2>
            
            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Nombre</label>
                    <input type="text" x-model="form.name" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 outline-none focus:border-indigo-500 font-bold text-slate-700">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Precio Bs</label>
                        <input type="number" step="0.01" x-model="form.price_bs" 
                               @input="if(rate > 0) form.price_usd = (form.price_bs / rate).toFixed(2)"
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 outline-none focus:border-indigo-500 font-bold text-slate-700">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-emerald-600 mb-1">Precio USD</label>
                        <input type="number" step="0.01" x-model="form.price_usd" class="w-full bg-emerald-50 border border-emerald-200 rounded-xl px-3 py-2 outline-none focus:border-emerald-500 font-bold text-emerald-700">
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Categoría</label>
                        <select x-model="form.category" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 outline-none focus:border-indigo-500 font-bold text-slate-700">
                            <option value="General">General</option>
                            <option value="Impresiones">Impresiones</option>
                            <option value="Copias">Copias</option>
                            <option value="Papelería">Papelería</option>
                            <option value="Servicios">Servicios</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                </div>
                
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-500">Icono</label>
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 h-32 overflow-y-auto grid grid-cols-6 gap-2">
                         <template x-for="icon in icons" :key="icon">
                             <button type="button" 
                                     @click="form.icon = icon"
                                     class="w-8 h-8 rounded-lg flex items-center justify-center transition-all bg-white shadow-sm border border-slate-100"
                                     :class="form.icon === icon ? 'bg-indigo-600 !text-white !border-indigo-600 ring-2 ring-indigo-200' : 'text-slate-400 hover:bg-slate-100'">
                                 <span class="material-icons text-lg" x-text="icon"></span>
                             </button>
                         </template>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-2">Color</label>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="c in colors">
                            <button @click="form.color = c" 
                                    class="w-8 h-8 rounded-full shadow-sm border-2 transition-transform hover:scale-110" 
                                    :class="'bg-' + c + '-500 ' + (form.color === c ? 'border-slate-800 scale-110 ring-2 ring-slate-200' : 'border-white')">
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <button @click="showModal = false" class="px-4 py-2 text-slate-500 font-bold hover:bg-slate-50 rounded-xl transition-colors">Cancelar</button>
                <button @click="save()" class="px-6 py-2 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 shadow-lg shadow-indigo-200 transition-colors">Guardar</button>
            </div>
        </div>
    </div>

    <script>
        function settingsApp() {
            return {
                showModal: false,
                rate: <?= $rate ?? 55 ?>,
                defaultAccount: '<?= $defaultAccount ?>',
                defaultCategory: '<?= $defaultCategory ?>',
                
                // Expanded Color Palette
                colors: [
                    'slate', 'red', 'orange', 'amber', 'yellow', 'lime',
                    'green', 'emerald', 'teal', 'cyan', 'sky',
                    'blue', 'indigo', 'violet', 'purple', 'fuchsia',
                    'pink', 'rose'
                ],

                // Extended Icon Set
                icons: [
                    'print', 'description', 'image', 'picture_as_pdf', 'folder', 'content_copy', 
                    'save', 'edit', 'delete', 'add', 'attach_file', 'cloud_upload', 
                    'keyboard', 'mouse', 'smartphone', 'tablet', 'laptop', 'desktop_windows', 
                    'router', 'scanner', 'memory', 'sd_storage', 'sim_card', 'headphones', 
                    'mic', 'camera_alt', 'videocam', 'photo_camera', 'palette', 'brush', 
                    'design_services', 'school', 'history_edu', 'assignment', 'book', 
                    'library_books', 'menu_book', 'auto_stories', 'star', 'favorite'
                ],

                form: {
                    id: null,
                    name: '',
                    price_bs: 0,
                    price_usd: 0,
                    category: 'General',
                    icon: 'print',
                    color: 'indigo'
                },

                openModal() {
                    this.form = { id: null, name: '', price_bs: 0, price_usd: 0, category: 'General', icon: 'print', color: 'indigo' };
                    this.showModal = true;
                },

                edit(product) {
                    this.form = { ...product };
                    this.showModal = true;
                },

                async save() {
                    try {
                        let res = await fetch('<?= base_url('printing/save-product') ?>', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(this.form)
                        });
                        let data = await res.json();
                        if(data.status === 'success') {
                            location.reload();
                        }
                    } catch(e) {
                        alert('Error al guardar');
                    }
                },

                async remove(id) {
                    if(!confirm('¿Eliminar producto?')) return;
                    try {
                        let res = await fetch('<?= base_url('printing/delete-product/') ?>' + id);
                        let data = await res.json();
                        if(data.status === 'success') location.reload();
                    } catch(e) {}
                },
                
                async saveGlobalSettings() {
                    try {
                        await this.saveSetting('default_print_account', this.defaultAccount);
                        await this.saveSetting('default_print_category', this.defaultCategory);
                        this.showToast('Configuraciones guardadas correctamente');
                    } catch(e) {
                         console.error(e);
                         this.showToast('Error al guardar configuraciones', true);
                    }
                },

                async saveSetting(key, value) {
                    let url = '<?= base_url('config/save') ?>';
                    
                    let res = await fetch(url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ key: key, value: value })
                    });
                    
                    if (!res.ok) throw new Error('Network response was not ok: ' + res.statusText);
                    
                    let data = await res.json();
                    
                    if (data.status !== 'success') throw new Error(data.message || 'Error saving');
                },
                
                showToast(msg, isError = false) {
                    const toast = document.createElement('div');
                    toast.className = `fixed bottom-10 right-10 px-4 py-2 rounded-lg text-sm font-bold shadow-xl z-50 text-white ${isError ? 'bg-rose-600' : 'bg-slate-800'}`;
                    toast.innerText = msg;
                    document.body.appendChild(toast);
                    setTimeout(() => toast.remove(), 2000);
                }
            }
        }
    </script>
</body>
</html>
