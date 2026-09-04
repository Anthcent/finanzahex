<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Bitácora del Sistema - Fi-Hex Wallet</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        [x-cloak] { display: none !important; }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
        .safe-top { padding-top: max(12px, env(safe-area-inset-top)); }
        .safe-bottom { padding-bottom: max(16px, env(safe-area-inset-bottom)); }
        .timeline-line { position: absolute; left: 23px; top: 0; bottom: 0; width: 2px; background-color: rgba(51, 65, 85, 0.7); z-index: 0; }
        .json-tree { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.78rem; }
        .customize-scrollbar::-webkit-scrollbar { width: 4px; height: 4px; }
        .customize-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .customize-scrollbar::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, 0.3); border-radius: 9999px; }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen safe-bottom antialiased selection:bg-emerald-500 selection:text-white" x-data="auditApp()">

    <!-- Executive Header -->
    <header class="bg-gradient-to-r from-emerald-950 via-slate-900 to-teal-950 border-b border-emerald-500/20 sticky top-0 z-40 backdrop-blur-xl bg-opacity-95 safe-top shadow-lg shadow-black/20">
        <div class="max-w-7xl mx-auto px-4 py-3 sm:py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="<?= base_url() ?>" class="w-10 h-10 rounded-xl bg-white/10 hover:bg-white/15 active:scale-95 border border-white/10 flex items-center justify-center text-slate-200 hover:text-white transition-all shadow-inner">
                    <span class="material-icons text-xl">arrow_back</span>
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="font-extrabold text-base sm:text-lg text-white tracking-tight leading-none flex items-center">
                            Bitácora del Sistema
                        </h1>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                            Auditoría
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-400 font-medium mt-0.5">Historial inmutable y diagnóstico de eventos</p>
                </div>
            </div>
            
            <!-- Mobile Filters Toggle -->
            <button @click="showFilters = !showFilters" 
                    class="lg:hidden w-10 h-10 rounded-xl bg-slate-800/80 hover:bg-slate-700/80 active:scale-95 border border-slate-700/60 flex items-center justify-center text-slate-300 hover:text-white transition-all">
                <span class="material-icons text-lg">filter_list</span>
            </button>
        </div>
    </header>

    <!-- Main Workspace -->
    <main class="max-w-7xl mx-auto px-4 py-4 sm:py-6 grid grid-cols-1 lg:grid-cols-3 gap-5 relative">
        
        <!-- Left Column: Timeline & Filters -->
        <div class="lg:col-span-2 space-y-4 pb-20 lg:pb-0">
            
            <!-- Filters Card -->
            <div class="bg-slate-800/80 border border-slate-700/70 p-3.5 sm:p-4 rounded-2xl shadow-md flex flex-col md:flex-row gap-2.5 transition-all backdrop-blur-md"
                 x-show="showFilters || window.innerWidth >= 1024" x-collapse>
                <div class="grid grid-cols-2 gap-2 md:flex md:gap-2.5 flex-1">
                    <div class="flex items-center bg-slate-900 border border-slate-700/80 rounded-xl px-2.5 py-1.5 flex-1">
                        <select x-model="filters.module" @change="fetchLogs()" class="w-full bg-transparent text-xs font-bold text-slate-200 focus:outline-none cursor-pointer">
                            <option value="" class="bg-slate-900 text-slate-300">Todos los Módulos</option>
                            <option value="transactions" class="bg-slate-900 text-slate-300">Transacciones</option>
                            <option value="accounts" class="bg-slate-900 text-slate-300">Cuentas</option>
                            <option value="sales" class="bg-slate-900 text-slate-300">Ventas</option>
                            <option value="printing" class="bg-slate-900 text-slate-300">Impresiones</option>
                        </select>
                    </div>
                    
                    <div class="flex items-center bg-slate-900 border border-slate-700/80 rounded-xl px-2.5 py-1.5 flex-1">
                        <select x-model="filters.action" @change="fetchLogs()" class="w-full bg-transparent text-xs font-bold text-slate-200 focus:outline-none cursor-pointer">
                            <option value="" class="bg-slate-900 text-slate-300">Todas las Acciones</option>
                            <option value="create" class="bg-slate-900 text-slate-300">Creación</option>
                            <option value="update" class="bg-slate-900 text-slate-300">Edición</option>
                            <option value="delete" class="bg-slate-900 text-slate-300">Eliminación</option>
                            <option value="transfer" class="bg-slate-900 text-slate-300">Transferencia</option>
                        </select>
                    </div>
                </div>

                <div class="flex-1 relative">
                    <input type="text" x-model="filters.search" @keydown.enter="fetchLogs()" placeholder="Buscar en notas o registros..." 
                           class="w-full bg-slate-900 border border-slate-700/80 rounded-xl px-3.5 py-2 text-xs font-semibold text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition shadow-inner">
                </div>
                
                <button @click="fetchLogs()" class="px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-500 hover:to-teal-600 text-white rounded-xl transition flex items-center justify-center font-bold text-xs shadow-md shadow-emerald-900/30 active:scale-95 shrink-0">
                    <span class="material-icons text-sm mr-1">refresh</span>
                    <span>Filtrar</span>
                </button>
            </div>

            <!-- Timeline Card -->
            <div class="relative bg-slate-800/60 border border-slate-700/60 rounded-3xl p-4 sm:p-6 min-h-[500px] backdrop-blur-md">
                <div class="timeline-line"></div>
                
                <div x-show="loading" class="flex justify-center py-16 relative z-10">
                    <div class="flex items-center gap-2 text-emerald-400 font-bold text-xs">
                        <div class="w-4 h-4 border-2 border-emerald-400 border-t-transparent rounded-full animate-spin"></div>
                        Cargando bitácora...
                    </div>
                </div>

                <div x-show="!loading && logs.length === 0" class="text-center py-16 relative z-10">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-slate-800 border border-slate-700 flex items-center justify-center text-slate-500 mb-3">
                        <span class="material-icons text-3xl">history_toggle_off</span>
                    </div>
                    <p class="text-slate-300 font-bold text-sm">Sin eventos registrados</p>
                    <p class="text-slate-500 text-xs mt-1">No hay registros con los filtros seleccionados.</p>
                </div>

                <!-- Event Feed -->
                <div class="space-y-3 relative z-10" x-show="!loading && logs.length > 0">
                    <template x-for="log in logs" :key="log.id">
                        <div class="flex group">
                            <!-- Timeline Dot / Icon -->
                            <div class="shrink-0 mr-3 mt-1">
                                <div class="w-10 h-10 rounded-2xl flex items-center justify-center border-2 border-slate-800 shadow-md transition-transform group-hover:scale-105"
                                     :class="getActionColor(log.action)">
                                    <span class="material-icons text-white text-base" x-text="getActionIcon(log.action)"></span>
                                </div>
                            </div>
                            
                            <!-- Log Card -->
                            <div class="flex-1 bg-slate-800/90 border border-slate-700/70 hover:border-emerald-500/40 rounded-2xl p-3.5 hover:bg-slate-800 transition-all cursor-pointer active:scale-[0.99] shadow-sm"
                                 @click="openDetail(log)"
                                 :class="selectedLog && selectedLog.id === log.id ? 'ring-2 ring-emerald-500 border-transparent' : ''">
                                <div class="flex justify-between items-start gap-2">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="text-[9px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded-md bg-slate-900/80 border border-slate-700/60 text-slate-300" x-text="log.module"></span>
                                            <span class="text-[9px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded-md border" 
                                                  :class="getActionBadgeClass(log.action)"
                                                  x-text="log.action"></span>
                                            <span class="text-[10px] text-slate-500 font-mono" x-text="'#' + log.id"></span>
                                        </div>
                                        <p class="font-bold text-white mt-1.5 text-xs sm:text-sm leading-snug group-hover:text-emerald-300 transition-colors" x-text="log.user_note || 'Sin descripción registrada'"></p>
                                    </div>
                                    <span class="text-[10px] font-medium text-slate-400 whitespace-nowrap bg-slate-900/60 px-2 py-0.5 rounded-md border border-slate-700/40 shrink-0" x-text="formatDateShort(log.created_at)"></span>
                                </div>
                                
                                <!-- Quick Impact Pill -->
                                <div x-show="log.impact" class="mt-2 text-xs bg-slate-900/80 px-2.5 py-1 rounded-xl border border-slate-700/60 inline-flex items-center gap-1.5">
                                    <template x-if="log.impact && log.impact.delta">
                                        <span class="font-mono flex items-center text-[11px]">
                                            <span :class="log.impact.delta > 0 ? 'text-emerald-400 font-bold' : 'text-rose-400 font-bold'" x-text="formatMoney(log.impact.delta)"></span>
                                            <span class="text-slate-500 mx-1">en</span>
                                            <span class="text-slate-300 font-semibold truncate max-w-[120px]" x-text="getAccountName(log.impact.account_id)"></span>
                                        </span>
                                    </template>
                                    <template x-if="!log.impact || !log.impact.delta">
                                        <span class="text-slate-400 text-[11px] flex items-center gap-1">
                                            <span class="material-icons text-[12px] text-emerald-400">touch_app</span> Ver detalle de impacto
                                        </span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Right Column: Detail Inspector & AI Chat Panel -->
        <div class="fixed inset-0 z-50 lg:static lg:z-auto lg:block lg:space-y-4 lg:col-span-1 lg:h-[calc(100vh-6rem)] lg:sticky lg:top-24"
             x-show="mobilePanelOpen || window.innerWidth >= 1024"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-y-full lg:translate-y-0"
             x-transition:enter-end="translate-y-0 lg:translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-y-0 lg:translate-y-0"
             x-transition:leave-end="translate-y-full lg:translate-y-0"
             x-cloak>
            
            <!-- Backdrop for Mobile -->
            <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-md lg:hidden" @click="mobilePanelOpen = false"></div>

            <!-- Content Container -->
            <div class="absolute inset-x-0 bottom-0 top-12 bg-slate-900 border-t border-slate-800 rounded-t-[2.5rem] lg:rounded-3xl shadow-2xl lg:shadow-none flex flex-col lg:h-full overflow-hidden text-slate-100 lg:border lg:border-slate-800">
                
                <!-- Mobile Drag Indicator & Header -->
                <div class="w-12 h-1.5 bg-slate-700 rounded-full mx-auto my-3 lg:hidden shrink-0"></div>
                <div class="flex-none px-4 py-2 border-b border-slate-800 flex justify-between items-center lg:hidden">
                    <h2 class="font-extrabold text-sm text-white flex items-center gap-2">
                        <span class="material-icons text-emerald-400 text-lg" x-text="selectedLog ? 'visibility' : 'smart_toy'"></span>
                        <span x-text="selectedLog ? 'Inspección de Evento' : 'Asistente Auditor'"></span>
                    </h2>
                    <button @click="mobilePanelOpen = false" class="w-7 h-7 rounded-full bg-white/10 flex items-center justify-center text-slate-300">
                        <span class="material-icons text-xs">close</span>
                    </button>
                </div>

                <!-- Flex Stack -->
                <div class="flex-1 flex flex-col min-h-0 bg-slate-900 lg:space-y-3 p-3 lg:p-0">

                    <!-- Detail Panel (If event selected) -->
                    <div x-show="selectedLog" class="bg-slate-800/90 border border-slate-700/80 rounded-2xl overflow-hidden flex-none max-h-[38vh] flex flex-col transition-all mb-3 lg:mb-0">
                        <div class="bg-slate-900/90 px-3.5 py-2 border-b border-slate-700/60 flex justify-between items-center shrink-0">
                            <h3 class="font-extrabold text-emerald-400 flex items-center text-xs">
                                <span class="material-icons text-sm mr-1.5">visibility</span>
                                Evento #<span x-text="selectedLog?.id"></span>
                            </h3>
                            <button @click="selectedLog = null" class="text-slate-400 hover:text-white p-1">
                                <span class="material-icons text-xs">close</span>
                            </button>
                        </div>
                        <div class="p-3.5 space-y-2.5 overflow-y-auto customize-scrollbar text-xs">
                            <div class="text-xs font-bold text-white border-b border-slate-700/60 pb-1.5">
                                <span x-text="selectedLog?.user_note"></span>
                            </div>

                            <!-- JSON Diffs -->
                            <div class="grid grid-cols-2 gap-2 text-[10px]">
                                <div x-show="selectedLog?.data_before" class="bg-rose-950/40 border border-rose-500/30 p-2.5 rounded-xl">
                                    <p class="font-black text-rose-400 mb-1 tracking-wider uppercase text-[9px]">Antes</p>
                                    <pre class="json-tree text-rose-200 whitespace-pre-wrap overflow-x-auto max-h-[90px] customize-scrollbar" x-text="JSON.stringify(selectedLog?.data_before, null, 2)"></pre>
                                </div>
                                <div x-show="selectedLog?.data_after" class="bg-emerald-950/40 border border-emerald-500/30 p-2.5 rounded-xl">
                                    <p class="font-black text-emerald-400 mb-1 tracking-wider uppercase text-[9px]">Después</p>
                                    <pre class="json-tree text-emerald-200 whitespace-pre-wrap overflow-x-auto max-h-[90px] customize-scrollbar" x-text="JSON.stringify(selectedLog?.data_after, null, 2)"></pre>
                                </div>
                            </div>
                            
                            <!-- Impact details -->
                            <div x-show="selectedLog?.impact" class="bg-slate-900/90 p-2.5 rounded-xl border border-slate-700/60">
                                <p class="font-black text-amber-400 text-[9px] uppercase tracking-wider mb-1">Impacto Calculado</p>
                                <pre class="json-tree text-slate-300 whitespace-pre-wrap overflow-x-auto max-h-[80px] customize-scrollbar" x-text="JSON.stringify(selectedLog?.impact, null, 2)"></pre>
                            </div>
                        </div>
                    </div>

                    <!-- AI Auditor Chat (Takes remaining vertical space) -->
                    <div class="bg-slate-800/80 border border-slate-700/70 rounded-2xl flex flex-col flex-1 min-h-0 overflow-hidden shadow-inner">
                        <!-- Chat Header -->
                        <div class="p-3 bg-gradient-to-r from-emerald-950 via-slate-900 to-teal-950 border-b border-emerald-500/20 shrink-0">
                            <h2 class="font-extrabold text-white flex items-center text-xs">
                                <span class="material-icons mr-1.5 text-base text-emerald-400">smart_toy</span>
                                Chat Diagnóstico AI
                            </h2>
                            <div x-show="selectedLog" class="mt-1.5 bg-emerald-500/10 border border-emerald-500/20 px-2 py-0.5 rounded-md text-[10px] text-emerald-300 flex items-center gap-1">
                                <span class="material-icons text-[11px]">filter_center_focus</span>
                                <span>Contexto: Evento #<span x-text="selectedLog?.id"></span></span>
                            </div>
                        </div>
                        
                        <!-- Messages Stream -->
                        <div class="flex-1 overflow-y-auto p-3.5 space-y-2.5 bg-slate-900/60 customize-scrollbar" id="chat-container">
                            <div class="bg-slate-800/90 border border-slate-700/60 p-3 rounded-2xl rounded-tl-sm text-xs text-slate-300 max-w-[92%] shadow-sm">
                                Hola. Soy tu Auditor AI. Selecciona cualquier evento de la bitácora para auditar discrepancias o hazme preguntas generales del flujo.
                            </div>

                            <template x-for="(msg, idx) in messages" :key="idx">
                                <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                                    <div class="p-3 rounded-2xl text-xs max-w-[92%] shadow-md whitespace-pre-wrap"
                                         :class="msg.role === 'user' 
                                            ? 'bg-gradient-to-r from-emerald-700 to-teal-800 text-white rounded-tr-sm border border-emerald-500/30' 
                                            : 'bg-slate-800 text-slate-200 rounded-tl-sm border border-slate-700/70'"
                                         x-html="formatMessage(msg.content)">
                                    </div>
                                </div>
                            </template>
                            
                            <div x-show="aiLoading" class="flex justify-start">
                                <div class="bg-slate-800 p-2.5 rounded-2xl rounded-tl-sm border border-slate-700 flex items-center gap-1.5">
                                     <div class="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-bounce"></div>
                                     <div class="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-bounce" style="animation-delay: 0.15s"></div>
                                     <div class="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-bounce" style="animation-delay: 0.3s"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Chat Input -->
                        <div class="p-2.5 bg-slate-900 border-t border-slate-800 shrink-0 safe-bottom">
                            <form @submit.prevent="sendMessage()" class="flex items-center gap-2">
                                <input type="text" x-model="query" 
                                       :placeholder="selectedLog ? 'Pregunta sobre el evento #' + selectedLog.id + '...' : 'Pregunta algo al auditor...'" 
                                       class="w-full bg-slate-800 border border-slate-700 rounded-2xl pl-3.5 pr-3 py-2 text-xs text-white placeholder-slate-500 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition">
                                <button type="submit" :disabled="aiLoading || !query.trim()" 
                                        class="p-2 bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-500 hover:to-teal-600 text-white rounded-xl disabled:opacity-40 transition shadow-md shrink-0">
                                    <span class="material-icons text-sm">send</span>
                                </button>
                            </form>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </main>

    <!-- Floating Action Button for Mobile Chat Panel -->
    <button @click="mobilePanelOpen = true" 
            x-show="!mobilePanelOpen && window.innerWidth < 1024"
            class="fixed bottom-6 right-6 w-13 h-13 p-3.5 bg-gradient-to-r from-emerald-600 to-teal-700 text-white rounded-2xl shadow-2xl flex items-center justify-center z-30 hover:scale-105 active:scale-95 transition-transform border border-emerald-400/30">
        <span class="material-icons text-2xl">smart_toy</span>
    </button>

    <!-- API Key Modal -->
    <div x-show="!apiKey && showApiKeyModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4" x-cloak>
        <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-md" @click="showApiKeyModal = false"></div>
        <div class="relative bg-slate-900 border border-slate-800 rounded-t-[2.5rem] sm:rounded-3xl p-5 sm:p-6 w-full sm:max-w-md z-10 shadow-2xl safe-bottom text-slate-100">
            <div class="w-12 h-1.5 bg-slate-700 rounded-full mx-auto mb-4 sm:hidden"></div>
            <h3 class="font-extrabold text-base text-white mb-2 flex items-center gap-2">
                <span class="material-icons text-emerald-400 text-lg">vpn_key</span>
                API Key Requerida
            </h3>
            <p class="text-xs text-slate-400 mb-4">Para usar el diagnóstico del Auditor AI, necesitas configurar tu clave privada de Google Gemini.</p>
            <input type="text" x-model="tempApiKey" placeholder="Pegar clave Gemini (AIza...)" 
                   class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 mb-4 text-xs font-mono text-white focus:outline-none focus:border-emerald-500">
            <button @click="saveApiKey()" class="w-full bg-gradient-to-r from-emerald-600 to-teal-700 text-white py-2.5 rounded-xl font-extrabold text-xs shadow-lg active:scale-95">
                Guardar y Activar
            </button>
        </div>
    </div>

    <script>
        function auditApp() {
            return {
                logs: [],
                loading: false,
                filters: {
                    module: '',
                    action: '',
                    search: ''
                },
                selectedLog: null,
                mobilePanelOpen: false, 
                showFilters: false,
                
                // Chat
                apiKey: localStorage.getItem('gemini_api_key') || '',
                tempApiKey: '',
                showApiKeyModal: false,
                query: '',
                messages: [],
                aiLoading: false,

                init() {
                    this.fetchLogs();
                    if (!this.apiKey) this.showApiKeyModal = true;
                    
                    window.addEventListener('resize', () => {
                        if (window.innerWidth >= 1024) this.mobilePanelOpen = false;
                    });
                },

                async fetchLogs() {
                    this.loading = true;
                    try {
                        const res = await fetch('<?= base_url('audit/fetch') ?>', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify(this.filters)
                        });
                        const data = await res.json();
                        if (data.status === 'success') {
                            this.logs = data.data;
                        }
                    } catch(e) {
                        console.error(e);
                    } finally {
                        this.loading = false;
                    }
                },

                openDetail(log) {
                    this.selectedLog = log;
                    if (window.innerWidth < 1024) {
                        this.mobilePanelOpen = true;
                    }
                },

                saveApiKey() {
                    if (this.tempApiKey.trim()) {
                        localStorage.setItem('gemini_api_key', this.tempApiKey.trim());
                        this.apiKey = this.tempApiKey.trim();
                        this.showApiKeyModal = false;
                    }
                },

                async sendMessage() {
                    if (!this.query.trim()) return;
                    if (!this.apiKey) { this.showApiKeyModal = true; return; }

                    const userQuery = this.query;
                    this.messages.push({ role: 'user', content: userQuery });
                    this.query = '';
                    this.aiLoading = true;
                    this.scrollToBottom();

                    try {
                        const res = await fetch('<?= base_url('audit/chat') ?>', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({
                                query: userQuery,
                                apiKey: this.apiKey,
                                history: this.messages,
                                focusedLog: this.selectedLog
                            })
                        });
                        const data = await res.json();
                        
                        if (data.status === 'success') {
                            this.messages.push({ role: 'assistant', content: data.response.content });
                        } else {
                            this.messages.push({ role: 'assistant', content: 'Error: ' + data.message });
                        }
                    } catch(e) {
                        this.messages.push({ role: 'assistant', content: 'Error de conexión con el servidor.' });
                    } finally {
                        this.aiLoading = false;
                        this.scrollToBottom();
                    }
                },

                scrollToBottom() {
                    this.$nextTick(() => {
                        const container = document.getElementById('chat-container');
                        if(container) container.scrollTop = container.scrollHeight;
                    });
                },
                
                formatMessage(text) {
                    if(!text) return '';
                    let formatted = text.replace(/\*\*(.*?)\*\*/g, '<b>$1</b>');
                    formatted = formatted.replace(/^\* /gm, '• ');
                    return formatted;
                },

                getActionColor(action) {
                    const colors = {
                        'create': 'bg-emerald-600',
                        'update': 'bg-blue-600',
                        'delete': 'bg-rose-600',
                        'transfer': 'bg-teal-600',
                        'payment': 'bg-emerald-700'
                    };
                    return colors[action] || 'bg-slate-700';
                },

                getActionBadgeClass(action) {
                    const classes = {
                        'create': 'bg-emerald-500/10 text-emerald-300 border-emerald-500/20',
                        'update': 'bg-blue-500/10 text-blue-300 border-blue-500/20',
                        'delete': 'bg-rose-500/10 text-rose-300 border-rose-500/20',
                        'transfer': 'bg-teal-500/10 text-teal-300 border-teal-500/20',
                        'payment': 'bg-emerald-500/10 text-emerald-300 border-emerald-500/20'
                    };
                    return classes[action] || 'bg-slate-800 text-slate-300 border-slate-700';
                },

                getActionIcon(action) {
                    const icons = {
                        'create': 'add',
                        'update': 'edit',
                        'delete': 'delete',
                        'transfer': 'swap_horiz',
                        'payment': 'attach_money'
                    };
                    return icons[action] || 'info';
                },
                
                getAccountName(id) {
                     return 'Cuenta #' + id; 
                },

                formatDate(date) {
                    if (!date) return '';
                    return new Date(date).toLocaleString();
                },
                
                formatDateShort(date) {
                    if (!date) return '';
                    const d = new Date(date);
                    return d.getDate() + '/' + (d.getMonth()+1) + ' ' + d.getHours() + ':' + String(d.getMinutes()).padStart(2, '0');
                },

                formatMoney(value) {
                    return new Intl.NumberFormat('es-VE', { style: 'currency', currency: 'VES' }).format(value);
                }
            }
        }
    </script>
</body>
</html>
