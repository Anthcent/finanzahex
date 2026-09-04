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

        <!-- Header Area -->
        <div class="flex-none pt-2 px-5 pb-2 z-50 relative lg:pt-6">
            <div class="flex justify-between items-center mb-4 transition-all duration-300" :class="compactLevel > 0 ? 'mb-2' : 'mb-4'">
                <!-- Brand / Title -->
                <!-- Brand / Title & Shortcuts -->
                <div class="flex items-center gap-3" :class="compactLevel > 1 ? 'hidden' : 'block'">
                    <div>
                        <h1 class="font-bold text-slate-800 tracking-tight leading-none" :class="compactLevel === 1 ? 'text-sm' : 'text-lg'">Fi-Hex <span class="bg-gradient-to-r from-emerald-600 to-teal-500 bg-clip-text text-transparent font-extrabold">Wallet</span></h1>
                        <p class="text-[9px] font-medium text-slate-400" x-show="compactLevel === 0">Resumen financiero</p>
                    </div>
                    
                    <!-- Shortcut: Printing -->
                    <a href="<?= base_url('printing') ?>" class="w-8 h-8 flex items-center justify-center bg-emerald-50 text-emerald-600 rounded-xl hover:bg-emerald-100 transition-colors shadow-sm" title="Ir a Impresiones">
                        <span class="material-icons text-sm">print</span>
                    </a>
                </div>
                
                <!-- Controls Container -->
                <div class="flex items-center gap-2" :class="compactLevel > 1 ? 'w-full justify-between' : ''">
                     
                     <!-- Ultra Mode Brand (Only visible in Level 2) -->
                     <div class="flex items-center gap-1" x-show="compactLevel > 1">
                        <div class="w-5 h-5 bg-gradient-to-br from-emerald-600 to-teal-600 rounded flex items-center justify-center text-white font-bold text-[10px] shadow-sm">F</div>
                        <span class="font-bold text-slate-700 text-[10px]">Fi-Hex</span>
                     </div>

                     <div class="flex items-center gap-2">
                         <!-- Rate Badge (Redesigned) -->
                         <div class="flex items-center bg-white/70 backdrop-blur-md border border-emerald-100 rounded-xl px-2 py-1 shadow-sm gap-2">
                             <div class="flex flex-col items-end leading-none">
                                <span class="text-[7px] font-bold uppercase tracking-wider transition-colors"
                                      :class="rateUpdated ? 'text-emerald-500' : 'text-slate-400'"
                                      x-text="rateUpdated ? 'ACTUALIZADO' : 'TASA BCV'"></span>
                                <div class="flex items-baseline">
                                     <span class="text-[10px] font-bold text-slate-500 mr-0.5" :class="compactLevel > 0 ? 'text-[9px]' : 'text-[10px]'">Bs</span>
                                     <input type="number" x-model.number="exchangeRate" :disabled="!manualRate" 
                                            class="bg-transparent font-bold text-slate-700 text-right focus:outline-none p-0 border-none h-auto w-16 transition-colors"
                                            :class="{'text-emerald-600': rateUpdated, 'text-xs': compactLevel > 0, 'text-sm': compactLevel === 0}"
                                            placeholder="0">
                                </div>
                             </div>
                             <button @click="toggleManualRate()" class="w-4 h-4 flex items-center justify-center rounded-full transition-colors" :class="manualRate ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-100 text-slate-300'">
                                <span class="material-icons text-[10px]">edit</span>
                             </button>
                         </div>
                         
                         <!-- Menu Button -->
                         <button @click="showMenu = !showMenu" class="w-8 h-8 flex items-center justify-center bg-white rounded-xl shadow-sm border border-slate-200 text-slate-600 active:scale-95 transition-all">
                            <span class="material-icons text-lg">menu</span>
                         </button>
                     </div>
                </div>
            </div>
            
            <!-- Compact Menu Modal -->
            <div x-cloak x-show="showMenu"
                 class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                 
                 <!-- Enhanced Backdrop (Darker for focus) -->
                 <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" 
                      x-transition:enter="duration-200 ease-out"
                      x-transition:enter-start="opacity-0"
                      x-transition:enter-end="opacity-100"
                      x-transition:leave="duration-150 ease-in"
                      x-transition:leave-start="opacity-100"
                      x-transition:leave-end="opacity-0"
                      @click="showMenu = false"></div>

                 <!-- Menu Content Card (Compact & Centered) -->
                 <div class="relative w-full max-w-sm bg-white/95 backdrop-blur-2xl rounded-[2rem] shadow-2xl ring-1 ring-white/20 overflow-hidden flex flex-col max-h-[85vh] transition-all transform pointer-events-auto"
                      x-transition:enter="duration-300 cubic-bezier(0.34, 1.56, 0.64, 1)"
                      x-transition:enter-start="opacity-0 scale-90 translate-y-8"
                      x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                      x-transition:leave="duration-200 ease-in"
                      x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                      x-transition:leave-end="opacity-0 scale-90 translate-y-8">
                      
                      <!-- Compact Header -->
                      <div class="px-6 pt-6 pb-2 flex justify-between items-center border-b border-slate-100/50 shrink-0">
                          <div>
                              <h2 class="text-xl font-black text-slate-800 tracking-tight">Menú Principal</h2>
                              <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Acceso Rápido</p>
                          </div>
                          <button @click="showMenu = false" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-rose-500 hover:bg-rose-50 flex items-center justify-center transition-all active:scale-90">
                              <span class="material-icons text-lg">close</span>
                          </button>
                      </div>

                      <!-- Compact Grid Content -->
                      <div class="flex-1 overflow-y-auto p-5 space-y-6 no-scrollbar">
                          
                          <!-- Group: Finanzas -->
                          <div class="space-y-3">
                              <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest pl-1">Finanzas</h3>
                              <div class="grid grid-cols-3 gap-4">
                                  <a href="<?= base_url('history') ?>" class="flex flex-col items-center gap-2 group">
                                      <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shadow-sm group-hover:scale-95 transition-transform group-hover:bg-emerald-100">
                                          <span class="material-icons text-3xl">list</span>
                                      </div>
                                      <span class="text-[11px] font-bold text-slate-600 text-center leading-tight">Registros</span>
                                  </a>
                                  <a href="<?= base_url('accounts') ?>" class="flex flex-col items-center gap-2 group">
                                      <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shadow-sm group-hover:scale-95 transition-transform group-hover:bg-emerald-100">
                                          <span class="material-icons text-3xl">account_balance_wallet</span>
                                      </div>
                                      <span class="text-[11px] font-bold text-slate-600 text-center leading-tight">Cuentas</span>
                                  </a>
                                  <a href="<?= base_url('metrics') ?>" class="flex flex-col items-center gap-2 group">
                                      <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shadow-sm group-hover:scale-95 transition-transform group-hover:bg-emerald-100">
                                          <span class="material-icons text-3xl">bar_chart</span>
                                      </div>
                                      <span class="text-[11px] font-bold text-slate-600 text-center leading-tight">Métricas</span>
                                  </a>
                              </div>
                          </div>

                          <!-- Group: Gestión -->
                          <div class="space-y-3">
                              <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest pl-1">Gestión</h3>
                              <div class="grid grid-cols-3 gap-4">
                                  <a href="<?= base_url('sales') ?>" class="flex flex-col items-center gap-2 group">
                                      <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shadow-sm group-hover:scale-95 transition-transform group-hover:bg-emerald-100">
                                          <span class="material-icons text-3xl">storefront</span>
                                      </div>
                                      <span class="text-[11px] font-bold text-slate-600 text-center leading-tight">Ventas</span>
                                  </a>
                                  <a href="<?= base_url('inventory') ?>" class="flex flex-col items-center gap-2 group">
                                      <div class="w-16 h-16 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center shadow-sm group-hover:scale-95 transition-transform group-hover:bg-teal-100">
                                          <span class="material-icons text-3xl">inventory_2</span>
                                      </div>
                                      <span class="text-[11px] font-bold text-slate-600 text-center leading-tight">Inventario</span>
                                  </a>
                                  <a href="<?= base_url('printing') ?>" class="flex flex-col items-center gap-2 group">
                                      <div class="w-16 h-16 rounded-2xl bg-cyan-50 text-cyan-600 flex items-center justify-center shadow-sm group-hover:scale-95 transition-transform group-hover:bg-cyan-100">
                                          <span class="material-icons text-3xl">print</span>
                                      </div>
                                      <span class="text-[11px] font-bold text-slate-600 text-center leading-tight">Impresiones</span>
                                  </a>
                              </div>
                          </div>

                          <!-- Group: Sistema -->
                          <div class="space-y-3">
                              <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest pl-1">Sistema</h3>
                              <div class="grid grid-cols-3 gap-4">
                                  <a href="<?= base_url('ai') ?>" class="flex flex-col items-center gap-2 group">
                                      <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shadow-sm group-hover:scale-95 transition-transform group-hover:bg-emerald-100">
                                          <span class="material-icons text-3xl">psychology</span>
                                      </div>
                                      <span class="text-[11px] font-bold text-slate-600 text-center leading-tight">AI Assistant</span>
                                  </a>
                                  <a href="<?= base_url('audit') ?>" class="flex flex-col items-center gap-2 group">
                                      <div class="w-16 h-16 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center shadow-sm group-hover:scale-95 transition-transform group-hover:bg-amber-100">
                                          <span class="material-icons text-3xl">manage_search</span>
                                      </div>
                                      <span class="text-[11px] font-bold text-slate-600 text-center leading-tight">Bitácora</span>
                                  </a>
                                  <a href="<?= base_url('config') ?>" class="flex flex-col items-center gap-2 group">
                                      <div class="w-16 h-16 rounded-2xl bg-slate-50 text-slate-600 flex items-center justify-center shadow-sm group-hover:scale-95 transition-transform group-hover:bg-slate-100">
                                          <span class="material-icons text-3xl">settings</span>
                                      </div>
                                      <span class="text-[11px] font-bold text-slate-600 text-center leading-tight">Configuración</span>
                                  </a>
                              </div>
                          </div>
                          
                          <!-- Footer Info -->
                          <div class="text-center pt-4 pb-2 opacity-30 border-t border-slate-50 mt-2">
                              <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Fi-Hex Wallet v2.1</p>
                          </div>
                      </div>
                 </div>
            </div>

            <!-- Emerald Fintech Digital Card -->
            <div class="relative w-full rounded-[1.75rem] bg-gradient-to-br from-emerald-950 via-emerald-800 to-teal-950 p-4 sm:p-5 text-white shadow-xl shadow-emerald-950/25 border border-emerald-500/30 overflow-hidden transition-all duration-300"
                 x-show="compactLevel < 2"
                 :class="compactLevel === 1 ? 'py-3 px-4' : 'p-4 sm:p-5'">
                <!-- Ambient Glow Elements -->
                <div class="absolute -right-8 -top-8 w-36 h-36 bg-emerald-400/15 rounded-full blur-2xl pointer-events-none"></div>
                <div class="absolute -left-8 -bottom-8 w-32 h-32 bg-teal-400/10 rounded-full blur-xl pointer-events-none"></div>
                <div class="absolute right-1/3 bottom-0 w-24 h-24 bg-emerald-500/10 rounded-full blur-lg pointer-events-none"></div>

                <div class="relative z-10">
                    <!-- Top Row: Card Brand + Contactless + Golden Chip -->
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span class="font-bold tracking-widest uppercase text-emerald-200/90 text-[10px]" :class="compactLevel === 1 ? 'text-[9px]' : 'text-[10px]'">Fi-Hex Card</span>
                        </div>
                        
                        <!-- Contactless + Chip Icon -->
                        <div class="flex items-center gap-2">
                            <span class="material-icons text-emerald-300/60 text-sm rotate-90 select-none">wifi</span>
                            <div class="w-6 h-4.5 rounded bg-gradient-to-tr from-amber-300 via-amber-200 to-amber-400 shadow-sm border border-amber-400/60 flex flex-col justify-between p-0.5 opacity-90 select-none">
                                <div class="w-full h-px bg-amber-700/40"></div>
                                <div class="flex justify-between w-full h-1 border-y border-amber-700/30">
                                    <div class="w-1 h-full border-r border-amber-700/30"></div>
                                    <div class="w-1 h-full border-l border-amber-700/30"></div>
                                </div>
                                <div class="w-full h-px bg-amber-700/40"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Balance Row: Main Amount + USD Conversion (Never Truncated!) -->
                    <div class="my-1 cursor-pointer" @click="showMsg('Balance total: ' + formatMoney(stats.balance))">
                        <p class="text-[10px] uppercase font-semibold text-emerald-300/80 tracking-wide mb-0.5">Balance Total</p>
                        <div class="flex items-baseline flex-wrap gap-x-2 gap-y-1">
                            <h2 class="font-extrabold tracking-tight text-white leading-none text-2xl sm:text-[28px] break-all"
                                :class="compactLevel === 1 ? 'text-xl' : 'text-2xl sm:text-[28px]'"
                                x-text="formatMoney(stats.balance)"></h2>
                            <div class="inline-flex items-center gap-1 bg-emerald-500/20 backdrop-blur-md border border-emerald-400/30 px-2 py-0.5 rounded-full text-emerald-200 text-[11px] font-semibold">
                                <span class="text-[9px] opacity-75 font-normal">≈</span>
                                <span x-text="exchangeRate > 0 ? '$ ' + (stats.balance / exchangeRate).toFixed(2) : '$ 0.00'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Bar inside Card: Gasto Hoy & Active Account -->
                    <div class="pt-2.5 mt-2.5 border-t border-emerald-500/20 flex items-center justify-between text-[10px]">
                        <div class="flex items-center gap-1.5 cursor-pointer hover:opacity-80 transition-opacity" 
                             @click="showMsg('Gasto hoy: ' + formatMoney(stats.today_expense))">
                            <div class="w-4 h-4 rounded-full bg-rose-500/20 text-rose-300 flex items-center justify-center">
                                <span class="material-icons text-[10px]">trending_down</span>
                            </div>
                            <span class="text-emerald-200/70 font-medium">Gasto Hoy:</span>
                            <span class="font-bold text-rose-200" x-text="formatMoney(stats.today_expense)"></span>
                        </div>

                        <div class="flex items-center gap-1 text-emerald-300/80 font-medium bg-emerald-900/50 px-2 py-0.5 rounded-lg border border-emerald-500/20">
                            <span class="material-icons text-[12px] text-emerald-400">account_balance</span>
                            <span class="truncate max-w-[110px]" x-text="(accounts.find(a => a.id == selectedAccount)?.name || 'Cuenta')"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content (Cart Layout) -->
        <div class="flex-1 overflow-y-auto px-5 pb-4 space-y-4 no-scrollbar min-h-0 relative z-0">
            
            <!-- Cart Items -->
            <template x-if="mode === 'cart' && cart.length > 0">
                <div class="space-y-4">
                    <div class="flex justify-between items-end px-1">
                        <h3 class="font-bold text-slate-800 text-lg">Carrito Actual</h3>
                        <button @click="toggleMode()" class="text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition-colors px-3 py-1.5 rounded-full">Limpiar Todo</button>
                    </div>

                    <template x-for="(item, index) in cart" :key="index">
                        <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 relative group transition-transform active:scale-[0.99]">
                            <!-- Delete Action -->
                             <button @click="removeItem(index)" class="absolute -top-2 -right-2 bg-white text-rose-500 p-1.5 rounded-full shadow-md hover:bg-rose-50 transition-colors z-10">
                                <span class="material-icons text-sm block">close</span>
                             </button>
                            
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <p class="font-bold text-slate-800 text-lg leading-tight" x-text="item.name"></p>
                                    <p class="text-xs font-medium text-slate-400 mt-0.5" x-text="item.description || 'Sin descripción adicional'"></p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-slate-800 text-lg" x-text="formatMoney(item.price * item.quantity)"></p>
                                    <p class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full inline-block mt-1" x-text="item.currency === 'USD' ? item.price_usd + ' USD' : 'Bs Base'"></p>
                                </div>
                            </div>

                            <!-- Quantity Control -->
                            <div class="flex justify-between items-center pt-2 border-t border-slate-50">
                                 <div class="flex items-center bg-slate-50 rounded-xl p-1">
                                    <button @click="item.quantity > 1 ? item.quantity-- : removeItem(index)" class="w-8 h-8 rounded-lg bg-white shadow-sm text-slate-600 flex items-center justify-center active:scale-90 transition-transform">
                                        <span class="material-icons text-sm font-bold">remove</span>
                                    </button>
                                    <span class="font-bold text-sm w-8 text-center text-slate-700" x-text="item.quantity"></span>
                                    <button @click="item.quantity++" class="w-8 h-8 rounded-lg bg-gradient-to-r from-emerald-600 to-teal-600 shadow-md shadow-emerald-500/25 text-white flex items-center justify-center active:scale-90 transition-transform">
                                        <span class="material-icons text-sm font-bold">add</span>
                                    </button>
                                 </div>
                                 <span class="text-xs font-medium text-slate-400" x-text="formatMoney(item.price) + ' / unidad'"></span>
                            </div>
                        </div>
                    </template>

                    <div class="bg-gradient-to-br from-emerald-800 to-teal-950 text-white p-5 rounded-3xl shadow-xl shadow-emerald-900/30 mt-4 flex justify-between items-center relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-5 rounded-full -mr-10 -mt-10"></div>
                        <span class="text-emerald-200 font-medium">Total Estimado</span>
                        <span class="font-extrabold text-2xl relative z-10" x-text="formatMoney(cartTotal)"></span>
                    </div>
                </div>
            </template>
            
            <div class="h-full flex flex-col items-center justify-center gap-4 text-center py-10 opacity-60" x-show="mode === 'cart' && cart.length === 0">
                <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center text-slate-300">
                    <span class="material-icons text-4xl">shopping_bag</span>
                </div>
                <p class="text-slate-400 font-medium text-sm">Tu carrito está vacío</p>
            </div>

            <!-- Single Mode: Categorías Rápidas & Actividad Viva -->
            <div x-show="mode === 'single'" class="space-y-3 pt-0.5">
                
                <!-- Quick Category Carousel -->
                <div>
                    <div class="flex items-center justify-between mb-1.5 px-0.5">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                            <span class="material-icons text-xs text-emerald-600">bolt</span>
                            Acceso Rápido
                        </span>
                        <span class="text-[9px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full" 
                              x-text="categories.find(c => c.id == selectedCategory)?.name || 'Seleccione'"></span>
                    </div>
                    
                    <div class="flex items-center gap-2 overflow-x-auto no-scrollbar py-1 -mx-1 px-1">
                        <template x-for="cat in categories" :key="cat.id">
                            <button type="button" 
                                    @click="selectedCategory = cat.id"
                                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all duration-200 shrink-0 select-none active:scale-95 border"
                                    :class="selectedCategory == cat.id 
                                        ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white border-transparent shadow-md shadow-emerald-600/30 scale-[1.02]' 
                                        : 'bg-white hover:bg-slate-50 text-slate-700 border-slate-200/80 shadow-xs'">
                                <span class="material-icons text-sm" 
                                      :class="selectedCategory == cat.id ? 'text-white' : 'text-emerald-600'" 
                                      x-text="getCategoryIcon(cat.name, cat.icon)"></span>
                                <span x-text="cat.name"></span>
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
                            <div class="bg-white p-2.5 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between hover:border-emerald-200 hover:shadow-md transition-all group">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <!-- Category Avatar -->
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 shadow-xs"
                                         :class="{
                                             'bg-emerald-50 text-emerald-600': item.type === 'income' || item.type === 'return',
                                             'bg-rose-50 text-rose-500': item.type === 'expense',
                                             'bg-teal-50 text-teal-600': item.type === 'savings' || item.type === 'exchange',
                                             'bg-slate-100 text-slate-600': !['income','expense','savings','exchange','return'].includes(item.type)
                                         }">
                                        <span class="material-icons text-lg" x-text="getCategoryIcon(item.category_name, item.category_icon)"></span>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-bold text-slate-800 text-xs truncate leading-tight" 
                                           x-text="item.description || item.category_name || 'Transacción'"></p>
                                        <div class="flex items-center gap-1.5 text-[9px] text-slate-400 mt-0.5">
                                            <span class="font-semibold text-slate-500" x-text="item.account_name || 'Cuenta'"></span>
                                            <span>•</span>
                                            <span x-text="formatTime(item.created_at)"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right shrink-0 pl-2">
                                    <p class="font-extrabold text-xs tracking-tight"
                                       :class="item.type === 'income' || item.type === 'return' ? 'text-emerald-600' : 'text-slate-800'"
                                       x-text="(item.type === 'income' || item.type === 'return' ? '+ ' : '- ') + formatMoney(item.amount)"></p>
                                    <p class="text-[9px] text-slate-400 font-medium" 
                                       x-show="item.amount_usd > 0"
                                       x-text="'$ ' + parseFloat(item.amount_usd).toFixed(2)"></p>
                                </div>
                            </div>
                        </template>

                        <!-- Empty state if no recent items -->
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

            <div class="px-4 pb-4 pt-0" :class="compactLevel > 0 ? 'p-2' : 'px-4 pb-4 pt-0'">
                <!-- Main Input & Display Card -->
                <div class="mb-2 relative bg-slate-50/80 rounded-2xl p-2.5 border border-slate-100 shadow-inner">
                     <div class="flex flex-col">
                        <div class="flex justify-between items-center mb-1">
                              <div class="flex items-center gap-1.5 flex-1 mr-2">
                                  <span class="material-icons text-slate-400 text-xs">edit_note</span>
                                  <input type="text" x-model="description" placeholder="Añadir nota o concepto..." class="w-full text-xs font-semibold text-slate-700 placeholder-slate-400 outline-none bg-transparent">
                              </div>
                              <button type="button" @click="currency = (currency === 'Bs' ? 'USD' : 'Bs')"
                                      class="px-2 py-0.5 rounded-full text-[9px] font-extrabold uppercase tracking-wider transition-all duration-200 border"
                                      :class="currency === 'USD' ? 'bg-emerald-500 text-white border-emerald-400 shadow-xs' : 'bg-white text-emerald-800 border-emerald-200 shadow-xs'"
                                      x-text="currency"></button>
                        </div>
                        <!-- Main Number Display -->
                        <div class="flex justify-between items-baseline group relative">
                             <div class="flex flex-col leading-none mr-2 shrink-0">
                                <span class="text-xs font-bold text-slate-400 select-none uppercase tracking-wide" x-text="currency === 'USD' ? '$' : 'Bs'"></span>
                                <span x-show="parseFloat(amount) > 0" class="text-[10px] font-bold text-emerald-700 bg-emerald-100/70 px-1.5 py-0.5 rounded-md mt-0.5" x-text="'≈ ' + (currency === 'Bs' ? '$ ' : 'Bs ') + formatMoney(currentConversion)"></span>
                             </div>
                             <div class="font-black text-slate-900 tracking-tight truncate leading-tight w-full text-right transition-all font-mono" 
                                  :class="{
                                      'text-3xl sm:text-4xl': compactLevel === 0,
                                      'text-2xl': compactLevel === 1,
                                      'text-xl': compactLevel === 2
                                  }"
                                  x-text="display"></div>
                        </div>
                     </div>
                </div>

                <div class="grid grid-cols-2 gap-2 mb-2">
                    <!-- Account Select -->
                    <div class="relative" @click.outside="showAccountMenu = false">
                        <button @click="showAccountMenu = !showAccountMenu" class="w-full px-2.5 flex items-center justify-between rounded-xl bg-emerald-50/70 hover:bg-emerald-100/60 border border-emerald-200/80 text-emerald-950 text-[10px] font-bold transition-all shadow-xs"
                                :class="compactLevel > 0 ? 'h-7 text-[9px]' : 'h-8 text-[10px]'">
                            <div class="flex items-center gap-1.5 overflow-hidden">
                                 <span class="material-icons text-emerald-600 text-[14px]">account_balance</span>
                                 <div class="flex items-center gap-1 overflow-hidden">
                                     <span class="truncate" x-text="accounts.find(a => a.id == selectedAccount)?.name || 'Cuenta'"></span>
                                     <span class="text-[8px] font-bold text-emerald-700 bg-white/90 px-1 py-px rounded shrink-0 shadow-xs" 
                                           x-text="formatMoney(accounts.find(a => a.id == selectedAccount)?.balance || 0, accounts.find(a => a.id == selectedAccount)?.currency)"></span>
                                 </div>
                            </div>
                            <span class="material-icons text-emerald-600 text-sm transition-transform" :class="{'rotate-180': showAccountMenu}">expand_more</span>
                        </button>
                        <div x-show="showAccountMenu" class="absolute bottom-full left-0 w-full mb-1 bg-white rounded-xl shadow-xl border border-slate-100 p-1 z-[80] max-h-40 overflow-y-auto">
                             <template x-for="acc in accounts" :key="acc.id">
                                 <div @click="selectedAccount = acc.id; showAccountMenu = false" class="p-2 hover:bg-emerald-50/60 result-item rounded flex justify-between items-center cursor-pointer group">
                                     <span class="text-[10px] font-bold text-slate-700 group-hover:text-emerald-950" x-text="acc.name"></span>
                                     <span class="text-[9px] font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded shadow-sm" x-text="formatMoney(acc.balance, acc.currency)"></span>
                                 </div>
                             </template>
                        </div>
                    </div>
                    
                    <!-- Right Column: Category + Calendar -->
                    <div class="flex gap-2">
                        <!-- Category Select -->
                        <div class="relative flex-1" @click.outside="showCategoryMenu = false">
                             <button @click="showCategoryMenu = !showCategoryMenu" class="w-full px-2 flex items-center justify-between rounded-xl bg-slate-50 hover:bg-slate-100/80 border border-slate-200 text-slate-700 text-[10px] font-bold transition-all shadow-xs"
                                     :class="compactLevel > 0 ? 'h-7 text-[9px]' : 'h-8 text-[10px]'">
                                <div class="flex items-center gap-1.5 overflow-hidden">
                                     <span class="material-icons text-slate-400 text-[14px]">category</span>
                                     <span class="truncate" x-text="categories.find(c => c.id == selectedCategory)?.name || 'Categoría'"></span>
                                </div>
                                <span class="material-icons text-slate-400 text-sm transition-transform" :class="{'rotate-180': showCategoryMenu}">expand_more</span>
                            </button>
                             <div x-show="showCategoryMenu" class="absolute bottom-full left-0 w-full mb-1 bg-white rounded-xl shadow-xl border border-slate-100 p-1 z-[80] max-h-40 overflow-y-auto">
                                 <template x-for="cat in categories" :key="cat.id">
                                     <div @click="selectedCategory = cat.id; showCategoryMenu = false" class="p-2 hover:bg-slate-50 rounded flex items-center gap-1.5 text-[10px] font-bold text-slate-700 cursor-pointer">
                                         <span class="material-icons text-xs text-slate-400" x-text="getCategoryIcon(cat.name, cat.icon)"></span>
                                         <span x-text="cat.name"></span>
                                     </div>
                                 </template>
                            </div>
                        </div>

                        <!-- Date Timer Toggle -->
                        <div class="relative flex-none" :class="compactLevel > 0 ? 'w-7 h-7' : 'w-8 h-8'">
                             <div :class="customDate ? 'bg-emerald-100 text-emerald-700 animate-pulse' : 'bg-slate-50 text-slate-400'" class="w-full h-full rounded-xl flex items-center justify-center border border-slate-200 shadow-xs transition-all relative">
                                 <span class="material-icons text-sm" x-text="customDate ? 'timer' : 'calendar_today'"></span>
                                 <div x-show="customDate" class="absolute -top-1 -right-1 w-2 h-2 bg-rose-500 rounded-full"></div>
                             </div>
                             <input type="datetime-local" @change="startTimer($event.target.value)" class="absolute inset-0 opacity-0 z-10 cursor-pointer w-full h-full">
                        </div>

                         <!-- Bubble Toggle -->
                         <button @click="showBubbles = !showBubbles" 
                                 class="flex-none rounded-xl flex items-center justify-center border transition-all shadow-xs active:scale-95"
                                 :class="[compactLevel > 0 ? 'w-7 h-7' : 'w-8 h-8', showBubbles ? 'bg-emerald-100 text-emerald-700 border-emerald-300' : 'bg-slate-50 text-slate-400 border-slate-200']">
                            <span class="material-icons text-sm">bubble_chart</span>
                         </button>
                    </div>
                </div>

                 <!-- NEGOCIO: Purchase Mode UI -->
                <div x-show="owner === 'Negocio'" x-transition class="mb-2 bg-purple-50/80 backdrop-blur rounded-xl p-3 border border-purple-100 shadow-sm">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-[10px] font-bold text-purple-800 uppercase tracking-wider flex items-center gap-1">
                            <span class="material-icons text-xs">inventory_2</span>
                            Compra de Inventario
                        </span>
                        <div class="flex items-center gap-2">
                            <span x-show="enablePurchase" class="text-[9px] text-purple-400">Total: <b x-text="formatMoney(purchaseQuantity * purchaseCost)"></b></span>
                            <button @click="togglePurchaseMode()" 
                                     class="text-[9px] font-bold px-3 py-1 rounded-full transition-all shadow-sm active:scale-95"
                                     :class="enablePurchase ? 'bg-purple-600 text-white shadow-purple-200' : 'bg-white text-purple-400 border border-purple-200'">
                                <span x-text="enablePurchase ? 'Activo' : 'Activar'"></span>
                            </button>
                        </div>
                    </div>

                    <div x-show="enablePurchase" class="space-y-2">
                        <!-- Product Search -->
                        <div class="relative" @click.outside="showInvMenu = false">
                            <div class="flex items-center gap-2 bg-white border border-purple-200 rounded-lg p-2 shadow-sm focus-within:border-purple-400 transition-colors" @click="showInvMenu = !showInvMenu">
                                <span class="material-icons text-slate-400 text-sm">search</span>
                                <input type="text" x-model="invSearch" @keydown.stop placeholder="Buscar o Crear producto..." 
                                       class="w-full text-[11px] font-bold text-slate-700 outline-none placeholder-slate-300">
                                <button x-show="purchaseItem" @click.stop="purchaseItem = null; invSearch = ''; purchaseCost = 0" class="text-rose-400 hover:text-rose-600">
                                    <span class="material-icons text-sm">close</span>
                                </button>
                            </div>
                            
                            <!-- Search Dropdown -->
                            <div x-show="showInvMenu && (invSearch.length > 0 || filteredInventory.length > 0)" 
                                 class="absolute bottom-full left-0 w-full mb-1 bg-white rounded-xl shadow-xl border border-purple-100 p-1 z-[90] max-h-40 overflow-y-auto">
                                
                                <template x-for="item in filteredInventory" :key="item.id">
                                    <div @click="selectInvItem(item)" class="p-2 hover:bg-purple-50 rounded flex justify-between items-center cursor-pointer group border-b border-slate-50 last:border-0">
                                        <div>
                                             <p class="text-[11px] font-bold text-slate-700 group-hover:text-purple-700" x-text="item.name"></p>
                                             <p class="text-[9px] text-slate-400" x-text="'Stock: ' + item.stock + ' ' + item.unit"></p>
                                        </div>
                                        <div class="text-right">
                                             <span class="text-[10px] font-bold text-slate-600 block" x-text="'$' + parseFloat(item.cost).toFixed(2)"></span>
                                        </div>
                                    </div>
                                </template>
                                
                                <!-- Quick Create Option -->
                                <div x-show="invSearch.length > 0 && !filteredInventory.some(i => i.name.toLowerCase() === invSearch.toLowerCase())" 
                                     @click="quickCreateItem()"
                                     class="p-2 bg-purple-50 hover:bg-purple-100 rounded text-center cursor-pointer flex items-center justify-center gap-1 text-purple-700 font-bold text-[10px]">
                                    <span class="material-icons text-xs">add</span>
                                    <span x-text="'Crear: ' + invSearch"></span>
                                </div>
                                
                                <div x-show="filteredInventory.length === 0 && invSearch.length === 0" class="p-2 text-center text-[10px] text-slate-400">
                                     Escribe para buscar...
                                </div>
                            </div>
                        </div>

                        <!-- Qty & Cost Inputs -->
                        <div class="flex gap-2" x-show="purchaseItem">
                            <div class="flex-1 bg-white border border-purple-200 rounded-lg p-1.5 flex flex-col items-center relative overflow-hidden">
                                <span class="text-[8px] font-bold text-purple-300 uppercase absolute top-1 left-2">Cantidad</span>
                                <input type="number" x-model="purchaseQuantity" @input="calcTotal()" @keydown.stop class="w-full text-sm font-black text-slate-700 outline-none text-center pt-3 pb-1">
                            </div>
                            <div class="flex-1 bg-white border border-purple-200 rounded-lg p-1.5 flex flex-col items-center relative overflow-hidden">
                                <span class="text-[8px] font-bold text-purple-300 uppercase absolute top-1 left-2">Costo ($)</span>
                                <input type="number" x-model="purchaseCost" @input="calcTotal()" @keydown.stop class="w-full text-sm font-black text-slate-700 outline-none text-center pt-3 pb-1">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Controls Row: Type & Currency & Features -->
                <div class="flex mb-2 gap-2 h-7">
                     <div class="flex-[3] flex bg-slate-100 p-0.5 rounded-lg shadow-inner">
                        <button @click="type = 'expense'" :class="type === 'expense' ? 'bg-white shadow-xs text-rose-600 font-extrabold' : 'text-slate-500 font-semibold'" class="flex-1 rounded-md py-0 text-[9px] transition-all">Gasto</button>
                        <button @click="type = 'income'" :class="type === 'income' ? 'bg-white shadow-xs text-emerald-600 font-extrabold' : 'text-slate-500 font-semibold'" class="flex-1 rounded-md py-0 text-[9px] transition-all">Ingreso</button>
                        <button @click="type = 'savings'" :class="type === 'savings' ? 'bg-white shadow-xs text-teal-700 font-extrabold' : 'text-slate-500 font-semibold'" class="flex-1 rounded-md py-0 text-[9px] transition-all">Ahorro</button>
                     </div>
                     <div class="flex-1 flex bg-slate-100 p-0.5 rounded-lg shadow-inner">
                        <button @click="currency = 'Bs'" :class="currency === 'Bs' ? 'bg-white shadow-xs text-emerald-700 font-extrabold' : 'text-slate-500 font-semibold'" class="flex-1 rounded-md py-0 text-[9px] transition-all">Bs</button>
                        <button @click="currency = 'USD'" :class="currency === 'USD' ? 'bg-white shadow-xs text-emerald-600 font-extrabold' : 'text-slate-500 font-semibold'" class="flex-1 rounded-md py-0 text-[9px] transition-all">$</button>
                     </div>
                     <button @click="showDivisasModal = true" class="px-2 rounded-lg font-bold text-[9px] border border-slate-200/80 bg-pink-50 hover:bg-pink-100/80 text-pink-500 shadow-xs transition-colors">
                        <span class="material-icons text-[16px]">savings</span>
                     </button>
                     <button @click="toggleMode()" class="px-2 rounded-lg font-bold text-[9px] border border-slate-200/80 shadow-xs transition-colors" 
                             :class="mode === 'cart' ? 'bg-emerald-50 text-emerald-700' : 'text-slate-500 bg-white hover:bg-slate-50'">
                        <span class="material-icons text-[16px]" x-text="mode === 'cart' ? 'shopping_cart' : 'payments'"></span>
                     </button>
                     <button @click="toggleCompactMode()" class="px-2 rounded-lg font-bold text-[9px] border border-slate-200/80 shadow-xs transition-colors" 
                             :class="compactLevel > 0 ? 'bg-emerald-50 text-emerald-700' : 'text-slate-500 bg-white hover:bg-slate-50'">
                        <span class="material-icons text-[16px]" x-text="compactLevel === 0 ? 'unfold_less' : (compactLevel === 1 ? 'unfold_more' : 'close_fullscreen')"></span>
                     </button>
                </div>

                <!-- Tactile Keypad -->
                <div class="grid grid-cols-4 gap-1.5 h-auto mb-2">
                    <button @click="press(7)" class="bg-slate-50 hover:bg-slate-100 active:bg-slate-200 rounded-xl text-slate-800 font-bold border border-slate-100/80 shadow-xs active:scale-95 transition-all" 
                            :class="compactLevel === 0 ? 'h-11 text-lg' : (compactLevel === 1 ? 'h-9 text-sm' : 'h-7 text-xs')">7</button>
                    <button @click="press(8)" class="bg-slate-50 hover:bg-slate-100 active:bg-slate-200 rounded-xl text-slate-800 font-bold border border-slate-100/80 shadow-xs active:scale-95 transition-all" 
                            :class="compactLevel === 0 ? 'h-11 text-lg' : (compactLevel === 1 ? 'h-9 text-sm' : 'h-7 text-xs')">8</button>
                    <button @click="press(9)" class="bg-slate-50 hover:bg-slate-100 active:bg-slate-200 rounded-xl text-slate-800 font-bold border border-slate-100/80 shadow-xs active:scale-95 transition-all" 
                            :class="compactLevel === 0 ? 'h-11 text-lg' : (compactLevel === 1 ? 'h-9 text-sm' : 'h-7 text-xs')">9</button>
                    <button @click="pressOperation('+')" class="bg-emerald-50 hover:bg-emerald-100/80 active:bg-emerald-200 rounded-xl text-emerald-700 font-black border border-emerald-100 shadow-xs active:scale-95 transition-all" 
                            :class="compactLevel === 0 ? 'h-11 text-xl' : (compactLevel === 1 ? 'h-9 text-base' : 'h-7 text-sm')">+</button>

                    <button @click="press(4)" class="bg-slate-50 hover:bg-slate-100 active:bg-slate-200 rounded-xl text-slate-800 font-bold border border-slate-100/80 shadow-xs active:scale-95 transition-all" 
                            :class="compactLevel === 0 ? 'h-11 text-lg' : (compactLevel === 1 ? 'h-9 text-sm' : 'h-7 text-xs')">4</button>
                    <button @click="press(5)" class="bg-slate-50 hover:bg-slate-100 active:bg-slate-200 rounded-xl text-slate-800 font-bold border border-slate-100/80 shadow-xs active:scale-95 transition-all" 
                            :class="compactLevel === 0 ? 'h-11 text-lg' : (compactLevel === 1 ? 'h-9 text-sm' : 'h-7 text-xs')">5</button>
                    <button @click="press(6)" class="bg-slate-50 hover:bg-slate-100 active:bg-slate-200 rounded-xl text-slate-800 font-bold border border-slate-100/80 shadow-xs active:scale-95 transition-all" 
                            :class="compactLevel === 0 ? 'h-11 text-lg' : (compactLevel === 1 ? 'h-9 text-sm' : 'h-7 text-xs')">6</button>
                    <button @click="clear()" class="bg-rose-50 hover:bg-rose-100 active:bg-rose-200 text-rose-600 rounded-xl font-black border border-rose-100/80 shadow-xs active:scale-95 transition-all" 
                            :class="compactLevel === 0 ? 'h-11 text-sm' : (compactLevel === 1 ? 'h-9 text-xs' : 'h-7 text-[10px]')">C</button>

                    <button @click="press(1)" class="bg-slate-50 hover:bg-slate-100 active:bg-slate-200 rounded-xl text-slate-800 font-bold border border-slate-100/80 shadow-xs active:scale-95 transition-all" 
                            :class="compactLevel === 0 ? 'h-11 text-lg' : (compactLevel === 1 ? 'h-9 text-sm' : 'h-7 text-xs')">1</button>
                    <button @click="press(2)" class="bg-slate-50 hover:bg-slate-100 active:bg-slate-200 rounded-xl text-slate-800 font-bold border border-slate-100/80 shadow-xs active:scale-95 transition-all" 
                            :class="compactLevel === 0 ? 'h-11 text-lg' : (compactLevel === 1 ? 'h-9 text-sm' : 'h-7 text-xs')">2</button>
                    <button @click="press(3)" class="bg-slate-50 hover:bg-slate-100 active:bg-slate-200 rounded-xl text-slate-800 font-bold border border-slate-100/80 shadow-xs active:scale-95 transition-all" 
                            :class="compactLevel === 0 ? 'h-11 text-lg' : (compactLevel === 1 ? 'h-9 text-sm' : 'h-7 text-xs')">3</button>
                    
                    <button @click="submit()" :disabled="!owner" class="row-span-2 rounded-2xl text-white shadow-lg flex items-center justify-center active:scale-95 transition-all"
                            :class="owner ? 'bg-gradient-to-br from-emerald-500 via-emerald-600 to-teal-600 hover:brightness-105 shadow-emerald-600/35' : 'bg-slate-200 shadow-none text-slate-400 cursor-not-allowed'">
                        <span class="material-icons text-2xl font-bold" x-text="mode === 'single' || mode === 'cart' && cart.length > 0 ? 'check' : 'add'"></span>
                    </button>

                    <button @click="press(0)" class="col-span-2 bg-slate-50 hover:bg-slate-100 active:bg-slate-200 rounded-xl text-slate-800 font-bold border border-slate-100/80 shadow-xs active:scale-95 transition-all" 
                            :class="compactLevel === 0 ? 'h-11 text-lg' : (compactLevel === 1 ? 'h-9 text-sm' : 'h-7 text-xs')">0</button>
                    <button @click="press('.')" class="bg-slate-50 hover:bg-slate-100 active:bg-slate-200 rounded-xl text-slate-800 font-bold border border-slate-100/80 shadow-xs active:scale-95 transition-all" 
                            :class="compactLevel === 0 ? 'h-11 text-lg' : (compactLevel === 1 ? 'h-9 text-sm' : 'h-7 text-xs')">.</button>
                </div>

                <!-- Modernized Owner Selector Chips -->
                <div class="flex space-x-1.5 pt-0.5">
                    <button @click="owner = (owner === 'Arianny' ? null : 'Arianny')" 
                            :class="owner === 'Arianny' 
                                ? 'bg-gradient-to-r from-emerald-700 to-teal-800 text-white border-transparent shadow-md shadow-emerald-900/20 ring-2 ring-emerald-500/30 scale-[1.02]' 
                                : 'bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200/80 hover:border-slate-300'" 
                            class="flex-1 py-1.5 px-2 rounded-xl font-bold text-[10px] transition-all flex items-center justify-center gap-1.5 active:scale-95">
                        <span class="w-4 h-4 rounded-full flex items-center justify-center text-[8px] font-black"
                              :class="owner === 'Arianny' ? 'bg-white/20 text-white' : 'bg-pink-100 text-pink-700'">Ar</span>
                        <span>Arianny</span>
                    </button>

                    <button @click="owner = (owner === 'Anthony' ? null : 'Anthony')" 
                            :class="owner === 'Anthony' 
                                ? 'bg-gradient-to-r from-emerald-700 to-teal-800 text-white border-transparent shadow-md shadow-emerald-900/20 ring-2 ring-emerald-500/30 scale-[1.02]' 
                                : 'bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200/80 hover:border-slate-300'" 
                            class="flex-1 py-1.5 px-2 rounded-xl font-bold text-[10px] transition-all flex items-center justify-center gap-1.5 active:scale-95">
                        <span class="w-4 h-4 rounded-full flex items-center justify-center text-[8px] font-black"
                              :class="owner === 'Anthony' ? 'bg-white/20 text-white' : 'bg-emerald-100 text-emerald-800'">An</span>
                        <span>Anthony</span>
                    </button>

                    <button @click="owner = (owner === 'Negocio' ? null : 'Negocio')" 
                            :class="owner === 'Negocio' 
                                ? 'bg-gradient-to-r from-emerald-700 to-teal-800 text-white border-transparent shadow-md shadow-emerald-900/20 ring-2 ring-emerald-500/30 scale-[1.02]' 
                                : 'bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200/80 hover:border-slate-300'" 
                            class="flex-1 py-1.5 px-2 rounded-xl font-bold text-[10px] transition-all flex items-center justify-center gap-1.5 active:scale-95">
                        <span class="w-4 h-4 rounded-full flex items-center justify-center text-[8px] font-black"
                              :class="owner === 'Negocio' ? 'bg-white/20 text-white' : 'bg-purple-100 text-purple-700'">
                            <span class="material-icons text-[10px]">store</span>
                        </span>
                        <span>Negocio</span>
                    </button>
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

        <!-- Divisas Modal -->
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

        <!-- Recent Bubbles Overlay -->
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
                showDivisasModal: false,
                divisasMode: 'exchange',
                divSource: '',
                divDest: '',
                divAmountBs: '',
                divAmountUsd: '',
                accounts: <?= json_encode($accounts ?? []) ?>,
                categories: <?= json_encode($categories ?? []) ?>,
                
                // Bubbles
                showBubbles: false,
                recentBubbles: [],
                
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
                customDate: null,

                dateTimer: null,
                compactLevel: 0,
                
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
                    this.cart = [];
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
                            return;
                     }

                     // Case 2: Submit Action (Single or Cart Checkout)
                     if (!this.owner) return this.showMsg('Seleccione quién registra');
                     
                     if (this.mode === 'single' && enteredValue <= 0) return;
                     if (this.mode === 'cart' && this.cart.length === 0) return;

                     // Fix: Update amount to calculated value so Modal shows number, not "6+7"
                     if (this.mode === 'single') {
                         this.amount = enteredValue.toString();
                         this.display = this.amount;
                     }

                     this.showConfirmModal = true;
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
                             // Append details to description if auto-generated one is not preferred, or let backend handle it.
                             // Backend handles description if empty, but here we might want to preserve user description + details.
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
                            this.addBubbleUI(payload); // Add bubble
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
                
                addBubbleUI(payload) {
                    const categoryName = this.categories.find(c => c.id == payload.category_id)?.name || 'General';
                    const newBubble = {
                        id: Date.now(),
                        amount: payload.amount, 
                        currency: 'Bs', // Default
                        category: categoryName,
                        type: payload.type,
                        desc: payload.description,
                        expanded: false
                    };
                    
                    // Handle USD currency display override
                    // If the payload was constructed from USD input, let's try to show USD if preferable, 
                    // but the payload to save only has amount (Bs) and amount_usd (USD).
                    
                    if (this.currency === 'USD' && payload.amount_usd > 0) {
                        newBubble.amount = payload.amount_usd;
                        newBubble.currency = 'USD';
                    }
                    
                    this.recentBubbles.unshift(newBubble);
                    // if (this.recentBubbles.length > 5) this.recentBubbles.pop(); // Remove limit or increase it since they are now scrollable? Let's keep a reasonable history like 20
                    if (this.recentBubbles.length > 30) this.recentBubbles.pop();
                    
                    // Auto-show bubbles if not already shown? 
                    // User said "quiero que al ir ingresando salgan burbujas" -> Implies auto-show or just show if active.
                    // If button is off, they shouldn't show.
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

                calcUsd() {
                    if (this.divAmountBs && this.exchangeRate > 0) {
                       this.divAmountUsd = (parseFloat(this.divAmountBs) / this.exchangeRate).toFixed(2);
                    }
                },

                calculateImplicitRate() {
                    if (this.divAmountBs > 0 && this.divAmountUsd > 0) {
                        return (parseFloat(this.divAmountBs) / parseFloat(this.divAmountUsd)).toFixed(2) + ' Bs/$';
                    }
                    return '-';
                },

                async submitDivisas() {
                    if(!this.divSource || !this.divDest) return this.showMsg('Seleccione Cuentas');
                    
                    let payload = {
                        type: this.divisasMode,
                        account_id: this.divSource,
                        destination_account_id: this.divDest,
                        owner: this.owner,
                        category_id: this.selectedCategory // Default category
                    };

                    if (this.divisasMode === 'exchange') {
                         if(!this.divAmountBs || !this.divAmountUsd) return this.showMsg('Ingrese Montos');
                         payload.amount = parseFloat(this.divAmountBs);
                         payload.amount_usd = parseFloat(this.divAmountUsd);
                         payload.exchange_rate = (payload.amount / payload.amount_usd).toFixed(4); // Actual rate
                    } else {
                         if(!this.divAmountUsd) return this.showMsg('Ingrese Monto');
                         payload.amount = 0;
                         payload.amount_usd = parseFloat(this.divAmountUsd);
                         payload.exchange_rate = this.exchangeRate;
                    }

                    await this.sendData(payload);
                    this.showDivisasModal = false;
                    this.divAmountBs = '';
                    this.divAmountUsd = '';
                    this.divSource = '';
                    this.divDest = '';
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
                    // Note: Backspace usually deletes last char, but in calculator "C" clears all. 
                    // Let's make Backspace delete last char (if we had that function) or Clear all? 
                    // Current app only has "Clear All" (C).
                    if (e.key === 'Escape' || e.key === 'Delete' || e.key === 'Backspace') {
                        this.clear();
                        return;
                    }
                }
            }
        }
    </script>
</body>
</html>
