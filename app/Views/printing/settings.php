<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Configuración - Impresiones & POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <meta name="theme-color" content="#047857">
    <style>
        body { font-family: 'Plus Jakarta Sans', 'Outfit', sans-serif; }
        .customize-scrollbar::-webkit-scrollbar { width: 5px; }
        .customize-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .customize-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
        @keyframes slide-up { from { transform: translateY(100%); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .animate-slide-up { animation: slide-up 0.28s cubic-bezier(0.16, 1, 0.3, 1); }
        [x-cloak] { display: none !important; }
        .safe-bottom { padding-bottom: env(safe-area-inset-bottom, 1rem); }
    </style>
</head>
<body class="bg-gradient-to-br from-emerald-50/60 via-slate-50 to-teal-50/40 min-h-screen text-slate-800 antialiased" x-data="settingsApp()">

    <!-- Header -->
    <header class="bg-white/90 backdrop-blur-xl border-b border-slate-200/80 sticky top-0 z-30 shadow-xs">
        <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5 min-w-0">
                <a href="<?= base_url('printing') ?>" class="w-9 h-9 flex items-center justify-center rounded-2xl bg-slate-100/80 hover:bg-emerald-50 text-slate-600 hover:text-emerald-700 transition-colors border border-slate-200/60 active:scale-95 shrink-0" title="Volver a Impresiones">
                    <span class="material-icons text-xl">arrow_back</span>
                </a>
                <div class="w-9 h-9 rounded-2xl bg-gradient-to-br from-emerald-600 to-teal-700 text-white flex items-center justify-center shadow-md shadow-emerald-950/20 ring-1 ring-emerald-400/40 shrink-0">
                    <span class="material-icons text-lg">tune</span>
                </div>
                <div class="leading-tight min-w-0">
                    <h1 class="font-black text-slate-900 tracking-tight text-sm sm:text-base truncate">
                        Catálogo <span class="bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent">Comercial</span>
                    </h1>
                    <p class="text-[9px] font-bold text-slate-400 hidden sm:block">Papelería, impresiones, franelas y personalizados</p>
                </div>
            </div>
            
            <button @click="openModal()" class="bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white px-3.5 sm:px-4 py-2 rounded-xl text-xs sm:text-sm font-black shadow-md shadow-emerald-950/20 transition-all flex items-center gap-1.5 active:scale-95 shrink-0">
                <span class="material-icons text-base">add</span>
                <span>Nuevo Producto</span>
            </button>
        </div>
    </header>

    <!-- Content Area -->
    <main class="max-w-6xl mx-auto p-4 sm:p-6 pb-24">
        
        <!-- General Config Section (Executive Fintech Gradient Card) -->
        <div class="bg-gradient-to-br from-slate-900 via-emerald-950 to-teal-950 rounded-3xl shadow-xl p-5 sm:p-6 mb-8 text-white relative overflow-hidden border border-emerald-500/20">
            <div class="absolute -right-8 -bottom-8 w-40 h-40 rounded-full bg-emerald-500/10 blur-2xl pointer-events-none"></div>
            
            <div class="flex items-center gap-2 mb-4">
                <span class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center">
                    <span class="material-icons text-lg">settings</span>
                </span>
                <div>
                    <h2 class="font-black text-base tracking-tight">Parámetros Predeterminados</h2>
                    <p class="text-[11px] text-emerald-200/70">Ajusta la cuenta de cobro y categoría de origen</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                     <label class="block text-[10px] font-black text-emerald-300 uppercase tracking-wider mb-1.5">Cuenta Predeterminada (Ventas)</label>
                     <select x-model="defaultAccount" class="w-full p-3 bg-white/10 backdrop-blur-md rounded-2xl font-bold text-white outline-none border border-white/15 focus:border-emerald-400 focus:bg-white/20 transition-all text-xs sm:text-sm">
                         <?php foreach($accounts as $acc): ?>
                         <option value="<?= $acc['id'] ?>" class="text-slate-800 font-bold">
                             <?= $acc['name'] ?> (<?= $acc['currency'] ?? 'Bs' ?>)
                         </option>
                         <?php endforeach; ?>
                     </select>
                     <p class="text-[10px] text-emerald-300/60 mt-1">Cuenta precargada automáticamente al abrir el TPV.</p>
                </div>

                <div>
                     <label class="block text-[10px] font-black text-emerald-300 uppercase tracking-wider mb-1.5">Categoría (Impresiones)</label>
                     <select x-model="defaultCategory" class="w-full p-3 bg-white/10 backdrop-blur-md rounded-2xl font-bold text-white outline-none border border-white/15 focus:border-emerald-400 focus:bg-white/20 transition-all text-xs sm:text-sm">
                         <?php foreach($categories as $cat): ?>
                         <option value="<?= $cat['id'] ?>" class="text-slate-800 font-bold">
                             <?= $cat['name'] ?>
                         </option>
                         <?php endforeach; ?>
                     </select>
                     <p class="text-[10px] text-emerald-300/60 mt-1">Categoría del módulo en el balance general.</p>
                </div>
            </div>

            <div class="pt-4 mt-4 border-t border-white/10 flex justify-end">
                <button @click="saveGlobalSettings()" class="w-full sm:w-auto px-6 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black py-2.5 rounded-xl shadow-lg hover:shadow-emerald-500/20 active:scale-98 transition-all flex items-center justify-center gap-2 text-xs sm:text-sm">
                    <span class="material-icons text-base">save</span>
                    <span>Guardar Preferencias</span>
                </button>
            </div>
        </div>

        <!-- Catalog Categories -->
        <section class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-4 sm:p-5 mb-7">
            <div class="flex items-center justify-between gap-3 mb-4">
                <div>
                    <h2 class="font-black text-slate-900 text-sm sm:text-base flex items-center gap-2">
                        <span class="material-icons text-amber-500">category</span>
                        Categorías del catálogo
                    </h2>
                    <p class="text-[10px] sm:text-xs text-slate-400 font-semibold mt-0.5">Organiza el punto de venta según las líneas de tu negocio.</p>
                </div>
                <button @click="openCategoryModal()" class="px-3 py-2 rounded-xl bg-amber-50 text-amber-800 border border-amber-200 text-xs font-black flex items-center gap-1 hover:bg-amber-100">
                    <span class="material-icons text-sm">add</span> Categoría
                </button>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5">
                <?php foreach ($catalogCategories as $catalogCategory): ?>
                <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-3 group">
                    <div class="flex items-start justify-between gap-1">
                        <span class="w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-600 flex items-center justify-center">
                            <span class="material-icons text-lg"><?= esc($catalogCategory['icon']) ?></span>
                        </span>
                        <div class="flex gap-1 opacity-70 group-hover:opacity-100">
                            <button @click='editCategory(<?= htmlspecialchars(json_encode($catalogCategory), ENT_QUOTES, 'UTF-8') ?>)' class="text-slate-500 hover:text-emerald-700"><span class="material-icons text-sm">edit</span></button>
                            <button @click="removeCategory(<?= (int) $catalogCategory['id'] ?>)" class="text-slate-400 hover:text-rose-600"><span class="material-icons text-sm">delete</span></button>
                        </div>
                    </div>
                    <p class="font-black text-xs text-slate-800 mt-2 truncate"><?= esc($catalogCategory['name']) ?></p>
                    <p class="text-[9px] font-bold mt-0.5 <?= !empty($catalogCategory['is_active']) ? 'text-emerald-600' : 'text-slate-400' ?>"><?= !empty($catalogCategory['is_active']) ? 'Activa' : 'Oculta' ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Products List Header -->
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-black text-slate-900 text-sm sm:text-base flex items-center gap-2">
                <span class="material-icons text-emerald-600 text-lg">format_list_bulleted</span>
                <span>Servicios y Productos Registrados</span>
            </h2>
            <span class="text-xs font-bold text-slate-400"><?= count($products) ?> items</span>
        </div>

        <!-- Products Grid (Mobile Friendly Cards) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5">
            <?php foreach($products as $p): ?>
            <div class="bg-white p-4 rounded-2xl shadow-2xs hover:shadow-md border border-slate-200/80 flex items-center justify-between group transition-all <?= empty($p['is_active']) ? 'opacity-60' : '' ?>">
                <div class="flex items-center gap-3 min-w-0 flex-1 pr-2">
                    <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0">
                        <span class="material-icons text-xl"><?= $p['icon'] ?? 'print' ?></span>
                    </div>
                    <div class="min-w-0 flex-1">
                         <h3 class="font-black text-slate-800 text-xs sm:text-sm leading-tight truncate"><?= $p['name'] ?></h3>
                         <p class="text-[10px] font-bold text-slate-400 mt-0.5"><?= $p['category'] ?></p>
                         <p class="text-[9px] font-semibold text-slate-400 truncate"><?= esc(($p['sku'] ?? '') ?: (['service' => 'Servicio', 'product' => 'Producto', 'custom' => 'Personalizado'][$p['product_type'] ?? 'service'] ?? 'Servicio')) ?> · por <?= esc($p['unit'] ?? 'unidad') ?></p>
                         <div class="flex items-baseline gap-2 mt-1">
                             <span class="text-xs font-black text-slate-900">Bs. <?= number_format($p['price_bs'], 2) ?></span>
                             <span class="text-[10px] font-bold text-emerald-700">$<?= number_format($p['price_usd'], 2) ?></span>
                         </div>
                         <?php if (!empty($p['characteristics'])): ?>
                         <p class="text-[9px] font-black text-violet-600 mt-1"><?= count($p['characteristics']) ?> característica<?= count($p['characteristics']) === 1 ? '' : 's' ?></p>
                         <?php endif; ?>
                         <?php if (empty($p['is_active'])): ?><p class="text-[9px] font-black text-rose-500 mt-1">Oculto en ventas</p><?php endif; ?>
                    </div>
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                    <button @click='edit(<?= json_encode($p) ?>)' class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-emerald-50 text-slate-600 hover:text-emerald-700 flex items-center justify-center transition-colors border border-slate-200/60" title="Editar">
                        <span class="material-icons text-sm">edit</span>
                    </button>
                    <button @click="remove(<?= $p['id'] ?>)" class="w-8 h-8 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 flex items-center justify-center transition-colors border border-rose-200/60" title="Eliminar">
                        <span class="material-icons text-sm">delete</span>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if(empty($products)): ?>
        <div class="text-center py-16 bg-white/60 rounded-3xl border border-dashed border-slate-200">
            <span class="material-icons text-4xl text-slate-300 mb-2">inventory_2</span>
            <p class="font-bold text-slate-600 text-sm">No hay productos registrados.</p>
        </div>
        <?php endif; ?>
    </main>

    <!-- Add / Edit Product Modal (Mobile Bottom Sheet & Desktop Dialog) -->
    <div x-show="showModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4" x-cloak style="display: none;">
        <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-xs" @click="showModal = false"></div>
        <div class="bg-white rounded-t-[2.5rem] sm:rounded-3xl shadow-2xl w-full sm:max-w-2xl relative z-10 p-6 space-y-4 max-h-[92vh] overflow-y-auto customize-scrollbar animate-slide-up safe-bottom">
            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                <h2 class="text-base font-black text-slate-900" x-text="form.id ? 'Editar Producto' : 'Nuevo Producto / Servicio'"></h2>
                <button @click="showModal = false" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 flex items-center justify-center">
                    <span class="material-icons text-base">close</span>
                </button>
            </div>
            
            <div class="space-y-3.5">
                <div>
                    <label class="block text-[11px] font-black text-slate-500 uppercase tracking-wider mb-1">Nombre</label>
                    <input type="text" x-model="form.name" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-3.5 py-2.5 outline-none focus:border-emerald-500 font-bold text-slate-800 text-sm" placeholder="Ej. Impresión Carta Color">
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 mb-1">Tipo</label>
                        <select x-model="form.product_type" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 font-bold text-xs">
                            <option value="service">Servicio</option><option value="product">Producto</option><option value="custom">Personalizado</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 mb-1">SKU / Código</label>
                        <input type="text" x-model="form.sku" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 font-bold text-xs" placeholder="FRA-BAS-001">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 mb-1">Unidad de venta</label>
                        <select x-model="form.unit" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 font-bold text-xs">
                            <option value="unidad">Unidad</option><option value="hoja">Hoja</option><option value="página">Página</option><option value="paquete">Paquete</option><option value="docena">Docena</option><option value="servicio">Servicio</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-500 mb-1">Descripción</label>
                    <textarea x-model="form.description" rows="2" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 outline-none focus:border-emerald-500 text-xs" placeholder="Detalles internos o comerciales del producto"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 mb-1">Precio Bs.</label>
                        <input type="number" step="0.01" x-model="form.price_bs" 
                               @input="if(rate > 0) form.price_usd = (form.price_bs / rate).toFixed(2)"
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 outline-none focus:border-emerald-500 font-black text-slate-800 text-sm">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-emerald-700 mb-1">Precio USD</label>
                        <input type="number" step="0.01" x-model="form.price_usd" class="w-full bg-emerald-50/50 border border-emerald-200 rounded-xl px-3 py-2.5 outline-none focus:border-emerald-500 font-black text-emerald-800 text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-500 mb-1">Categoría</label>
                    <select x-model="form.category_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 outline-none focus:border-emerald-500 font-bold text-slate-800 text-xs sm:text-sm">
                        <template x-for="category in catalogCategories" :key="category.id">
                            <option :value="category.id" x-text="category.name"></option>
                        </template>
                    </select>
                </div>

                <section class="rounded-2xl border border-violet-200 bg-violet-50/50 p-3.5 space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <div><p class="text-xs font-black text-violet-900">Características y variantes</p><p class="text-[9px] text-violet-600 font-semibold">Talla, color, material, gramaje, formato o acabado.</p></div>
                        <button type="button" @click="addCharacteristic()" class="px-2.5 py-1.5 bg-violet-600 text-white rounded-lg text-[10px] font-black">+ Agregar</button>
                    </div>
                    <template x-for="(feature, featureIndex) in form.characteristics" :key="featureIndex">
                        <div class="bg-white rounded-xl border border-violet-100 p-3 space-y-2">
                            <div class="grid grid-cols-[1fr_auto_auto] gap-2 items-center">
                                <input x-model="feature.name" class="bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-2 text-xs font-bold" placeholder="Ej. Talla">
                                <select x-model="feature.type" class="bg-slate-50 border border-slate-200 rounded-lg px-2 py-2 text-[10px] font-bold"><option value="select">Opciones</option><option value="text">Texto libre</option></select>
                                <button type="button" @click="form.characteristics.splice(featureIndex, 1)" class="text-rose-500"><span class="material-icons text-base">delete</span></button>
                            </div>
                            <label class="flex items-center gap-2 text-[10px] font-bold text-slate-600"><input type="checkbox" x-model="feature.required" class="accent-violet-600"> Requerida al vender</label>
                            <div x-show="feature.type === 'select'" class="space-y-1.5">
                                <template x-for="(option, optionIndex) in feature.options" :key="optionIndex">
                                    <div class="grid grid-cols-[1fr_90px_28px] gap-2">
                                        <input x-model="option.label" class="bg-slate-50 border border-slate-200 rounded-lg px-2 py-1.5 text-[10px]" placeholder="Opción (Ej. XL)">
                                        <input type="number" min="0" step="0.01" x-model.number="option.price_usd" class="bg-emerald-50 border border-emerald-200 rounded-lg px-2 py-1.5 text-[10px]" placeholder="Recargo $">
                                        <button type="button" @click="feature.options.splice(optionIndex, 1)" class="text-slate-400 hover:text-rose-500">×</button>
                                    </div>
                                </template>
                                <button type="button" @click="feature.options.push({label:'', price_bs:0, price_usd:0})" class="text-[10px] font-black text-violet-700">+ Añadir opción</button>
                            </div>
                        </div>
                    </template>
                    <p x-show="form.characteristics.length === 0" class="text-[10px] text-violet-500 font-semibold text-center py-2">Este producto no requiere variantes.</p>
                </section>

                <label class="flex items-center justify-between rounded-xl bg-slate-50 border border-slate-200 p-3">
                    <span><b class="block text-xs text-slate-800">Visible en el punto de venta</b><small class="text-[9px] text-slate-400">Puedes ocultarlo sin eliminar su historial.</small></span>
                    <input type="checkbox" x-model="form.is_active" class="w-5 h-5 accent-emerald-600">
                </label>
                
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 mb-1">Icono Representativo</label>
                    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-2.5 h-28 overflow-y-auto customize-scrollbar grid grid-cols-6 gap-1.5">
                         <template x-for="icon in icons" :key="icon">
                             <button type="button" 
                                     @click="form.icon = icon"
                                     class="w-8 h-8 rounded-xl flex items-center justify-center transition-all bg-white shadow-2xs border border-slate-200/60"
                                     :class="form.icon === icon ? 'bg-gradient-to-r from-emerald-600 to-teal-700 !text-white !border-emerald-600 ring-2 ring-emerald-300' : 'text-slate-400 hover:bg-slate-100'">
                                 <span class="material-icons text-base" x-text="icon"></span>
                             </button>
                         </template>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-500 mb-1.5">Color de Distinción</label>
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="c in colors">
                            <button @click="form.color = c" 
                                    class="w-6 h-6 rounded-full shadow-2xs border transition-transform hover:scale-110" 
                                    :class="'bg-' + c + '-500 ' + (form.color === c ? 'border-slate-900 scale-110 ring-2 ring-emerald-400' : 'border-white')">
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <div class="flex gap-2.5 pt-3 border-t border-slate-100">
                <button @click="showModal = false" class="flex-1 py-3 text-slate-600 font-bold bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors text-xs sm:text-sm">Cancelar</button>
                <button @click="save()" class="flex-1 py-3 bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white font-black rounded-xl shadow-md shadow-emerald-950/20 active:scale-98 transition-all text-xs sm:text-sm">Guardar</button>
            </div>
        </div>
    </div>

    <!-- Category Modal -->
    <div x-show="categoryModal.open" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4" x-cloak>
        <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" @click="categoryModal.open = false"></div>
        <div class="bg-white rounded-t-[2rem] sm:rounded-3xl shadow-2xl w-full sm:max-w-md relative z-10 p-6 space-y-4">
            <div class="flex justify-between items-center"><h2 class="font-black text-slate-900" x-text="categoryForm.id ? 'Editar categoría' : 'Nueva categoría'"></h2><button @click="categoryModal.open = false" class="material-icons text-slate-400">close</button></div>
            <div><label class="text-[10px] font-black text-slate-500">NOMBRE</label><input x-model="categoryForm.name" class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 font-bold" placeholder="Ej. Franelas"></div>
            <div class="grid grid-cols-3 gap-3">
                <div><label class="text-[10px] font-black text-slate-500">ICONO</label><select x-model="categoryForm.icon" class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-xs"><template x-for="icon in icons"><option :value="icon" x-text="icon"></option></template></select></div>
                <div><label class="text-[10px] font-black text-slate-500">COLOR</label><select x-model="categoryForm.color" class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-xs"><template x-for="color in colors"><option :value="color" x-text="color"></option></template></select></div>
                <div><label class="text-[10px] font-black text-slate-500">ORDEN</label><input type="number" x-model.number="categoryForm.sort_order" class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5"></div>
            </div>
            <label class="flex items-center gap-2 text-xs font-bold text-slate-700"><input type="checkbox" x-model="categoryForm.is_active" class="accent-emerald-600"> Mostrar categoría en el catálogo</label>
            <div class="flex gap-2"><button @click="categoryModal.open = false" class="flex-1 py-3 bg-slate-100 rounded-xl text-xs font-bold">Cancelar</button><button @click="saveCategory()" class="flex-1 py-3 bg-amber-500 text-slate-950 rounded-xl text-xs font-black">Guardar</button></div>
        </div>
    </div>

    <script>
        function settingsApp() {
            return {
                showModal: false,
                catalogCategories: <?= json_encode($catalogCategories, JSON_UNESCAPED_UNICODE) ?>,
                categoryModal: { open: false },
                categoryForm: { id: null, name: '', icon: 'category', color: 'emerald', sort_order: 0, is_active: true },
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
                    'library_books', 'menu_book', 'auto_stories', 'star', 'favorite',
                    'checkroom', 'edit_note', 'inventory_2', 'straighten', 'texture', 'auto_awesome'
                ],

                form: {
                    id: null,
                    name: '',
                    price_bs: 0,
                    price_usd: 0,
                    category_id: <?= json_encode((string) ($catalogCategories[0]['id'] ?? '')) ?>,
                    product_type: 'service',
                    sku: '',
                    description: '',
                    unit: 'unidad',
                    characteristics: [],
                    is_active: true,
                    icon: 'print',
                    color: 'emerald'
                },

                openModal() {
                    this.form = { id: null, name: '', price_bs: 0, price_usd: 0, category_id: String(this.catalogCategories[0]?.id || ''), product_type: 'service', sku: '', description: '', unit: 'unidad', characteristics: [], is_active: true, icon: 'print', color: 'emerald' };
                    this.showModal = true;
                },

                edit(product) {
                    this.form = JSON.parse(JSON.stringify(product));
                    this.form.category_id = String(product.category_id || this.catalogCategories.find(c => c.name === product.category)?.id || '');
                    this.form.characteristics = Array.isArray(product.characteristics) ? this.form.characteristics : [];
                    this.form.is_active = Number(product.is_active ?? 1) === 1;
                    this.showModal = true;
                },

                addCharacteristic() {
                    this.form.characteristics.push({ name: '', type: 'select', required: true, options: [{ label: '', price_bs: 0, price_usd: 0 }] });
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
                        } else {
                            alert(data.message || 'No se pudo guardar el producto');
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

                openCategoryModal() {
                    this.categoryForm = { id: null, name: '', icon: 'category', color: 'emerald', sort_order: 0, is_active: true };
                    this.categoryModal.open = true;
                },

                editCategory(category) {
                    this.categoryForm = { ...category, is_active: Number(category.is_active) === 1 };
                    this.categoryModal.open = true;
                },

                async saveCategory() {
                    try {
                        const response = await fetch('<?= base_url('printing/save-catalog-category') ?>', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(this.categoryForm) });
                        const data = await response.json();
                        if (data.status !== 'success') throw new Error(data.message || 'No se pudo guardar');
                        location.reload();
                    } catch (error) { alert(error.message); }
                },

                async removeCategory(id) {
                    if (!confirm('¿Eliminar esta categoría?')) return;
                    try {
                        const response = await fetch('<?= base_url('printing/delete-catalog-category/') ?>' + id, { method: 'POST' });
                        const data = await response.json();
                        if (data.status !== 'success') throw new Error(data.message || 'No se pudo eliminar');
                        location.reload();
                    } catch (error) { alert(error.message); }
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
