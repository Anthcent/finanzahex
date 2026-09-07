<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Escáner OCR & Facturas | Finanzahex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=Outfit:wght@400;500;600;700;800;900&family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <meta name="theme-color" content="#064e3b">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        [x-cloak] { display: none !important; }
        .font-ticket { font-family: 'Space Mono', monospace, 'Courier New', Courier; }
        .customize-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
        .customize-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .customize-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
        .safe-bottom { padding-bottom: max(6.5rem, env(safe-area-inset-bottom)); }
        .safe-top { padding-top: max(0.5rem, env(safe-area-inset-top)); }

        /* Jagged zigzag thermal receipt border */
        .receipt-zigzag-top {
            background-image: radial-gradient(circle at 10px -5px, transparent 12px, #fffef7 13px);
            background-size: 20px 20px;
        }
        .receipt-zigzag-bottom {
            background-image: radial-gradient(circle at 10px 15px, transparent 12px, #fffef7 13px);
            background-size: 20px 20px;
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800 antialiased selection:bg-emerald-500 selection:text-white" x-data="ocrApp()">

    <!-- Executive Top Nav Header (Optimized for Mobile and Desktop) -->
    <header class="sticky top-0 z-40 bg-gradient-to-r from-emerald-950 via-slate-900 to-teal-950 text-white shadow-xl border-b border-emerald-800/30 safe-top">
        <div class="max-w-5xl mx-auto px-3 sm:px-4 py-2.5 flex items-center justify-between gap-2">
            
            <!-- Left: Back & Title -->
            <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                <a href="<?= base_url() ?>" class="w-9 h-9 sm:w-10 sm:h-10 rounded-2xl bg-white/10 hover:bg-white/20 active:scale-95 flex items-center justify-center transition-all border border-white/10 text-white shrink-0" title="Volver al Inicio">
                    <span class="material-icons text-lg sm:text-xl">arrow_back</span>
                </a>
                <div class="flex items-center gap-2 min-w-0">
                    <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-gradient-to-tr from-emerald-500 via-teal-400 to-cyan-400 p-[1.5px] shadow-sm shrink-0">
                        <div class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center">
                            <span class="material-icons text-emerald-400 text-base sm:text-lg">document_scanner</span>
                        </div>
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-xs sm:text-base font-black tracking-tight text-white flex items-center gap-1.5 truncate">
                            <span>Facturas OCR</span>
                            <span class="bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 text-[8px] sm:text-[9px] font-black uppercase px-1.5 py-0.5 rounded-md">Móvil</span>
                        </h1>
                        <p class="text-[9px] sm:text-[10px] text-emerald-200/70 font-semibold truncate hidden sm:block">Digitalización rápida de gastos con cámara</p>
                    </div>
                </div>
            </div>

            <!-- Right: Interactive BCV Rate Pill (Mobile & Desktop) + Settings -->
            <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                
                <!-- Live BCV Rate Widget with direct sync -->
                <div class="flex items-center gap-1 px-2.5 py-1 sm:py-1.5 bg-white/10 border border-white/15 hover:border-emerald-400/50 rounded-xl text-white shadow-xs transition-all">
                    <div class="flex flex-col text-right leading-tight">
                        <span class="text-[7.5px] sm:text-[8px] font-black text-emerald-300 uppercase tracking-wider">TASA BCV</span>
                        <div class="flex items-baseline gap-0.5 font-mono font-black text-[11px] sm:text-xs text-white">
                            <span class="text-[9px] text-emerald-400">Bs.</span>
                            <span x-text="formatNumber(exchangeRate)"></span>
                        </div>
                    </div>
                    
                    <!-- Direct Refresh Rate Button -->
                    <button type="button" @click="syncRate()" 
                            class="w-6 h-6 flex items-center justify-center rounded-lg hover:bg-white/20 active:scale-90 text-emerald-300 hover:text-white transition-all ml-1"
                            :title="'Actualizar Tasa BCV Oficial'">
                        <span class="material-icons text-sm" :class="{'animate-spin': isSyncingRate}">sync</span>
                    </button>
                </div>

                <!-- API Key Config Button -->
                <button @click="openSettingsModal = true" 
                        class="w-9 h-9 rounded-2xl bg-white/10 hover:bg-white/20 active:scale-95 flex items-center justify-center transition-all border border-white/10 text-white shrink-0" 
                        title="Configuración API OCR">
                    <span class="material-icons text-base sm:text-lg">settings</span>
                </button>
            </div>
        </div>

        <!-- Segmented Tab Navigation Bar (Mobile Optimized) -->
        <div class="max-w-5xl mx-auto px-2 sm:px-4 pb-2">
            <div class="bg-slate-950/60 backdrop-blur-md p-1 rounded-2xl border border-white/10 flex items-center gap-1">
                
                <!-- Tab 1: Escanear -->
                <button type="button" @click="activeTab = 'scan'" 
                        :class="activeTab === 'scan' ? 'bg-gradient-to-r from-emerald-600 to-teal-700 text-white font-black shadow-md' : 'text-slate-400 hover:text-white font-bold'"
                        class="flex-1 py-1.5 sm:py-2 text-[11px] sm:text-xs rounded-xl transition-all flex items-center justify-center gap-1.5">
                    <span class="material-icons text-sm sm:text-base">photo_camera</span>
                    <span>Escanear</span>
                </button>

                <!-- Tab 2: Pendientes por Revisar (With Overdue 72h Alert Badge) -->
                <button type="button" @click="activeTab = 'pending'" 
                        :class="activeTab === 'pending' ? 'bg-gradient-to-r from-emerald-600 to-teal-700 text-white font-black shadow-md' : 'text-slate-400 hover:text-white font-bold'"
                        class="flex-1 py-1.5 sm:py-2 text-[11px] sm:text-xs rounded-xl transition-all flex items-center justify-center gap-1.5 relative">
                    <span class="material-icons text-sm sm:text-base">hourglass_top</span>
                    <span>Por Revisar</span>
                    
                    <!-- Badge Counter -->
                    <template x-if="pendingInvoices.length > 0">
                        <span class="px-1.5 py-0.2 rounded-full text-[9px] font-black text-white"
                              :class="overdueCount > 0 ? 'bg-rose-500 animate-pulse ring-2 ring-rose-400/60' : 'bg-amber-500'"
                              x-text="pendingInvoices.length"></span>
                    </template>
                </button>

                <!-- Tab 3: Historial Escaneado -->
                <button type="button" @click="activeTab = 'history'" 
                        :class="activeTab === 'history' ? 'bg-gradient-to-r from-emerald-600 to-teal-700 text-white font-black shadow-md' : 'text-slate-400 hover:text-white font-bold'"
                        class="flex-1 py-1.5 sm:py-2 text-[11px] sm:text-xs rounded-xl transition-all flex items-center justify-center gap-1.5">
                    <span class="material-icons text-sm sm:text-base">receipt_long</span>
                    <span>Historial</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="max-w-5xl mx-auto px-3 sm:px-4 py-4 sm:py-6 space-y-4 sm:space-y-6 safe-bottom">

        <!-- CRITICAL OVERDUE 72-HOUR ALERT BANNER (If any pending invoice > 72h) -->
        <div x-show="overdueCount > 0" x-transition
             class="bg-gradient-to-r from-rose-600 via-rose-700 to-red-800 text-white rounded-3xl p-4 sm:p-5 shadow-xl shadow-rose-950/20 border border-rose-400/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div class="flex items-start gap-3 min-w-0">
                <div class="w-10 h-10 rounded-2xl bg-white/20 flex items-center justify-center text-white shrink-0 animate-bounce">
                    <span class="material-icons text-2xl">warning</span>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h4 class="text-xs sm:text-sm font-black tracking-tight">¡ALERTA DE REVISIÓN PENDIENTE (> 72 HORAS)!</h4>
                        <span class="bg-white/20 border border-white/30 text-[9px] font-black uppercase px-2 py-0.5 rounded-md" x-text="overdueCount + ' Factura(s) Vencida(s)'"></span>
                    </div>
                    <p class="text-[11px] sm:text-xs text-rose-100 font-semibold mt-0.5 leading-relaxed">
                        Tienes facturas de <b>Carga Rápida</b> escaneadas hace más de 72 horas sin verificar. Por favor revisa y confirma para asegurar la contabilidad exacta.
                    </p>
                </div>
            </div>
            <button @click="activeTab = 'pending'" 
                    class="w-full sm:w-auto px-4 py-2 bg-white hover:bg-rose-50 text-rose-800 text-xs font-black rounded-xl shrink-0 active:scale-95 shadow-md flex items-center justify-center gap-1 transition-all">
                <span class="material-icons text-sm">visibility</span>
                <span>Revisar Ahora</span>
            </button>
        </div>

        <!-- API Key Banner Warning if not set -->
        <div x-show="!apiKeyConfigured" class="bg-amber-50 border border-amber-200 rounded-3xl p-3.5 sm:p-4 flex items-center justify-between gap-3 shadow-xs">
            <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0">
                    <span class="material-icons text-base sm:text-lg">vpn_key</span>
                </div>
                <div class="min-w-0">
                    <h4 class="text-xs font-black text-amber-900 truncate">API Key de OCR.space demo</h4>
                    <p class="text-[10px] sm:text-[11px] text-amber-700 font-semibold truncate">Configura tu API Key gratuita para mayor velocidad y sin límites.</p>
                </div>
            </div>
            <button @click="openSettingsModal = true" class="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-black rounded-xl shrink-0 active:scale-95">Configurar</button>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 1: ESCANEAR & CARGA RÁPIDA AUTOMÁTICA -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'scan'" class="space-y-4 sm:space-y-5">
            
            <!-- Quick Scan Mode Toggle Card -->
            <div class="bg-gradient-to-br from-white to-emerald-50/50 rounded-3xl p-4 sm:p-5 shadow-xs border border-emerald-200/80 space-y-4">
                
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-emerald-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl flex items-center justify-center transition-all shrink-0"
                             :class="quickScanMode ? 'bg-amber-500 text-white shadow-md shadow-amber-500/30' : 'bg-slate-100 text-slate-500'">
                            <span class="material-icons text-xl" :class="{'animate-pulse': quickScanMode}">bolt</span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm sm:text-base font-black text-slate-900">Carga Rápida Automática</h3>
                                <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-md"
                                      :class="quickScanMode ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-slate-100 text-slate-500'">
                                    <span x-text="quickScanMode ? 'ACTIVADA' : 'MANUAL'"></span>
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500 font-semibold mt-0.5">
                                <span x-show="quickScanMode">⚡ Foto ➔ OCR ➔ Débito automático en cuenta bancaria ➔ Queda en <b>Por Revisar</b> para verificar.</span>
                                <span x-show="!quickScanMode">📝 Escanea y muestra todos los ítems y totales en pantalla para revisar y editar antes de guardar.</span>
                            </p>
                        </div>
                    </div>

                    <!-- Toggle Button -->
                    <button type="button" @click="quickScanMode = !quickScanMode" 
                            class="relative inline-flex h-7 w-12 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out self-start sm:self-auto"
                            :class="quickScanMode ? 'bg-emerald-600' : 'bg-slate-300'">
                        <span class="sr-only">Toggle Carga Rápida</span>
                        <span aria-hidden="true" 
                              class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out"
                              :class="quickScanMode ? 'translate-x-5' : 'translate-x-0'"></span>
                    </button>
                </div>

                <!-- Carga Rápida Configuration (Bank Account & Owner Pre-selection) -->
                <div x-show="quickScanMode" x-transition class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                    <div>
                        <label class="block text-[10px] font-black text-emerald-800 uppercase mb-1 flex items-center gap-1">
                            <span class="material-icons text-xs">account_balance</span>
                            <span>Cuenta Bancaria a Debitar *</span>
                        </label>
                        <select x-model="quickAccountId" class="w-full bg-white border border-emerald-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 outline-none focus:ring-2 focus:ring-emerald-400">
                            <?php foreach ($accounts as $acc): ?>
                                <option value="<?= $acc['id'] ?>"><?= esc($acc['name']) ?> (<?= esc($acc['currency']) ?> - Saldo: Bs. <?= number_format($acc['balance'], 2, ',', '.') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase mb-1 flex items-center gap-1">
                            <span class="material-icons text-xs">tune</span>
                            <span>Asignación del Gasto</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="quickOwner = 'Negocio'" 
                                    :class="quickOwner === 'Negocio' ? 'bg-emerald-600 text-white font-black shadow-xs' : 'bg-white text-slate-600 font-bold border border-slate-200'"
                                    class="flex-1 py-2 text-xs rounded-xl transition-all">Negocio</button>
                            <button type="button" @click="quickOwner = 'Personal'" 
                                    :class="quickOwner === 'Personal' ? 'bg-blue-600 text-white font-black shadow-xs' : 'bg-white text-slate-600 font-bold border border-slate-200'"
                                    class="flex-1 py-2 text-xs rounded-xl transition-all">Personal</button>
                        </div>
                    </div>
                </div>

                <!-- Pre-processing Filter Mode Toggle -->
                <div class="flex items-center justify-between pt-1 text-xs">
                    <span class="text-[11px] font-bold text-slate-500">Optimización de imagen térmica:</span>
                    <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl border border-slate-200">
                        <button type="button" @click="filterMode = 'normal'" 
                                :class="filterMode === 'normal' ? 'bg-white text-emerald-800 font-black shadow-xs' : 'text-slate-500 font-bold'"
                                class="px-2.5 py-1 text-[10px] rounded-lg transition-all">Normal</button>
                        <button type="button" @click="filterMode = 'thermal'" 
                                :class="filterMode === 'thermal' ? 'bg-emerald-600 text-white font-black shadow-xs' : 'text-slate-500 font-bold'"
                                class="px-2.5 py-1 text-[10px] rounded-lg transition-all">Alto Contraste</button>
                    </div>
                </div>
            </div>

            <!-- Two Main Action Buttons (Mobile First Touch Targets) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                
                <!-- 1. Direct Camera Trigger -->
                <label class="cursor-pointer bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 active:scale-98 text-white p-5 rounded-3xl shadow-lg shadow-emerald-950/20 border border-emerald-500/30 flex items-center gap-4 transition-all group">
                    <input type="file" accept="image/*" capture="environment" class="hidden" @change="handleImageInput($event)">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-white/15 flex items-center justify-center group-hover:scale-110 transition-transform shrink-0">
                        <span class="material-icons text-2xl sm:text-3xl">photo_camera</span>
                    </div>
                    <div class="min-w-0">
                        <span class="text-sm sm:text-base font-black block tracking-tight">Tomar Foto con Cámara</span>
                        <span class="text-[11px] text-emerald-100/80 font-semibold block" x-text="quickScanMode ? '⚡ Foto ➔ Procesa y guarda de una vez' : 'Abre la cámara nativa del celular'"></span>
                    </div>
                </label>

                <!-- 2. Gallery / File Upload (Supports Multiple Files) -->
                <label class="cursor-pointer bg-white hover:bg-slate-50 active:scale-98 text-slate-800 p-5 rounded-3xl border border-slate-200/90 shadow-xs flex items-center gap-4 transition-all group">
                    <input type="file" accept="image/*" multiple class="hidden" @change="handleImageInput($event)">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-slate-100 flex items-center justify-center group-hover:scale-110 transition-transform shrink-0 text-slate-700">
                        <span class="material-icons text-2xl sm:text-3xl">collections</span>
                    </div>
                    <div class="min-w-0">
                        <span class="text-sm sm:text-base font-black block tracking-tight">Subir desde Galería</span>
                        <span class="text-[11px] text-slate-500 font-semibold block">Selecciona 1 o varias fotos de facturas</span>
                    </div>
                </label>

            </div>

            <!-- Image Queue / Staging Area (Visible when images are in queue in manual mode) -->
            <div x-show="pendingImages.length > 0 && !quickScanMode" x-transition class="bg-white rounded-3xl p-4 sm:p-5 shadow-xs border border-slate-200/80 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center font-black text-xs" x-text="pendingImages.length"></span>
                        <h3 class="text-xs sm:text-sm font-black text-slate-800">Fotos en Cola para Escanear</h3>
                    </div>
                    <button @click="pendingImages = []" class="text-[11px] font-bold text-rose-600 hover:text-rose-700 flex items-center gap-1">
                        <span class="material-icons text-xs">delete_sweep</span>
                        <span>Limpiar</span>
                    </button>
                </div>

                <!-- Image Thumbnails Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                    <template x-for="(img, idx) in pendingImages" :key="'pimg-' + idx">
                        <div class="relative group rounded-2xl overflow-hidden border border-slate-200 bg-slate-900 aspect-3/4 flex items-center justify-center">
                            <img :src="img" class="w-full h-full object-cover">
                            <button @click="removePendingImage(idx)" class="absolute top-2 right-2 w-6 h-6 rounded-full bg-rose-600/90 text-white flex items-center justify-center shadow-md active:scale-95">
                                <span class="material-icons text-xs">close</span>
                            </button>
                            <span class="absolute bottom-2 left-2 bg-black/60 backdrop-blur-sm text-white text-[9px] font-black px-1.5 py-0.5 rounded-md" x-text="'#' + (idx + 1)"></span>
                        </div>
                    </template>
                </div>

                <!-- Process Action Button -->
                <div class="pt-2">
                    <button @click="processOcrQueue()" :disabled="isProcessing" 
                            class="w-full bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-700 hover:opacity-95 active:scale-98 text-white py-3 px-4 rounded-2xl font-black text-xs sm:text-sm shadow-xl shadow-emerald-950/20 flex items-center justify-center gap-2 transition-all disabled:opacity-50">
                        <span class="material-icons text-base sm:text-lg" :class="{'animate-spin': isProcessing}">document_scanner</span>
                        <span x-text="isProcessing ? 'Procesando con OCR (' + processingProgress + ')...' : 'Escanear ' + pendingImages.length + ' Factura(s)'"></span>
                    </button>
                </div>
            </div>

            <!-- Active Loading Indicator -->
            <div x-show="isProcessing" x-transition 
                 class="bg-slate-900 text-white rounded-3xl p-5 shadow-2xl flex items-center gap-4 border border-slate-800">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400 shrink-0">
                    <span class="material-icons text-2xl animate-spin">refresh</span>
                </div>
                <div class="min-w-0">
                    <h4 class="text-sm font-black text-white" x-text="quickScanMode ? 'Procesando Carga Rápida...' : 'Digitalizando con OCR...'"></h4>
                    <p class="text-xs text-slate-400 font-semibold mt-0.5">Extrayendo datos de la factura con OCR.space e identificando productos.</p>
                </div>
            </div>

            <!-- Manual Review Invoice Cards (When processed in manual mode) -->
            <div x-show="invoices.length > 0 && !quickScanMode" class="space-y-4 sm:space-y-5">
                
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm sm:text-base font-black text-slate-900 flex items-center gap-2">
                            <span>Facturas Escaneadas para Revisar</span>
                            <span class="bg-emerald-100 text-emerald-800 text-xs font-black px-2 py-0.5 rounded-full" x-text="invoices.length"></span>
                        </h3>
                        <p class="text-[10px] sm:text-[11px] text-slate-400 font-bold">Verifica y ajusta los renglones antes de confirmar el gasto</p>
                    </div>
                    <button @click="invoices = []" class="text-xs font-bold text-rose-600 hover:text-rose-700">Descartar todo</button>
                </div>

                <!-- Loop of Scanned Invoice Cards -->
                <template x-for="(inv, iIdx) in invoices" :key="inv.uid">
                    <div class="bg-white rounded-3xl p-4 sm:p-5 shadow-xs border border-slate-200/90 space-y-4 relative">
                        
                        <!-- Header & Meta -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                            <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl overflow-hidden bg-slate-100 border border-slate-200 shrink-0 cursor-pointer shadow-xs" @click="previewFullImage(inv.image_preview)">
                                    <img :src="inv.image_preview" class="w-full h-full object-cover">
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-1.5 flex-wrap mb-1">
                                        <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 border border-emerald-200"
                                              x-text="inv.model_label || 'Factura'"></span>
                                        <template x-if="inv.payment_method">
                                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-md bg-slate-100 text-slate-700 border border-slate-200" x-text="inv.payment_method"></span>
                                        </template>
                                    </div>
                                    <h4 class="text-sm sm:text-base font-black text-slate-900 truncate" x-text="inv.merchant || 'Comercio'"></h4>
                                </div>
                            </div>

                            <!-- Actions Header: Button to Open Dedicated OCR Text Modal + Delete -->
                            <div class="flex items-center gap-2 self-end sm:self-auto flex-wrap">
                                <button type="button" @click="openOcrTextModal(inv.raw_text, inv.merchant)" 
                                        class="text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-xs font-black px-3 py-1.5 rounded-xl transition-all flex items-center gap-1.5 shadow-xs active:scale-95">
                                    <span class="material-icons text-sm">description</span>
                                    <span>Ver Texto OCR</span>
                                </button>
                                
                                <button @click="removeInvoice(iIdx)" class="text-rose-500 hover:text-rose-700 hover:bg-rose-50 text-xs font-bold p-1.5 rounded-xl transition-all" title="Eliminar factura">
                                    <span class="material-icons text-base">delete</span>
                                </button>
                            </div>
                        </div>

                        <!-- Form Fields Grid (Mobile Responsive) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5">
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Comercio / Proveedor</label>
                                <input type="text" x-model="inv.merchant" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 outline-none focus:border-emerald-500 focus:bg-white">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">RIF</label>
                                <input type="text" x-model="inv.rif" placeholder="J-XXXXXXXXX" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 outline-none focus:border-emerald-500 focus:bg-white">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Nro. Factura / Control</label>
                                <input type="text" x-model="inv.invoice_number" placeholder="00000000" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 outline-none focus:border-emerald-500 focus:bg-white">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Fecha Emisión</label>
                                <input type="date" x-model="inv.date" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 outline-none focus:border-emerald-500 focus:bg-white">
                            </div>
                        </div>

                        <!-- Payment & Account Selectors -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 p-3 sm:p-4 rounded-2xl bg-slate-50 border border-slate-200/70">
                            <div>
                                <label class="block text-[10px] font-black text-emerald-800 uppercase mb-1">Cuenta Banco *</label>
                                <select x-model="inv.account_id" class="w-full bg-white border border-emerald-300 rounded-xl px-2.5 py-2 text-xs font-bold text-slate-800 outline-none focus:ring-2 focus:ring-emerald-400">
                                    <option value="">Seleccionar cuenta...</option>
                                    <?php foreach ($accounts as $acc): ?>
                                        <option value="<?= $acc['id'] ?>"><?= esc($acc['name']) ?> (<?= esc($acc['currency']) ?> - Saldo: <?= number_format($acc['balance'], 2, ',', '.') ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-500 uppercase mb-1">Categoría</label>
                                <select x-model="inv.category_id" class="w-full bg-white border border-slate-200 rounded-xl px-2.5 py-2 text-xs font-bold text-slate-800 outline-none">
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= esc($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-500 uppercase mb-1">Tipo de Gasto</label>
                                <div class="flex items-center gap-1">
                                    <button type="button" @click="inv.owner = 'Negocio'" 
                                            :class="inv.owner === 'Negocio' ? 'bg-emerald-600 text-white font-black shadow-xs' : 'bg-white text-slate-600 font-bold border border-slate-200'"
                                            class="flex-1 py-1.5 text-xs rounded-xl transition-all">Negocio</button>
                                    <button type="button" @click="inv.owner = 'Personal'" 
                                            :class="inv.owner === 'Personal' ? 'bg-blue-600 text-white font-black shadow-xs' : 'bg-white text-slate-600 font-bold border border-slate-200'"
                                            class="flex-1 py-1.5 text-xs rounded-xl transition-all">Personal</button>
                                </div>
                            </div>
                        </div>

                        <!-- Items Section: Guaranteed Rendering with No Alpine Template Clashing -->
                        <div class="space-y-3 pt-1">
                            <div class="flex items-center justify-between">
                                <h5 class="text-xs font-black text-slate-700 flex items-center gap-1.5">
                                    <span class="material-icons text-sm text-slate-400">shopping_cart</span>
                                    <span>Productos / Servicios Detectados</span>
                                    <span class="bg-emerald-100 text-emerald-800 text-[10px] font-black px-2 py-0.5 rounded-full" x-text="inv.items.length + ' ítems'"></span>
                                </h5>
                                <div class="flex items-center gap-1.5">
                                    <button type="button" @click="recalculateInvoiceTotal(inv); showToast('Totales recalculados')" class="text-[10px] font-bold text-slate-600 hover:text-slate-800 bg-slate-100 hover:bg-slate-200 px-2.5 py-1.5 rounded-xl transition-all flex items-center gap-1">
                                        <span class="material-icons text-xs">calculate</span>
                                        <span>Recalcular</span>
                                    </button>
                                    <button type="button" @click="addItemToInvoice(inv)" class="text-[10px] font-black text-emerald-700 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 px-2.5 py-1.5 rounded-xl transition-all flex items-center gap-1">
                                        <span class="material-icons text-xs">add</span>
                                        <span>Agregar Ítem</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Mobile Cards (Visible on screens < 640px) -->
                            <div class="block sm:hidden space-y-2.5">
                                <template x-for="(item, itIdx) in inv.items" :key="'mob-' + inv.uid + '-' + itIdx">
                                    <div class="p-3 bg-slate-50 border border-slate-200/90 rounded-2xl space-y-2 shadow-2xs">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="w-5 h-5 rounded-full bg-slate-200 text-slate-600 text-[10px] font-black flex items-center justify-center shrink-0" x-text="itIdx + 1"></span>
                                            <input type="text" x-model="item.name" placeholder="Nombre o descripción del producto" class="flex-1 bg-white border border-slate-200 rounded-xl px-2.5 py-2 text-xs font-bold text-slate-800 outline-none focus:border-emerald-500">
                                            <button @click="removeItemFromInvoice(inv, itIdx)" class="text-slate-300 hover:text-rose-500 p-1.5 rounded-lg active:scale-95 transition-all">
                                                <span class="material-icons text-base">close</span>
                                            </button>
                                        </div>
                                        <div class="grid grid-cols-3 gap-2 text-xs">
                                            <div>
                                                <span class="text-[9px] font-bold text-slate-400 block mb-0.5">Cant</span>
                                                <input type="number" step="0.01" x-model.number="item.quantity" class="w-full bg-white border border-slate-200 rounded-lg py-1.5 px-2 text-center font-bold outline-none text-xs">
                                            </div>
                                            <div>
                                                <span class="text-[9px] font-bold text-slate-400 block mb-0.5">Precio (Bs)</span>
                                                <input type="number" step="0.01" x-model.number="item.price" @input="updateItemUsd(item, inv.exchange_rate); recalculateInvoiceTotal(inv)" class="w-full bg-white border border-slate-200 rounded-lg py-1.5 px-2 text-right font-black text-slate-800 outline-none text-xs">
                                            </div>
                                            <div>
                                                <span class="text-[9px] font-bold text-slate-400 block mb-0.5">Impuesto</span>
                                                <select x-model="item.tax_type" @change="recalculateInvoiceTotal(inv)" class="w-full bg-white border border-slate-200 rounded-lg py-1.5 px-1 text-center font-bold text-xs outline-none">
                                                    <option value="G">G (16%)</option>
                                                    <option value="E">E (Exento)</option>
                                                    <option value="R">R (Reducido)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between pt-1 border-t border-slate-200/50 text-[10px]">
                                            <span class="text-slate-400 font-semibold">Ref. Dólar:</span>
                                            <span class="font-black text-emerald-700" x-text="formatUsd(item.price_usd)"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Desktop Table (Visible on screens >= 640px) -->
                            <div class="hidden sm:block overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="text-[9px] font-black uppercase text-slate-400 border-b border-slate-100 pb-1">
                                            <th class="py-2 px-1 w-16">Cant</th>
                                            <th class="py-2 px-2">Descripción</th>
                                            <th class="py-2 px-2 text-right w-28">Precio (Bs)</th>
                                            <th class="py-2 px-2 text-right w-24">Precio ($)</th>
                                            <th class="py-2 px-1 text-center w-20">IVA</th>
                                            <th class="py-2 px-1 text-right w-8"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 text-xs font-bold">
                                        <template x-for="(item, itIdx) in inv.items" :key="'desk-' + inv.uid + '-' + itIdx">
                                            <tr>
                                                <td class="py-1.5 px-1">
                                                    <input type="number" step="0.01" x-model.number="item.quantity" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-1.5 text-center font-bold text-xs outline-none focus:bg-white focus:border-emerald-500">
                                                </td>
                                                <td class="py-1.5 px-2">
                                                    <input type="text" x-model="item.name" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-1.5 font-bold text-xs outline-none focus:bg-white focus:border-emerald-500">
                                                </td>
                                                <td class="py-1.5 px-2 text-right">
                                                    <input type="number" step="0.01" x-model.number="item.price" @input="updateItemUsd(item, inv.exchange_rate); recalculateInvoiceTotal(inv)" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-1.5 text-right font-black text-xs text-slate-800 outline-none focus:bg-white focus:border-emerald-500">
                                                </td>
                                                <td class="py-1.5 px-2 text-right">
                                                    <input type="number" step="0.01" x-model.number="item.price_usd" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-1.5 text-right font-black text-xs text-emerald-700 outline-none focus:bg-white">
                                                </td>
                                                <td class="py-1.5 px-1 text-center">
                                                    <select x-model="item.tax_type" @change="recalculateInvoiceTotal(inv)" class="bg-slate-50 border border-slate-200 rounded-lg p-1.5 text-[10px] font-black outline-none">
                                                        <option value="G">G (16%)</option>
                                                        <option value="E">E (Exento)</option>
                                                        <option value="R">R (Red.)</option>
                                                    </select>
                                                </td>
                                                <td class="py-1.5 px-1 text-right">
                                                    <button @click="removeItemFromInvoice(inv, itIdx)" class="text-slate-300 hover:text-rose-500 transition-colors">
                                                        <span class="material-icons text-sm">close</span>
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Empty Items Indicator if none found -->
                            <div x-show="inv.items.length === 0" class="p-4 text-center bg-slate-50 border border-dashed border-slate-200 rounded-2xl text-xs text-slate-400 font-bold space-y-1">
                                <p>No se encontraron renglones separados automáticamente.</p>
                                <button type="button" @click="addItemToInvoice(inv)" class="text-emerald-700 underline font-black">+ Agregar el primer producto</button>
                            </div>
                        </div>

                        <!-- Grand Total Breakdown Card -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-3 border-t border-slate-100 items-end">
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

                            <div class="bg-gradient-to-br from-emerald-50 to-teal-50/50 p-3.5 rounded-2xl border border-emerald-200/80 text-right">
                                <span class="text-[9px] font-black uppercase text-emerald-800 tracking-wider block">Total Factura</span>
                                <div class="text-xl sm:text-2xl font-black text-slate-900 mt-0.5" x-text="formatBs(inv.total_bs)"></div>
                                <div class="text-xs sm:text-sm font-black text-emerald-700 mt-0.5" x-text="formatUsd(inv.total_usd)"></div>
                            </div>
                        </div>

                    </div>
                </template>

            </div>

            <!-- Sticky Floating Save Bar (Visible when invoices exist in manual mode) -->
            <div x-show="invoices.length > 0 && !quickScanMode" class="fixed bottom-0 left-0 right-0 z-30 bg-white/95 backdrop-blur-md border-t border-slate-200 p-3 sm:p-4 shadow-2xl safe-bottom">
                <div class="max-w-5xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="self-start sm:self-auto">
                        <span class="text-[10px] font-black uppercase text-slate-400 block">Total a Registrar</span>
                        <div class="flex items-center gap-2">
                            <span class="text-base sm:text-lg font-black text-slate-900" x-text="formatBs(grandTotalBs())"></span>
                            <span class="text-xs sm:text-sm font-black text-emerald-700" x-text="'(' + formatUsd(grandTotalUsd()) + ')'"></span>
                        </div>
                    </div>

                    <button @click="saveInvoices()" :disabled="isSaving"
                            class="w-full sm:w-auto bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 active:scale-95 text-white px-6 py-3 rounded-2xl font-black text-xs sm:text-sm shadow-lg shadow-emerald-950/20 flex items-center justify-center gap-2 transition-all disabled:opacity-50">
                        <span class="material-icons text-base" :class="{'animate-spin': isSaving}">check_circle</span>
                        <span x-text="isSaving ? 'Registrando gastos...' : 'Guardar ' + invoices.length + ' Factura(s)'"></span>
                    </button>
                </div>
            </div>

        </div>

        <!-- ========================================================================= -->
        <!-- TAB 2: PENDIENTES POR REVISAR (CARGA RÁPIDA & 72H ALERT) -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'pending'" class="space-y-4">
            
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm sm:text-base font-black text-slate-900 flex items-center gap-2">
                        <span>Facturas Pendientes por Revisar</span>
                        <span class="bg-amber-100 text-amber-800 text-xs font-black px-2 py-0.5 rounded-full" x-text="pendingInvoices.length"></span>
                    </h3>
                    <p class="text-[11px] text-slate-500 font-semibold">Facturas registradas por Carga Rápida que requieren tu confirmación o ajuste</p>
                </div>
                <button type="button" @click="fetchPendingInvoices()" class="text-xs font-bold text-emerald-600 hover:text-emerald-800 flex items-center gap-1">
                    <span class="material-icons text-sm">refresh</span>
                    <span>Actualizar</span>
                </button>
            </div>

            <!-- Empty State -->
            <div x-show="pendingInvoices.length === 0" class="bg-white rounded-3xl p-8 text-center border border-slate-200/80 space-y-3">
                <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto">
                    <span class="material-icons text-3xl">task_alt</span>
                </div>
                <div>
                    <h4 class="text-sm font-black text-slate-800">¡Todo al día! No hay facturas pendientes</h4>
                    <p class="text-xs text-slate-400 font-semibold mt-1">Todas las facturas escaneadas han sido confirmadas y aprobadas.</p>
                </div>
            </div>

            <!-- List of Pending Cards -->
            <div class="grid grid-cols-1 gap-3.5">
                <template x-for="pInv in pendingInvoices" :key="pInv.id">
                    <div class="bg-white rounded-3xl p-4 sm:p-5 shadow-xs border transition-all space-y-3.5"
                         :class="pInv.is_overdue_72h ? 'border-rose-400/90 ring-1 ring-rose-300 bg-rose-50/10' : 'border-slate-200/90'">
                        
                        <!-- Card Top Bar: Overdue Pill & Date -->
                        <div class="flex items-center justify-between gap-2 flex-wrap pb-2 border-b border-slate-100">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <!-- 72h Alert Pill -->
                                <template x-if="pInv.is_overdue_72h">
                                    <span class="bg-rose-600 text-white text-[9px] font-black uppercase px-2 py-0.5 rounded-md flex items-center gap-1 shadow-xs animate-pulse">
                                        <span class="material-icons text-[10px]">alarm</span>
                                        <span x-text="'¡VENCIDA (' + pInv.elapsed_hours + 'h sin revisar)!'"></span>
                                    </span>
                                </template>
                                <template x-if="!pInv.is_overdue_72h">
                                    <span class="bg-amber-100 text-amber-800 text-[9px] font-black uppercase px-2 py-0.5 rounded-md flex items-center gap-1">
                                        <span class="material-icons text-[10px]">schedule</span>
                                        <span x-text="'Hace ' + pInv.elapsed_hours + 'h (Quedan ' + pInv.hours_remaining + 'h)'"></span>
                                    </span>
                                </template>

                                <span class="bg-slate-100 text-slate-600 text-[9px] font-bold px-2 py-0.5 rounded-md" x-text="pInv.model_label || 'Factura'"></span>
                            </div>

                            <div class="text-[11px] font-bold text-slate-400" x-text="pInv.invoice_date + ' ' + (pInv.invoice_time || '')"></div>
                        </div>

                        <!-- Card Body -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="min-w-0">
                                <h4 class="text-sm sm:text-base font-black text-slate-900 truncate" x-text="pInv.merchant || 'Comercio'"></h4>
                                <div class="flex items-center gap-2 text-xs font-bold text-slate-500 mt-1 flex-wrap">
                                    <span x-show="pInv.rif" x-text="'RIF: ' + pInv.rif"></span>
                                    <span x-show="pInv.invoice_number" x-text="'Fac #: ' + pInv.invoice_number"></span>
                                    <span class="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md" x-text="'Debitado de: ' + (pInv.account_name || 'Banco')"></span>
                                </div>
                            </div>

                            <div class="text-left sm:text-right shrink-0 bg-slate-50 sm:bg-transparent p-2.5 sm:p-0 rounded-2xl">
                                <div class="text-base sm:text-lg font-black text-slate-900" x-text="formatBs(pInv.total_bs)"></div>
                                <div class="text-xs font-black text-emerald-700" x-text="formatUsd(pInv.total_usd)"></div>
                            </div>
                        </div>

                        <!-- Item Breakdown Drawer inside Pending Card -->
                        <div class="pt-2 border-t border-slate-100 space-y-2">
                            <div class="flex items-center justify-between">
                                <button type="button" @click="pInv.showItems = !pInv.showItems" 
                                        class="flex items-center gap-1.5 text-xs font-black text-slate-700 hover:text-emerald-700 transition-all">
                                    <span class="material-icons text-sm transition-transform duration-200" :class="{'rotate-180': pInv.showItems}">expand_more</span>
                                    <span>Ítems / Renglones de la Factura</span>
                                    <span class="bg-emerald-100 text-emerald-800 text-[10px] font-black px-2 py-0.5 rounded-full" 
                                          x-text="(pInv.items && pInv.items.length ? pInv.items.length : 0) + ' ítem(s)'"></span>
                                </button>
                                <span class="text-[10px] font-bold text-slate-400" x-show="!pInv.showItems">Toca para ver</span>
                            </div>

                            <!-- List of items (Expanded by default or on click) -->
                            <div x-show="pInv.showItems || (pInv.items && pInv.items.length <= 3 && pInv.showItems !== false)" x-transition class="space-y-1.5 pt-1">
                                <template x-for="(it, itIdx) in (pInv.items || [])" :key="'pitem-' + pInv.id + '-' + itIdx">
                                    <div class="flex items-center justify-between p-2.5 bg-slate-50 hover:bg-slate-100/80 rounded-xl text-xs transition-colors">
                                        <div class="min-w-0 flex-1 pr-2">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <span class="font-mono font-black text-slate-400 text-[11px]" x-text="(parseFloat(it.quantity || 1).toFixed(2)) + 'x'"></span>
                                                <span class="font-bold text-slate-800 truncate" x-text="it.name"></span>
                                                <span class="text-[9px] font-black px-1.5 py-0.2 rounded"
                                                      :class="it.tax_type === 'E' ? 'bg-amber-100 text-amber-800' : 'bg-slate-200 text-slate-700'"
                                                      x-text="it.tax_type || 'G'"></span>
                                            </div>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <span class="font-black text-slate-900 block" x-text="formatBs(it.price)"></span>
                                            <span class="text-[10px] font-bold text-emerald-700 block" x-show="it.price_usd > 0" x-text="formatUsd(it.price_usd)"></span>
                                        </div>
                                    </div>
                                </template>
                                
                                <div x-show="!pInv.items || pInv.items.length === 0" class="p-2.5 text-center text-xs text-slate-400 font-bold bg-slate-50 rounded-xl">
                                    Factura registrada por monto global sin desglose de renglones individuales.
                                </div>
                            </div>
                        </div>

                        <!-- Card Actions Toolbar (Mobile-Friendly Flex) -->
                        <div class="flex items-center gap-2 pt-2 border-t border-slate-100 flex-wrap">
                            
                            <!-- 1. Simulación Ticket Digital -->
                            <button type="button" @click="openReceiptSimulator(pInv)" 
                                    class="flex-1 sm:flex-none px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5 active:scale-95">
                                <span class="material-icons text-sm">receipt</span>
                                <span>Ver Ticket</span>
                            </button>

                            <!-- 2. Ver Texto OCR -->
                            <button type="button" @click="openOcrTextModal(pInv.raw_text, pInv.merchant)" 
                                    class="flex-1 sm:flex-none px-3 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5 active:scale-95">
                                <span class="material-icons text-sm">description</span>
                                <span>Ver OCR</span>
                            </button>

                            <!-- 3. Editar -->
                            <button type="button" @click="editPendingInvoice(pInv)" 
                                    class="flex-1 sm:flex-none px-3 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5 active:scale-95">
                                <span class="material-icons text-sm">edit</span>
                                <span>Editar</span>
                            </button>

                            <!-- 4. Aprobar / Confirmar -->
                            <button type="button" @click="approvePendingInvoice(pInv.id)" 
                                    class="flex-1 sm:flex-none px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black rounded-xl transition-all shadow-md shadow-emerald-900/10 flex items-center justify-center gap-1.5 active:scale-95">
                                <span class="material-icons text-sm">check</span>
                                <span>Confirmar</span>
                            </button>

                            <!-- 5. Cancelar / Anular -->
                            <button type="button" @click="cancelPendingInvoice(pInv)" 
                                    class="px-2.5 py-2 text-rose-500 hover:bg-rose-50 rounded-xl transition-all text-xs font-bold flex items-center justify-center gap-1">
                                <span class="material-icons text-sm">cancel</span>
                                <span>Anular</span>
                            </button>
                        </div>

                    </div>
                </template>
            </div>

        </div>

        <!-- ========================================================================= -->
        <!-- TAB 3: HISTORIAL DE FACTURAS ESCANEADAS -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'history'" class="space-y-4">
            
            <!-- Filters Card -->
            <div class="bg-white rounded-3xl p-4 shadow-xs border border-slate-200/80 space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Buscar Comercio</label>
                        <input type="text" x-model="historyFilter.merchant" @input="filterHistory()" placeholder="Ej: Supermercado, Farmatodo..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Estado</label>
                        <select x-model="historyFilter.status" @change="filterHistory()" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 outline-none">
                            <option value="all">Todos los estados</option>
                            <option value="approved">Aprobadas</option>
                            <option value="pending_review">Pendientes por revisar</option>
                            <option value="cancelled">Anuladas</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="button" @click="fetchHistoryInvoices()" class="w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-black rounded-xl transition-all flex items-center justify-center gap-1">
                            <span class="material-icons text-sm">refresh</span>
                            <span>Refrescar Historial</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Invoices List -->
            <div class="grid grid-cols-1 gap-3">
                <template x-for="hInv in filteredHistoryList" :key="hInv.id">
                    <div class="bg-white rounded-3xl p-4 shadow-xs border border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:border-emerald-300 transition-all">
                        
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                <!-- Status Badge -->
                                <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-md"
                                      :class="{
                                          'bg-emerald-100 text-emerald-800 border border-emerald-200': hInv.status === 'approved',
                                          'bg-amber-100 text-amber-800 border border-amber-200': hInv.status === 'pending_review',
                                          'bg-rose-100 text-rose-800 border border-rose-200': hInv.status === 'cancelled'
                                      }"
                                      x-text="hInv.status === 'approved' ? 'Aprobada' : (hInv.status === 'pending_review' ? 'Pendiente' : 'Anulada')"></span>

                                <span class="text-[10px] font-bold text-slate-400" x-text="hInv.invoice_date + ' ' + (hInv.invoice_time || '')"></span>
                                
                                <template x-if="hInv.quick_scan">
                                    <span class="text-[9px] font-black px-1.5 py-0.2 rounded bg-amber-50 text-amber-700 border border-amber-200">Carga Rápida</span>
                                </template>
                            </div>

                            <h4 class="text-sm sm:text-base font-black text-slate-900 truncate" x-text="hInv.merchant || 'Comercio'"></h4>
                            
                            <div class="flex items-center gap-2 text-xs font-bold text-slate-500 mt-0.5 flex-wrap">
                                <span x-show="hInv.rif" x-text="'RIF: ' + hInv.rif"></span>
                                <span x-show="hInv.invoice_number" x-text="'Fac: ' + hInv.invoice_number"></span>
                                <span class="text-slate-600" x-text="'Cuenta: ' + (hInv.account_name || 'N/D')"></span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between sm:justify-end gap-2 shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-100">
                            <div class="text-left sm:text-right mr-1">
                                <div class="text-sm sm:text-base font-black text-slate-900" x-text="formatBs(hInv.total_bs)"></div>
                                <div class="text-[11px] font-black text-emerald-700" x-text="formatUsd(hInv.total_usd)"></div>
                            </div>

                            <!-- Open OCR Modal Button -->
                            <button type="button" @click="openOcrTextModal(hInv.raw_text, hInv.merchant)" 
                                    class="w-9 h-9 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center transition-all shadow-xs"
                                    title="Ver Texto OCR Crudo">
                                <span class="material-icons text-base">description</span>
                            </button>

                            <!-- Open Simulator Button -->
                            <button type="button" @click="openReceiptSimulator(hInv)" 
                                    class="w-9 h-9 rounded-2xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 flex items-center justify-center transition-all shadow-xs"
                                    title="Ver Simulación de Factura / Ticket Térmico">
                                <span class="material-icons text-base">receipt</span>
                            </button>
                        </div>

                    </div>
                </template>
            </div>

        </div>

    </main>

    <!-- ========================================================================= -->
    <!-- NEW MODAL: TEXTO EXTRAÍDO POR OCR (RAW TEXT MODAL) -->
    <!-- ========================================================================= -->
    <div x-show="ocrTextModal.show" x-transition x-cloak 
         class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4" 
         @click.self="ocrTextModal.show = false">
        
        <div class="w-full max-w-xl bg-slate-950 text-white rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] border border-slate-800">
            
            <!-- Modal Header -->
            <div class="p-4 sm:p-5 border-b border-slate-800/80 flex items-center justify-between gap-3 bg-slate-900/60">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center shrink-0">
                        <span class="material-icons text-xl">description</span>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-sm sm:text-base font-black tracking-tight text-white truncate">Texto Extraído por OCR</h3>
                            <span class="bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 text-[9px] font-black uppercase px-2 py-0.5 rounded-md"
                                  x-text="ocrTextModal.lineCount + ' líneas'"></span>
                            <span class="bg-slate-800 text-slate-300 text-[9px] font-black uppercase px-2 py-0.5 rounded-md"
                                  x-text="ocrTextModal.charCount + ' caract.'"></span>
                        </div>
                        <p class="text-xs text-slate-400 font-semibold truncate mt-0.5" x-text="ocrTextModal.merchant"></p>
                    </div>
                </div>

                <button @click="ocrTextModal.show = false" class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center transition-all">
                    <span class="material-icons text-sm">close</span>
                </button>
            </div>

            <!-- Modal Content: Raw Preformatted Text -->
            <div class="p-4 sm:p-5 overflow-y-auto customize-scrollbar flex-1 bg-slate-950">
                <div class="bg-slate-900/90 rounded-2xl p-4 border border-slate-800 font-ticket text-xs text-emerald-300 leading-relaxed max-h-[60vh] overflow-y-auto whitespace-pre-wrap select-all customize-scrollbar" 
                     x-text="ocrTextModal.text"></div>
            </div>

            <!-- Modal Actions Bar -->
            <div class="p-3.5 sm:p-4 bg-slate-900/90 border-t border-slate-800 flex items-center justify-between gap-2 shrink-0">
                <button type="button" @click="copyOcrText()" 
                        class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black flex items-center gap-1.5 active:scale-95 transition-all shadow-md shadow-emerald-950/30">
                    <span class="material-icons text-sm">content_copy</span>
                    <span>Copiar Texto OCR</span>
                </button>

                <button type="button" @click="ocrTextModal.show = false" 
                        class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-bold active:scale-95 transition-all">
                    Cerrar
                </button>
            </div>

        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL: SIMULACIÓN DE FACTURA / TICKET TÉRMICO DIGITAL -->
    <!-- ========================================================================= -->
    <div x-show="receiptModal.show" x-transition x-cloak 
         class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4" 
         @click.self="receiptModal.show = false">
        
        <div class="w-full max-w-sm sm:max-w-md bg-[#fffef7] text-slate-800 rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] border border-amber-100 relative">
            
            <!-- Jagged Top Border -->
            <div class="h-3 w-full receipt-zigzag-top shrink-0"></div>

            <!-- Receipt Content (Scrollable with thermal paper look) -->
            <div class="p-5 sm:p-6 overflow-y-auto customize-scrollbar font-ticket text-xs space-y-4 flex-1">
                
                <!-- Ticket Header -->
                <div class="text-center space-y-1 pb-3 border-b-2 border-dashed border-slate-400">
                    <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">REPÚBLICA BOLIVARIANA DE VENEZUELA</div>
                    <div class="text-[10px] font-bold text-slate-500 uppercase">SENIAT</div>
                    <div class="text-base sm:text-lg font-black text-slate-900 tracking-tight leading-tight mt-1" x-text="receiptModal.data?.merchant || 'COMERCIO'"></div>
                    <div class="text-xs font-bold text-slate-700" x-text="'RIF: ' + (receiptModal.data?.rif || 'NO REGISTRADO')"></div>
                    <div class="text-[10px] font-semibold text-slate-500" x-text="receiptModal.data?.model_label || 'COMPROBANTE FISCAL'"></div>
                </div>

                <!-- Ticket Invoice Details -->
                <div class="flex justify-between text-[11px] font-bold text-slate-700 pb-2 border-b border-dashed border-slate-300">
                    <div>
                        <div>FACTURA FISCAL: <span class="font-black" x-text="receiptModal.data?.invoice_number || '00000000'"></span></div>
                        <div>FECHA: <span x-text="receiptModal.data?.invoice_date || ''"></span></div>
                    </div>
                    <div class="text-right">
                        <div>CONTROL: <span x-text="receiptModal.data?.invoice_number ? '00-' + receiptModal.data.invoice_number : '00-S/N'"></span></div>
                        <div>HORA: <span x-text="receiptModal.data?.invoice_time || '12:00'"></span></div>
                    </div>
                </div>

                <!-- Items Breakdown Table -->
                <div class="space-y-1.5 pb-3 border-b-2 border-dashed border-slate-400">
                    <div class="flex justify-between text-[10px] font-black text-slate-500 uppercase pb-1 border-b border-slate-200">
                        <span class="w-10">CANT</span>
                        <span class="flex-1 px-1">DESCRIPCIÓN</span>
                        <span class="w-20 text-right">TOTAL BS</span>
                    </div>

                    <template x-for="(it, idx) in (receiptModal.data?.items || [])" :key="'ritem-' + idx">
                        <div class="flex justify-between text-[11px] leading-tight py-0.5">
                            <span class="w-10 font-bold" x-text="parseFloat(it.quantity || 1).toFixed(2)"></span>
                            <span class="flex-1 px-1 truncate font-semibold" x-text="it.name + ' (' + (it.tax_type || 'G') + ')'"></span>
                            <span class="w-20 text-right font-black" x-text="formatBsNumber(it.price)"></span>
                        </div>
                    </template>

                    <div x-show="!receiptModal.data?.items || receiptModal.data?.items.length === 0" class="text-center py-2 text-slate-400 text-[11px]">
                        Consumo general registrado
                    </div>
                </div>

                <!-- Tax and Subtotal Calculations -->
                <div class="space-y-1 text-[11px] font-bold text-slate-700 pb-3 border-b-2 border-dashed border-slate-400">
                    <div class="flex justify-between" x-show="receiptModal.data?.subtotal > 0">
                        <span>SUBTOTAL:</span>
                        <span x-text="formatBs(receiptModal.data?.subtotal)"></span>
                    </div>
                    <div class="flex justify-between" x-show="receiptModal.data?.exento > 0">
                        <span>TOTAL EXENTO (E):</span>
                        <span x-text="formatBs(receiptModal.data?.exento)"></span>
                    </div>
                    <div class="flex justify-between" x-show="receiptModal.data?.base_imponible > 0">
                        <span>BASE IMPONIBLE (G 16%):</span>
                        <span x-text="formatBs(receiptModal.data?.base_imponible)"></span>
                    </div>
                    <div class="flex justify-between" x-show="receiptModal.data?.iva_amount > 0">
                        <span>IVA (16%):</span>
                        <span x-text="formatBs(receiptModal.data?.iva_amount)"></span>
                    </div>
                    <div class="flex justify-between" x-show="receiptModal.data?.igtf_amount > 0">
                        <span>IGTF (3%):</span>
                        <span x-text="formatBs(receiptModal.data?.igtf_amount)"></span>
                    </div>
                    
                    <!-- Grand Total Big Line -->
                    <div class="flex justify-between text-sm sm:text-base font-black text-slate-950 pt-2 border-t border-slate-400">
                        <span>TOTAL A PAGAR:</span>
                        <span x-text="formatBs(receiptModal.data?.total_bs)"></span>
                    </div>

                    <div class="flex justify-between text-xs font-black text-emerald-800">
                        <span>REF. USD (Tasa <span x-text="parseFloat(receiptModal.data?.exchange_rate || 50).toFixed(2)"></span>):</span>
                        <span x-text="formatUsd(receiptModal.data?.total_usd)"></span>
                    </div>
                </div>

                <!-- Payment Details in Ticket -->
                <div class="text-[10px] space-y-0.5 text-slate-600 pb-2 border-b border-dashed border-slate-300">
                    <div>FORMA DE PAGO: <span class="font-bold" x-text="receiptModal.data?.payment_method || 'BioPago / Débito'"></span></div>
                    <div x-show="receiptModal.data?.cashea_amount > 0">CASHEA FINANCIAMIENTO: <span class="font-bold" x-text="formatBs(receiptModal.data?.cashea_amount)"></span></div>
                    <div>CUENTA REGISTRO: <span class="font-bold" x-text="receiptModal.data?.account_name || 'Caja / Banco'"></span></div>
                    <div>ESTADO: <span class="font-bold uppercase" x-text="receiptModal.data?.status"></span></div>
                </div>

                <!-- Footer Barcode & Fiscal Hash -->
                <div class="text-center space-y-1.5 pt-1">
                    <div class="text-[9px] font-mono tracking-widest text-slate-400">||||| | |||| |||||| || ||||| |||||</div>
                    <div class="text-[8px] text-slate-400 font-mono">SERIAL IMPRESORA: Z1F0001063</div>
                    <div class="text-[10px] font-bold text-slate-700 uppercase">¡GRACIAS POR SU COMPRA!</div>
                </div>

            </div>

            <!-- Jagged Bottom Border -->
            <div class="h-3 w-full receipt-zigzag-bottom shrink-0"></div>

            <!-- Receipt Actions Modal Bar -->
            <div class="p-3.5 bg-slate-900 text-white flex items-center justify-between gap-2 shrink-0">
                <button type="button" @click="copyTicketSummary(receiptModal.data)" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-bold flex items-center gap-1 active:scale-95 transition-all">
                    <span class="material-icons text-xs">content_copy</span>
                    <span>Copiar Ticket</span>
                </button>

                <button type="button" @click="window.print()" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-bold flex items-center gap-1 active:scale-95 transition-all">
                    <span class="material-icons text-xs">print</span>
                    <span>Imprimir</span>
                </button>

                <button type="button" @click="receiptModal.show = false" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black active:scale-95 transition-all">
                    Cerrar
                </button>
            </div>

        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL: EDIT PENDING INVOICE -->
    <!-- ========================================================================= -->
    <div x-show="editModal.show" x-transition x-cloak 
         class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4" 
         @click.self="editModal.show = false">
        <div class="w-full max-w-lg bg-white rounded-3xl p-5 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto customize-scrollbar border border-slate-100">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <span class="material-icons text-blue-600">edit_note</span>
                    <h3 class="font-black text-slate-800 text-sm sm:text-base">Editar Factura Pendiente</h3>
                </div>
                <button @click="editModal.show = false" class="text-slate-400 hover:text-slate-600">
                    <span class="material-icons text-sm">close</span>
                </button>
            </div>

            <template x-if="editModal.data">
                <div class="space-y-3 text-xs font-bold">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Comercio</label>
                            <input type="text" x-model="editModal.data.merchant" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">RIF</label>
                            <input type="text" x-model="editModal.data.rif" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Nro Factura</label>
                            <input type="text" x-model="editModal.data.invoice_number" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Fecha</label>
                            <input type="date" x-model="editModal.data.invoice_date" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Total en Bs *</label>
                            <input type="number" step="0.01" x-model.number="editModal.data.total_bs" @input="editModal.data.total_usd = parseFloat((editModal.data.total_bs / exchangeRate).toFixed(2))" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-black outline-none text-slate-800">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Total Ref ($)</label>
                            <input type="number" step="0.01" x-model.number="editModal.data.total_usd" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-black outline-none text-emerald-700">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-emerald-800 uppercase mb-1">Cuenta Bancaria Debitada *</label>
                        <select x-model="editModal.data.account_id" class="w-full bg-slate-50 border border-emerald-300 rounded-xl px-3 py-2 text-xs font-bold outline-none">
                            <?php foreach ($accounts as $acc): ?>
                                <option value="<?= $acc['id'] ?>"><?= esc($acc['name']) ?> (<?= esc($acc['currency']) ?> - Saldo: Bs. <?= number_format($acc['balance'], 2, ',', '.') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button @click="editModal.show = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-100">Cancelar</button>
                        <button @click="saveEditedInvoice()" class="px-5 py-2 rounded-xl text-xs font-black bg-emerald-600 hover:bg-emerald-700 text-white shadow-md active:scale-95">Guardar Cambios</button>
                    </div>
                </div>
            </template>
        </div>
    </div>

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
        <div class="max-w-md w-full bg-white rounded-3xl p-5 sm:p-6 shadow-2xl space-y-4 border border-slate-100" @click.away="openSettingsModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <span class="material-icons text-emerald-600">settings</span>
                    <h3 class="font-black text-slate-800 text-sm sm:text-base">Configuración OCR</h3>
                </div>
                <button @click="openSettingsModal = false" class="text-slate-400 hover:text-slate-600">
                    <span class="material-icons text-sm">close</span>
                </button>
            </div>

            <div class="space-y-3 text-xs font-bold text-slate-600">
                <p>Ingresa tu API Key de <a href="https://ocr.space/ocrapi" target="_blank" class="text-emerald-600 underline font-black">ocr.space</a> para procesamiento a alta velocidad y sin restricciones de la demo.</p>
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
         class="fixed top-4 sm:top-6 right-4 sm:right-6 z-50 bg-slate-900 text-white px-4 py-3 rounded-2xl shadow-2xl flex items-center gap-2 border border-slate-700 text-xs font-bold max-w-sm">
        <span class="material-icons text-emerald-400 text-base">check_circle</span>
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
                isSyncingRate: false,

                // Active Tab: 'scan' | 'pending' | 'history'
                activeTab: '<?= $overdueCount > 0 ? "pending" : "scan" ?>',

                // Carga Rápida Mode Toggle: Default to FALSE so items list is shown after scanning
                quickScanMode: false,
                quickAccountId: <?= !empty($accounts[0]['id']) ? $accounts[0]['id'] : 0 ?>,
                quickOwner: 'Negocio',
                filterMode: 'normal', // 'normal' | 'thermal'

                // Queue of images in manual mode
                pendingImages: [],
                isProcessing: false,
                processingProgress: '',
                isSaving: false,

                // Invoices in manual review
                invoices: [],

                // Pending Invoices with 72h rule
                pendingInvoices: <?= json_encode($pendingInvoices, JSON_UNESCAPED_UNICODE) ?>,
                overdueCount: <?= $overdueCount ?>,

                // History Invoices
                historyInvoices: <?= json_encode($historyInvoices, JSON_UNESCAPED_UNICODE) ?>,
                historyFilter: { merchant: '', status: 'all' },

                // Modals
                ocrTextModal: { show: false, merchant: '', text: '', lineCount: 0, charCount: 0 },
                receiptModal: { show: false, data: null },
                editModal: { show: false, data: null },
                previewModal: { show: false, src: '' },

                // Toast
                toast: { show: false, message: '' },

                init() {
                    // Pre-select first account if not set
                    if (!this.quickAccountId && <?= !empty($accounts[0]['id']) ? 'true' : 'false' ?>) {
                        this.quickAccountId = <?= !empty($accounts[0]['id']) ? $accounts[0]['id'] : 0 ?>;
                    }
                },

                get filteredHistoryList() {
                    let list = this.historyInvoices || [];
                    if (this.historyFilter.status && this.historyFilter.status !== 'all') {
                        list = list.filter(item => item.status === this.historyFilter.status);
                    }
                    if (this.historyFilter.merchant && this.historyFilter.merchant.trim() !== '') {
                        let term = this.historyFilter.merchant.toLowerCase();
                        list = list.filter(item => (item.merchant || '').toLowerCase().includes(term));
                    }
                    return list;
                },

                // Handle Camera Snapshot / Gallery Selection
                handleImageInput(event) {
                    const files = event.target.files;
                    if (!files || files.length === 0) return;

                    Array.from(files).forEach(file => {
                        this.compressAndProcessImage(file);
                    });

                    event.target.value = '';
                },

                // Compress image on client canvas
                compressAndProcessImage(file) {
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

                            if (this.filterMode === 'thermal') {
                                ctx.filter = 'grayscale(100%) contrast(185%) brightness(105%)';
                            } else {
                                ctx.filter = 'none';
                            }

                            ctx.drawImage(img, 0, 0, width, height);
                            const compressedDataUrl = canvas.toDataURL('image/jpeg', 0.85);

                            if (this.quickScanMode) {
                                // Direct Quick Mode: Auto-scan, auto-deduct, auto-save to pending!
                                this.executeQuickScan([compressedDataUrl]);
                            } else {
                                // Manual Mode: Add to queue or process directly
                                this.pendingImages.push(compressedDataUrl);
                                // If user took 1 photo directly from camera, auto-process queue for fast experience!
                                if (this.pendingImages.length === 1) {
                                    this.processOcrQueue();
                                }
                            }
                        };
                        img.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                },

                // Execute Quick Scan & Auto Register
                async executeQuickScan(imagesList) {
                    this.isProcessing = true;
                    try {
                        const res = await fetch('<?= base_url('ocr/quick-process') ?>', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                images: imagesList,
                                account_id: this.quickAccountId,
                                owner: this.quickOwner
                            })
                        });

                        const result = await res.json();
                        if (result.status === 'success') {
                            this.showToast(result.message);
                            if (result.pending_invoices) {
                                this.pendingInvoices = result.pending_invoices;
                            }
                            this.overdueCount = result.overdue_count || 0;
                            // Switch to pending tab so user sees it right away with items
                            this.activeTab = 'pending';
                        } else {
                            alert(result.message || 'Error en Carga Rápida.');
                        }
                    } catch (e) {
                        console.error('Quick scan error:', e);
                        alert('Error al comunicar con el servidor.');
                    } finally {
                        this.isProcessing = false;
                    }
                },

                // Process OCR in manual mode
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
                                const defaultAcc = this.quickAccountId || <?= !empty($accounts[0]['id']) ? $accounts[0]['id'] : 'null' ?>;
                                const defaultCat = <?= !empty($categories[0]['id']) ? $categories[0]['id'] : 'null' ?>;
                                item.account_id = item.account_id || defaultAcc;
                                item.category_id = item.category_id || defaultCat;
                                this.invoices.push(item);
                            });

                            this.pendingImages = [];
                            this.showToast('¡Factura escaneada! Revisa todos los renglones abajo.');
                        } else {
                            alert(result.message || 'Error al procesar con OCR');
                        }
                    } catch (e) {
                        console.error('Error in OCR:', e);
                        alert('Error de conexión con el OCR.');
                    } finally {
                        this.isProcessing = false;
                        this.processingProgress = '';
                    }
                },

                // Confirm and save manual invoices
                async saveInvoices() {
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
                            this.fetchHistoryInvoices();
                            this.activeTab = 'history';
                        } else {
                            alert(result.message || 'Error al guardar.');
                        }
                    } catch (e) {
                        console.error('Error saving:', e);
                        alert('Error al registrar las facturas.');
                    } finally {
                        this.isSaving = false;
                    }
                },

                // Approve a pending review invoice
                async approvePendingInvoice(id) {
                    try {
                        const res = await fetch(`<?= base_url('ocr/approve') ?>/${id}`, { method: 'POST' });
                        const result = await res.json();
                        if (result.status === 'success') {
                            this.showToast(result.message);
                            this.pendingInvoices = result.pending_invoices;
                            this.overdueCount = result.overdue_count || 0;
                            this.fetchHistoryInvoices();
                        } else {
                            alert(result.message || 'Error al confirmar.');
                        }
                    } catch (e) {
                        console.error(e);
                    }
                },

                // Open edit pending invoice modal
                editPendingInvoice(inv) {
                    this.editModal.data = JSON.parse(JSON.stringify(inv));
                    this.editModal.show = true;
                },

                async saveEditedInvoice() {
                    if (!this.editModal.data) return;
                    const id = this.editModal.data.id;
                    try {
                        const res = await fetch(`<?= base_url('ocr/update-pending') ?>/${id}`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(this.editModal.data)
                        });
                        const result = await res.json();
                        if (result.status === 'success') {
                            this.showToast(result.message);
                            this.editModal.show = false;
                            this.pendingInvoices = result.pending_invoices;
                            this.overdueCount = result.overdue_count || 0;
                            this.fetchHistoryInvoices();
                        } else {
                            alert(result.message || 'Error al actualizar.');
                        }
                    } catch (e) {
                        console.error(e);
                    }
                },

                // Cancel / Void pending invoice and reverse bank deduction
                async cancelPendingInvoice(inv) {
                    if (!confirm(`¿Estás seguro de anular la factura de ${inv.merchant}? Se reintegrarán Bs. ${parseFloat(inv.total_bs).toFixed(2)} a tu cuenta bancaria.`)) {
                        return;
                    }

                    try {
                        const res = await fetch(`<?= base_url('ocr/cancel') ?>/${inv.id}`, { method: 'POST' });
                        const result = await res.json();
                        if (result.status === 'success') {
                            this.showToast(result.message);
                            this.pendingInvoices = result.pending_invoices;
                            this.overdueCount = result.overdue_count || 0;
                            if (result.history_invoices) {
                                this.historyInvoices = result.history_invoices;
                            }
                        } else {
                            alert(result.message || 'Error al anular la factura.');
                        }
                    } catch (e) {
                        console.error(e);
                    }
                },

                // Fetch pending invoices from server
                async fetchPendingInvoices() {
                    try {
                        const res = await fetch('<?= base_url('ocr/pending') ?>');
                        const result = await res.json();
                        if (result.status === 'success') {
                            this.pendingInvoices = result.data;
                            this.overdueCount = result.overdue_count || 0;
                        }
                    } catch (e) {}
                },

                // Fetch history invoices from server
                async fetchHistoryInvoices() {
                    try {
                        const res = await fetch('<?= base_url('ocr/history') ?>');
                        const result = await res.json();
                        if (result.status === 'success') {
                            this.historyInvoices = result.data;
                        }
                    } catch (e) {}
                },

                filterHistory() {
                    // Reactive through filteredHistoryList getter
                },

                // Open Dedicated OCR Text Modal
                openOcrTextModal(rawText, merchant) {
                    const text = (rawText || '').trim() || 'No hay texto crudo extraído disponible para esta factura.';
                    const lines = text.split('\n').filter(l => l.trim().length > 0);
                    this.ocrTextModal = {
                        show: true,
                        merchant: merchant || 'Factura Escaneada',
                        text: text,
                        lineCount: lines.length,
                        charCount: text.length
                    };
                },

                copyOcrText() {
                    if (!this.ocrTextModal.text) return;
                    navigator.clipboard.writeText(this.ocrTextModal.text);
                    this.showToast('¡Texto OCR copiado al portapapeles!');
                },

                // Open Thermal Receipt Simulator Modal
                openReceiptSimulator(inv) {
                    this.receiptModal.data = inv;
                    this.receiptModal.show = true;
                },

                // Copy clean receipt summary
                copyTicketSummary(inv) {
                    if (!inv) return;
                    let lines = [];
                    lines.push(`*** COMPROBANTE FISCAL ***`);
                    lines.push(`Comercio: ${inv.merchant || 'Comercio'}`);
                    if (inv.rif) lines.push(`RIF: ${inv.rif}`);
                    if (inv.invoice_number) lines.push(`Factura: ${inv.invoice_number}`);
                    lines.push(`Fecha: ${inv.invoice_date} ${inv.invoice_time || ''}`);
                    lines.push(`---------------------------`);
                    if (inv.items && inv.items.length > 0) {
                        inv.items.forEach(it => {
                            lines.push(`${it.quantity}x ${it.name} - Bs. ${parseFloat(it.price).toFixed(2)} (${it.tax_type || 'G'})`);
                        });
                        lines.push(`---------------------------`);
                    }
                    if (inv.subtotal > 0) lines.push(`Subtotal: Bs. ${parseFloat(inv.subtotal).toFixed(2)}`);
                    if (inv.exento > 0) lines.push(`Exento: Bs. ${parseFloat(inv.exento).toFixed(2)}`);
                    if (inv.base_imponible > 0) lines.push(`Base Imponible: Bs. ${parseFloat(inv.base_imponible).toFixed(2)}`);
                    if (inv.iva_amount > 0) lines.push(`IVA (16%): Bs. ${parseFloat(inv.iva_amount).toFixed(2)}`);
                    lines.push(`TOTAL A PAGAR: Bs. ${parseFloat(inv.total_bs).toFixed(2)} ($ ${parseFloat(inv.total_usd).toFixed(2)})`);
                    if (inv.payment_method) lines.push(`Pago: ${inv.payment_method}`);
                    lines.push(`Cuenta: ${inv.account_name || ''}`);

                    navigator.clipboard.writeText(lines.join('\n'));
                    this.showToast('¡Ticket copiado al portapapeles!');
                },

                // Live Sync BCV Dollar Rate
                async syncRate() {
                    this.isSyncingRate = true;
                    try {
                        const res = await fetch('<?= base_url('currency/get-rate') ?>');
                        const data = await res.json();
                        if (data.status === 'success' && data.rate > 0) {
                            this.exchangeRate = data.rate;
                            this.showToast(`Tasa BCV actualizada: Bs. ${this.formatNumber(data.rate)} (${data.source || 'Oficial'})`);
                            // Update open invoice USD calculations
                            this.invoices.forEach(inv => {
                                inv.exchange_rate = data.rate;
                                this.recalculateInvoiceTotal(inv);
                            });
                        } else {
                            alert(data.message || 'No se pudo actualizar la tasa.');
                        }
                    } catch (e) {
                        console.error('Rate sync error:', e);
                    } finally {
                        this.isSyncingRate = false;
                    }
                },

                removePendingImage(idx) {
                    this.pendingImages.splice(idx, 1);
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

                formatNumber(val) {
                    return parseFloat(val || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },

                formatBsNumber(val) {
                    return parseFloat(val || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },

                formatBs(val) {
                    return 'Bs. ' + this.formatNumber(val);
                },

                formatUsd(val) {
                    return '$ ' + parseFloat(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },

                showToast(msg) {
                    this.toast.message = msg;
                    this.toast.show = true;
                    setTimeout(() => { this.toast.show = false; }, 4000);
                }
            }
        }
    </script>
</body>
</html>
