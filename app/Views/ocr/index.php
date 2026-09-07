<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Escáner OCR de Facturas | Finanzahex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <meta name="theme-color" content="#064e3b">
    <style>
        body { font-family: 'Plus Jakarta Sans', 'Outfit', sans-serif; }
        [x-cloak] { display: none !important; }
        .customize-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
        .customize-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .customize-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
        .safe-bottom { padding-bottom: max(6rem, env(safe-area-inset-bottom)); }
        .safe-top { padding-top: max(0.75rem, env(safe-area-inset-top)); }
    </style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800 antialiased" x-data="ocrApp()">

    <!-- Executive Top Nav Header -->
    <header class="sticky top-0 z-40 bg-gradient-to-r from-emerald-950 via-slate-900 to-teal-950 text-white shadow-xl border-b border-emerald-800/30 safe-top">
        <div class="max-w-5xl mx-auto px-4 py-3 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <a href="<?= base_url() ?>" class="w-10 h-10 rounded-2xl bg-white/10 hover:bg-white/20 active:scale-95 flex items-center justify-center transition-all border border-white/10 text-white shrink-0" title="Volver al Inicio">
                    <span class="material-icons text-xl">arrow_back</span>
                </a>
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-500 via-teal-400 to-cyan-400 p-[1.5px] shadow-sm shrink-0">
                        <div class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center">
                            <span class="material-icons text-emerald-400 text-lg">document_scanner</span>
                        </div>
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-sm sm:text-base font-black tracking-tight text-white flex items-center gap-1.5 truncate">
                            <span>Escáner OCR</span>
                            <span class="bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 text-[9px] font-black uppercase px-2 py-0.5 rounded-md">Facturas</span>
                        </h1>
                        <p class="text-[10px] text-emerald-200/70 font-semibold truncate">Captura por cámara y digitalización de gastos</p>
                    </div>
                </div>
            </div>

            <!-- Header Actions: Tasa BCV & Settings Modal -->
            <div class="flex items-center gap-2 shrink-0">
                <div class="hidden sm:flex items-center gap-1 px-3 py-1.5 bg-white/10 border border-white/10 rounded-xl text-xs font-black text-emerald-300">
                    <span class="material-icons text-xs">currency_exchange</span>
                    <span>BCV: Bs. <?= number_format($exchangeRate, 2, ',', '.') ?></span>
                </div>

                <button @click="openSettingsModal = true" 
                        class="w-10 h-10 rounded-2xl bg-white/10 hover:bg-white/20 active:scale-95 flex items-center justify-center transition-all border border-white/10 text-white" 
                        title="Configuración de API Key OCR">
                    <span class="material-icons text-lg">settings</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="max-w-5xl mx-auto px-4 py-6 space-y-6 safe-bottom">

        <!-- API Key Banner Warning if not set -->
        <div x-show="!apiKeyConfigured" class="bg-amber-50 border border-amber-200 rounded-3xl p-4 flex items-center justify-between gap-3 shadow-xs">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0">
                    <span class="material-icons text-lg">vpn_key</span>
                </div>
                <div>
                    <h4 class="text-xs font-black text-amber-900">API Key de OCR.space no configurada</h4>
                    <p class="text-[11px] text-amber-700 font-semibold">Usa la demo pública o ingresa tu API Key gratuita para mayor velocidad y sin límites.</p>
                </div>
            </div>
            <button @click="openSettingsModal = true" class="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-black rounded-xl shrink-0 active:scale-95">Configurar</button>
        </div>

        <!-- Camera & Upload Actions Section (Mobile First Priority) -->
        <div class="bg-white rounded-3xl p-5 shadow-xs border border-slate-200/80 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h3 class="text-sm sm:text-base font-black text-slate-800 flex items-center gap-2">
                        <span class="material-icons text-emerald-600 text-lg">add_a_photo</span>
                        <span>Capturar o Cargar Facturas</span>
                    </h3>
                    <p class="text-[11px] text-slate-400 font-semibold">Toma fotos directamente con la cámara de tu celular o sube imágenes de tu galería</p>
                </div>

                <!-- Pre-processing Filter Mode Toggle -->
                <div class="flex items-center gap-1.5 bg-slate-100 p-1 rounded-2xl self-start sm:self-auto border border-slate-200/80">
                    <button type="button" @click="filterMode = 'normal'" 
                            :class="filterMode === 'normal' ? 'bg-white text-emerald-800 font-black shadow-xs' : 'text-slate-500 font-bold hover:text-slate-800'"
                            class="px-2.5 py-1 text-[11px] rounded-xl transition-all flex items-center gap-1">
                        <span class="material-icons text-xs">palette</span>
                        <span>Normal</span>
                    </button>
                    <button type="button" @click="filterMode = 'thermal'" 
                            :class="filterMode === 'thermal' ? 'bg-emerald-600 text-white font-black shadow-xs' : 'text-slate-500 font-bold hover:text-slate-800'"
                            class="px-2.5 py-1 text-[11px] rounded-xl transition-all flex items-center gap-1"
                            title="Mejora el contraste para tickets térmicos pálidos o fotos con sombra">
                        <span class="material-icons text-xs">contrast</span>
                        <span>Alto Contraste Térmico</span>
                    </button>
                </div>
            </div>

            <!-- Two Main Action Buttons -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                
                <!-- 1. Direct Camera Trigger -->
                <label class="cursor-pointer bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 active:scale-95 text-white p-5 rounded-3xl shadow-lg shadow-emerald-950/20 border border-emerald-500/30 flex items-center gap-4 transition-all group">
                    <input type="file" accept="image/*" capture="environment" class="hidden" @change="handleImageInput($event)">
                    <div class="w-13 h-13 rounded-2xl bg-white/15 flex items-center justify-center group-hover:scale-110 transition-transform shrink-0">
                        <span class="material-icons text-3xl">photo_camera</span>
                    </div>
                    <div class="min-w-0">
                        <span class="text-sm font-black block tracking-tight">Tomar Foto con Cámara</span>
                        <span class="text-[11px] text-emerald-100/80 font-semibold block">Abre la cámara nativa del celular</span>
                    </div>
                </label>

                <!-- 2. Gallery / File Upload (Supports Multiple Files) -->
                <label class="cursor-pointer bg-slate-100 hover:bg-slate-200/80 active:scale-95 text-slate-800 p-5 rounded-3xl border border-slate-200/80 flex items-center gap-4 transition-all group">
                    <input type="file" accept="image/*" multiple class="hidden" @change="handleImageInput($event)">
                    <div class="w-13 h-13 rounded-2xl bg-slate-200 flex items-center justify-center group-hover:scale-110 transition-transform shrink-0 text-slate-700">
                        <span class="material-icons text-3xl">collections</span>
                    </div>
                    <div class="min-w-0">
                        <span class="text-sm font-black block tracking-tight">Subir desde Galería</span>
                        <span class="text-[11px] text-slate-500 font-semibold block">Selecciona 1 o varias imágenes</span>
                    </div>
                </label>

            </div>
        </div>

        <!-- Image Queue / Staging Area (Photos taken, ready to scan) -->
        <div x-show="pendingImages.length > 0" x-transition class="bg-white rounded-3xl p-5 shadow-xs border border-slate-200/80 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center font-black text-xs" x-text="pendingImages.length"></span>
                    <h3 class="text-xs sm:text-sm font-black text-slate-800">Fotos en Cola para Escanear</h3>
                </div>
                <button @click="pendingImages = []" class="text-[11px] font-bold text-rose-600 hover:text-rose-700 flex items-center gap-1">
                    <span class="material-icons text-xs">delete_sweep</span>
                    <span>Limpiar Cola</span>
                </button>
            </div>

            <!-- Image Thumbnails Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <template x-for="(img, idx) in pendingImages" :key="idx">
                    <div class="relative group rounded-2xl overflow-hidden border border-slate-200 bg-slate-900 aspect-3/4 flex items-center justify-center">
                        <img :src="img" class="w-full h-full object-cover">
                        <button @click="removePendingImage(idx)" class="absolute top-2 right-2 w-7 h-7 rounded-full bg-rose-600/90 text-white flex items-center justify-center shadow-md active:scale-95">
                            <span class="material-icons text-xs">close</span>
                        </button>
                        <span class="absolute bottom-2 left-2 bg-black/60 backdrop-blur-sm text-white text-[9px] font-black px-2 py-0.5 rounded-md" x-text="'Foto #' + (idx + 1)"></span>
                    </div>
                </template>
            </div>

            <!-- Process OCR Action Button -->
            <div class="pt-2">
                <button @click="processOcrQueue()" :disabled="isProcessing" 
                        class="w-full bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-700 hover:opacity-95 active:scale-98 text-white py-3.5 px-5 rounded-2xl font-black text-sm shadow-xl shadow-emerald-950/20 flex items-center justify-center gap-2 transition-all disabled:opacity-50">
                    <span class="material-icons text-lg" :class="{'animate-spin': isProcessing}">document_scanner</span>
                    <span x-text="isProcessing ? 'Procesando con OCR.space (' + processingProgress + ')...' : 'Escanear ' + pendingImages.length + ' Factura(s) con OCR'"></span>
                </button>
            </div>
        </div>

        <!-- Scanned Invoices Review & Confirmation Cards -->
        <div x-show="invoices.length > 0" class="space-y-6">
            
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                        <span>Facturas Escaneadas</span>
                        <span class="bg-emerald-100 text-emerald-800 text-xs font-black px-2.5 py-0.5 rounded-full" x-text="invoices.length"></span>
                    </h3>
                    <p class="text-[11px] text-slate-400 font-bold">Verifica los datos detectados antes de guardarlos en el sistema</p>
                </div>
                <button @click="invoices = []" class="text-xs font-bold text-rose-600 hover:text-rose-700">Descartar todo</button>
            </div>

            <!-- Loop of Scanned Invoice Cards -->
            <template x-for="(inv, iIdx) in invoices" :key="inv.uid">
                <div class="bg-white rounded-3xl p-5 shadow-sm border border-slate-200/90 space-y-5 relative">
                    
                    <!-- Invoice Header & Actions -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-12 h-12 rounded-2xl overflow-hidden bg-slate-100 border border-slate-200 shrink-0 cursor-pointer shadow-xs" @click="previewFullImage(inv.image_preview)" title="Ver imagen completa">
                                <img :src="inv.image_preview" class="w-full h-full object-cover">
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5 flex-wrap mb-1">
                                    <span class="text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-md"
                                          :class="{
                                              'bg-emerald-100 text-emerald-800 border border-emerald-300': inv.model_type === 'FISCAL_PRINTER',
                                              'bg-blue-100 text-blue-800 border border-blue-300': inv.model_type === 'POS_VOUCHER',
                                              'bg-purple-100 text-purple-800 border border-purple-300': inv.model_type === 'ELECTRONIC_INVOICE',
                                              'bg-amber-100 text-amber-800 border border-amber-300': inv.model_type === 'RETAIL_RECEIPT',
                                              'bg-cyan-100 text-cyan-800 border border-cyan-300': inv.model_type === 'PAGO_MOVIL',
                                              'bg-slate-100 text-slate-700 border border-slate-300': !inv.model_type || inv.model_type === 'GENERIC_RECEIPT'
                                          }"
                                          x-text="inv.model_label || 'Factura Detectada'"></span>

                                    <template x-if="inv.payment_method">
                                        <span class="text-[9px] font-bold px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 border border-slate-200 flex items-center gap-1">
                                            <span class="material-icons text-[10px]">payments</span>
                                            <span x-text="inv.payment_method"></span>
                                        </span>
                                    </template>

                                    <template x-if="inv.cashea">
                                        <span class="text-[9px] font-black px-2 py-0.5 rounded-md bg-purple-50 text-purple-700 border border-purple-200" x-text="'Cashea: ' + formatBs(inv.cashea)"></span>
                                    </template>

                                    <template x-if="inv.igtf_amount > 0">
                                        <span class="text-[9px] font-black px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 border border-amber-200" x-text="'IGTF 3%: ' + formatBs(inv.igtf_amount)"></span>
                                    </template>
                                </div>
                                <h4 class="text-sm sm:text-base font-black text-slate-900 truncate" x-text="inv.merchant || 'Comercio'"></h4>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 self-end sm:self-auto">
                            <button type="button" @click="inv.showRaw = !inv.showRaw" class="text-slate-500 hover:text-slate-800 text-xs font-bold px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 flex items-center gap-1 transition-all">
                                <span class="material-icons text-xs">notes</span>
                                <span x-text="inv.showRaw ? 'Ocultar OCR' : 'Ver OCR'"></span>
                            </button>
                            <button @click="removeInvoice(iIdx)" class="text-rose-500 hover:text-rose-700 text-xs font-bold flex items-center gap-1 px-2 py-1">
                                <span class="material-icons text-sm">delete</span>
                                <span>Quitar</span>
                            </button>
                        </div>
                    </div>

                    <!-- Raw OCR Collapsible Text -->
                    <div x-show="inv.showRaw" x-transition class="p-4 bg-slate-900 text-slate-200 rounded-2xl text-[11px] font-mono space-y-2 border border-slate-800">
                        <div class="flex items-center justify-between text-[10px] text-slate-400 pb-1 border-b border-slate-800">
                            <span class="font-bold text-slate-300">Texto Reconocido por el Motor OCR</span>
                            <button type="button" @click="navigator.clipboard.writeText(inv.raw_text); showToast('Texto copiado al portapapeles')" class="hover:text-white flex items-center gap-1 text-emerald-400 font-bold">
                                <span class="material-icons text-xs">content_copy</span>
                                <span>Copiar Texto</span>
                            </button>
                        </div>
                        <div class="max-h-48 overflow-y-auto whitespace-pre-wrap leading-relaxed select-all customize-scrollbar" x-text="inv.raw_text"></div>
                    </div>

                    <!-- Invoice Metadata Form (Editable) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Comercio / Proveedor</label>
                            <input type="text" x-model="inv.merchant" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 outline-none focus:border-emerald-500 focus:bg-white">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">RIF del Comercio</label>
                            <input type="text" x-model="inv.rif" placeholder="J-XXXXXXXXX" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 outline-none focus:border-emerald-500 focus:bg-white">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Nro. Factura / Control</label>
                            <input type="text" x-model="inv.invoice_number" placeholder="00000000" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 outline-none focus:border-emerald-500 focus:bg-white">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Fecha de Emisión</label>
                            <input type="date" x-model="inv.date" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 outline-none focus:border-emerald-500 focus:bg-white">
                        </div>
                    </div>

                    <!-- Payment & Ledger Mapping (Where did the money come from?) -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-4 rounded-2xl bg-slate-50 border border-slate-200/70">
                        <div>
                            <label class="block text-[10px] font-black text-emerald-800 uppercase mb-1 flex items-center gap-1">
                                <span class="material-icons text-xs">account_balance</span>
                                <span>Cuenta Origen (Banco) *</span>
                            </label>
                            <select x-model="inv.account_id" class="w-full bg-white border border-emerald-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 outline-none focus:ring-2 focus:ring-emerald-400">
                                <option value="">Seleccionar cuenta...</option>
                                <?php foreach ($accounts as $acc): ?>
                                    <option value="<?= $acc['id'] ?>"><?= esc($acc['name']) ?> (<?= esc($acc['currency']) ?> - Saldo: <?= number_format($acc['balance'], 2) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase mb-1 flex items-center gap-1">
                                <span class="material-icons text-xs">category</span>
                                <span>Categoría de Gasto</span>
                            </label>
                            <select x-model="inv.category_id" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 outline-none focus:border-emerald-500">
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= esc($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase mb-1">Propietario</label>
                            <div class="flex items-center gap-1">
                                <button type="button" @click="inv.owner = 'Negocio'" 
                                        :class="inv.owner === 'Negocio' ? 'bg-emerald-600 text-white font-black' : 'bg-white text-slate-600 font-bold border border-slate-200'"
                                        class="flex-1 py-2 text-xs rounded-xl transition-all">Negocio</button>
                                <button type="button" @click="inv.owner = 'Personal'" 
                                        :class="inv.owner === 'Personal' ? 'bg-blue-600 text-white font-black' : 'bg-white text-slate-600 font-bold border border-slate-200'"
                                        class="flex-1 py-2 text-xs rounded-xl transition-all">Personal</button>
                            </div>
                        </div>
                    </div>

                    <!-- Items Breakdown List -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h5 class="text-xs font-black text-slate-700 flex items-center gap-1.5">
                                <span class="material-icons text-sm text-slate-400">shopping_cart</span>
                                <span>Productos / Servicios Detectados</span>
                                <span class="bg-slate-100 text-slate-600 text-[10px] font-bold px-2 py-0.5 rounded-md" x-text="inv.items.length + ' items'"></span>
                            </h5>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="recalculateInvoiceTotal(inv); showToast('Totales recalculados')" class="text-[11px] font-bold text-slate-500 hover:text-slate-800 flex items-center gap-1 bg-slate-50 hover:bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200 transition-all">
                                    <span class="material-icons text-xs">calculate</span>
                                    <span>Recalcular</span>
                                </button>
                                <button @click="addItemToInvoice(inv)" class="text-[11px] font-black text-emerald-700 hover:text-emerald-800 flex items-center gap-1 bg-emerald-50 hover:bg-emerald-100 px-2.5 py-1 rounded-lg border border-emerald-200 transition-all">
                                    <span class="material-icons text-sm">add</span>
                                    <span>Agregar Item</span>
                                </button>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse min-w-[500px]">
                                <thead>
                                    <tr class="text-[9px] font-black uppercase text-slate-400 border-b border-slate-100 pb-1">
                                        <th class="py-2 px-1">Cant</th>
                                        <th class="py-2 px-2">Descripción</th>
                                        <th class="py-2 px-2 text-right">Precio Total (Bs)</th>
                                        <th class="py-2 px-2 text-right">Precio ($)</th>
                                        <th class="py-2 px-1 text-center">IVA</th>
                                        <th class="py-2 px-1 text-right"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-xs font-bold">
                                    <template x-for="(item, itIdx) in inv.items" :key="itIdx">
                                        <tr>
                                            <td class="py-2 px-1 w-16">
                                                <input type="number" step="0.01" x-model.number="item.quantity" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-1 text-center font-bold text-xs">
                                            </td>
                                            <td class="py-2 px-2">
                                                <input type="text" x-model="item.name" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-1 font-bold text-xs">
                                            </td>
                                            <td class="py-2 px-2 text-right w-28">
                                                <input type="number" step="0.01" x-model.number="item.price" @input="updateItemUsd(item, inv.exchange_rate); recalculateInvoiceTotal(inv)" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-1 text-right font-black text-xs text-slate-800">
                                            </td>
                                            <td class="py-2 px-2 text-right w-24">
                                                <input type="number" step="0.01" x-model.number="item.price_usd" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-1 text-right font-black text-xs text-emerald-700">
                                            </td>
                                            <td class="py-2 px-1 text-center w-12">
                                                <span class="text-[9px] font-black uppercase px-1.5 py-0.5 rounded"
                                                      :class="item.tax_type === 'E' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700'"
                                                      x-text="item.tax_type || 'G'"></span>
                                            </td>
                                            <td class="py-2 px-1 text-right w-8">
                                                <button @click="removeItemFromInvoice(inv, itIdx)" class="text-slate-300 hover:text-rose-500">
                                                    <span class="material-icons text-sm">close</span>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Totals and Tax Breakdown -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3 border-t border-slate-100 items-end">
                        <div class="text-[11px] font-bold text-slate-500 space-y-1">
                            <div class="flex justify-between" x-show="inv.subtotal > 0">
                                <span>Subtotal:</span>
                                <span class="font-black text-slate-700" x-text="formatBs(inv.subtotal)"></span>
                            </div>
                            <div class="flex justify-between" x-show="inv.exento > 0">
                                <span>Exento:</span>
                                <span class="font-black text-amber-700" x-text="formatBs(inv.exento)"></span>
                            </div>
                            <div class="flex justify-between" x-show="inv.base_imponible > 0">
                                <span>Base Imponible:</span>
                                <span class="font-black text-slate-700" x-text="formatBs(inv.base_imponible)"></span>
                            </div>
                            <div class="flex justify-between" x-show="inv.iva_amount > 0">
                                <span>IVA (16%):</span>
                                <span class="font-black text-rose-600" x-text="formatBs(inv.iva_amount)"></span>
                            </div>
                        </div>

                        <!-- Grand Total Card -->
                        <div class="bg-gradient-to-br from-emerald-50 to-teal-50/50 p-4 rounded-2xl border border-emerald-200/80 text-right">
                            <span class="text-[9px] font-black uppercase text-emerald-800 tracking-wider block">Total de la Factura</span>
                            <div class="text-2xl font-black text-slate-900 mt-1" x-text="formatBs(inv.total_bs)"></div>
                            <div class="text-sm font-black text-emerald-700 mt-0.5" x-text="formatUsd(inv.total_usd)"></div>
                            <div class="text-[10px] font-bold text-slate-400 mt-1">
                                <span>Tasa aplicada: </span>
                                <span class="font-black" x-text="'Bs. ' + parseFloat(inv.exchange_rate).toFixed(2)"></span>
                            </div>
                        </div>
                    </div>

                </div>
            </template>

        </div>

        <!-- Sticky Floating Save Bar (Visible when invoices exist) -->
        <div x-show="invoices.length > 0" class="fixed bottom-0 left-0 right-0 z-30 bg-white/95 backdrop-blur-md border-t border-slate-200 p-4 shadow-2xl safe-bottom">
            <div class="max-w-5xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3">
                <div>
                    <span class="text-[10px] font-black uppercase text-slate-400 block">Total a Registrar</span>
                    <div class="flex items-center gap-2">
                        <span class="text-lg font-black text-slate-900" x-text="formatBs(grandTotalBs())"></span>
                        <span class="text-sm font-black text-emerald-700" x-text="'(' + formatUsd(grandTotalUsd()) + ')'"></span>
                    </div>
                </div>

                <button @click="saveInvoices()" :disabled="isSaving"
                        class="w-full sm:w-auto bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 active:scale-95 text-white px-7 py-3.5 rounded-2xl font-black text-sm shadow-lg shadow-emerald-950/20 flex items-center justify-center gap-2 transition-all disabled:opacity-50">
                    <span class="material-icons text-base" :class="{'animate-spin': isSaving}">check_circle</span>
                    <span x-text="isSaving ? 'Registrando gastos...' : 'Guardar ' + invoices.length + ' Factura(s) en Finanzahex'"></span>
                </button>
            </div>
        </div>

    </main>

    <!-- Full Image Preview Modal -->
    <div x-show="previewModal.show" x-transition x-cloak class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4" @click.self="previewModal.show = false">
        <div class="max-w-2xl w-full bg-slate-900 rounded-3xl overflow-hidden shadow-2xl relative">
            <button @click="previewModal.show = false" class="absolute top-3 right-3 w-8 h-8 rounded-full bg-black/60 text-white flex items-center justify-center">
                <span class="material-icons text-sm">close</span>
            </button>
            <img :src="previewModal.src" class="w-full max-h-[80vh] object-contain">
        </div>
    </div>

    <!-- OCR Settings Modal (API Key) -->
    <div x-show="openSettingsModal" x-transition x-cloak class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="max-w-md w-full bg-white rounded-3xl p-6 shadow-2xl space-y-4 border border-slate-100" @click.away="openSettingsModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <span class="material-icons text-emerald-600">settings</span>
                    <h3 class="font-black text-slate-800 text-base">Configuración OCR</h3>
                </div>
                <button @click="openSettingsModal = false" class="text-slate-400 hover:text-slate-600">
                    <span class="material-icons text-sm">close</span>
                </button>
            </div>

            <div class="space-y-3 text-xs font-bold text-slate-600">
                <p>Ingresa tu API Key de <a href="https://ocr.space/ocrapi" target="_blank" class="text-emerald-600 underline font-black">ocr.space</a> para un procesamiento más rápido y sin restricciones de la demo pública.</p>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">API Key de OCR.space</label>
                    <input type="text" x-model="ocrApiKeyInput" placeholder="Ej: K8XXXXXXXX88957" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-800 outline-none focus:border-emerald-500 focus:bg-white">
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button @click="openSettingsModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-100">Cancelar</button>
                <button @click="saveApiKey()" class="px-5 py-2 rounded-xl text-xs font-black bg-emerald-600 hover:bg-emerald-700 text-white shadow-md active:scale-95">Guardar Clave</button>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div x-show="toast.show" x-transition x-cloak
         class="fixed top-6 right-6 z-50 bg-slate-900 text-white px-4 py-3 rounded-2xl shadow-2xl flex items-center gap-2 border border-slate-700 text-xs font-bold">
        <span class="material-icons text-emerald-400 text-sm">check_circle</span>
        <span x-text="toast.message"></span>
    </div>

    <!-- Application Script -->
    <script>
        function ocrApp() {
            return {
                exchangeRate: <?= $exchangeRate ?>,
                ocrApiKeyInput: '<?= esc($ocrApiKey) ?>',
                apiKeyConfigured: <?= !empty($ocrApiKey) ? 'true' : 'false' ?>,
                openSettingsModal: false,
                filterMode: 'normal', // 'normal' | 'thermal'

                // Queue of images taken/uploaded
                pendingImages: [],
                isProcessing: false,
                processingProgress: '',
                isSaving: false,

                // Confirmed parsed invoices
                invoices: [],

                // Preview modal
                previewModal: { show: false, src: '' },

                // Toast
                toast: { show: false, message: '' },

                handleImageInput(event) {
                    const files = event.target.files;
                    if (!files || files.length === 0) return;

                    Array.from(files).forEach(file => {
                        this.compressAndAddImage(file);
                    });

                    // Reset input so same file can be chosen again if needed
                    event.target.value = '';
                },

                // Compress high-res mobile photo using canvas before upload
                compressAndAddImage(file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        const img = new Image();
                        img.onload = () => {
                            const canvas = document.createElement('canvas');
                            let width = img.width;
                            let height = img.height;
                            const maxDim = 1600;

                            if (width > maxDim || height > maxDim) {
                                if (width > height) {
                                    height = Math.round((height * maxDim) / width);
                                    width = maxDim;
                                } else {
                                    width = Math.round((width * maxDim) / height);
                                    height = maxDim;
                                }
                            }

                            canvas.width = width;
                            canvas.height = height;
                            const ctx = canvas.getContext('2d');

                            // Apply thermal high-contrast enhancement if mode enabled
                            if (this.filterMode === 'thermal') {
                                ctx.filter = 'grayscale(100%) contrast(185%) brightness(105%)';
                            } else {
                                ctx.filter = 'none';
                            }

                            ctx.drawImage(img, 0, 0, width, height);

                            const compressedDataUrl = canvas.toDataURL('image/jpeg', 0.85);
                            this.pendingImages.push(compressedDataUrl);
                        };
                        img.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                },

                removePendingImage(idx) {
                    this.pendingImages.splice(idx, 1);
                },

                async processOcrQueue() {
                    if (this.pendingImages.length === 0) return;
                    this.isProcessing = true;

                    try {
                        let total = this.pendingImages.length;
                        this.processingProgress = `1 de ${total}`;

                        const res = await fetch('<?= base_url('ocr/process') ?>', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ images: this.pendingImages })
                        });

                        const result = await res.json();

                        if (result.status === 'success' && result.data) {
                            result.data.forEach(item => {
                                // Default first account if not set
                                const defaultAcc = <?= !empty($accounts[0]['id']) ? $accounts[0]['id'] : 'null' ?>;
                                const defaultCat = <?= !empty($categories[0]['id']) ? $categories[0]['id'] : 'null' ?>;
                                item.account_id = item.account_id || defaultAcc;
                                item.category_id = item.category_id || defaultCat;
                                this.invoices.push(item);
                            });

                            this.pendingImages = [];
                            this.showToast('¡Facturas escaneadas y extraídas con éxito!');
                        } else {
                            alert(result.message || 'Error al procesar imágenes con OCR');
                        }
                    } catch (e) {
                        console.error('Error in OCR process:', e);
                        alert('Error de conexión al procesar las facturas.');
                    } finally {
                        this.isProcessing = false;
                        this.processingProgress = '';
                    }
                },

                removeInvoice(idx) {
                    this.invoices.splice(idx, 1);
                },

                addItemToInvoice(inv) {
                    inv.items.push({
                        name: 'Nuevo Producto',
                        quantity: 1,
                        price: 0,
                        price_usd: 0,
                        tax_type: 'G'
                    });
                },

                removeItemFromInvoice(inv, itIdx) {
                    inv.items.splice(itIdx, 1);
                    this.recalculateInvoiceTotal(inv);
                },

                updateItemUsd(item, rate) {
                    if (rate > 0 && item.price > 0) {
                        item.price_usd = parseFloat((item.price / rate).toFixed(2));
                    }
                },

                recalculateInvoiceTotal(inv) {
                    const totalBs = inv.items.reduce((sum, it) => sum + parseFloat(it.price || 0), 0);
                    inv.total_bs = totalBs;
                    if (inv.exchange_rate > 0) {
                        inv.total_usd = parseFloat((totalBs / inv.exchange_rate).toFixed(2));
                    }
                },

                grandTotalBs() {
                    return this.invoices.reduce((sum, inv) => sum + parseFloat(inv.total_bs || 0), 0);
                },

                grandTotalUsd() {
                    return this.invoices.reduce((sum, inv) => sum + parseFloat(inv.total_usd || 0), 0);
                },

                async saveInvoices() {
                    // Validate each invoice has an account selected
                    for (let inv of this.invoices) {
                        if (!inv.account_id) {
                            alert(`Por favor selecciona la cuenta bancaria de donde se pagó la factura de ${inv.merchant}`);
                            return;
                        }
                    }

                    this.isSaving = true;

                    try {
                        const res = await fetch('<?= base_url('ocr/save') ?>', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ invoices: this.invoices })
                        });

                        const result = await res.json();

                        if (result.status === 'success') {
                            this.showToast(result.message);
                            this.invoices = [];
                            setTimeout(() => {
                                window.location.href = '<?= base_url('history') ?>';
                            }, 1800);
                        } else {
                            alert(result.message || 'Error al guardar los gastos.');
                        }
                    } catch (e) {
                        console.error('Error saving:', e);
                        alert('Error de conexión al registrar los gastos.');
                    } finally {
                        this.isSaving = false;
                    }
                },

                async saveApiKey() {
                    if (!this.ocrApiKeyInput.trim()) {
                        alert('Ingresa una API Key válida.');
                        return;
                    }

                    try {
                        const res = await fetch('<?= base_url('ocr/settings') ?>', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ api_key: this.ocrApiKeyInput.trim() })
                        });

                        const result = await res.json();
                        if (result.status === 'success') {
                            this.apiKeyConfigured = true;
                            this.openSettingsModal = false;
                            this.showToast('API Key guardada correctamente.');
                        }
                    } catch (e) {
                        console.error(e);
                    }
                },

                previewFullImage(src) {
                    this.previewModal.src = src;
                    this.previewModal.show = true;
                },

                formatBs(val) {
                    return 'Bs. ' + parseFloat(val || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },

                formatUsd(val) {
                    return '$ ' + parseFloat(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },

                showToast(msg) {
                    this.toast.message = msg;
                    this.toast.show = true;
                    setTimeout(() => { this.toast.show = false; }, 3500);
                }
            }
        }
    </script>
</body>
</html>
