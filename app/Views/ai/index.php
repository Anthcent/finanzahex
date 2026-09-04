<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>AI Assistant - Fi-Hex Wallet</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
        .safe-top { padding-top: max(12px, env(safe-area-inset-top)); }
        .safe-bottom { padding-bottom: max(16px, env(safe-area-inset-bottom)); }
        .customize-scrollbar::-webkit-scrollbar { width: 4px; height: 4px; }
        .customize-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .customize-scrollbar::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, 0.3); border-radius: 9999px; }
        .message-enter { animation: slideIn 0.25s cubic-bezier(0.16, 1, 0.3, 1); }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(12px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .toast-slide { animation: toastSlide 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
        @keyframes toastSlide {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex flex-col antialiased selection:bg-emerald-500 selection:text-white" x-data="aiApp()">
    
    <!-- Executive Header -->
    <header class="bg-gradient-to-r from-emerald-950 via-slate-900 to-teal-950 border-b border-emerald-500/20 sticky top-0 z-40 backdrop-blur-xl bg-opacity-95 safe-top shadow-lg shadow-black/20">
        <div class="max-w-5xl mx-auto px-4 py-3 sm:py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="<?= base_url() ?>" class="w-10 h-10 rounded-xl bg-white/10 hover:bg-white/15 active:scale-95 border border-white/10 flex items-center justify-center text-slate-200 hover:text-white transition-all shadow-inner">
                    <span class="material-icons text-xl">arrow_back</span>
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="font-extrabold text-base sm:text-lg text-white tracking-tight leading-none flex items-center">
                            AI Asistente
                        </h1>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            Gemini Pro
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-400 font-medium mt-0.5">Diagnóstico y analítica financiera inteligente</p>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <button @click="showHistory = !showHistory" 
                        class="w-10 h-10 rounded-xl bg-slate-800/80 hover:bg-slate-700/80 active:scale-95 border border-slate-700/60 flex items-center justify-center text-slate-300 hover:text-white transition-all relative" 
                        title="Historial de Chats">
                    <span class="material-icons text-lg">history</span>
                    <span x-show="savedConversations.length > 0" class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-emerald-400"></span>
                </button>
                <button @click="showApiKeyModal = true" 
                        class="w-10 h-10 rounded-xl bg-slate-800/80 hover:bg-slate-700/80 active:scale-95 border border-slate-700/60 flex items-center justify-center text-slate-300 hover:text-white transition-all" 
                        title="Configuración API">
                    <span class="material-icons text-lg">settings</span>
                </button>
            </div>
        </div>
    </header>
    
    <!-- History Slide-out Drawer -->
    <div x-show="showHistory" x-cloak class="fixed inset-0 z-50 flex justify-end">
        <!-- Backdrop -->
        <div x-show="showHistory" 
             x-transition:enter="transition ease-out duration-300" 
             x-transition:enter-start="opacity-0" 
             x-transition:enter-end="opacity-100" 
             x-transition:leave="transition ease-in duration-200" 
             x-transition:leave-start="opacity-100" 
             x-transition:leave-end="opacity-0" 
             class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm" 
             @click="showHistory = false"></div>

        <!-- Sidebar Content -->
        <div x-show="showHistory" 
             x-transition:enter="transition ease-out duration-300" 
             x-transition:enter-start="translate-x-full" 
             x-transition:enter-end="translate-x-0" 
             x-transition:leave="transition ease-in duration-200" 
             x-transition:leave-start="translate-x-0" 
             x-transition:leave-end="translate-x-full" 
             class="relative w-full max-w-sm bg-slate-900 border-l border-slate-800 shadow-2xl h-full flex flex-col z-10 text-slate-100 safe-top safe-bottom">
            
            <div class="p-4 sm:p-5 border-b border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="material-icons text-emerald-400 text-xl">history</span>
                    <h2 class="font-extrabold text-sm sm:text-base text-white">Sesiones Guardadas</h2>
                </div>
                <button @click="showHistory = false" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-slate-300 hover:text-white transition-colors">
                    <span class="material-icons text-sm">close</span>
                </button>
            </div>
            
            <div class="flex-1 overflow-y-auto p-4 space-y-2.5 customize-scrollbar">
                <template x-for="conv in savedConversations" :key="conv.id">
                    <div class="bg-slate-800/80 hover:bg-slate-800 border border-slate-700/60 hover:border-emerald-500/40 rounded-xl p-3 transition-all cursor-pointer relative group">
                        <div @click="confirmLoad(conv.id)" class="pr-8">
                            <p class="font-bold text-white text-xs sm:text-sm truncate" x-text="conv.title"></p>
                            <p class="text-[10px] text-slate-400 mt-1 flex items-center gap-1">
                                <span class="material-icons text-[12px]">schedule</span>
                                <span x-text="formatDate(conv.created_at)"></span>
                            </p>
                        </div>
                        <button @click.stop="deleteConv(conv.id)" class="absolute top-3 right-3 opacity-70 group-hover:opacity-100 text-slate-400 hover:text-rose-400 transition p-1">
                            <span class="material-icons text-[16px]">delete</span>
                        </button>
                    </div>
                </template>
                
                <div x-show="savedConversations.length === 0" class="text-center py-16 text-slate-500">
                    <span class="material-icons text-4xl mb-2 text-slate-600 block">chat_bubble_outline</span>
                    <p class="text-xs font-semibold">No hay sesiones guardadas</p>
                    <p class="text-[11px] text-slate-500 mt-1">Guarda cualquier análisis con el botón flotante.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Toast Notifications -->
    <div class="fixed top-5 right-4 left-4 sm:left-auto sm:w-80 z-50 space-y-2 pointer-events-none">
        <template x-for="(toast, idx) in toasts" :key="toast.id || idx">
            <div class="toast-slide pointer-events-auto bg-slate-800 border shadow-2xl rounded-2xl p-3.5 flex items-center gap-3 backdrop-blur-md"
                 :class="{
                     'border-emerald-500/50 bg-emerald-950/80 text-emerald-200': toast.type === 'success',
                     'border-rose-500/50 bg-rose-950/80 text-rose-200': toast.type === 'error',
                     'border-blue-500/50 bg-blue-950/80 text-blue-200': toast.type === 'info',
                     'border-amber-500/50 bg-amber-950/80 text-amber-200': toast.type === 'warning'
                 }">
                <span class="material-icons text-lg" 
                      x-text="toast.type === 'success' ? 'check_circle' : toast.type === 'error' ? 'error' : toast.type === 'info' ? 'info' : 'warning'">
                </span>
                <p class="flex-1 text-xs font-bold leading-snug" x-text="toast.message"></p>
                <button @click="removeToast(idx)" class="text-slate-400 hover:text-white p-1">
                    <span class="material-icons text-xs">close</span>
                </button>
            </div>
        </template>
    </div>

    <!-- API Key Bottom Sheet Modal -->
    <div x-show="showApiKeyModal" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <!-- Backdrop -->
        <div x-show="showApiKeyModal" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-950/80 backdrop-blur-md" 
             @click="showApiKeyModal = false"></div>
        
        <!-- Sheet Card -->
        <div x-show="showApiKeyModal" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-y-full sm:translate-y-4 sm:scale-95 opacity-0"
             x-transition:enter-end="translate-y-0 sm:scale-100 opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-y-0 sm:scale-100 opacity-100"
             x-transition:leave-end="translate-y-full sm:translate-y-4 sm:scale-95 opacity-0"
             class="relative bg-slate-900 border border-slate-800 w-full sm:max-w-md rounded-t-[2.5rem] sm:rounded-3xl shadow-2xl p-5 sm:p-6 z-10 text-slate-100 safe-bottom">
            
            <div class="w-12 h-1.5 bg-slate-700 rounded-full mx-auto mb-4 sm:hidden"></div>

            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center">
                        <span class="material-icons text-lg">vpn_key</span>
                    </div>
                    <h2 class="text-base font-extrabold text-white">Configurar Gemini API Key</h2>
                </div>
                <button @click="showApiKeyModal = false" class="w-7 h-7 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-slate-300">
                    <span class="material-icons text-xs">close</span>
                </button>
            </div>

            <p class="text-xs text-slate-400 mb-4 leading-relaxed">
                Ingresa tu clave privada de Google Gemini. Es gratuita y la obtienes en 
                <a href="https://aistudio.google.com/app/apikey" target="_blank" class="text-emerald-400 underline font-bold hover:text-emerald-300">Google AI Studio</a>.
            </p>
            
            <!-- Current key status -->
            <div x-show="apiKey" class="mb-4 p-3 bg-emerald-950/50 border border-emerald-500/30 rounded-xl text-xs">
                <p class="text-emerald-400 font-bold flex items-center gap-1">
                    <span class="material-icons text-sm">verified</span> Clave activa detectada
                </p>
                <p class="text-slate-400 font-mono text-[11px] truncate mt-1" x-text="apiKey.substring(0, 18) + '...'"></p>
            </div>
            
            <div class="space-y-1 mb-4">
                <label class="text-[10px] uppercase font-bold text-slate-400">Token de API</label>
                <input type="text" 
                       x-model="tempApiKey" 
                       @focus="if(!tempApiKey) tempApiKey = apiKey"
                       placeholder="AIzaSy..." 
                       class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-xs font-mono text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
            </div>
            
            <div class="flex gap-2">
                <button @click="showApiKeyModal = false" class="flex-1 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs transition">
                    Cancelar
                </button>
                <button @click="saveApiKey()" class="flex-1 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-500 hover:to-teal-600 text-white font-extrabold text-xs shadow-lg shadow-emerald-900/30 active:scale-95 transition">
                    Guardar Clave
                </button>
            </div>
        </div>
    </div>
    
    <!-- Main Chat Workspace -->
    <main class="flex-1 max-w-5xl mx-auto w-full px-4 py-5 pb-32">
        
        <!-- API Key Missing Warning Banner -->
        <div x-show="!apiKey" class="bg-amber-950/60 border border-amber-500/30 rounded-2xl p-4 mb-4 flex items-start gap-3 backdrop-blur-md">
            <span class="material-icons text-amber-400 shrink-0 mt-0.5">warning</span>
            <div class="flex-1">
                <p class="font-extrabold text-amber-300 text-sm">Configuración Requerida</p>
                <p class="text-xs text-amber-200/80 mt-0.5">Para activar el análisis inteligente debes ingresar tu API key de Gemini.</p>
                <button @click="showApiKeyModal = true" class="mt-2 text-xs font-extrabold text-amber-300 underline hover:text-white">
                    Configurar Clave Ahora →
                </button>
            </div>
        </div>
        
        <!-- Messages Stack -->
        <div class="space-y-4">

            <!-- Welcome Empty State & Prompts -->
            <div x-show="messages.length === 0" class="bg-slate-800/60 border border-slate-700/60 rounded-3xl p-6 sm:p-8 text-center backdrop-blur-md shadow-xl my-4">
                <div class="w-16 h-16 bg-gradient-to-br from-emerald-500 to-teal-700 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-emerald-900/40">
                    <span class="material-icons text-3xl text-white">psychology</span>
                </div>
                <h2 class="text-lg sm:text-xl font-black text-white mb-1 tracking-tight">Bienvenido a tu Asistente Financiero</h2>
                <p class="text-xs sm:text-sm text-slate-400 max-w-md mx-auto mb-6">
                    Puedo analizar tus movimientos, detectar anomalías de gasto y proyectar presupuestos automáticamente.
                </p>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 max-w-2xl mx-auto text-left">
                    <button @click="query = 'Dame un resumen de mis gastos del mes actual'" 
                            class="p-3.5 bg-slate-800 hover:bg-slate-700/80 border border-slate-700/70 hover:border-emerald-500/40 rounded-2xl transition group active:scale-[0.99]">
                        <p class="font-bold text-emerald-400 text-xs flex items-center gap-1.5">
                            <span class="material-icons text-sm">bar_chart</span> Resumen Mensual
                        </p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Gastos consolidados e ingresos del período</p>
                    </button>

                    <button @click="query = 'Muéstrame mis gastos más altos de la última semana'" 
                            class="p-3.5 bg-slate-800 hover:bg-slate-700/80 border border-slate-700/70 hover:border-rose-500/40 rounded-2xl transition group active:scale-[0.99]">
                        <p class="font-bold text-rose-400 text-xs flex items-center gap-1.5">
                            <span class="material-icons text-sm">trending_up</span> Gastos Significativos
                        </p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Identificar egresos extraordinarios recientes</p>
                    </button>

                    <button @click="query = '¿En qué categoría gasto más dinero?'" 
                            class="p-3.5 bg-slate-800 hover:bg-slate-700/80 border border-slate-700/70 hover:border-blue-500/40 rounded-2xl transition group active:scale-[0.99]">
                        <p class="font-bold text-blue-400 text-xs flex items-center gap-1.5">
                            <span class="material-icons text-sm">pie_chart</span> Distribución por Categoría
                        </p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Desglose porcentual de salidas</p>
                    </button>

                    <button @click="query = 'Compara mis gastos de este mes con el anterior'" 
                            class="p-3.5 bg-slate-800 hover:bg-slate-700/80 border border-slate-700/70 hover:border-teal-500/40 rounded-2xl transition group active:scale-[0.99]">
                        <p class="font-bold text-teal-400 text-xs flex items-center gap-1.5">
                            <span class="material-icons text-sm">compare_arrows</span> Comparativa Mensual
                        </p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Variación porcentual entre períodos</p>
                    </button>
                </div>
            </div>
            
            <!-- Conversation Stream -->
            <template x-for="(msg, idx) in messages" :key="idx">
                <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'" class="message-enter">
                    <!-- User Bubble -->
                    <div x-show="msg.role === 'user'" 
                         class="bg-gradient-to-r from-emerald-700 to-teal-800 text-white rounded-2xl rounded-tr-sm px-4 py-3 max-w-lg shadow-lg border border-emerald-500/30">
                        <p class="text-xs sm:text-sm font-medium leading-relaxed" x-text="msg.content"></p>
                    </div>
                    
                    <!-- AI Assistant Response Card -->
                    <div x-show="msg.role === 'assistant'" 
                         class="bg-slate-800/95 border border-slate-700/80 rounded-2xl rounded-tl-sm p-4 sm:p-6 max-w-3xl shadow-xl w-full text-slate-100">
                        
                        <!-- Summary Type -->
                        <div x-show="msg.data?.type === 'summary'">
                            <h3 class="text-sm sm:text-base font-extrabold text-white mb-3 flex items-center gap-2" x-text="msg.data.title"></h3>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 mb-4">
                                <div class="bg-slate-900/80 border border-slate-700/60 rounded-xl p-3">
                                    <p class="text-[10px] text-emerald-400 font-black uppercase tracking-wider">Total</p>
                                    <p class="text-base sm:text-lg font-black text-white font-mono mt-0.5" x-text="formatMoney(msg.data.data?.total || 0)"></p>
                                </div>
                                <div class="bg-slate-900/80 border border-slate-700/60 rounded-xl p-3">
                                    <p class="text-[10px] text-blue-400 font-black uppercase tracking-wider">Registros</p>
                                    <p class="text-base sm:text-lg font-black text-white font-mono mt-0.5" x-text="msg.data.data?.count || 0"></p>
                                </div>
                                <div class="bg-slate-900/80 border border-slate-700/60 rounded-xl p-3">
                                    <p class="text-[10px] text-teal-400 font-black uppercase tracking-wider">Promedio</p>
                                    <p class="text-base sm:text-lg font-black text-white font-mono mt-0.5" x-text="formatMoney(msg.data.data?.average || 0)"></p>
                                </div>
                                <div class="bg-slate-900/80 border border-slate-700/60 rounded-xl p-3">
                                    <p class="text-[10px] text-slate-400 font-black uppercase tracking-wider">Período</p>
                                    <p class="text-xs font-bold text-slate-200 mt-1 truncate" x-text="msg.data.data?.period || 'N/A'"></p>
                                </div>
                            </div>
                            <div x-show="msg.data.insights" class="bg-slate-900/60 border border-slate-700/50 rounded-xl p-3.5">
                                <p class="text-[10px] font-black text-emerald-400 uppercase tracking-wider mb-2 flex items-center gap-1">
                                    <span class="material-icons text-xs">insights</span> Diagnóstico Clave
                                </p>
                                <ul class="space-y-1.5">
                                    <template x-for="insight in msg.data.insights">
                                        <li class="text-xs text-slate-300 flex items-start gap-2">
                                            <span class="material-icons text-emerald-400 text-xs mt-0.5">check</span>
                                            <span x-text="insight"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Cards Type -->
                        <div x-show="msg.data?.type === 'cards'">
                            <h3 class="text-sm sm:text-base font-extrabold text-white mb-3" x-text="msg.data.title"></h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <template x-for="card in msg.data.data">
                                    <div class="rounded-xl p-3.5 border bg-slate-900/80 border-slate-700/60 relative overflow-hidden">
                                        <p class="font-bold text-white text-xs" x-text="card.title"></p>
                                        <p class="text-lg font-black my-1 font-mono text-emerald-400" x-text="formatMoney(card.amount)"></p>
                                        <p class="text-[11px] text-slate-400" x-text="card.description"></p>
                                    </div>
                                </template>
                            </div>
                        </div>
                        
                        <!-- Table Type -->
                        <div x-show="msg.data?.type === 'table'">
                            <h3 class="text-sm sm:text-base font-extrabold text-white mb-3" x-text="msg.data.title"></h3>
                            <div class="overflow-x-auto customize-scrollbar border border-slate-700/60 rounded-xl">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-900/90 text-slate-400 uppercase text-[10px] font-bold border-b border-slate-700/60">
                                        <tr>
                                            <template x-for="header in msg.data.data?.headers">
                                                <th class="px-3.5 py-2.5" x-text="header"></th>
                                            </template>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-700/40">
                                        <template x-for="row in msg.data.data?.rows">
                                            <tr class="hover:bg-slate-700/30 transition-colors">
                                                <template x-for="cell in row">
                                                    <td class="px-3.5 py-2 text-slate-300" x-text="cell"></td>
                                                </template>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Text / Error Type -->
                        <div x-show="msg.data?.type === 'text' || msg.data?.type === 'error'">
                            <p class="text-xs sm:text-sm text-slate-200 whitespace-pre-wrap leading-relaxed" 
                               :class="msg.data?.type === 'error' ? 'text-rose-300' : ''"
                               x-text="msg.data.content || msg.data.message"></p>
                        </div>
                        
                        <!-- List Type -->
                        <div x-show="msg.data?.type === 'list'">
                            <h3 class="text-sm sm:text-base font-extrabold text-white mb-3" x-text="msg.data.title"></h3>
                            <div class="space-y-2">
                                <template x-for="(item, idx) in msg.data.data">
                                    <div class="flex items-center justify-between p-3 bg-slate-900/80 border border-slate-700/50 rounded-xl">
                                        <div class="flex items-center gap-3">
                                            <span class="w-7 h-7 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-black text-xs" x-text="idx + 1"></span>
                                            <div>
                                                <p class="font-bold text-white text-xs" x-text="item.title"></p>
                                                <p class="text-[10px] text-slate-400" x-text="item.description"></p>
                                            </div>
                                        </div>
                                        <p class="font-mono font-bold text-sm" :class="item.amount < 0 ? 'text-rose-400' : 'text-emerald-400'" x-text="formatMoney(item.amount)"></p>
                                    </div>
                                </template>
                            </div>
                        </div>
                        
                        <!-- Comparison Type -->
                        <div x-show="msg.data?.type === 'comparison'">
                            <h3 class="text-sm sm:text-base font-extrabold text-white mb-3" x-text="msg.data.title"></h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div class="bg-slate-900/80 rounded-xl p-4 border border-slate-700/60">
                                    <p class="text-[10px] text-blue-400 font-black uppercase tracking-wider mb-1" x-text="msg.data.data?.period1?.label || 'Período 1'"></p>
                                    <p class="text-xl font-black text-white font-mono" x-text="formatMoney(msg.data.data?.period1?.value || 0)"></p>
                                    <p class="text-[10px] text-slate-400 mt-1" x-text="msg.data.data?.period1?.description || ''"></p>
                                </div>
                                <div class="bg-slate-900/80 rounded-xl p-4 border border-slate-700/60">
                                    <p class="text-[10px] text-teal-400 font-black uppercase tracking-wider mb-1" x-text="msg.data.data?.period2?.label || 'Período 2'"></p>
                                    <p class="text-xl font-black text-white font-mono" x-text="formatMoney(msg.data.data?.period2?.value || 0)"></p>
                                    <p class="text-[10px] text-slate-400 mt-1" x-text="msg.data.data?.period2?.description || ''"></p>
                                </div>
                            </div>
                            <div class="mt-3 p-3 bg-slate-900/60 rounded-xl border border-slate-700/50 flex items-center justify-between">
                                <div>
                                    <p class="text-[10px] font-bold uppercase text-slate-400">Variación</p>
                                    <p class="text-sm font-black font-mono" :class="(msg.data.data?.difference || 0) >= 0 ? 'text-rose-400' : 'text-emerald-400'" 
                                       x-text="((msg.data.data?.difference || 0) >= 0 ? '+' : '') + formatMoney(msg.data.data?.difference || 0)"></p>
                                </div>
                                <span class="px-2.5 py-1 rounded-lg text-xs font-extrabold border"
                                      :class="(msg.data.data?.difference || 0) >= 0 ? 'bg-rose-500/10 text-rose-300 border-rose-500/20' : 'bg-emerald-500/10 text-emerald-300 border-emerald-500/20'"
                                      x-text="(msg.data.data?.differencePercent || 0) + '% ' + ((msg.data.data?.difference || 0) >= 0 ? 'más' : 'menos')"></span>
                            </div>
                        </div>
                        
                        <!-- Progress Bars Type -->
                        <div x-show="msg.data?.type === 'progress'">
                            <h3 class="text-sm sm:text-base font-extrabold text-white mb-3" x-text="msg.data.title"></h3>
                            <div class="space-y-3">
                                <template x-for="item in msg.data.data">
                                    <div>
                                        <div class="flex justify-between text-xs mb-1">
                                            <span class="font-bold text-slate-300" x-text="item.label"></span>
                                            <span class="font-mono font-bold text-emerald-400" x-text="formatMoney(item.value)"></span>
                                        </div>
                                        <div class="w-full bg-slate-900 rounded-full h-2 overflow-hidden border border-slate-700/60">
                                            <div class="h-2 rounded-full bg-gradient-to-r from-emerald-500 to-teal-400 transition-all duration-500" 
                                                 :style="'width: ' + Math.min(100, item.percentage) + '%'"></div>
                                        </div>
                                        <p class="text-[10px] text-slate-400 mt-0.5 text-right" x-text="item.percentage + '% del total'"></p>
                                    </div>
                                </template>
                            </div>
                        </div>
                        
                        <!-- Forecast Type -->
                        <div x-show="msg.data?.type === 'forecast'">
                            <h3 class="text-sm sm:text-base font-extrabold text-white mb-3" x-text="msg.data.title"></h3>
                            <div class="grid grid-cols-2 gap-3 mb-3">
                                <div class="bg-slate-900/80 p-3 rounded-xl border border-slate-700/60">
                                    <p class="text-[10px] uppercase font-bold text-blue-400">Histórico</p>
                                    <p class="text-lg font-black text-white font-mono" x-text="formatMoney(msg.data.data?.current || 0)"></p>
                                </div>
                                <div class="bg-slate-900/80 p-3 rounded-xl border border-slate-700/60">
                                    <p class="text-[10px] uppercase font-bold text-emerald-400">Proyección</p>
                                    <p class="text-lg font-black text-emerald-400 font-mono" x-text="formatMoney(msg.data.data?.projected || 0)"></p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </template>
            
            <!-- Thinking Animation -->
            <div x-show="loading" class="flex justify-start message-enter">
                <div class="bg-slate-800/90 border border-slate-700/80 rounded-2xl rounded-tl-sm px-5 py-3 shadow-md">
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-slate-400 font-medium">Analizando finanzas...</span>
                        <div class="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-bounce"></div>
                        <div class="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-bounce" style="animation-delay: 0.15s"></div>
                        <div class="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-bounce" style="animation-delay: 0.3s"></div>
                    </div>
                </div>
            </div>

        </div>
    </main>
    
    <!-- Floating Save Button -->
    <button x-show="messages.length > 0" 
            @click="showSaveModal = true; saveTitle = messages[0]?.content?.substring(0, 45) || 'Sesión Financiera'" 
            class="fixed bottom-20 right-4 sm:right-8 bg-gradient-to-r from-emerald-600 to-teal-700 text-white p-3.5 rounded-2xl shadow-xl hover:from-emerald-500 hover:to-teal-600 transition-all hover:scale-105 active:scale-95 z-30 flex items-center gap-1.5 border border-emerald-400/30"
            title="Guardar Sesión">
        <span class="material-icons text-xl">bookmark</span>
        <span class="text-xs font-bold hidden sm:inline">Guardar Chat</span>
    </button>
    
    <!-- Save Session Bottom Sheet Modal -->
    <div x-show="showSaveModal" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-md" @click="showSaveModal = false"></div>
        <div class="relative bg-slate-900 border border-slate-800 rounded-t-[2.5rem] sm:rounded-3xl p-5 sm:p-6 w-full sm:max-w-md z-10 shadow-2xl safe-bottom text-slate-100">
            <div class="w-12 h-1.5 bg-slate-700 rounded-full mx-auto mb-4 sm:hidden"></div>
            <h2 class="text-base font-extrabold text-white mb-2 flex items-center gap-2">
                <span class="material-icons text-emerald-400 text-lg">bookmark</span>
                Guardar Conversación
            </h2>
            <p class="text-xs text-slate-400 mb-4">Esta sesión tiene <strong class="text-white" x-text="messages.length"></strong> mensajes. Asigna un título descriptivo:</p>
            
            <input type="text" x-model="saveTitle" placeholder="Ej: Análisis de gastos marzo" 
                   class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-xs text-white placeholder-slate-500 mb-4 focus:outline-none focus:border-emerald-500" 
                   @keyup.enter="saveConversation()">
            
            <div class="flex gap-2">
                <button @click="showSaveModal = false" class="flex-1 py-2.5 rounded-xl bg-slate-800 text-slate-300 font-bold text-xs hover:bg-slate-700">Cancelar</button>
                <button @click="saveConversation()" class="flex-1 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-700 text-white font-extrabold text-xs shadow-lg active:scale-95">Guardar</button>
            </div>
        </div>
    </div>
    
    <!-- Input Bar (Fixed Bottom) -->
    <div class="fixed bottom-0 left-0 right-0 bg-slate-950/95 backdrop-blur-xl border-t border-slate-800/80 z-30 safe-bottom">
        <div class="max-w-5xl mx-auto px-4 py-2.5 sm:py-3.5">
            <form @submit.prevent="sendMessage()" class="flex items-center gap-2">
                <input type="text" 
                       x-model="query" 
                       placeholder="Escribe una pregunta o petición financiera..." 
                       class="flex-1 bg-slate-900 border border-slate-700/80 rounded-2xl px-4 sm:px-5 py-2.5 text-xs sm:text-sm text-white placeholder-slate-500 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-all shadow-inner"
                       :disabled="!apiKey || loading">
                <button type="submit" 
                        :disabled="!apiKey || !query.trim() || loading"
                        class="bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-500 hover:to-teal-600 text-white px-4 sm:px-6 py-2.5 rounded-2xl font-extrabold text-xs sm:text-sm disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-1.5 transition-all shadow-lg shadow-emerald-900/40 active:scale-95 shrink-0">
                    <span x-show="!loading" class="hidden sm:inline">Consultar</span>
                    <span x-show="loading" class="hidden sm:inline">Analizando...</span>
                    <span class="material-icons text-base" x-show="!loading">send</span>
                    <div x-show="loading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                </button>
            </form>
        </div>
    </div>
    
    <script>
        function aiApp() {
            return {
                apiKey: localStorage.getItem('gemini_api_key') || '',
                tempApiKey: '',
                showApiKeyModal: false,
                showSaveModal: false,
                showHistory: false,
                saveTitle: '',
                savedConversations: [],
                toasts: [],
                query: '',
                messages: [],
                loading: false,
                
                init() {
                    if (!this.apiKey) {
                        this.showApiKeyModal = true;
                    }
                    this.fetchSavedConversations();
                },
                
                async fetchSavedConversations() {
                    try {
                        const res = await fetch('<?= base_url('ai/conversations') ?>');
                        const data = await res.json();
                        if (data.status === 'success') {
                            this.savedConversations = data.data;
                        }
                    } catch(e) {
                        console.error('Error loading conversations:', e);
                    }
                },
                
                showToast(message, type = 'info') {
                    const toast = { message, type, id: Date.now() };
                    this.toasts.push(toast);
                    setTimeout(() => {
                        const index = this.toasts.findIndex(t => t.id === toast.id);
                        if (index > -1) this.toasts.splice(index, 1);
                    }, 4000);
                },
                
                removeToast(index) {
                    this.toasts.splice(index, 1);
                },
                
                saveApiKey() {
                    if (this.tempApiKey.trim()) {
                        this.apiKey = this.tempApiKey.trim();
                        localStorage.setItem('gemini_api_key', this.apiKey);
                        this.showToast('API Key guardada correctamente', 'success');
                        this.showApiKeyModal = false;
                        this.tempApiKey = '';
                    } else {
                        this.showToast('Por favor ingresa una API key válida', 'warning');
                    }
                },
                
                async sendMessage() {
                    if (!this.query.trim() || !this.apiKey) return;
                    
                    const userQuery = this.query;
                    this.messages.push({
                        role: 'user',
                        content: userQuery
                    });
                    
                    this.query = '';
                    this.loading = true;
                    
                    try {
                        const res = await fetch('<?= base_url('ai/chat') ?>', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({
                                query: userQuery,
                                apiKey: this.apiKey
                            })
                        });
                        
                        const data = await res.json();
                        
                        if (data.status === 'success') {
                            this.messages.push({
                                role: 'assistant',
                                data: data.response
                            });
                        } else {
                            this.messages.push({
                                role: 'assistant',
                                data: {
                                    type: 'error',
                                    message: data.message || 'Error al procesar la solicitud'
                                }
                            });
                        }
                    } catch (e) {
                        this.messages.push({
                            role: 'assistant',
                            data: {
                                type: 'error',
                                message: 'Error de conexión: ' + e.message
                            }
                        });
                    } finally {
                        this.loading = false;
                        this.$nextTick(() => {
                            window.scrollTo({top: document.body.scrollHeight, behavior: 'smooth'});
                        });
                    }
                },
                
                async saveConversation() {
                    if (!this.saveTitle.trim()) {
                        this.showToast('Por favor ingresa un título', 'warning');
                        return;
                    }
                    
                    if (this.messages.length === 0) {
                        this.showToast('No hay mensajes para guardar', 'warning');
                        return;
                    }
                    
                    try {
                        const res = await fetch('<?= base_url('ai/save-conversation') ?>', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({
                                title: this.saveTitle,
                                messages: this.messages
                            })
                        });
                        
                        const data = await res.json();
                        if (data.status === 'success') {
                            this.showToast('Conversación guardada exitosamente', 'success');
                            this.showSaveModal = false;
                            this.fetchSavedConversations();
                        } else {
                            this.showToast('Error: ' + data.message, 'error');
                        }
                    } catch(e) {
                        this.showToast('Error al guardar: ' + e.message, 'error');
                    }
                },
                
                formatMoney(value) {
                    return new Intl.NumberFormat('es-VE', { style: 'currency', currency: 'VES' }).format(value);
                },

                confirmLoad(id) {
                    if(this.messages.length > 0 && confirm('¿Cargar conversación? Se perderá el chat actual no guardado.')) {
                        this.loadConversation(id);
                    } else if(this.messages.length === 0) {
                        this.loadConversation(id);
                    }
                },

                async loadConversation(id) {
                    this.loading = true;
                    try {
                        const res = await fetch('<?= base_url('ai/conversation') ?>/' + id);
                        const data = await res.json();
                        
                        if(data.status === 'success') {
                            this.messages = data.data.messages;
                            this.saveTitle = data.data.title;
                            this.showHistory = false;
                            this.showToast('Conversación cargada', 'success');
                            this.$nextTick(() => {
                                window.scrollTo({top: document.body.scrollHeight, behavior: 'smooth'});
                            });
                        } else {
                            this.showToast('Error al cargar: ' + data.message, 'error');
                        }
                    } catch(e) {
                        this.showToast('Error de conexión', 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                async deleteConv(id) {
                    if(!confirm('¿Eliminar conversación?')) return;
                    
                    try {
                        const res = await fetch('<?= base_url('ai/conversation') ?>/' + id, { method: 'DELETE' });
                        const data = await res.json();
                        if(data.status === 'success') {
                            this.showToast('Eliminada correctamente', 'success');
                            this.fetchSavedConversations();
                        }
                    } catch(e) {}
                },

                formatDate(dateStr) {
                    if(!dateStr) return '';
                    return new Date(dateStr).toLocaleDateString() + ' ' + new Date(dateStr).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                }
            }
        }
    </script>
</body>
</html>
