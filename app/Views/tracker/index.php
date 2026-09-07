<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Fi-Hex Wallet</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <!-- Google Fonts: Plus Jakarta Sans for a more modern feel -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- PWA -->
    <meta name="theme-color" content="#f8fafc">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="<?= base_url('manifest.json') ?>">
    <link rel="apple-touch-icon" href="<?= base_url('favicon.ico') ?>">
    
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('<?= base_url("sw.js") ?>')
                    .then(reg => console.log('SW registrado', reg))
                    .catch(err => console.log('SW error', err));
            });
        }
    </script>

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        [x-cloak] { display: none !important; }
        .touch-action-manipulation { touch-action: manipulation; }
        /* Hide scrollbar for Chrome, Safari and Opera */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        /* Hide scrollbar for IE, Edge and Firefox */
        .no-scrollbar { -ms-overflow-style: none;  scrollbar-width: none; }
        
        .glass-panel {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        
        .safe-bottom {
            padding-bottom: env(safe-area-inset-bottom);
        }
        /* Clamp text size for very small screens */
        .text-clamp-xl { font-size: clamp(1.25rem, 5vw, 1.5rem); }
        .text-clamp-amount { font-size: clamp(2.5rem, 10vw, 3rem); }
    </style>
</head>
<body class="bg-gradient-to-br from-emerald-50/70 via-slate-50 to-teal-50/50 fixed inset-0 w-full flex items-center justify-center lg:py-10 text-slate-800 overflow-hidden" 
      x-data="transactionApp()"
      @keydown.window="handleKeydown($event)">

    <!-- App Container (Responsive Card) -->
    <div class="w-full h-full lg:w-[400px] lg:h-[90vh] lg:max-h-[850px] bg-[#F8FAFC] lg:rounded-[2.5rem] lg:shadow-2xl overflow-hidden flex flex-col relative ring-1 ring-emerald-950/5 safe-bottom">

        <!-- Header Area (Executive Fintech Top Nav) -->
        <div class="flex-none pt-2.5 px-4 pb-2 z-50 relative lg:pt-6">
            <div class="mb-2 transition-all duration-300" :class="compactLevel > 0 ? 'mb-1.5' : 'mb-2'">
                <div class="flex justify-between items-center gap-2">
                
                <!-- Brand / Logo & Title -->
                <div class="flex items-center gap-2.5 min-w-0" :class="compactLevel > 1 ? 'hidden' : 'flex'">
                    <!-- Fintech Hexagon Monogram Badge -->
                    <div class="w-9 h-9 rounded-2xl bg-gradient-to-br from-emerald-600 via-teal-700 to-emerald-950 text-white flex items-center justify-center shadow-md shadow-emerald-950/20 ring-1 ring-emerald-400/40 shrink-0">
                        <span class="material-icons text-lg">account_balance_wallet</span>
                    </div>
                    
                    <div class="leading-tight min-w-0">
                        <h1 class="font-black text-slate-900 tracking-tight leading-none truncate" :class="compactLevel === 1 ? 'text-sm' : 'text-base'">
                            Fi-Hex <span class="bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent font-black">Wallet</span>
                        </h1>
                        <p class="text-[9px] font-bold text-slate-400 flex items-center gap-1 mt-0.5" x-show="compactLevel === 0">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block animate-pulse"></span>
                            <span>Resumen financiero</span>
                        </p>
                    </div>
                </div>
                
                <!-- Ultra Mode Brand (Only visible in Level 2) -->
                <div class="flex items-center gap-1.5 shrink-0" x-show="compactLevel > 1">
                    <div class="w-7 h-7 bg-gradient-to-br from-emerald-600 to-teal-800 rounded-xl flex items-center justify-center text-white font-bold text-xs shadow-xs">
                        <span class="material-icons text-sm">account_balance_wallet</span>
                    </div>
                    <span class="font-black text-slate-800 text-xs">Fi-Hex</span>
                </div>

                <!-- Controls & Actions Toolbar -->
                <div class="flex items-center gap-1.5 shrink-0">
                    <!-- Shortcut: OCR Scanner -->
                    <a href="<?= base_url('ocr') ?>" 
                       class="w-9 h-9 flex items-center justify-center bg-white/90 hover:bg-emerald-50 text-slate-600 hover:text-emerald-700 rounded-2xl border border-slate-200/80 hover:border-emerald-300 transition-all shadow-xs active:scale-95 group shrink-0" 
                       title="Escanear Facturas (OCR)">
                        <span class="material-icons text-base group-hover:scale-110 transition-transform">document_scanner</span>
                    </a>

                    <!-- Shortcut: Printing Tool -->
                    <a href="<?= base_url('printing') ?>" 
                       class="w-9 h-9 flex items-center justify-center bg-white/90 hover:bg-emerald-50 text-slate-600 hover:text-emerald-700 rounded-2xl border border-slate-200/80 hover:border-emerald-300 transition-all shadow-xs active:scale-95 group shrink-0" 
                       title="Módulo de Impresiones">
                        <span class="material-icons text-base group-hover:scale-110 transition-transform">print</span>
                    </a>

                    <!-- Main Menu Hamburger Button -->
                    <button type="button" @click="showMenu = !showMenu" 
                            class="w-9 h-9 flex items-center justify-center bg-white/90 hover:bg-slate-100 text-slate-700 rounded-2xl border border-slate-200/80 hover:border-slate-300 shadow-xs active:scale-95 transition-all shrink-0" 
                            title="Menú Principal">
                        <span class="material-icons text-xl">menu</span>
                    </button>
                </div>
                </div>

                <!-- Dedicated rate row: prevents header controls from overlapping -->
                <div class="flex items-center gap-2 mt-2">
                    <div class="h-9 min-w-0 flex-1 flex items-center justify-between bg-white/90 backdrop-blur-md border rounded-2xl px-3 py-1 shadow-xs transition-all"
                         :class="manualRate ? 'border-amber-300 bg-amber-50/40 ring-1 ring-amber-400/20' : 'border-emerald-200/80 hover:border-emerald-300'">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="w-2 h-2 rounded-full shrink-0" :class="manualRate ? 'bg-amber-500' : (rateUpdated ? 'bg-emerald-400 animate-ping' : 'bg-emerald-500')"></span>
                            <div class="leading-none min-w-0">
                                <span class="text-[7.5px] font-black uppercase tracking-wider block" :class="manualRate ? 'text-amber-700' : 'text-slate-500'" x-text="rateUpdated ? 'ACTUALIZADO' : (manualRate ? 'TASA MANUAL USD' : 'DÓLAR BCV')"></span>
                                <div class="flex items-baseline mt-0.5">
                                    <span class="text-[9px] font-black mr-1 text-slate-500">Bs</span>
                                    <input type="number" step="0.01" x-model.number="exchangeRate" :disabled="!manualRate"
                                           class="bg-transparent font-mono font-black text-slate-800 focus:outline-none p-0 border-none h-auto w-24 text-sm transition-colors"
                                           :class="{'text-emerald-700': rateUpdated && !manualRate, 'text-amber-900': manualRate}" placeholder="0.00">
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 pl-2 border-l border-slate-100">
                            <button type="button" @click="fetchRate(); manualRate = false; showMsg('Sincronizando tasa BCV...')" x-show="manualRate" class="w-6 h-6 flex items-center justify-center rounded-lg bg-amber-100 text-amber-700" title="Volver a tasa BCV"><span class="material-icons text-[13px]">sync</span></button>
                            <button type="button" @click="toggleManualRate()" class="w-6 h-6 flex items-center justify-center rounded-lg transition-colors" :class="manualRate ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-500'" :title="manualRate ? 'Guardar tasa' : 'Editar tasa'"><span class="material-icons text-[12px]" x-text="manualRate ? 'check' : 'edit'"></span></button>
                        </div>
                    </div>
                    <button type="button" @click="openCurrencyCalculator()" class="h-9 px-3 rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-700 text-white border border-indigo-500 shadow-md shadow-indigo-900/20 flex items-center gap-1.5 shrink-0 active:scale-95 transition-all" title="Calculadora USD, EUR y Bs">
                        <span class="material-icons text-base">calculate</span>
                        <span class="text-[10px] font-black hidden min-[340px]:inline">Calcular</span>
                    </button>
                </div>
            </div>
            
        <!-- ═══════════════════════════════════════════ -->
        <!-- QUICK-ACCESS FAB PANEL                      -->
        <!-- ═══════════════════════════════════════════ -->

        <!-- Hidden camera/file input for Quick OCR -->
        <input id="quickOcrInput" type="file" accept="image/*" capture="environment"
               class="hidden" @change="handleQuickOcrFile($event)" multiple>

        <!-- Toast notification for Quick OCR result -->
        <div x-cloak x-show="quickOcrToast"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             :class="quickOcrToastType === 'success' ? 'bg-emerald-600' : 'bg-rose-600'"
             class="fixed bottom-24 left-1/2 -translate-x-1/2 z-[200] px-4 py-2.5 rounded-2xl text-white text-xs font-semibold shadow-xl shadow-black/30 flex items-center gap-2 whitespace-nowrap max-w-[90vw]">
            <span x-show="quickOcrLoading" class="w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
            <span x-text="quickOcrLoading ? 'Procesando factura...' : quickOcrToast"></span>
        </div>

        <!-- Loading overlay for Quick OCR -->
        <div x-cloak x-show="quickOcrLoading"
             class="fixed inset-0 z-[150] bg-slate-950/60 backdrop-blur-sm flex flex-col items-center justify-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-amber-500 flex items-center justify-center shadow-xl">
                <span class="material-icons text-white text-2xl animate-pulse">document_scanner</span>
            </div>
            <p class="text-white text-sm font-semibold">Escaneando factura...</p>
            <div class="w-32 h-1 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-amber-400 rounded-full animate-pulse w-3/4"></div>
            </div>
        </div>

        <!-- Backdrop: closes panel when tapping outside -->
        <div x-cloak x-show="showQuickAccess"
             class="fixed inset-0 z-[39]"
             @click="showQuickAccess = false"
             x-transition:enter="duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
        </div>

        <!-- FAB Tab Handle (always visible right edge) -->
        <button @click="showQuickAccess = !showQuickAccess"
                class="fixed right-0 top-1/3 z-[45] flex flex-col items-center justify-center gap-0.5
                       w-6 h-20 bg-gradient-to-b from-emerald-600 to-teal-700
                       rounded-l-2xl shadow-lg shadow-emerald-900/40
                       transition-all duration-300 hover:w-7 active:scale-95"
                :class="showQuickAccess ? 'opacity-0 pointer-events-none' : 'opacity-100'"
                title="Accesos rápidos">
            <span class="material-icons text-white" style="font-size:14px">bolt</span>
            <div class="flex flex-col gap-0.5">
                <div class="w-0.5 h-3 bg-white/60 rounded-full"></div>
                <div class="w-0.5 h-2 bg-white/40 rounded-full"></div>
            </div>
        </button>

        <!-- Quick-Access Slide-in Panel -->
        <div x-cloak x-show="showQuickAccess"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-x-full"
             x-transition:enter-end="opacity-100 translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-x-0"
             x-transition:leave-end="opacity-0 translate-x-full"
             class="fixed right-0 top-1/2 -translate-y-1/2 z-[46]
                    w-[72px] bg-white rounded-l-3xl shadow-2xl shadow-black/25
                    border border-slate-200/80 py-4 px-2 flex flex-col items-center gap-4">

            <!-- Close handle -->
            <button @click="showQuickAccess = false"
                    class="w-8 h-1.5 bg-slate-200 rounded-full mb-1 hover:bg-slate-300 transition-colors"
                    title="Cerrar"></button>

            <!-- 1. Quick OCR Camera Scan -->
            <button @click="triggerQuickOcr()"
                    class="group flex flex-col items-center gap-1 w-full">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 border border-amber-200
                            flex items-center justify-center
                            group-active:scale-90 transition-transform shadow-sm
                            group-hover:bg-amber-100">
                    <span class="material-icons text-amber-500" style="font-size:22px">photo_camera</span>
                </div>
                <span class="text-[9px] font-semibold text-amber-600 leading-tight text-center">Factura</span>
            </button>

            <!-- Divider -->
            <div class="w-8 h-px bg-slate-100"></div>

            <!-- 2. Metrics -->
            <a href="<?= base_url('metrics') ?>"
               class="group flex flex-col items-center gap-1 w-full">
                <div class="w-12 h-12 rounded-2xl bg-teal-50 border border-teal-200
                            flex items-center justify-center
                            group-active:scale-90 transition-transform shadow-sm
                            group-hover:bg-teal-100">
                    <span class="material-icons text-teal-500" style="font-size:22px">bar_chart</span>
                </div>
                <span class="text-[9px] font-semibold text-teal-600 leading-tight text-center">Métricas</span>
            </a>

            <!-- 3. Registro / History -->
            <a href="<?= base_url('history') ?>"
               class="group flex flex-col items-center gap-1 w-full">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 border border-emerald-200
                            flex items-center justify-center
                            group-active:scale-90 transition-transform shadow-sm
                            group-hover:bg-emerald-100">
                    <span class="material-icons text-emerald-600" style="font-size:22px">receipt_long</span>
                </div>
                <span class="text-[9px] font-semibold text-emerald-700 leading-tight text-center">Registro</span>
            </a>

            <!-- 4. Printing / Deudas -->
            <a href="<?= base_url('printing?tab=debts') ?>"
               class="group flex flex-col items-center gap-1 w-full">
                <div class="w-12 h-12 rounded-2xl bg-violet-50 border border-violet-200
                            flex items-center justify-center
                            group-active:scale-90 transition-transform shadow-sm
                            group-hover:bg-violet-100">
                    <span class="material-icons text-violet-500" style="font-size:22px">group</span>
                </div>
                <span class="text-[9px] font-semibold text-violet-600 leading-tight text-center">Deudas</span>
            </a>
        </div>
        <!-- END QUICK-ACCESS FAB PANEL -->

            <!-- Compact Menu Modal (Executive Fintech Design) -->

            <div x-cloak x-show="showMenu"
                 class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                 
                 <!-- Enhanced Backdrop (Darker for focus) -->
                 <div class="absolute inset-0 bg-slate-950/70 backdrop-blur-md transition-opacity" 
                      x-transition:enter="duration-200 ease-out"
                      x-transition:enter-start="opacity-0"
                      x-transition:enter-end="opacity-100"
                      x-transition:leave="duration-150 ease-in"
                      x-transition:leave-start="opacity-100"
                      x-transition:leave-end="opacity-0"
                      @click="showMenu = false"></div>

                 <!-- Menu Content Card (Compact & Centered) -->
                 <div class="relative w-full max-w-md bg-white rounded-[2rem] shadow-2xl shadow-slate-950/30 border border-white/80 ring-1 ring-slate-900/10 overflow-hidden flex flex-col max-h-[92vh] transition-all transform pointer-events-auto"
                      x-transition:enter="duration-300 cubic-bezier(0.34, 1.56, 0.64, 1)"
                      x-transition:enter-start="opacity-0 scale-90 translate-y-8"
                      x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                      x-transition:leave="duration-200 ease-in"
                      x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                      x-transition:leave-end="opacity-0 scale-90 translate-y-8">
                      
                      <!-- Compact Header -->
                      <div class="px-5 py-4 flex justify-between items-center border-b border-slate-100 bg-gradient-to-r from-white via-emerald-50/50 to-indigo-50/50 shrink-0">
                          <div class="flex items-center gap-2.5">
                              <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-emerald-600 to-teal-700 text-white flex items-center justify-center shadow-md shadow-emerald-900/20">
                                  <span class="material-icons text-base">grid_view</span>
                              </div>
                              <div>
                                  <h2 class="text-lg font-black text-slate-900 tracking-tight leading-none">Menú Principal</h2>
                                  <p class="text-[9px] font-extrabold text-slate-400 uppercase tracking-wider mt-0.5">Acceso a Módulos</p>
                              </div>
                          </div>
                          <button type="button" @click="showMenu = false" class="w-9 h-9 flex items-center justify-center rounded-xl bg-white hover:bg-rose-50 text-slate-500 hover:text-rose-600 border border-slate-200 transition-colors active:scale-95">
                              <span class="material-icons text-sm">close</span>
                          </button>
                      </div>

                      <!-- Scrollable Modules Body -->
                      <div class="p-5 space-y-5 overflow-y-auto customize-scrollbar flex-1 bg-slate-50/60">
                          
                          <!-- Group: Finanzas -->
                          <div class="space-y-2">
                              <span class="text-[9.5px] font-black text-emerald-800 uppercase tracking-widest pl-1 flex items-center gap-1">
                                  <span class="w-1 h-3 bg-emerald-500 rounded-full inline-block"></span>
                                  Finanzas
                              </span>
                              <div class="grid grid-cols-3 gap-2.5">
                                  <a href="<?= base_url('printing') ?>" class="p-2.5 rounded-2xl bg-white hover:bg-cyan-50/50 border border-slate-100 hover:border-cyan-200 shadow-xs flex flex-col items-center gap-1.5 transition-all active:scale-95 group">
                                      <div class="w-12 h-12 rounded-xl bg-cyan-50 group-hover:bg-cyan-100 text-cyan-700 flex items-center justify-center shadow-xs transition-colors">
                                          <span class="material-icons text-2xl">print</span>
                                      </div>
                                      <span class="text-[11px] font-bold text-slate-700 group-hover:text-cyan-950 text-center leading-tight">Impresiones</span>
                                  </a>
                                  <a href="<?= base_url('history') ?>" class="p-2.5 rounded-2xl bg-white hover:bg-emerald-50/50 border border-slate-100 hover:border-emerald-200 shadow-xs flex flex-col items-center gap-1.5 transition-all active:scale-95 group">
                                      <div class="w-12 h-12 rounded-xl bg-emerald-50 group-hover:bg-emerald-100 text-emerald-700 flex items-center justify-center shadow-xs transition-colors">
                                          <span class="material-icons text-2xl">list</span>
                                      </div>
                                      <span class="text-[11px] font-bold text-slate-700 group-hover:text-emerald-950 text-center leading-tight">Registros</span>
                                  </a>
                                  <a href="<?= base_url('accounts') ?>" class="p-2.5 rounded-2xl bg-white hover:bg-emerald-50/50 border border-slate-100 hover:border-emerald-200 shadow-xs flex flex-col items-center gap-1.5 transition-all active:scale-95 group">
                                      <div class="w-12 h-12 rounded-xl bg-emerald-50 group-hover:bg-emerald-100 text-emerald-700 flex items-center justify-center shadow-xs transition-colors">
                                          <span class="material-icons text-2xl">account_balance_wallet</span>
                                      </div>
                                      <span class="text-[11px] font-bold text-slate-700 group-hover:text-emerald-950 text-center leading-tight">Cuentas</span>
                                  </a>
                                  <a href="<?= base_url('divisas') ?>" class="p-2.5 rounded-2xl bg-white hover:bg-indigo-50/50 border border-slate-100 hover:border-indigo-200 shadow-xs flex flex-col items-center gap-1.5 transition-all active:scale-95 group">
                                      <div class="w-12 h-12 rounded-xl bg-indigo-50 group-hover:bg-indigo-100 text-indigo-700 flex items-center justify-center shadow-xs transition-colors">
                                          <span class="material-icons text-2xl">currency_exchange</span>
                                      </div>
                                      <span class="text-[11px] font-bold text-slate-700 group-hover:text-indigo-950 text-center leading-tight">Divisas</span>
                                  </a>
                                  <a href="<?= base_url('metrics') ?>" class="p-2.5 rounded-2xl bg-white hover:bg-emerald-50/50 border border-slate-100 hover:border-emerald-200 shadow-xs flex flex-col items-center gap-1.5 transition-all active:scale-95 group">
                                      <div class="w-12 h-12 rounded-xl bg-emerald-50 group-hover:bg-emerald-100 text-emerald-700 flex items-center justify-center shadow-xs transition-colors">
                                          <span class="material-icons text-2xl">bar_chart</span>
                                      </div>
                                      <span class="text-[11px] font-bold text-slate-700 group-hover:text-emerald-950 text-center leading-tight">Métricas</span>
                                  </a>
                                  <a href="<?= base_url('ocr') ?>" class="p-2.5 rounded-2xl bg-white hover:bg-emerald-50/50 border border-slate-100 hover:border-emerald-200 shadow-xs flex flex-col items-center gap-1.5 transition-all active:scale-95 group">
                                      <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-600 text-white flex items-center justify-center shadow-xs transition-transform group-hover:scale-105">
                                          <span class="material-icons text-2xl">document_scanner</span>
                                      </div>
                                      <span class="text-[11px] font-black text-slate-800 group-hover:text-emerald-950 text-center leading-tight">Escáner OCR</span>
                                  </a>
                              </div>
                          </div>

                          <!-- Group: Sistema -->
                          <div class="space-y-2">
                              <span class="text-[9.5px] font-black text-slate-400 uppercase tracking-widest pl-1 flex items-center gap-1">
                                  <span class="w-1 h-3 bg-slate-400 rounded-full inline-block"></span>
                                  Sistema
                              </span>
                              <div class="grid grid-cols-3 gap-2">
                                  <a href="<?= base_url('ai') ?>" class="p-2.5 rounded-2xl bg-white hover:bg-emerald-50/50 border border-slate-100 hover:border-emerald-200 shadow-xs flex flex-col items-center gap-1.5 transition-all active:scale-95 group">
                                      <div class="w-12 h-12 rounded-xl bg-emerald-50 group-hover:bg-emerald-100 text-emerald-700 flex items-center justify-center shadow-xs transition-colors">
                                          <span class="material-icons text-2xl">psychology</span>
                                      </div>
                                      <span class="text-[11px] font-bold text-slate-700 group-hover:text-emerald-950 text-center leading-tight">AI Assistant</span>
                                  </a>
                                  <a href="<?= base_url('audit') ?>" class="p-2.5 rounded-2xl bg-white hover:bg-amber-50/50 border border-slate-100 hover:border-amber-200 shadow-xs flex flex-col items-center gap-1.5 transition-all active:scale-95 group">
                                      <div class="w-12 h-12 rounded-xl bg-amber-50 group-hover:bg-amber-100 text-amber-700 flex items-center justify-center shadow-xs transition-colors">
                                          <span class="material-icons text-2xl">manage_search</span>
                                      </div>
                                      <span class="text-[11px] font-bold text-slate-700 group-hover:text-amber-950 text-center leading-tight">Bitácora</span>
                                  </a>
                                  <a href="<?= base_url('config') ?>" class="p-2.5 rounded-2xl bg-white hover:bg-slate-100/70 border border-slate-100 hover:border-slate-300 shadow-xs flex flex-col items-center gap-1.5 transition-all active:scale-95 group">
                                      <div class="w-12 h-12 rounded-xl bg-slate-100 group-hover:bg-slate-200 text-slate-700 flex items-center justify-center shadow-xs transition-colors">
                                          <span class="material-icons text-2xl">settings</span>
                                      </div>
                                      <span class="text-[11px] font-bold text-slate-700 group-hover:text-slate-900 text-center leading-tight">Ajustes</span>
                                  </a>
                              </div>
                          </div>
                      </div>

                      <!-- Footer Info -->
                      <div class="text-center py-3 bg-white border-t border-slate-100 shrink-0">
                          <p class="text-[9px] font-extrabold text-slate-400 uppercase tracking-widest flex items-center justify-center gap-1">
                              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block animate-pulse"></span>
                              Fi-Hex Wallet • v2.5 Executive
                          </p>
                      </div>
                 </div>
            </div>

            <!-- Emerald Fintech Digital Card (Compact & Rich Dark Emerald) -->
            <div class="relative w-full rounded-2xl bg-gradient-to-br from-[#021810] via-[#052e1f] to-[#01140e] p-3.5 sm:p-4 text-white shadow-xl shadow-emerald-950/30 border border-emerald-500/25 overflow-hidden transition-all duration-300"
                 x-show="compactLevel < 2"
                 :class="compactLevel === 1 ? 'py-2.5 px-3' : 'p-3.5 sm:p-4'">
                <!-- Ambient Subtle Glow Elements -->
                <div class="absolute -right-8 -top-8 w-32 h-32 bg-emerald-400/10 rounded-full blur-2xl pointer-events-none"></div>
                <div class="absolute -left-6 -bottom-6 w-28 h-28 bg-teal-400/5 rounded-full blur-xl pointer-events-none"></div>

                <div class="relative z-10">
                    <!-- Top Row: Card Brand + Contactless + Golden Chip -->
                    <div class="flex items-center justify-between mb-1.5">
                        <div class="flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span class="font-bold tracking-widest uppercase text-emerald-300/80 text-[9px]">Fi-Hex Card</span>
                        </div>
                        
                        <!-- Contactless + Chip Icon -->
                        <div class="flex items-center gap-1.5">
                            <span class="material-icons text-emerald-300/50 text-xs rotate-90 select-none">wifi</span>
                            <div class="w-5 h-3.5 rounded bg-gradient-to-tr from-amber-300 via-amber-200 to-amber-400 shadow-xs border border-amber-400/60 flex flex-col justify-between p-0.5 opacity-90 select-none">
                                <div class="w-full h-px bg-amber-700/40"></div>
                                <div class="flex justify-between w-full h-0.5 border-y border-amber-700/30">
                                    <div class="w-1 h-full border-r border-amber-700/30"></div>
                                    <div class="w-1 h-full border-l border-amber-700/30"></div>
                                </div>
                                <div class="w-full h-px bg-amber-700/40"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Balance Row: Main Amount + USD Conversion -->
                    <div class="cursor-pointer my-0.5" @click="showMsg('Balance total: ' + formatMoney(stats.balance))">
                        <p class="text-[9px] uppercase font-semibold text-emerald-400/75 tracking-wider mb-0.5">Balance Total</p>
                        <div class="flex items-baseline flex-wrap gap-x-2 gap-y-1">
                            <h2 class="font-black tracking-tight text-white leading-none text-2xl sm:text-[26px] break-all drop-shadow-sm"
                                :class="compactLevel === 1 ? 'text-lg' : 'text-2xl sm:text-[26px]'"
                                x-text="formatMoney(stats.balance)"></h2>
                            <div class="inline-flex items-center gap-1 bg-emerald-500/15 backdrop-blur-md border border-emerald-400/25 px-2 py-0.5 rounded-full text-emerald-300 text-[10px] font-bold">
                                <span class="text-[8px] opacity-70 font-normal">≈</span>
                                <span x-text="exchangeRate > 0 ? '$ ' + (stats.balance / exchangeRate).toFixed(2) : '$ 0.00'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Bar inside Card: Gasto Hoy & Active Account -->
                    <div class="pt-2 mt-2 border-t border-emerald-500/15 flex items-center justify-between text-[10px]">
                        <div class="flex items-center gap-1.5 cursor-pointer hover:opacity-80 transition-opacity" 
                             @click="showMsg('Gasto hoy: ' + formatMoney(stats.today_expense))">
                            <div class="w-3.5 h-3.5 rounded-full bg-rose-500/20 text-rose-300 flex items-center justify-center">
                                <span class="material-icons text-[9px]">trending_down</span>
                            </div>
                            <span class="text-emerald-300/70 font-medium text-[9px]">Gasto Hoy:</span>
                            <span class="font-bold text-rose-200 text-[10px]" x-text="formatMoney(stats.today_expense)"></span>
                        </div>

                        <div class="flex items-center gap-1 text-emerald-300/90 font-medium bg-emerald-950/60 px-2 py-0.5 rounded-md border border-emerald-500/20 text-[9px]">
                            <span class="material-icons text-[11px] text-emerald-400">account_balance</span>
                            <span class="truncate max-w-[100px]" x-text="(accounts.find(a => a.id == selectedAccount)?.name || 'Cuenta')"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content (Cart Layout) -->
        <div class="flex-1 overflow-y-auto px-5 pb-4 space-y-4 no-scrollbar min-h-0 relative z-0">
            
            <!-- Cart Layout (Compact, Streamlined, Highly Ergonomic) -->
            <div x-show="mode === 'cart'" class="space-y-2.5">
                
                <!-- Cart Header Bar -->
                <div class="flex items-center justify-between px-0.5">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center shadow-xs">
                            <span class="material-icons text-sm font-bold">shopping_cart</span>
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5">
                                <h3 class="font-extrabold text-slate-800 text-sm leading-tight">Carrito Actual</h3>
                                <span x-show="cart.length > 0" 
                                      class="text-[9px] font-black bg-emerald-100 text-emerald-800 px-1.5 py-0.2 rounded-full"
                                      x-text="cart.length + (cart.length === 1 ? ' item' : ' items')"></span>
                            </div>
                            <span class="text-[9px] text-slate-400 font-medium leading-none block">Agrega productos con el teclado</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <button type="button" 
                                x-show="cart.length > 0"
                                @click="clearCart()" 
                                title="Vaciar Carrito"
                                class="h-7 px-2 rounded-lg text-[10px] font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 active:scale-95 transition-all flex items-center gap-1 border border-rose-100">
                            <span class="material-icons text-[12px]">delete_sweep</span>
                            <span>Vaciar</span>
                        </button>
                        
                        <button type="button" 
                                @click="toggleMode()" 
                                title="Salir del Carrito"
                                class="h-7 px-2 rounded-lg text-[10px] font-bold text-slate-500 bg-slate-100 hover:bg-slate-200 active:scale-95 transition-all flex items-center gap-0.5">
                            <span class="material-icons text-[14px]">close</span>
                        </button>
                    </div>
                </div>

                <!-- Total Estimado Card (Compact, Modern & at top of list) -->
                <div x-show="cart.length > 0" 
                     class="bg-gradient-to-r from-emerald-900 via-emerald-800 to-teal-900 text-white p-3 rounded-2xl shadow-md border border-emerald-700/40 flex items-center justify-between relative overflow-hidden">
                    <div class="flex flex-col">
                        <span class="text-[10px] font-bold text-emerald-300/90 uppercase tracking-wider flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            Total Estimado
                        </span>
                        <span class="text-[9px] text-emerald-200/80 font-medium" 
                              x-text="cart.reduce((sum, item) => sum + item.quantity, 0) + ' unidades en total'"></span>
                    </div>
                    <div class="text-right">
                        <div class="font-black text-xl font-mono text-white leading-tight" x-text="formatMoney(cartTotal)"></div>
                        <div class="text-[9px] font-semibold text-emerald-300/80" 
                             x-text="'≈ $ ' + (exchangeRate > 0 ? (cartTotal / exchangeRate).toFixed(2) : '0.00')"></div>
                    </div>
                </div>

                <!-- Empty State (When in cart mode with 0 items) -->
                <div x-show="cart.length === 0" 
                     class="py-8 px-4 text-center bg-white/70 rounded-2xl border border-dashed border-slate-200 flex flex-col items-center justify-center">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-2 shadow-xs">
                        <span class="material-icons text-2xl">add_shopping_cart</span>
                    </div>
                    <h4 class="text-xs font-bold text-slate-700">El carrito está vacío</h4>
                    <p class="text-[10px] text-slate-400 mt-0.5 max-w-[200px]">Escribe un monto abajo y presiona el botón '+' para agregar cada artículo.</p>
                    <button type="button" @click="toggleMode()" class="mt-3 text-[10px] font-bold text-emerald-700 bg-emerald-100/70 hover:bg-emerald-200/80 px-3 py-1 rounded-lg transition-colors">
                        Volver a Modo Normal
                    </button>
                </div>

                <!-- Cart Items List (Compact & Ergonomic) -->
                <div x-show="cart.length > 0" class="space-y-1.5 max-h-[260px] overflow-y-auto no-scrollbar pr-0.5">
                    <template x-for="(item, index) in cart" :key="index">
                        <div class="bg-white px-3 py-2 rounded-xl border border-slate-100 shadow-xs flex items-center justify-between gap-2 hover:border-emerald-200 transition-all">
                            
                            <!-- Left: Item Name & Unit Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-5 h-5 rounded-md bg-slate-100 text-slate-600 font-black text-[9px] flex items-center justify-center shrink-0" 
                                          x-text="index + 1"></span>
                                    <p class="font-bold text-slate-800 text-xs truncate" x-text="item.name"></p>
                                </div>
                                <div class="flex items-center gap-1.5 mt-0.5 pl-6">
                                    <span class="text-[9px] font-semibold text-slate-400" 
                                          x-text="item.currency === 'USD' ? ('$' + parseFloat(item.price_usd).toFixed(2) + ' c/u') : (formatMoney(item.price) + ' c/u')"></span>
                                    <span class="text-[8px] font-bold px-1 rounded uppercase tracking-wider"
                                          :class="item.currency === 'USD' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'"
                                          x-text="item.currency"></span>
                                </div>
                            </div>

                            <!-- Center: Tactile Micro Stepper -->
                            <div class="flex items-center bg-slate-50 rounded-lg p-0.5 border border-slate-100 shrink-0">
                                <button type="button" 
                                        @click="item.quantity > 1 ? item.quantity-- : removeItem(index)" 
                                        class="w-6 h-6 rounded bg-white hover:bg-slate-200 active:scale-90 text-slate-600 flex items-center justify-center shadow-2xs transition-all">
                                    <span class="material-icons text-[12px] font-bold">remove</span>
                                </button>
                                <span class="font-black text-xs w-6 text-center text-slate-800 select-none" x-text="item.quantity"></span>
                                <button type="button" 
                                        @click="item.quantity++" 
                                        class="w-6 h-6 rounded bg-emerald-600 hover:bg-emerald-700 active:scale-90 text-white flex items-center justify-center shadow-xs transition-all">
                                    <span class="material-icons text-[12px] font-bold">add</span>
                                </button>
                            </div>

                            <!-- Right: Line Total & Delete Button -->
                            <div class="flex items-center gap-2 shrink-0">
                                <div class="text-right">
                                    <p class="font-black text-slate-900 text-xs font-mono" 
                                       x-text="item.currency === 'USD' ? ('$' + (item.price_usd * item.quantity).toFixed(2)) : formatMoney(item.price * item.quantity)"></p>
                                    <span x-show="item.currency === 'USD'" class="text-[8px] font-semibold text-emerald-600 block leading-tight" 
                                          x-text="'≈ ' + formatMoney(item.price * item.quantity)"></span>
                                </div>
                                <button type="button" 
                                        @click="removeItem(index)" 
                                        title="Eliminar producto"
                                        class="w-6 h-6 rounded-lg text-slate-300 hover:text-rose-500 hover:bg-rose-50 flex items-center justify-center transition-colors active:scale-90">
                                    <span class="material-icons text-[15px]">delete_outline</span>
                                </button>
                            </div>

                        </div>
                    </template>
                </div>

            </div>

            <!-- Single Mode: Categorías Rápidas & Actividad Viva -->
            <div x-show="mode === 'single'" class="space-y-3 pt-0.5">
                
                <!-- Quick Accounts Carousel (Acceso Directo Cuentas) -->
                <div>
                    <div class="flex items-center justify-between mb-1.5 px-0.5">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                            <span class="material-icons text-xs text-emerald-600">account_balance_wallet</span>
                            Cuentas (Acceso Directo)
                        </span>
                        <a href="<?= base_url('accounts') ?>" class="text-[9px] font-bold text-emerald-600 hover:text-emerald-700 flex items-center gap-0.5 transition-colors">
                            Gestionar
                            <span class="material-icons text-[10px]">tune</span>
                        </a>
                    </div>
                    
                    <div class="flex items-center gap-2 overflow-x-auto no-scrollbar py-1 -mx-1 px-1 snap-x">
                        <template x-for="acc in accounts" :key="acc.id">
                            <button type="button" 
                                    @click="selectedAccount = acc.id"
                                    class="flex items-center gap-2 px-3 py-2 rounded-2xl text-xs font-bold whitespace-nowrap transition-all duration-200 shrink-0 select-none active:scale-95 border snap-start"
                                    :class="selectedAccount == acc.id 
                                        ? 'bg-gradient-to-br from-[#021810] via-[#052e1f] to-[#01140e] text-white border-emerald-500/50 shadow-md shadow-emerald-950/20 ring-2 ring-emerald-400/40 scale-[1.02]' 
                                        : 'bg-white hover:bg-slate-50 text-slate-700 border-slate-200/90 shadow-xs'">
                                <div class="w-6 h-6 rounded-xl flex items-center justify-center text-xs shrink-0"
                                     :class="selectedAccount == acc.id ? 'bg-emerald-500/20 text-emerald-300' : 'bg-slate-100 text-slate-500'">
                                    <span class="material-icons text-[14px]">account_balance</span>
                                </div>
                                <div class="flex flex-col text-left leading-tight">
                                    <span class="font-bold text-[11px]" :class="selectedAccount == acc.id ? 'text-white' : 'text-slate-800'" x-text="acc.name"></span>
                                    <span class="text-[9px]" :class="selectedAccount == acc.id ? 'text-emerald-300 font-bold' : 'text-emerald-700 font-semibold'" x-text="formatMoney(acc.balance, acc.currency)"></span>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Actividad Viva: Recent Transactions Feed -->
                <div>
                    <div class="flex items-center justify-between mb-2 px-0.5">
                        <div class="flex items-center gap-1.5">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            <h3 class="text-xs font-bold text-slate-700 tracking-tight">Últimos Movimientos</h3>
                        </div>
                        <a href="<?= base_url('history') ?>" class="text-[10px] font-bold text-emerald-600 hover:text-emerald-700 flex items-center gap-0.5 transition-colors">
                            Ver todo
                            <span class="material-icons text-xs">arrow_forward</span>
                        </a>
                    </div>

                    <!-- Items Feed List -->
                    <div class="space-y-2">
                        <template x-for="item in (stats.recent || [])" :key="item.id">
                            <article class="bg-white p-2.5 rounded-2xl border border-slate-200/80 shadow-sm grid grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-2.5 hover:border-emerald-300 hover:shadow-md transition-all group">
                                <div class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 relative ring-1 ring-inset"
                                     :class="{
                                         'bg-emerald-50 text-emerald-700 ring-emerald-100': item.type === 'income' || item.type === 'return',
                                         'bg-rose-50 text-rose-700 ring-rose-100': item.type === 'expense',
                                         'bg-blue-50 text-blue-700 ring-blue-100': item.type === 'savings' || ['exchange_out','exchange_in','transfer_out','transfer_in','currency_reversal_in','currency_reversal_out'].includes(item.type),
                                         'bg-amber-50 text-amber-700 ring-amber-100': item.type === 'invoice' || item.ocr_merchant,
                                         'bg-slate-100 text-slate-500 ring-slate-200': !['income','expense','savings','exchange_out','exchange_in','transfer_out','transfer_in','currency_reversal_in','currency_reversal_out','return','invoice'].includes(item.type) && !item.ocr_merchant
                                     }">
                                    <span class="material-icons text-lg"
                                          x-text="item.ocr_merchant ? 'receipt' : getCategoryIcon(item.category_name, item.category_icon)"></span>
                                    <span x-show="item.ocr_merchant"
                                          class="absolute -top-0.5 -right-0.5 w-3 h-3 bg-amber-400 border-2 border-white rounded-full"></span>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-1.5 mb-0.5 min-w-0">
                                        <span class="text-[8px] leading-none font-black uppercase tracking-wider px-1.5 py-1 rounded-md shrink-0"
                                              :class="{
                                                  'bg-emerald-100 text-emerald-800': item.type === 'income' || item.type === 'return',
                                                  'bg-rose-100 text-rose-800': item.type === 'expense',
                                                  'bg-blue-100 text-blue-800': item.type === 'savings' || item.type?.includes('exchange') || item.type?.includes('transfer'),
                                                  'bg-slate-100 text-slate-600': !['income','return','expense','savings'].includes(item.type) && !item.type?.includes('exchange') && !item.type?.includes('transfer')
                                              }"
                                              x-text="movementTypeLabel(item)"></span>
                                        <span class="text-[9px] font-bold text-slate-400 truncate" x-text="item.category_name || 'General'"></span>
                                        <span x-show="item.ocr_invoice_number" class="text-[8px] font-black text-amber-700 bg-amber-50 px-1 py-0.5 rounded shrink-0" x-text="'#' + item.ocr_invoice_number"></span>
                                    </div>
                                    <p class="font-extrabold text-slate-800 text-[11px] sm:text-xs leading-tight line-clamp-2"
                                       x-text="item.description || item.ocr_merchant || item.category_name || 'Transacción'"></p>
                                    <div class="flex items-center gap-1 mt-1 text-[9px] font-semibold text-slate-400 min-w-0">
                                        <span class="material-icons text-[11px]">account_balance_wallet</span>
                                        <span class="truncate max-w-[95px]" x-text="item.account_name || 'Cuenta'"></span>
                                        <span class="text-slate-300">•</span>
                                        <span class="shrink-0" x-text="formatTime(item.created_at)"></span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-1.5 shrink-0">
                                    <div class="text-right max-w-[105px]">
                                        <p class="font-black text-[11px] sm:text-xs tracking-tight whitespace-nowrap"
                                           :class="movementAmountClass(item)"
                                           x-text="movementPrefix(item) + formatMoney(item.display_amount_bs ?? item.amount)"></p>
                                        <p class="text-[9px] text-slate-400 font-bold whitespace-nowrap"
                                           x-show="(item.display_amount_usd ?? item.amount_usd) > 0"
                                           x-text="'$ ' + parseFloat(item.display_amount_usd ?? item.amount_usd ?? 0).toFixed(2)"></p>
                                    </div>
                                    <a :href="'<?= base_url('history') ?>?transaction_id=' + item.id"
                                       class="w-8 h-8 rounded-xl bg-slate-50 border border-slate-200 text-slate-500 hover:bg-emerald-600 hover:text-white hover:border-emerald-600 flex items-center justify-center transition-all active:scale-90"
                                       :aria-label="'Ir al movimiento ' + item.id" title="Ver este movimiento en el historial">
                                        <span class="material-icons text-base">arrow_forward</span>
                                    </a>
                                </div>
                            </article>
                        </template>

                        <!-- Empty state -->
                        <div x-show="!stats.recent || stats.recent.length === 0" class="p-6 text-center bg-white/70 rounded-2xl border border-dashed border-slate-200">
                            <span class="material-icons text-slate-300 text-3xl mb-1">receipt_long</span>
                            <p class="text-xs font-semibold text-slate-400">Sin movimientos registrados</p>
                            <p class="text-[10px] text-slate-400 mt-0.5">Ingresa un monto para empezar</p>
                        </div>
                    </div>

                </div>

            </div>
            
        </div>

        <!-- Input Pad Area (Bottom Sheet) -->
        <div class="flex-none bg-white rounded-t-[2rem] shadow-[0_-10px_40px_-15px_rgba(0,0,0,0.1)] p-0 z-40 transition-all duration-300 safe-bottom"> 
            
            <div class="w-full flex justify-center pt-2 pb-1">
                <div class="w-10 h-1 bg-slate-200 rounded-full"></div>
            </div>

            <div class="px-3.5 pb-2 pt-0" :class="compactLevel > 0 ? 'p-1.5' : 'px-3.5 pb-2 pt-0'">
                <!-- Unified Note, Amount and Type Panel -->
                <div class="mb-1.5 relative rounded-xl px-3 py-2 border transition-all duration-200 shadow-inner select-none cursor-grab active:cursor-grabbing touch-pan-y"
                     :class="{
                         'bg-red-950/[0.03] border-red-300/80 shadow-red-950/5': type === 'expense',
                         'bg-emerald-950/[0.03] border-emerald-300/80 shadow-emerald-950/5': type === 'income',
                         'bg-blue-950/[0.03] border-blue-300/80 shadow-blue-950/5': type === 'savings'
                     }"
                     @touchstart.passive="handleSwipeStart($event)"
                     @touchend.passive="handleSwipeEnd($event)"
                     @mousedown="handleMouseDown($event)"
                     @mouseup="handleMouseUp($event)">
                     
                     <div class="flex items-center justify-between gap-2 min-h-8">
                        <!-- Note Input -->
                        <div class="flex items-center gap-1.5 flex-1 min-w-0">
                            <span class="material-icons text-slate-400 text-base">edit_note</span>
                            <input type="text" x-model="description" placeholder="Añadir nota o descripción..."
                                   class="w-full text-xs font-semibold text-slate-700 placeholder-slate-400 outline-none bg-transparent cursor-text select-text">
                        </div>
                        
                        <!-- Currency Toggle & Main Amount with Dynamic Type Indicator -->
                        <div class="flex items-baseline gap-1 shrink-0">
                            <!-- Currency Toggle Pill -->
                            <button type="button" @click="currency = (currency === 'Bs' ? 'USD' : 'Bs')"
                                    class="px-1.5 py-0.5 rounded text-[9px] font-extrabold uppercase tracking-wider transition-all border select-none"
                                    :class="currency === 'USD' ? 'bg-emerald-700 text-white border-emerald-600 shadow-xs' : 'bg-white text-emerald-900 border-emerald-300 shadow-xs'"
                                    x-text="currency"></button>

                            <!-- Amount with Dynamic +/-/★ prefix -->
                            <div class="flex items-baseline font-mono leading-none">
                                <span class="font-black text-lg mr-0.5 select-none transition-all duration-200"
                                      :class="{
                                          'text-red-700': type === 'expense',
                                          'text-emerald-800': type === 'income',
                                          'text-blue-700': type === 'savings'
                                      }"
                                      x-text="type === 'expense' ? '-' : (type === 'income' ? '+' : '★')"></span>
                                <div class="font-black text-slate-900 tracking-tight font-mono leading-none transition-all duration-200"
                                     :class="{
                                         'text-2xl': compactLevel === 0,
                                         'text-xl': compactLevel === 1,
                                         'text-lg': compactLevel === 2
                                     }"
                                     x-text="display"></div>
                            </div>
                        </div>
                     </div>
                     <!-- Conversion Sub-line -->
                     <div x-show="parseFloat(amount) > 0" class="flex justify-end">
                         <span class="text-[9px] font-bold text-emerald-700 bg-emerald-100/60 px-1.5 py-0.2 rounded" 
                               x-text="'≈ ' + (currency === 'Bs' ? '$ ' : 'Bs ') + formatMoney(currentConversion)"></span>
                     </div>

                </div>

                <!-- Attributes (Account + Category) & Action Tools -->
                <div class="relative flex items-center gap-1 mb-1 h-8"
                     @click.outside="showAccountMenu = false; showCategoryMenu = false"
                     @keydown.escape.window="showAccountMenu = false; showCategoryMenu = false">

                    <!-- Account Dropdown Floating Popover (High Contrast, Full Detail, Never Cut Off) -->
                    <div x-show="showAccountMenu" 
                         x-transition:enter="transition ease-out duration-150 transform"
                         x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100 transform"
                         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                         x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                         class="absolute bottom-full left-0 right-0 mb-2 z-[90] bg-white/98 backdrop-blur-md rounded-2xl p-2.5 shadow-2xl border border-emerald-100/90 ring-1 ring-black/10 flex flex-col gap-1.5"
                         x-cloak>
                        
                        <!-- Popover Header -->
                        <div class="flex items-center justify-between px-1 pb-1 border-b border-slate-100">
                            <div class="flex items-center gap-1.5">
                                <span class="material-icons text-emerald-600 text-xs">account_balance_wallet</span>
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500">Seleccionar Cuenta</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-[9px] font-bold text-slate-400" x-text="accounts.length + ' cuentas'"></span>
                                <button type="button" @click="showAccountMenu = false" class="text-slate-400 hover:text-slate-600 p-0.5 rounded-full hover:bg-slate-100">
                                    <span class="material-icons text-xs">close</span>
                                </button>
                            </div>
                        </div>

                        <!-- Accounts List (Spacious, No Line Break, Full Balances) -->
                        <div class="space-y-1 max-h-56 overflow-y-auto no-scrollbar pt-0.5 pr-0.5">
                            <template x-for="acc in accounts" :key="acc.id">
                                <div @click="selectedAccount = acc.id; showAccountMenu = false" 
                                     class="p-2 rounded-xl border flex items-center justify-between gap-2 transition-all cursor-pointer select-none active:scale-[0.98] group"
                                     :class="selectedAccount == acc.id 
                                         ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-950 ring-1 ring-emerald-500/20 shadow-xs' 
                                         : 'bg-white hover:bg-slate-50 border-slate-100 text-slate-700 hover:border-slate-200'">
                                    
                                    <!-- Left: Icon & Account Name -->
                                    <div class="flex items-center gap-2 min-w-0 flex-1">
                                        <div class="w-7 h-7 rounded-lg flex items-center justify-center text-xs shrink-0"
                                             :class="selectedAccount == acc.id ? 'bg-emerald-700 text-white shadow-xs' : 'bg-slate-100 text-slate-600 group-hover:bg-slate-200'">
                                            <span class="material-icons text-[14px]" x-text="acc.type === 'cash' ? 'payments' : (acc.type === 'bank' ? 'account_balance' : 'account_balance_wallet')"></span>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-1.5">
                                                <span class="font-bold text-xs truncate" :class="selectedAccount == acc.id ? 'text-emerald-950 font-black' : 'text-slate-800'" x-text="acc.name"></span>
                                                <span class="text-[8px] font-extrabold px-1 py-0.2 rounded uppercase tracking-wider shrink-0"
                                                      :class="acc.currency === 'USD' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'"
                                                      x-text="acc.currency"></span>
                                            </div>
                                            <span class="text-[9px] text-slate-400 block truncate" x-text="acc.type === 'cash' ? 'Efectivo disponible' : (acc.type === 'bank' ? 'Cuenta bancaria' : 'Cuenta de fondos')"></span>
                                        </div>
                                    </div>

                                    <!-- Right: Balance (Full & Never Truncated) + Checkmark -->
                                    <div class="flex items-center gap-2 shrink-0 pl-1">
                                        <span class="font-mono font-black text-xs whitespace-nowrap"
                                              :class="selectedAccount == acc.id ? 'text-emerald-700' : 'text-slate-700'"
                                              x-text="formatMoney(acc.balance, acc.currency)"></span>
                                        <span class="material-icons text-sm text-emerald-600 shrink-0" x-show="selectedAccount == acc.id">check_circle</span>
                                        <span class="w-3.5" x-show="selectedAccount != acc.id"></span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Footer -->
                        <div class="pt-1 mt-0.5 border-t border-slate-100 flex items-center justify-between text-[10px] px-1">
                            <a href="<?= base_url('accounts') ?>" class="text-emerald-700 hover:text-emerald-800 font-bold flex items-center gap-1 transition-colors">
                                <span class="material-icons text-[12px]">tune</span>
                                <span>Gestionar cuentas</span>
                            </a>
                            <span class="text-[8px] text-slate-400">Esc para cerrar</span>
                        </div>

                        <!-- Pointer triangle aligned directly to Account button center -->
                        <div class="absolute -bottom-1.5 left-[20%] -translate-x-1/2 w-3 h-3 bg-white rotate-45 border-r border-b border-emerald-100 shadow-xs"></div>
                    </div>

                    <!-- Category Dropdown Floating Popover (Searchable, Visual, Fast) -->
                    <div x-show="showCategoryMenu" 
                         x-transition:enter="transition ease-out duration-150 transform"
                         x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100 transform"
                         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                         x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                         class="absolute bottom-full left-0 right-0 mb-2 z-[90] bg-white/98 backdrop-blur-md rounded-2xl p-2.5 shadow-2xl border border-slate-200/90 ring-1 ring-black/10 flex flex-col gap-1.5"
                         x-cloak>
                        
                        <!-- Popover Header & Search -->
                        <div class="flex items-center justify-between pb-1 border-b border-slate-100 gap-2">
                            <div class="flex items-center gap-1.5 flex-1 relative">
                                <span class="material-icons text-slate-400 text-xs absolute left-2 pointer-events-none">search</span>
                                <input type="text" x-model="categorySearch" placeholder="Buscar categoría..." 
                                       class="w-full pl-6 pr-2 py-1 bg-slate-50 hover:bg-slate-100/80 focus:bg-white border border-slate-200 rounded-lg text-[11px] font-bold text-slate-700 outline-none focus:border-emerald-500 transition-all">
                                <button type="button" x-show="categorySearch" @click="categorySearch = ''" class="absolute right-1.5 text-slate-400 hover:text-slate-600">
                                    <span class="material-icons text-[11px]">cancel</span>
                                </button>
                            </div>
                            <button type="button" @click="showCategoryMenu = false; categorySearch = ''" class="text-slate-400 hover:text-slate-600 p-0.5 rounded-full hover:bg-slate-100 shrink-0">
                                <span class="material-icons text-xs">close</span>
                            </button>
                        </div>

                        <!-- Categories Grid (Visual, 2-Columns, Fast Tapping) -->
                        <div class="grid grid-cols-2 gap-1.5 max-h-52 overflow-y-auto no-scrollbar pt-0.5 pr-0.5">
                            <template x-for="cat in filteredCategories" :key="cat.id">
                                <button type="button" 
                                        @click="selectedCategory = cat.id; showCategoryMenu = false; categorySearch = ''" 
                                        class="p-2 rounded-xl border flex items-center justify-between gap-1.5 transition-all cursor-pointer select-none active:scale-95 text-left group"
                                        :class="selectedCategory == cat.id 
                                            ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-950 ring-1 ring-emerald-500/20 shadow-xs font-black' 
                                            : 'bg-white hover:bg-slate-50 border-slate-100 text-slate-700 hover:border-slate-200 font-bold'">
                                    
                                    <div class="flex items-center gap-1.5 min-w-0 flex-1">
                                        <span class="w-6 h-6 rounded-lg flex items-center justify-center text-xs shrink-0"
                                              :class="selectedCategory == cat.id ? 'bg-emerald-700 text-white shadow-xs' : 'bg-slate-100 text-slate-500 group-hover:bg-slate-200'">
                                            <span class="material-icons text-[13px]" x-text="getCategoryIcon(cat.name, cat.icon)"></span>
                                        </span>
                                        <span class="text-[11px] truncate" x-text="cat.name"></span>
                                    </div>
                                    <span class="material-icons text-xs text-emerald-600 shrink-0" x-show="selectedCategory == cat.id">check</span>
                                </button>
                            </template>

                            <!-- Empty search state -->
                            <div x-show="filteredCategories.length === 0" class="col-span-2 py-4 text-center text-slate-400 text-xs">
                                No se encontraron categorías para "<span x-text="categorySearch" class="font-bold"></span>"
                            </div>
                        </div>

                        <!-- Pointer triangle aligned directly to Category button center -->
                        <div class="absolute -bottom-1.5 left-[52%] -translate-x-1/2 w-3 h-3 bg-white rotate-45 border-r border-b border-slate-200 shadow-xs"></div>
                    </div>

                    <!-- Account Select Button -->
                    <button type="button" 
                            @click="showAccountMenu = !showAccountMenu; showCategoryMenu = false; showQuickOwnerPicker = false" 
                            class="flex-1 min-w-0 h-full px-2 flex items-center justify-between rounded-xl transition-all shadow-xs select-none active:scale-95 border"
                            :class="showAccountMenu 
                                ? 'bg-emerald-100 border-emerald-400 text-emerald-950 ring-2 ring-emerald-500/30 font-black' 
                                : 'bg-emerald-50/80 hover:bg-emerald-100/70 border-emerald-200/90 text-emerald-950 font-bold'">
                        <div class="flex items-center gap-1.5 min-w-0">
                             <span class="material-icons text-emerald-700 text-sm">account_balance_wallet</span>
                             <span class="text-xs truncate font-bold" x-text="accounts.find(a => a.id == selectedAccount)?.name || 'Cuenta'"></span>
                        </div>
                        <span class="material-icons text-emerald-700 text-xs transition-transform duration-200" :class="{'rotate-180': showAccountMenu}">expand_more</span>
                    </button>

                    <!-- Category Select Button -->
                    <button type="button" 
                            @click="showCategoryMenu = !showCategoryMenu; showAccountMenu = false; showQuickOwnerPicker = false" 
                            class="flex-1 min-w-0 h-full px-2 flex items-center justify-between rounded-xl transition-all shadow-xs select-none active:scale-95 border"
                            :class="showCategoryMenu 
                                ? 'bg-slate-200 border-slate-400 text-slate-900 ring-2 ring-slate-400/30 font-black' 
                                : 'bg-slate-50 hover:bg-slate-100/90 border-slate-200 text-slate-800 font-bold'">
                        <div class="flex items-center gap-1.5 min-w-0">
                             <span class="material-icons text-slate-500 text-sm" x-text="getCategoryIcon(categories.find(c => c.id == selectedCategory)?.name, categories.find(c => c.id == selectedCategory)?.icon)"></span>
                             <span class="text-xs truncate font-bold" x-text="categories.find(c => c.id == selectedCategory)?.name || 'Categoría'"></span>
                        </div>
                        <span class="material-icons text-slate-400 text-xs transition-transform duration-200" :class="{'rotate-180': showCategoryMenu}">expand_more</span>
                    </button>

                    <!-- Transaction type icons beside the selectors -->
                    <div class="h-full flex items-center gap-0.5 p-0.5 bg-white rounded-xl border border-slate-200 shadow-xs shrink-0">
                        <button type="button" @click="type = 'expense'" title="Egreso" aria-label="Seleccionar egreso" :aria-pressed="type === 'expense'"
                                class="w-[22px] h-full rounded-lg flex items-center justify-center transition-all active:scale-90"
                                :class="type === 'expense' ? 'bg-gradient-to-br from-red-800 to-rose-950 text-white shadow-sm' : 'text-red-700 hover:bg-red-50'">
                            <span class="material-icons text-[14px]">trending_down</span>
                        </button>
                        <button type="button" @click="type = 'income'" title="Ingreso" aria-label="Seleccionar ingreso" :aria-pressed="type === 'income'"
                                class="w-[22px] h-full rounded-lg flex items-center justify-center transition-all active:scale-90"
                                :class="type === 'income' ? 'bg-gradient-to-br from-emerald-700 to-teal-950 text-white shadow-sm' : 'text-emerald-700 hover:bg-emerald-50'">
                            <span class="material-icons text-[14px]">trending_up</span>
                        </button>
                        <button type="button" @click="type = 'savings'" title="Ahorro" aria-label="Seleccionar ahorro" :aria-pressed="type === 'savings'"
                                class="w-[22px] h-full rounded-lg flex items-center justify-center transition-all active:scale-90"
                                :class="type === 'savings' ? 'bg-gradient-to-br from-blue-700 to-indigo-950 text-white shadow-sm' : 'text-blue-700 hover:bg-blue-50'">
                            <span class="material-icons text-[14px]">savings</span>
                        </button>
                    </div>

                    <!-- Owner selector as a compact icon -->
                    <button type="button"
                            @click="showQuickOwnerPicker = !showQuickOwnerPicker; showAccountMenu = false; showCategoryMenu = false"
                            :title="owner ? ('Dueño: ' + owner) : 'Seleccionar dueño'"
                            :aria-label="owner ? ('Dueño seleccionado: ' + owner) : 'Seleccionar dueño'"
                            class="relative w-8 h-full rounded-xl border flex items-center justify-center shrink-0 shadow-xs transition-all active:scale-95"
                            :class="owner
                                ? (owner === 'Arianny' ? 'bg-pink-50 text-pink-700 border-pink-300' : (owner === 'Anthony' ? 'bg-emerald-50 text-emerald-800 border-emerald-300' : 'bg-purple-50 text-purple-700 border-purple-300'))
                                : 'bg-slate-50 text-slate-500 border-slate-200 hover:bg-slate-100'">
                        <span class="material-icons text-[15px]" x-text="owner === 'Negocio' ? 'business' : 'person'"></span>
                        <span x-show="owner" class="absolute top-1 right-1 w-1.5 h-1.5 rounded-full border border-white"
                              :class="owner === 'Arianny' ? 'bg-pink-500' : (owner === 'Anthony' ? 'bg-emerald-600' : 'bg-purple-600')"></span>
                    </button>

                    <!-- Prominent compact cart access -->
                    <button type="button"
                            @click="toggleMode(); showAccountMenu = false; showCategoryMenu = false"
                            title="Abrir modo carrito"
                            class="relative w-9 h-full rounded-xl flex items-center justify-center border text-white shadow-md shrink-0 active:scale-95 transition-all"
                            :class="mode === 'cart'
                                ? 'bg-gradient-to-br from-emerald-800 to-teal-900 border-emerald-700 ring-2 ring-emerald-300/70 shadow-emerald-900/30'
                                : 'bg-gradient-to-br from-emerald-500 to-teal-700 border-emerald-500 hover:brightness-105 shadow-emerald-600/25'">
                        <span class="material-icons text-[17px]" x-text="mode === 'cart' ? 'shopping_cart' : 'add_shopping_cart'"></span>
                        <span x-show="cart.length > 0"
                              class="absolute -top-1.5 -right-1.5 min-w-4 h-4 px-1 rounded-full bg-amber-400 text-amber-950 border-2 border-white text-[8px] font-black flex items-center justify-center leading-none"
                              x-text="cart.length"></span>
                    </button>

                    <!-- Date Timer Toggle -->
                    <div class="relative w-8 h-full shrink-0">
                         <div :class="customDate ? 'bg-emerald-100 text-emerald-700 animate-pulse' : 'bg-slate-50 text-slate-400'" 
                              class="w-full h-full rounded-xl flex items-center justify-center border border-slate-200 shadow-xs transition-all relative">
                             <span class="material-icons text-[14px]" x-text="customDate ? 'timer' : 'calendar_today'"></span>
                             <div x-show="customDate" class="absolute -top-0.5 -right-0.5 w-1.5 h-1.5 bg-rose-500 rounded-full"></div>
                         </div>
                         <input type="datetime-local" @change="startTimer($event.target.value)" class="absolute inset-0 opacity-0 z-10 cursor-pointer w-full h-full">
                    </div>

                </div>

                <!-- NEGOCIO: Purchase Mode Drawer (Conditional) -->
                <div x-show="owner === 'Negocio'" x-transition class="mb-1.5 bg-purple-50/80 backdrop-blur rounded-xl p-2.5 border border-purple-100 shadow-xs">
                    <div class="flex justify-between items-center mb-1.5">
                        <span class="text-[9px] font-bold text-purple-800 uppercase tracking-wider flex items-center gap-1">
                            <span class="material-icons text-xs">inventory_2</span>
                            Compra de Inventario
                        </span>
                        <div class="flex items-center gap-2">
                            <span x-show="enablePurchase" class="text-[8px] text-purple-400">Total: <b x-text="formatMoney(purchaseQuantity * purchaseCost)"></b></span>
                            <button @click="togglePurchaseMode()" 
                                     class="text-[8px] font-bold px-2.5 py-0.5 rounded-full transition-all shadow-xs active:scale-95"
                                     :class="enablePurchase ? 'bg-purple-600 text-white shadow-purple-200' : 'bg-white text-purple-400 border border-purple-200'">
                                <span x-text="enablePurchase ? 'Activo' : 'Activar'"></span>
                            </button>
                        </div>
                    </div>

                    <div x-show="enablePurchase" class="space-y-1.5">
                        <div class="relative" @click.outside="showInvMenu = false">
                            <div class="flex items-center gap-1.5 bg-white border border-purple-200 rounded-lg p-1.5 shadow-xs focus-within:border-purple-400 transition-colors" @click="showInvMenu = !showInvMenu">
                                <span class="material-icons text-slate-400 text-xs">search</span>
                                <input type="text" x-model="invSearch" @keydown.stop placeholder="Buscar producto..." 
                                       class="w-full text-[10px] font-bold text-slate-700 outline-none placeholder-slate-300">
                                <button x-show="purchaseItem" @click.stop="purchaseItem = null; invSearch = ''; purchaseCost = 0" class="text-rose-400 hover:text-rose-600">
                                    <span class="material-icons text-xs">close</span>
                                </button>
                            </div>
                            
                            <div x-show="showInvMenu && (invSearch.length > 0 || filteredInventory.length > 0)" 
                                 class="absolute bottom-full left-0 w-full mb-1 bg-white rounded-xl shadow-xl border border-purple-100 p-1 z-[90] max-h-36 overflow-y-auto">
                                <template x-for="item in filteredInventory" :key="item.id">
                                    <div @click="selectInvItem(item)" class="p-1.5 hover:bg-purple-50 rounded flex justify-between items-center cursor-pointer group border-b border-slate-50 last:border-0">
                                        <div>
                                             <p class="text-[10px] font-bold text-slate-700 group-hover:text-purple-700" x-text="item.name"></p>
                                             <p class="text-[8px] text-slate-400" x-text="'Stock: ' + item.stock + ' ' + item.unit"></p>
                                        </div>
                                        <div class="text-right">
                                             <span class="text-[9px] font-bold text-slate-600 block" x-text="'$' + parseFloat(item.cost).toFixed(2)"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="flex gap-1.5" x-show="purchaseItem">
                            <div class="flex-1 bg-white border border-purple-200 rounded-lg p-1 flex flex-col items-center relative overflow-hidden">
                                <span class="text-[7px] font-bold text-purple-300 uppercase absolute top-0.5 left-1.5">Cantidad</span>
                                <input type="number" x-model="purchaseQuantity" @input="calcTotal()" @keydown.stop class="w-full text-xs font-black text-slate-700 outline-none text-center pt-2.5 pb-0.5">
                            </div>
                            <div class="flex-1 bg-white border border-purple-200 rounded-lg p-1 flex flex-col items-center relative overflow-hidden">
                                <span class="text-[7px] font-bold text-purple-300 uppercase absolute top-0.5 left-1.5">Costo ($)</span>
                                <input type="number" x-model="purchaseCost" @input="calcTotal()" @keydown.stop class="w-full text-xs font-black text-slate-700 outline-none text-center pt-2.5 pb-0.5">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ergonomic Compact Tactile Keypad (Directly at the bottom) -->
                <div class="grid grid-cols-4 gap-1.5 h-auto relative">
                    <!-- Quick Owner Picker Floating Popover (Fast & Non-invasive) -->
                    <div x-show="showQuickOwnerPicker" 
                         x-transition:enter="transition ease-out duration-150 transform"
                         x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100 transform"
                         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                         x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                         @click.outside="showQuickOwnerPicker = false"
                         class="absolute bottom-20 right-0 z-50 w-64 bg-white/95 backdrop-blur-md rounded-2xl p-2.5 shadow-2xl border border-emerald-100/90 ring-1 ring-black/10 flex flex-col gap-1.5"
                         x-cloak>
                        
                        <!-- Header -->
                        <div class="flex items-center justify-between px-1 pb-1 border-b border-slate-100">
                            <div class="flex items-center gap-1.5">
                                <span class="material-icons text-emerald-600 text-xs">how_to_reg</span>
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500">¿Quién registra?</span>
                            </div>
                            <button type="button" @click="showQuickOwnerPicker = false" class="text-slate-400 hover:text-slate-600 p-0.5 rounded-full hover:bg-slate-100">
                                <span class="material-icons text-xs">close</span>
                            </button>
                        </div>

                        <!-- 3 Quick Options -->
                        <div class="space-y-1.5 pt-0.5">
                            <!-- Arianny -->
                            <button type="button" @click="selectOwnerAndSubmit('Arianny')" 
                                    class="w-full h-9 px-2.5 rounded-xl bg-pink-50/90 hover:bg-pink-100 active:scale-98 border border-pink-200/70 flex items-center justify-between transition-all group">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-full bg-pink-500 text-white font-black text-[10px] flex items-center justify-center shadow-xs">Ar</span>
                                    <span class="text-xs font-bold text-pink-900 group-hover:translate-x-0.5 transition-transform">Arianny</span>
                                </div>
                                <span class="text-[9px] font-extrabold text-pink-600 bg-pink-100/80 px-1.5 py-0.5 rounded flex items-center gap-0.5">
                                    Guardar <span class="material-icons text-[10px]">arrow_forward</span>
                                </span>
                            </button>

                            <!-- Anthony -->
                            <button type="button" @click="selectOwnerAndSubmit('Anthony')" 
                                    class="w-full h-9 px-2.5 rounded-xl bg-emerald-50/90 hover:bg-emerald-100 active:scale-98 border border-emerald-200/70 flex items-center justify-between transition-all group">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-full bg-emerald-700 text-white font-black text-[10px] flex items-center justify-center shadow-xs">An</span>
                                    <span class="text-xs font-bold text-emerald-950 group-hover:translate-x-0.5 transition-transform">Anthony</span>
                                </div>
                                <span class="text-[9px] font-extrabold text-emerald-700 bg-emerald-100/80 px-1.5 py-0.5 rounded flex items-center gap-0.5">
                                    Guardar <span class="material-icons text-[10px]">arrow_forward</span>
                                </span>
                            </button>

                            <!-- Negocio -->
                            <button type="button" @click="selectOwnerAndSubmit('Negocio')" 
                                    class="w-full h-9 px-2.5 rounded-xl bg-purple-50/90 hover:bg-purple-100 active:scale-98 border border-purple-200/70 flex items-center justify-between transition-all group">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-full bg-purple-600 text-white flex items-center justify-center shadow-xs">
                                        <span class="material-icons text-[12px]">store</span>
                                    </span>
                                    <div class="text-left">
                                        <span class="text-xs font-bold text-purple-900 leading-none block">Negocio</span>
                                    </div>
                                </div>
                                <span class="text-[9px] font-extrabold text-purple-700 bg-purple-100/80 px-1.5 py-0.5 rounded flex items-center gap-0.5">
                                    Guardar <span class="material-icons text-[10px]">arrow_forward</span>
                                </span>
                            </button>
                        </div>

                        <!-- Footer: Option to remember -->
                        <div class="pt-1 mt-0.5 border-t border-slate-100 flex items-center justify-between text-[9px] text-slate-400 px-1">
                            <label class="flex items-center gap-1.5 cursor-pointer select-none text-slate-500 hover:text-slate-700">
                                <input type="checkbox" x-model="rememberOwner" class="rounded text-emerald-600 focus:ring-0 w-3 h-3">
                                <span>Recordar selección</span>
                            </label>
                            <span class="text-[8px] text-slate-300">Esc para cerrar</span>
                        </div>

                        <!-- Pointer triangle aligned to big button -->
                        <div class="absolute -bottom-1.5 right-6 w-3 h-3 bg-white rotate-45 border-r border-b border-emerald-100 shadow-xs"></div>
                    </div>

                    <button @click="press(7)" class="bg-slate-50 hover:bg-slate-100 active:bg-slate-200 rounded-xl text-slate-800 font-bold border border-slate-100/80 shadow-xs active:scale-95 transition-all" 
                            :class="compactLevel === 0 ? 'h-9 sm:h-10 text-base' : 'h-8 text-xs'">7</button>
                    <button @click="press(8)" class="bg-slate-50 hover:bg-slate-100 active:bg-slate-200 rounded-xl text-slate-800 font-bold border border-slate-100/80 shadow-xs active:scale-95 transition-all" 
                            :class="compactLevel === 0 ? 'h-9 sm:h-10 text-base' : 'h-8 text-xs'">8</button>
                    <button @click="press(9)" class="bg-slate-50 hover:bg-slate-100 active:bg-slate-200 rounded-xl text-slate-800 font-bold border border-slate-100/80 shadow-xs active:scale-95 transition-all" 
                            :class="compactLevel === 0 ? 'h-9 sm:h-10 text-base' : 'h-8 text-xs'">9</button>
                    <button @click="pressOperation('+')" class="bg-emerald-50 hover:bg-emerald-100 active:bg-emerald-200 rounded-xl text-emerald-700 font-black border border-emerald-100 shadow-xs active:scale-95 transition-all" 
                            :class="compactLevel === 0 ? 'h-9 sm:h-10 text-lg' : 'h-8 text-sm'">+</button>

                    <button @click="press(4)" class="bg-slate-50 hover:bg-slate-100 active:bg-slate-200 rounded-xl text-slate-800 font-bold border border-slate-100/80 shadow-xs active:scale-95 transition-all" 
                            :class="compactLevel === 0 ? 'h-9 sm:h-10 text-base' : 'h-8 text-xs'">4</button>
                    <button @click="press(5)" class="bg-slate-50 hover:bg-slate-100 active:bg-slate-200 rounded-xl text-slate-800 font-bold border border-slate-100/80 shadow-xs active:scale-95 transition-all" 
                            :class="compactLevel === 0 ? 'h-9 sm:h-10 text-base' : 'h-8 text-xs'">5</button>
                    <button @click="press(6)" class="bg-slate-50 hover:bg-slate-100 active:bg-slate-200 rounded-xl text-slate-800 font-bold border border-slate-100/80 shadow-xs active:scale-95 transition-all" 
                            :class="compactLevel === 0 ? 'h-9 sm:h-10 text-base' : 'h-8 text-xs'">6</button>
                    <button @click="clear()" class="bg-rose-50 hover:bg-rose-100 active:bg-rose-200 text-rose-600 rounded-xl font-black border border-rose-100/80 shadow-xs active:scale-95 transition-all" 
                            :class="compactLevel === 0 ? 'h-9 sm:h-10 text-xs' : 'h-8 text-[9px]'">C</button>

                    <button @click="press(1)" class="bg-slate-50 hover:bg-slate-100 active:bg-slate-200 rounded-xl text-slate-800 font-bold border border-slate-100/80 shadow-xs active:scale-95 transition-all" 
                            :class="compactLevel === 0 ? 'h-9 sm:h-10 text-base' : 'h-8 text-xs'">1</button>
                    <button @click="press(2)" class="bg-slate-50 hover:bg-slate-100 active:bg-slate-200 rounded-xl text-slate-800 font-bold border border-slate-100/80 shadow-xs active:scale-95 transition-all" 
                            :class="compactLevel === 0 ? 'h-9 sm:h-10 text-base' : 'h-8 text-xs'">2</button>
                    <button @click="press(3)" class="bg-slate-50 hover:bg-slate-100 active:bg-slate-200 rounded-xl text-slate-800 font-bold border border-slate-100/80 shadow-xs active:scale-95 transition-all" 
                            :class="compactLevel === 0 ? 'h-9 sm:h-10 text-base' : 'h-8 text-xs'">3</button>
                    
                    <!-- Big Action / Submit Button (Always Active with Non-Invasive Flow) -->
                    <button @click="submit()" 
                            class="row-span-2 rounded-xl text-white shadow-lg flex flex-col items-center justify-center active:scale-95 transition-all relative overflow-hidden bg-gradient-to-br from-emerald-500 via-emerald-600 to-teal-700 hover:brightness-105 shadow-emerald-600/35 cursor-pointer">
                        <span class="material-icons text-xl font-bold" 
                              x-text="mode === 'cart' ? (calculate() > 0 ? 'add_shopping_cart' : 'shopping_bag') : 'check'"></span>
                        <span x-show="mode === 'cart'" class="text-[8px] font-black leading-none mt-0.5"
                              x-text="calculate() > 0 ? 'Agregar' : 'Cobrar'"></span>
                        <!-- Micro indicator badge if owner is active -->
                        <span x-show="owner" 
                              x-text="owner === 'Arianny' ? 'Ar' : (owner === 'Anthony' ? 'An' : '🏢')"
                              :class="{
                                  'bg-pink-400 text-white': owner === 'Arianny',
                                  'bg-emerald-800 text-white': owner === 'Anthony',
                                  'bg-purple-500 text-white': owner === 'Negocio'
                              }"
                              class="absolute top-1 right-1 px-1 py-0.2 rounded text-[7px] font-black leading-tight shadow-xs"></span>
                    </button>

                    <button @click="press(0)" class="col-span-2 bg-slate-50 hover:bg-slate-100 active:bg-slate-200 rounded-xl text-slate-800 font-bold border border-slate-100/80 shadow-xs active:scale-95 transition-all" 
                            :class="compactLevel === 0 ? 'h-9 sm:h-10 text-base' : 'h-8 text-xs'">0</button>
                    <button @click="press('.')" class="bg-slate-50 hover:bg-slate-100 active:bg-slate-200 rounded-xl text-slate-800 font-bold border border-slate-100/80 shadow-xs active:scale-95 transition-all" 
                            :class="compactLevel === 0 ? 'h-9 sm:h-10 text-base' : 'h-8 text-xs'">.</button>
                </div>
            </div>
        </div>

        <!-- Confirm Modal -->
        <div x-show="showConfirmModal" class="fixed inset-0 z-[100] flex items-center justify-center px-4" x-cloak>
            <div class="fixed inset-0 bg-black bg-opacity-60 transition-opacity" @click="showConfirmModal = false"></div>
            <div class="bg-white rounded-2xl p-6 w-full max-w-[280px] shadow-2xl relative z-10 text-center">
                 <div class="w-12 h-12 rounded-full bg-emerald-50 flex items-center justify-center mx-auto mb-3">
                     <span class="material-icons text-emerald-600">priority_high</span>
                 </div>
                 <h3 class="text-lg font-extrabold text-slate-800 mb-1">Confirmar</h3>
                 <p class="text-xs text-slate-500 mb-4 px-2">
                     <span x-text="mode === 'cart' ? 'Guardar Carrito (' + cart.length + ' items)' : 'Guardar transacción'"></span>
                     <span x-show="owner" class="text-[11px] font-bold text-emerald-800 block mt-0.5" x-text="'Registrado por: ' + owner"></span>
                     <br>
                     <span class="font-bold text-slate-800 text-sm block mt-1" x-text="mode === 'cart' ? formatMoney(cartTotal) : formatMoney(amount)"></span>
                 </p>
                 
                 <div x-show="customDate" class="mb-4 bg-orange-50 p-2 rounded-lg border border-orange-100">
                     <p class="text-[10px] font-bold text-orange-600 uppercase mb-0.5">Usando Fecha Temporal</p>
                     <p class="text-xs font-medium text-orange-800" x-text="new Date(customDate).toLocaleString()"></p>
                 </div>

                 <div class="flex space-x-2">
                     <button @click="showConfirmModal = false" class="flex-1 py-2 rounded-xl bg-slate-100 text-slate-600 text-xs font-bold">Cancelar</button>
                     <button @click="submitConfirmed()" class="flex-1 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 text-white text-xs font-bold shadow-lg shadow-emerald-500/25">Confirmar</button>
                 </div>
            </div>
        </div>

        <!-- Homepage-only BCV price calculator -->
        <div x-show="showCurrencyCalculator" class="fixed inset-0 z-[120] flex items-center justify-center p-3 sm:p-4" x-cloak>
            <div class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm" @click="showCurrencyCalculator = false"></div>
            <div class="relative z-10 w-full max-w-sm max-h-[92vh] overflow-y-auto bg-white rounded-3xl shadow-2xl border border-slate-200">
                <div class="sticky top-0 z-10 px-4 py-3.5 bg-white/95 backdrop-blur border-b border-slate-100 flex items-center justify-between rounded-t-3xl">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-700 text-white flex items-center justify-center shadow-md"><span class="material-icons text-lg">calculate</span></div>
                        <div><h2 class="text-sm font-black text-slate-900 leading-tight">Calculadora BCV</h2><p class="text-[9px] font-bold text-slate-400">USD · EUR · Bolívares</p></div>
                    </div>
                    <button type="button" @click="showCurrencyCalculator = false" class="w-8 h-8 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center active:scale-95"><span class="material-icons text-base">close</span></button>
                </div>

                <div class="p-4 space-y-4">
                    <section class="rounded-2xl bg-slate-50 border border-slate-200 p-3">
                        <div class="flex items-center justify-between mb-2.5">
                            <div><span class="block text-[9px] font-black uppercase tracking-wider text-slate-500">Tasas utilizadas</span><span class="text-[8px] font-semibold text-slate-400" x-text="calculatorRateSource"></span></div>
                            <button type="button" @click="fetchCalculatorRates()" :disabled="calculatorLoading" class="h-7 px-2 rounded-lg bg-white border border-slate-200 text-indigo-700 text-[9px] font-black flex items-center gap-1 disabled:opacity-50"><span class="material-icons text-xs" :class="calculatorLoading ? 'animate-spin' : ''">sync</span>Actualizar</button>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="rounded-xl bg-white border border-emerald-200 px-2.5 py-2"><span class="text-[8px] font-black text-emerald-700 block">1 USD EN BS</span><input x-model.number="calculatorUsdRate" type="number" min="0" step="0.0001" class="w-full bg-transparent outline-none font-mono font-black text-sm text-slate-900 mt-0.5"></label>
                            <label class="rounded-xl bg-white border border-blue-200 px-2.5 py-2"><span class="text-[8px] font-black text-blue-700 block">1 EUR EN BS</span><input x-model.number="calculatorEurRate" type="number" min="0" step="0.0001" class="w-full bg-transparent outline-none font-mono font-black text-sm text-slate-900 mt-0.5"></label>
                        </div>
                        <p class="mt-2 text-[8px] leading-relaxed text-slate-400">Estas tasas son editables solo para este cálculo y no modifican la tasa usada por el resto de la aplicación.</p>
                    </section>

                    <section>
                        <div class="mb-2"><span class="block text-[10px] font-black uppercase tracking-wider text-slate-700">Comparar precios</span><span class="text-[9px] font-medium text-slate-400">Introduce ambos precios para ver su diferencia en bolívares.</span></div>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-3"><span class="text-[9px] font-black text-emerald-700">PRECIO USD</span><div class="mt-1 flex items-center gap-1"><span class="font-black text-emerald-700">$</span><input x-model.number="calculatorUsdValue" type="number" min="0" step="0.01" class="w-full bg-transparent outline-none text-xl font-black text-slate-900" placeholder="21"></div><span class="mt-1 block text-[10px] font-black text-emerald-800" x-text="formatMoney(calculatorUsdBs)"></span></label>
                            <label class="rounded-2xl border border-blue-200 bg-blue-50/60 p-3"><span class="text-[9px] font-black text-blue-700">PRECIO EUR</span><div class="mt-1 flex items-center gap-1"><span class="font-black text-blue-700">€</span><input x-model.number="calculatorEurValue" type="number" min="0" step="0.01" class="w-full bg-transparent outline-none text-xl font-black text-slate-900" placeholder="22"></div><span class="mt-1 block text-[10px] font-black text-blue-800" x-text="formatMoney(calculatorEurBs)"></span></label>
                        </div>
                        <div class="mt-2 rounded-2xl p-3 flex items-center justify-between gap-3 border" :class="comparisonDifference === 0 ? 'bg-slate-50 border-slate-200' : 'bg-amber-50 border-amber-200'">
                            <div class="flex items-center gap-2 min-w-0"><span class="material-icons text-lg text-amber-600">difference</span><div class="min-w-0"><span class="block text-[9px] font-black uppercase tracking-wider text-slate-500">Diferencia</span><span class="block text-[9px] font-bold text-slate-600 truncate" x-text="comparisonMessage"></span></div></div>
                            <strong class="font-mono text-sm text-slate-900 whitespace-nowrap" x-text="formatMoney(Math.abs(comparisonDifference))"></strong>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-violet-200 bg-violet-50/50 p-3">
                        <label><span class="text-[9px] font-black uppercase tracking-wider text-violet-700">Convertir bolívares</span><div class="mt-1.5 flex items-center gap-2 rounded-xl bg-white border border-violet-200 px-3 py-2"><span class="text-xs font-black text-violet-700">Bs</span><input x-model.number="calculatorBsValue" type="number" min="0" step="0.01" class="w-full bg-transparent outline-none text-lg font-black text-slate-900" placeholder="0,00"></div></label>
                        <div class="grid grid-cols-2 gap-2 mt-2 text-center"><div class="rounded-xl bg-white p-2 border border-slate-100"><span class="block text-[8px] font-black text-slate-400">EQUIVALE EN USD</span><strong class="text-xs text-emerald-700" x-text="'$ ' + calculatorBsToUsd.toFixed(2)"></strong></div><div class="rounded-xl bg-white p-2 border border-slate-100"><span class="block text-[8px] font-black text-slate-400">EQUIVALE EN EUR</span><strong class="text-xs text-blue-700" x-text="'€ ' + calculatorBsToEur.toFixed(2)"></strong></div></div>
                    </section>
                </div>
            </div>
        </div>

        <?php /* El flujo de divisas ahora vive en /divisas; se conserva temporalmente este marcado fuera del render para facilitar despliegues con vistas cacheadas. */ if (false): ?>
        <!-- Divisas Modal (retirado) -->
        <div x-show="showDivisasModal" class="fixed inset-0 z-[90] flex items-center justify-center px-4" x-cloak>
            <div class="fixed inset-0 bg-black bg-opacity-60 transition-opacity" @click="showDivisasModal = false"></div>
            <div class="bg-white rounded-2xl p-5 w-full max-w-sm shadow-2xl relative z-10">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-bold text-slate-800">Operaciones Divisas</h2>
                    <button @click="showDivisasModal = false" class="text-slate-400 hover:text-slate-600"><span class="material-icons">close</span></button>
                </div>

                <!-- Tabs -->
                <div class="flex bg-slate-100 p-1 rounded-xl mb-4">
                    <button @click="divisasMode = 'exchange'" :class="divisasMode === 'exchange' ? 'bg-white shadow text-pink-600' : 'text-slate-500'" class="flex-1 py-2 text-xs font-bold rounded-lg transition-all">Compra ($)</button>
                    <button @click="divisasMode = 'movement'" :class="divisasMode === 'movement' ? 'bg-white shadow text-emerald-600' : 'text-slate-500'" class="flex-1 py-2 text-xs font-bold rounded-lg transition-all">Movimiento</button>
                </div>

                <!-- Exchange Form -->
                <div x-show="divisasMode === 'exchange'" class="space-y-3">
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase">Origen (Bs)</label>
                        <select x-model="divSource" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none focus:border-pink-500">
                             <option value="">Seleccione Cuenta</option>
                             <template x-for="acc in accounts.filter(a => a.currency !== 'USD')" :key="acc.id">
                                 <option :value="acc.id" x-text="acc.name + ' (' + formatMoney(acc.balance) + ')'"></option>
                             </template>
                        </select>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase">Monto a Pagar (Bs)</label>
                        <input type="number" x-model="divAmountBs" @input="calcUsd()" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-lg font-bold text-slate-800 outline-none focus:border-pink-500" placeholder="0.00">
                    </div>
                    
                    <div class="flex justify-center text-slate-300"><span class="material-icons">arrow_downward</span></div>

                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase">Destino (USD)</label>
                        <select x-model="divDest" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none focus:border-pink-500">
                             <option value="">Seleccione Cuenta</option>
                             <template x-for="acc in accounts.filter(a => a.currency === 'USD')" :key="acc.id">
                                 <option :value="acc.id" x-text="acc.name + ' ($ ' + Math.abs(acc.balance).toFixed(2) + ')'"></option>
                             </template>
                        </select>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase">Monto Recibido ($)</label>
                        <input type="number" x-model="divAmountUsd" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-lg font-bold text-emerald-600 outline-none focus:border-pink-500" placeholder="0.00">
                         <p class="text-[9px] text-slate-400 mt-1 text-right">Tasa Implícita: <span class="font-bold" x-text="calculateImplicitRate()"></span></p>
                    </div>
                </div>

                <!-- Movement Form -->
                <div x-show="divisasMode === 'movement'" class="space-y-3">
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase">Origen (Digital)</label>
                        <select x-model="divSource" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none focus:border-emerald-500">
                             <option value="">Seleccione Cuenta</option>
                             <template x-for="acc in accounts.filter(a => a.currency === 'USD' && a.tenure_type === 'digital')" :key="acc.id">
                                 <option :value="acc.id" x-text="acc.name + ' ($ ' + Math.abs(acc.balance).toFixed(2) + ')'"></option>
                             </template>
                        </select>
                    </div>
                    
                    <div class="flex justify-center text-slate-300"><span class="material-icons">arrow_downward</span></div>

                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase">Destino (Físico/Otro)</label>
                        <select x-model="divDest" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none focus:border-emerald-500">
                             <option value="">Seleccione Cuenta</option>
                             <template x-for="acc in accounts.filter(a => a.currency === 'USD')" :key="acc.id">
                                 <option :value="acc.id" x-text="acc.name + ' ($ ' + Math.abs(acc.balance).toFixed(2) + ')'"></option>
                             </template>
                        </select>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase">Monto a Mover ($)</label>
                        <input type="number" x-model="divAmountUsd" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-lg font-bold text-slate-800 outline-none focus:border-emerald-500" placeholder="0.00">
                    </div>
                </div>

                <div class="mt-6">
                    <button @click="submitDivisas()" class="w-full py-3 rounded-xl text-white font-bold shadow-lg transition-transform active:scale-95"
                            :class="divisasMode === 'exchange' ? 'bg-pink-600 hover:bg-pink-700 shadow-pink-200' : 'bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 shadow-emerald-500/25'">
                        Procesar Operación
                    </button>
                </div>
            </div>
        </div>

        <?php endif; ?>

        <!-- Feedback Toast (Moved here to ensure Top Z-Index) -->
        <!-- Minimalist Alert (Dynamic) -->
        <div x-show="message" x-cloak
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 -translate-y-4 scale-75"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 -translate-y-4 scale-75"
             class="fixed left-1/2 -translate-x-1/2 z-[150] shadow-2xl flex items-center justify-center pointer-events-none transition-all duration-300"
             :class="message.includes('Guardado') ? 'top-2 bg-emerald-500 text-white rounded-full w-8 h-8' : 'top-6 bg-slate-900/95 backdrop-blur text-white px-4 py-2 rounded-full border border-slate-700 min-w-[max-content]'">
            
            <!-- Success Icon Only -->
            <span x-show="message.includes('Guardado')" class="material-icons text-sm font-bold animate-bounce">check</span>

            <!-- Full Message for Errors/Info -->
            <div x-show="!message.includes('Guardado')" class="flex items-center gap-2">
                <span class="material-icons text-sm" :class="message.includes('Error') ? 'text-rose-400' : 'text-emerald-400'" x-text="message.includes('Error') ? 'error' : 'info'"></span>
                <span x-text="message" class="font-bold text-[10px] tracking-wide whitespace-nowrap"></span>
            </div>
        </div>

        <?php /* Las burbujas de actividad fueron retiradas de la pantalla principal. */ if (false): ?>
        <!-- Recent Bubbles Overlay (retirado) -->
        <div x-show="showBubbles" x-cloak class="absolute top-20 inset-x-0 bottom-0 pointer-events-none z-[60]">
            
            <!-- LEFT COLUMN: Income & Savings -->
            <div class="absolute top-0 left-4 flex flex-col gap-2 items-start max-h-[60vh] overflow-y-auto pb-4 pl-1 no-scrollbar pt-1 w-48">
                 <!-- Clear Left -->
                 <button x-show="recentBubbles.some(b => b.type !== 'expense')" 
                         @click="recentBubbles = recentBubbles.filter(b => b.type === 'expense')" 
                         class="bg-emerald-600/90 text-white w-6 h-6 rounded-full shadow-lg pointer-events-auto flex items-center justify-center hover:bg-emerald-700 transition-colors mb-1 active:scale-90 sticky top-0 z-10 shrink-0 self-start ml-1">
                    <span class="material-icons text-[12px]">delete</span>
                </button>

                <template x-for="bubble in recentBubbles.filter(b => b.type !== 'expense')" :key="bubble.id">
                    <div @click="bubble.expanded = !bubble.expanded"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 -translate-x-4 scale-90"
                         x-transition:enter-end="opacity-100 translate-x-0 scale-100"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100 translate-x-0 scale-100"
                         x-transition:leave-end="opacity-0 -translate-x-4 scale-90"
                         class="bg-white/95 backdrop-blur shadow-lg border border-slate-100 rounded-2xl pointer-events-auto relative overflow-hidden group cursor-pointer transition-all duration-300 origin-left shrink-0"
                         :class="bubble.expanded ? 'w-48 p-3' : 'w-auto px-3 py-2'">
                        
                        <!-- Type Stripe (Left) -->
                        <div class="absolute left-0 top-0 bottom-0 w-1 transition-all" 
                             :class="{
                                'bg-emerald-500': bubble.type === 'income',
                                'bg-teal-500': bubble.type === 'savings',
                                'w-1': bubble.expanded,
                                'w-1.5 rounded-l-2xl': !bubble.expanded
                            }"></div>

                        <!-- Compact View -->
                        <div x-show="!bubble.expanded" class="pl-2 flex items-center gap-2">
                             <p class="font-bold text-slate-700 text-xs whitespace-nowrap" x-text="formatMoney(bubble.amount, bubble.currency)"></p>
                        </div>

                        <!-- Expanded View -->
                        <div x-show="bubble.expanded" class="pl-2">
                             <div class="flex justify-between items-start mb-1">
                                 <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wider truncate max-w-[100px]" x-text="bubble.category"></p>
                                 <button @click.stop="recentBubbles = recentBubbles.filter(b => b.id !== bubble.id)" class="text-slate-300 hover:text-emerald-500 -mt-1 -mr-1">
                                    <span class="material-icons text-sm">close</span>
                                 </button>
                             </div>
                             <p class="font-black text-slate-800 text-lg leading-none" x-text="formatMoney(bubble.amount, bubble.currency)"></p>
                             <p class="text-[9px] text-slate-500 mt-1 truncate" x-show="bubble.desc" x-text="bubble.desc"></p>
                        </div>
                    </div>
                </template>
            </div>

            <!-- RIGHT COLUMN: Expenses -->
            <div class="absolute top-0 right-4 flex flex-col gap-2 items-end max-h-[60vh] overflow-y-auto pb-4 pr-1 no-scrollbar pt-1 w-48">
                 <!-- Clear Right -->
                 <button x-show="recentBubbles.some(b => b.type === 'expense')" 
                         @click="recentBubbles = recentBubbles.filter(b => b.type !== 'expense')" 
                         class="bg-rose-600/90 text-white w-6 h-6 rounded-full shadow-lg pointer-events-auto flex items-center justify-center hover:bg-rose-700 transition-colors mb-1 active:scale-90 sticky top-0 z-10 shrink-0 self-end mr-1">
                    <span class="material-icons text-[12px]">delete</span>
                </button>

                <template x-for="bubble in recentBubbles.filter(b => b.type === 'expense')" :key="bubble.id">
                    <div @click="bubble.expanded = !bubble.expanded"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 translate-x-4 scale-90"
                         x-transition:enter-end="opacity-100 translate-x-0 scale-100"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100 translate-x-0 scale-100"
                         x-transition:leave-end="opacity-0 translate-x-4 scale-90"
                         class="bg-white/95 backdrop-blur shadow-lg border border-slate-100 rounded-2xl pointer-events-auto relative overflow-hidden group cursor-pointer transition-all duration-300 origin-right shrink-0"
                         :class="bubble.expanded ? 'w-48 p-3' : 'w-auto px-3 py-2'">
                        
                        <!-- Type Stripe (Right Side for Right Column? Or Keep Left uniformity? Let's keep Left stripe for consistency but maybe Right border?) -->
                        <!-- Actually, for Right aligned bubbles, having the stripe on the Right might look cool, but let's stick to Left stripe for design consistency -->
                        <div class="absolute left-0 top-0 bottom-0 w-1 transition-all bg-rose-500" 
                             :class="{
                                'w-1': bubble.expanded,
                                'w-1.5 rounded-l-2xl': !bubble.expanded
                            }"></div>

                        <!-- Compact View -->
                        <div x-show="!bubble.expanded" class="pl-2 flex items-center gap-2 justify-end">
                             <p class="font-bold text-slate-700 text-xs whitespace-nowrap" x-text="formatMoney(bubble.amount, bubble.currency)"></p>
                        </div>

                        <!-- Expanded View -->
                        <div x-show="bubble.expanded" class="pl-2">
                             <div class="flex justify-between items-start mb-1">
                                 <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wider truncate max-w-[100px]" x-text="bubble.category"></p>
                                 <button @click.stop="recentBubbles = recentBubbles.filter(b => b.id !== bubble.id)" class="text-slate-300 hover:text-rose-500 -mt-1 -mr-1">
                                    <span class="material-icons text-sm">close</span>
                                 </button>
                             </div>
                             <p class="font-black text-slate-800 text-lg leading-none" x-text="formatMoney(bubble.amount, bubble.currency)"></p>
                             <p class="text-[9px] text-slate-500 mt-1 truncate" x-show="bubble.desc" x-text="bubble.desc"></p>
                        </div>
                    </div>
                </template>
            </div>
        </div>
        <?php endif; ?>

    </div> <!-- End App Container -->

    <script>
        function transactionApp() {
            return {
                amount: '0',
                display: '0',
                description: '',
                mode: 'single', 
                type: 'expense',
                currency: 'Bs', 
                owner: null, // Initial check requires selection
                showQuickOwnerPicker: false,
                rememberOwner: false,
                ownerLockedInBar: false,
                exchangeRate: 50,
                manualRate: false, // Prevents auto-update if true
                selectedAccount: '<?= $accounts[0]['id'] ?? 1 ?>',
                selectedCategory: '<?= $categories[0]['id'] ?? 1 ?>',
                cart: [],
                stats: { balance: 0, today_expense: 0, recent: [] },
                message: '',
                showMenu: false,
                showAccountMenu: false,
                showCategoryMenu: false,
                categorySearch: '',
                // Swipe Navigation State
                touchStartX: 0,
                touchStartY: 0,
                touchStartTime: 0,
                isSwiping: false,
                isMouseDown: false,
                accounts: <?= json_encode($accounts ?? []) ?>,
                categories: <?= json_encode($categories ?? []) ?>,
                
                // Inventory Logic
                inventoryItems: <?= json_encode($inventory_items ?? []) ?>,
                enablePurchase: false,
                purchaseItem: null,
                purchaseQuantity: 1,
                purchaseCost: 0,
                invSearch: '',
                showInvMenu: false,

                // Refinement
                showConfirmModal: false,
                showCurrencyCalculator: false,
                calculatorLoading: false,
                calculatorUsdRate: 0,
                calculatorEurRate: 0,
                calculatorUsdValue: 21,
                calculatorEurValue: 22,
                calculatorBsValue: '',
                calculatorRateSource: 'Tasas de referencia BCV',
                customDate: null,

                dateTimer: null,
                compactLevel: 0,
                showQuickAccess: false,
                quickOcrLoading: false,
                quickOcrToast: '',
                quickOcrToastType: 'success',
                
                init() {
                    this.fetchStats();
                    this.fetchConfig();
                    
                    // Load saved settings
                    const savedRate = localStorage.getItem('exchangeRate');
                    if(savedRate) this.exchangeRate = parseFloat(savedRate);
                    
                    const savedManual = localStorage.getItem('manualRate');
                    if(savedManual === 'true') this.manualRate = true;
                    
                    const savedLevel = localStorage.getItem('compactLevel');
                    if(savedLevel !== null) this.compactLevel = parseInt(savedLevel);

                    if(!this.manualRate) {
                        this.fetchRate();
                    }
                    
                    this.$watch('exchangeRate', val => localStorage.setItem('exchangeRate', val));
                    this.$watch('manualRate', val => localStorage.setItem('manualRate', val));
                    this.$watch('compactLevel', val => localStorage.setItem('compactLevel', val));
                },
                
                get filteredInventory() {
                    const q = this.invSearch.toLowerCase();
                    return this.inventoryItems.filter(i => i.name.toLowerCase().includes(q));
                },

                get filteredCategories() {
                    if(!this.categorySearch) return this.categories;
                    const q = this.categorySearch.toLowerCase().trim();
                    return this.categories.filter(c => c.name.toLowerCase().includes(q));
                },

                // Swipe navigation for Transaction Types (Gasto <-> Ingreso <-> Ahorro)
                handleSwipeStart(e) {
                    const touch = e.touches ? e.touches[0] : e;
                    this.touchStartX = touch.clientX;
                    this.touchStartY = touch.clientY;
                    this.touchStartTime = Date.now();
                    this.isSwiping = true;
                },

                handleSwipeEnd(e) {
                    if (!this.isSwiping) return;
                    this.isSwiping = false;
                    const touch = e.changedTouches ? e.changedTouches[0] : e;
                    const deltaX = touch.clientX - this.touchStartX;
                    const deltaY = touch.clientY - this.touchStartY;
                    const deltaTime = Date.now() - this.touchStartTime;

                    // Close quick-access panel on any right swipe if it's open
                    if (this.showQuickAccess && deltaX > 30 && Math.abs(deltaX) > Math.abs(deltaY) * 1.1) {
                        this.showQuickAccess = false;
                        return;
                    }

                    // Open quick-access panel: left-swipe starting from right 30% of screen
                    if (deltaX < -40 && Math.abs(deltaX) > Math.abs(deltaY) * 1.1 && deltaTime < 750
                        && this.touchStartX > (window.innerWidth * 0.7)) {
                        this.showQuickAccess = true;
                        return;
                    }

                    // Require distance > 30px, predominantly horizontal, within 750ms
                    if (Math.abs(deltaX) > 30 && Math.abs(deltaX) > Math.abs(deltaY) * 1.1 && deltaTime < 750) {
                        if (deltaX < 0) {
                            // Swiped LEFT: Gasto -> Ingreso -> Ahorro
                            this.nextType();
                        } else {
                            // Swiped RIGHT: Ahorro -> Ingreso -> Gasto
                            this.prevType();
                        }
                        if (navigator.vibrate) {
                            try { navigator.vibrate(15); } catch(err) {}
                        }
                    }
                },

                handleMouseDown(e) {
                    if (e.target.tagName === 'INPUT' || e.target.closest('button')) {
                        this.isMouseDown = false;
                        return;
                    }
                    this.touchStartX = e.clientX;
                    this.touchStartY = e.clientY;
                    this.touchStartTime = Date.now();
                    this.isMouseDown = true;
                },

                handleMouseUp(e) {
                    if (!this.isMouseDown) return;
                    this.isMouseDown = false;
                    const deltaX = e.clientX - this.touchStartX;
                    const deltaY = e.clientY - this.touchStartY;
                    if (Math.abs(deltaX) > 35 && Math.abs(deltaX) > Math.abs(deltaY) * 1.1) {
                        if (deltaX < 0) {
                            this.nextType();
                        } else {
                            this.prevType();
                        }
                    }
                },

                nextType() {
                    const types = ['expense', 'income', 'savings'];
                    const currentIndex = types.indexOf(this.type);
                    const nextIndex = (currentIndex + 1) % types.length;
                    this.type = types[nextIndex];
                },

                prevType() {
                    const types = ['expense', 'income', 'savings'];
                    const currentIndex = types.indexOf(this.type);
                    const prevIndex = (currentIndex - 1 + types.length) % types.length;
                    this.type = types[prevIndex];
                },

                // Quick OCR Scan — triggered from FAB panel camera button
                triggerQuickOcr() {
                    this.showQuickAccess = false;
                    this.$nextTick(() => {
                        const input = document.getElementById('quickOcrInput');
                        if (input) input.click();
                    });
                },

                async handleQuickOcrFile(e) {
                    const files = Array.from(e.target.files || []);
                    if (!files || !files.length) return;
                    this.quickOcrLoading = true;
                    try {
                        const images = [];
                        for (const file of files) {
                            images.push(await this.prepareQuickOcrImage(file));
                        }
                        const accountId = this.selectedAccount;
                        const categoryId = this.selectedCategory;
                        const owner = this.owner || '<?= $_SESSION['owner'] ?? 'personal' ?>';
                        const resp = await fetch('<?= base_url('ocr/quick-process') ?>', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ images, account_id: accountId, category_id: categoryId, owner })
                        });
                        const data = await resp.json();
                        if (resp.ok && data.status === 'success') {
                            const saved = Number(data.saved_count || 1);
                            const failed = Number(data.failed_count || 0);
                            this.quickOcrToast = failed > 0
                                ? `✅ ${saved} factura(s) guardada(s); ${failed} foto(s) no reconocida(s)`
                                : `✅ ${saved} factura(s) escaneada(s) — pendiente(s) de revisión`;
                            this.quickOcrToastType = 'success';
                            this.fetchStats();
                        } else {
                            this.quickOcrToast = '⚠️ ' + (data.message || 'Error al procesar');
                            this.quickOcrToastType = 'error';
                        }
                    } catch(err) {
                        this.quickOcrToast = '❌ ' + (err.message || 'Error al procesar la factura');
                        this.quickOcrToastType = 'error';
                    } finally {
                        this.quickOcrLoading = false;
                        e.target.value = '';
                        setTimeout(() => { this.quickOcrToast = ''; }, 4000);
                    }
                },

                prepareQuickOcrImage(file) {
                    return new Promise((resolve, reject) => {
                        if (!file || !String(file.type || '').startsWith('image/')) {
                            reject(new Error('El archivo seleccionado no es una imagen.'));
                            return;
                        }

                        const reader = new FileReader();
                        reader.onerror = () => reject(new Error('No se pudo leer la fotografía.'));
                        reader.onload = event => {
                            const image = new Image();
                            image.onerror = () => reject(new Error('La fotografía no tiene un formato válido.'));
                            image.onload = () => {
                                const maxDimension = 1600;
                                let width = image.naturalWidth || image.width;
                                let height = image.naturalHeight || image.height;
                                if (width > maxDimension || height > maxDimension) {
                                    const scale = maxDimension / Math.max(width, height);
                                    width = Math.round(width * scale);
                                    height = Math.round(height * scale);
                                }

                                const canvas = document.createElement('canvas');
                                canvas.width = width;
                                canvas.height = height;
                                const context = canvas.getContext('2d');
                                if (!context) {
                                    reject(new Error('No se pudo preparar la fotografía.'));
                                    return;
                                }
                                context.drawImage(image, 0, 0, width, height);
                                // Keep the exact payload format used by the full OCR module.
                                resolve(canvas.toDataURL('image/jpeg', 0.85));
                            };
                            image.src = event.target.result;
                        };
                        reader.readAsDataURL(file);
                    });
                },
                
                selectInvItem(item) {
                    this.purchaseItem = item;
                    this.purchaseCost = parseFloat(item.cost); // Default to last cost
                    this.invSearch = ''; // Clear search but keep item selected details visible
                    this.showInvMenu = false;
                    this.calcTotal();
                },
                
                calcTotal() {
                    if(this.purchaseItem) {
                        const total = this.purchaseQuantity * this.purchaseCost;
                        this.amount = total.toString();
                        this.display = total.toFixed(2); // Auto update display
                    }
                },

                toggleManualRate() {
                    this.manualRate = !this.manualRate;
                    if (!this.manualRate) {
                        this.fetchRate(); 
                    } else {
                        this.showMsg('Modo Tasa Manual Activo');
                    }
                },

                toggleCompactMode() {
                    this.compactLevel = (this.compactLevel + 1) % 3;
                    const levels = ['Modo Normal', 'Modo Compacto', 'Modo Ultra'];
                    this.showMsg(levels[this.compactLevel]);
                },

                togglePurchaseMode() {
                    this.purchaseItem = null; 
                    this.purchaseQuantity = 1; 
                    this.enablePurchase = !this.enablePurchase;
                    
                    if(this.enablePurchase) {
                        this.type = 'expense';
                        // Keep current currency or default to USD? Usually Inventory is bought in USD or BS.
                        // Let's force focus or something? No.
                    }
                },
                
                async quickCreateItem() {
                    const name = this.invSearch;
                    if(!name) return;
                    
                    try {
                        let res = await fetch('<?= base_url('inventory/quick-create') ?>', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ name: name })
                        });
                        let data = await res.json();
                        
                        if(data.status === 'success') {
                            this.inventoryItems.push(data.data); // Add to local list
                            this.selectInvItem(data.data); // Select it
                        } else {
                            this.showMsg('Error: ' + data.message);
                        }
                    } catch(e) {
                        this.showMsg('Error al crear item');
                    }
                },

                formatMoney(value, currency = 'Bs') {
                    if (currency === 'USD') {
                        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 2 }).format(value);
                    }
                    return new Intl.NumberFormat('es-VE', { style: 'currency', currency: 'VES', minimumFractionDigits: 2 }).format(value);
                },

                formatTime(dateStr) {
                    if (!dateStr) return '';
                    try {
                        const d = new Date(dateStr.replace(' ', 'T'));
                        const now = new Date();
                        const isToday = d.toDateString() === now.toDateString();
                        const timePart = d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                        if (isToday) return timePart;
                        return d.toLocaleDateString([], { month: 'short', day: 'numeric' }) + ' ' + timePart;
                    } catch(e) {
                        return dateStr;
                    }
                },

                movementTypeLabel(item) {
                    if ((item.description || '').toUpperCase().includes('[ANULADA]')) return 'Anulado';
                    const labels = {
                        income: 'Ingreso', return: 'Reintegro', expense: 'Gasto', savings: 'Ahorro',
                        exchange_out: 'Compra USD', exchange_in: 'Recepción USD',
                        transfer_out: 'Traslado', transfer_in: 'Recepción',
                        currency_reversal_in: 'Reverso', currency_reversal_out: 'Reverso'
                    };
                    return labels[item.type] || (item.ocr_merchant ? 'Factura' : 'Movimiento');
                },

                movementPrefix(item) {
                    if (['income', 'return'].includes(item.type)) return '+ ';
                    if (item.type === 'savings') return '★ ';
                    if (['exchange_out','exchange_in','transfer_out','transfer_in','currency_reversal_in','currency_reversal_out'].includes(item.type)) return '↔ ';
                    return '− ';
                },

                movementAmountClass(item) {
                    if (['income', 'return'].includes(item.type)) return 'text-emerald-700';
                    if (item.type === 'expense') return 'text-rose-700';
                    if (item.type === 'savings' || item.type?.includes('exchange') || item.type?.includes('transfer')) return 'text-blue-700';
                    return item.ocr_merchant ? 'text-amber-700' : 'text-slate-700';
                },

                getCategoryIcon(catName, iconName) {
                    const map = {
                        'fast-food': 'fastfood',
                        'food': 'restaurant',
                        'car': 'directions_car',
                        'transport': 'directions_car',
                        'cash': 'payments',
                        'store': 'storefront',
                        'comida': 'fastfood',
                        'transporte': 'directions_car',
                        'salario': 'payments',
                        'ventas': 'storefront',
                        'general': 'category'
                    };
                    if (iconName && map[iconName]) return map[iconName];
                    if (catName && map[catName.toLowerCase()]) return map[catName.toLowerCase()];
                    return 'category';
                },

                get currentConversion() {
                    const val = this.calculate();
                    if (this.currency === 'USD') {
                        return val * this.exchangeRate; // Show Bs equivalent
                    } else {
                        return (this.exchangeRate > 0) ? (val / this.exchangeRate) : 0; // Show USD equivalent
                    }
                },

                get calculatorUsdBs() {
                    return (parseFloat(this.calculatorUsdValue) || 0) * (parseFloat(this.calculatorUsdRate) || 0);
                },

                get calculatorEurBs() {
                    return (parseFloat(this.calculatorEurValue) || 0) * (parseFloat(this.calculatorEurRate) || 0);
                },

                get comparisonDifference() {
                    return this.calculatorEurBs - this.calculatorUsdBs;
                },

                get comparisonMessage() {
                    if (Math.abs(this.comparisonDifference) < 0.01) return 'Ambos precios cuestan lo mismo';
                    return this.comparisonDifference > 0 ? 'El precio en euros es mayor' : 'El precio en dólares es mayor';
                },

                get calculatorBsToUsd() {
                    const rate = parseFloat(this.calculatorUsdRate) || 0;
                    return rate > 0 ? (parseFloat(this.calculatorBsValue) || 0) / rate : 0;
                },

                get calculatorBsToEur() {
                    const rate = parseFloat(this.calculatorEurRate) || 0;
                    return rate > 0 ? (parseFloat(this.calculatorBsValue) || 0) / rate : 0;
                },

                press(key) {
                    if (this.amount === '0') this.amount = '';
                    this.amount += key;
                    this.display = this.amount;
                },

                pressOperation(op) {
                    try {
                        if (this.amount.endsWith('+') || this.amount.endsWith('-')) return;
                        this.amount += op;
                        this.display = this.amount;
                    } catch(e) {}
                },

                clear() {
                    this.amount = '0';
                    this.display = '0';
                    this.description = '';
                },

                toggleMode() {
                    this.mode = this.mode === 'single' ? 'cart' : 'single';
                    if (this.mode === 'single') {
                        this.cart = [];
                    }
                    this.showMsg(this.mode === 'cart' ? 'Modo Carrito Activo' : 'Modo Registro Individual');
                },

                clearCart() {
                    if (this.cart.length > 0) {
                        this.cart = [];
                        this.showMsg('Carrito vaciado');
                    }
                },
                
                removeItem(index) {
                    this.cart.splice(index, 1);
                },

                calculate() {
                    try {
                        let safeExpr = this.amount.replace(/[^0-9\.\+\-]/g, '');
                        return Function('"use strict";return (' + safeExpr + ')')() || 0;
                    } catch (e) {
                        return 0;
                    }
                },

                startTimer(dateVal) {
                    if (!dateVal) return;
                    this.customDate = dateVal;
                    if (this.dateTimer) clearInterval(this.dateTimer);
                    
                    this.showMsg('Fecha temporal activada (10 min)');
                    this.dateTimer = setTimeout(() => {
                        this.customDate = null;
                        this.showMsg('Fecha temporal expirada');
                    }, 600000); // 10 minutes
                },

                toggleOwnerInBar(name) {
                    if (this.owner === name) {
                        this.owner = null;
                        this.ownerLockedInBar = false;
                    } else {
                        this.owner = name;
                        this.ownerLockedInBar = true;
                    }
                },

                // Modified submit check
                async submit() {
                     let enteredValue = this.calculate();

                     // Case 1: Add to Cart (No confirm needed)
                     if (this.mode === 'cart' && enteredValue > 0) {
                         // Conversion for Cart Item
                         let amountBs = 0;
                         let amountUsd = 0;
                         if (this.currency === 'USD') {
                            amountUsd = enteredValue;
                            amountBs = enteredValue * this.exchangeRate;
                         } else {
                            amountBs = enteredValue;
                            amountUsd = (this.exchangeRate > 0) ? (enteredValue / this.exchangeRate) : 0;
                         }
                         
                            this.cart.push({
                                name: this.description || ('Item ' + (this.cart.length + 1)),
                                description: this.description,
                                quantity: 1,
                                price: amountBs, 
                                price_usd: amountUsd,
                                currency: this.currency
                            });
                            this.clear();
                            this.showMsg('Agregado al carrito · ' + this.cart.length + (this.cart.length === 1 ? ' producto' : ' productos'));
                            return;
                     }

                     // Validation
                     if (this.mode === 'single' && enteredValue <= 0) return this.showMsg('Ingrese un monto');
                     if (this.mode === 'cart' && this.cart.length === 0) return this.showMsg('El carrito está vacío');

                     // If owner is not selected yet, trigger the fast non-invasive quick-picker!
                     if (!this.owner) {
                         this.showQuickOwnerPicker = true;
                         return;
                     }

                     // Fix: Update amount to calculated value so Modal shows number, not "6+7"
                     if (this.mode === 'single') {
                         this.amount = enteredValue.toString();
                         this.display = this.amount;
                     }

                     this.showConfirmModal = true;
                },

                async selectOwnerAndSubmit(selectedOwner) {
                    this.owner = selectedOwner;
                    this.showQuickOwnerPicker = false;
                    let enteredValue = this.calculate();
                    if (this.mode === 'single') {
                        this.amount = enteredValue.toString();
                        this.display = this.amount;
                    }
                    await this.processSubmit();
                },

                async submitConfirmed() {
                    this.showConfirmModal = false;
                    await this.processSubmit();
                },

                async processSubmit() {
                     let enteredValue = this.calculate();
                     
                     // Auto-detect Return in Temporary Mode (User Request: "Return is the function of temp account")
                     let finalType = this.type;
                     if (this.customDate && this.type === 'income') {
                         finalType = 'return';
                     }

                     // Conversion Logic same as before...
                     let amountBs = 0;
                     let amountUsd = 0;

                    if (this.currency === 'USD') {
                        amountUsd = enteredValue;
                        amountBs = enteredValue * this.exchangeRate;
                    } else {
                        amountBs = enteredValue;
                        amountUsd = (this.exchangeRate > 0) ? (enteredValue / this.exchangeRate) : 0;
                    }

                     if (this.mode === 'cart') {
                        // Submit Cart
                        let totalBs = this.cartTotal;
                        let totalUsd = this.cart.reduce((sum, item) => sum + (item.price_usd * item.quantity), 0);
                        
                        await this.sendData({
                            amount: totalBs,
                            amount_usd: totalUsd,
                            exchange_rate: this.exchangeRate,
                            type: finalType,
                            owner: this.owner,
                            account_id: this.selectedAccount,
                            category_id: this.selectedCategory,
                            description: 'Compra Múltiple', 
                            items: this.cart,
                            created_at: this.customDate
                        });
                        this.cart = [];
                        this.display = "Enviado";
                        setTimeout(() => this.clear(), 1000);
                     } else {
                        // Single Mode
                        let payload = {
                            amount: amountBs,
                            amount_usd: amountUsd,
                            exchange_rate: this.exchangeRate,
                            type: finalType,
                            owner: this.owner,
                            account_id: this.selectedAccount,
                            category_id: this.selectedCategory,
                            description: this.description || 'Transacción Rápida',
                            created_at: this.customDate
                         };

                         // Inventory Data
                         if(this.enablePurchase && this.purchaseItem) {
                             payload.inventory_item_id = this.purchaseItem.id;
                             payload.quantity = this.purchaseQuantity;
                             if(!this.description) {
                                 // Let backend handle default
                             } else {
                                 payload.description += ` (Stock: ${this.purchaseItem.name} x${this.purchaseQuantity})`;
                             }
                         }

                         await this.sendData(payload);
                         
                         // Reset Inventory State
                         this.enablePurchase = false;
                         this.purchaseItem = null;
                         this.purchaseQuantity = 1;
                         this.purchaseCost = 0;
                         this.invSearch = '';

                         this.display = "Enviado";
                         setTimeout(() => this.clear(), 1000);
                     }

                     // If owner was chosen through the quick picker without checking "remember", reset it
                     if (!this.rememberOwner && !this.ownerLockedInBar) {
                         this.owner = null;
                     }
                },

                get cartTotal() {
                    return this.cart.reduce((sum, item) => sum + (item.quantity * item.price), 0);
                },

                async sendData(payload) {
                    try {
                        let res = await fetch('<?= base_url('transaction/save') ?>', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            body: JSON.stringify(payload)
                        });
                        let data = await res.json();
                        if (data.status === 'success') {
                            this.showMsg('Guardado Exitosamente');
                            this.fetchStats();
                            this.fetchConfig();
                        } else {
                            this.showMsg('Error: ' + data.message);
                        }
                    } catch (e) {
                        console.error(e);
                        this.showMsg('Error de conexión');
                    }
                },
                
                async fetchStats() {
                    try {
                        let res = await fetch('<?= base_url('transaction/stats') ?>');
                        let data = await res.json();
                        this.stats = data;
                    } catch(e) {}
                },

                async fetchConfig() {
                    try {
                        let res = await fetch('<?= base_url('config/get-data') ?>');
                        let data = await res.json();
                        this.accounts = data.accounts;
                        this.categories = data.categories;
                        // Select first if not set
                        if(this.accounts.length > 0 && !this.selectedAccount) this.selectedAccount = this.accounts[0].id;
                        if(this.categories.length > 0 && !this.selectedCategory) this.selectedCategory = this.categories[0].id;
                    } catch(e) {}
                },

                async fetchRate() {
                    try {
                        let res = await fetch('<?= base_url('currency/get-rate') ?>');
                        let data = await res.json();
                        if (data.status === 'success' && data.rate > 0) {
                            this.exchangeRate = data.rate;
                            // visual feedback in badge instead of toast
                            this.rateUpdated = true;
                            setTimeout(() => this.rateUpdated = false, 3000);
                        }
                    } catch(e) { console.log('BCV Error'); }
                },

                openCurrencyCalculator() {
                    this.calculatorUsdRate = parseFloat(this.exchangeRate) || 0;
                    this.showCurrencyCalculator = true;
                    this.fetchCalculatorRates();
                },

                async fetchCalculatorRates() {
                    if (this.calculatorLoading) return;
                    this.calculatorLoading = true;
                    try {
                        const response = await fetch('<?= base_url('currency/get-rates') ?>');
                        const data = await response.json();
                        if (data.rates) {
                            if (parseFloat(data.rates.USD) > 0) this.calculatorUsdRate = parseFloat(data.rates.USD);
                            if (parseFloat(data.rates.EUR) > 0) this.calculatorEurRate = parseFloat(data.rates.EUR);
                        }
                        this.calculatorRateSource = data.source || 'Tasas editables para este cálculo';
                        if (!response.ok || data.status !== 'success') this.showMsg(data.message || 'No se pudieron actualizar ambas tasas');
                    } catch (e) {
                        this.calculatorRateSource = 'Sin conexión · introduce las tasas manualmente';
                        this.showMsg('No se pudieron actualizar las tasas BCV');
                    } finally {
                        this.calculatorLoading = false;
                    }
                },

                showMsg(txt) {
                    this.message = txt;
                    setTimeout(() => this.message = '', 2000);
                },

                handleKeydown(e) {
                    // Ignore if typing in an input field
                    if (['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) {
                        // Exception: Allow Enter to submit if not in a textarea/select (maybe?)
                        // For now, let's keep it simple and block calculator keys when typing notes
                        if (e.key === 'Enter' && e.target.type !== 'textarea') {
                            // Optional: Blur input on Enter?
                            // e.target.blur();
                        }
                        return;
                    }

                    // Number Keys
                    if (e.key >= '0' && e.key <= '9') {
                        this.press(e.key);
                        return;
                    }

                    // Decimal Point
                    if (e.key === '.' || e.key === ',') {
                        this.press('.');
                        return;
                    }

                    // Operations
                    if (e.key === '+') {
                        this.pressOperation('+');
                        return;
                    }

                    // Submit / Enter
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        this.submit();
                        return;
                    }

                    // Clear / Esc / Backspace
                    if (e.key === 'Escape') {
                        if (this.showCurrencyCalculator) {
                            this.showCurrencyCalculator = false;
                            return;
                        }
                        if (this.showQuickOwnerPicker) {
                            this.showQuickOwnerPicker = false;
                            return;
                        }
                        if (this.showConfirmModal) {
                            this.showConfirmModal = false;
                            return;
                        }
                        this.clear();
                        return;
                    }

                    if (e.key === 'Delete' || e.key === 'Backspace') {
                        this.clear();
                        return;
                    }
                }
            }
        }
    </script>
</body>
</html>
