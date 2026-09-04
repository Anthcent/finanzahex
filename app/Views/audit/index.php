<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bitácora del Sistema - FinazaPersonal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        [x-cloak] { display: none !important; }
        .timeline-line { position: absolute; left: 24px; top: 0; bottom: 0; width: 2px; background-color: #e5e7eb; z-index: 0; }
        .json-tree { font-family: monospace; font-size: 0.85rem; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen safe-bottom" x-data="auditApp()">

    <!-- Header -->
    <div class="bg-white shadow-sm border-b sticky top-0 z-30 safe-top">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <a href="<?= base_url() ?>" class="text-gray-500 hover:text-gray-800">
                    <span class="material-icons">arrow_back</span>
                </a>
                <h1 class="text-lg md:text-xl font-bold text-gray-800 flex items-center">
                    <span class="material-icons text-indigo-600 mr-2">manage_search</span>
                    Bitácora
                </h1>
            </div>
            <!-- Mobile Toggle for Filters -->
            <button @click="showFilters = !showFilters" class="lg:hidden p-2 text-gray-500 hover:bg-gray-100 rounded-full">
                <span class="material-icons">filter_list</span>
            </button>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-4 lg:py-6 grid grid-cols-1 lg:grid-cols-3 gap-6 relative">
        
        <!-- Left Panel: Audit Timeline -->
        <div class="lg:col-span-2 space-y-4 pb-20 lg:pb-0">
            
            <!-- Filters (Collapsible on Mobile) -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col md:flex-row gap-3 transition-all"
                 x-show="showFilters || window.innerWidth >= 1024" x-collapse>
                <div class="grid grid-cols-2 gap-2 md:flex md:gap-3">
                    <div class="flex items-center space-x-2 border rounded-lg px-2 py-2 bg-gray-50 flex-1">
                        <select x-model="filters.module" @change="fetchLogs()" class="w-full bg-transparent text-xs font-bold text-gray-700 focus:outline-none">
                            <option value="">Todo Módulo</option>
                            <option value="transactions">Transacciones</option>
                            <option value="accounts">Cuentas</option>
                            <option value="sales">Ventas</option>
                            <option value="printing">Impresiones</option>
                        </select>
                    </div>
                    
                    <div class="flex items-center space-x-2 border rounded-lg px-2 py-2 bg-gray-50 flex-1">
                        <select x-model="filters.action" @change="fetchLogs()" class="w-full bg-transparent text-xs font-bold text-gray-700 focus:outline-none">
                            <option value="">Toda Acción</option>
                            <option value="create">Creación</option>
                            <option value="update">Edición</option>
                            <option value="delete">Eliminación</option>
                            <option value="transfer">Transferencia</option>
                        </select>
                    </div>
                </div>

                <div class="flex-1">
                    <input type="text" x-model="filters.search" @keydown.enter="fetchLogs()" placeholder="Buscar..." class="w-full border rounded-lg px-3 py-2 text-xs bg-gray-50 focus:bg-white transition">
                </div>
                
                <button @click="fetchLogs()" class="p-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition flex items-center justify-center">
                    <span class="material-icons text-sm">refresh</span>
                </button>
            </div>

            <!-- Timeline -->
            <div class="relative bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:p-6 min-h-[500px]">
                <div class="timeline-line"></div>
                
                <div x-show="loading" class="flex justify-center py-10 relative z-10 bg-white">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                </div>

                <div x-show="!loading && logs.length === 0" class="text-center py-10 relative z-10 bg-white">
                    <span class="material-icons text-gray-300 text-5xl">history_toggle_off</span>
                    <p class="text-gray-500 mt-2 text-sm">Sin registros.</p>
                </div>

                <div class="space-y-4 relative z-10">
                    <template x-for="log in logs" :key="log.id">
                        <div class="flex group">
                            <!-- Icon -->
                            <div class="flex-shrink-0 mr-3 mt-1">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center border-4 border-white shadow-sm"
                                     :class="getActionColor(log.action)">
                                    <span class="material-icons text-white text-base" x-text="getActionIcon(log.action)"></span>
                                </div>
                            </div>
                            
                            <!-- Content -->
                            <div class="flex-1 bg-gray-50 rounded-lg p-3 hover:bg-white hover:shadow-md transition border border-transparent hover:border-gray-200 cursor-pointer active:scale-[0.99]"
                                 @click="openDetail(log)">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500" x-text="log.module"></span>
                                            <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-white border border-gray-200 text-gray-600" x-text="log.action"></span>
                                        </div>
                                        <p class="font-bold text-gray-800 mt-1 text-sm leading-tight" x-text="log.user_note || 'Sin nota'"></p>
                                    </div>
                                    <span class="text-[10px] text-gray-400 whitespace-nowrap ml-2" x-text="formatDateShort(log.created_at)"></span>
                                </div>
                                
                                <!-- Quick Impact Preview -->
                                <div x-show="log.impact" class="mt-2 text-xs bg-white p-1.5 rounded border border-gray-200 inline-block">
                                    <template x-if="log.impact.delta">
                                        <span class="font-mono flex items-center">
                                            <span :class="log.impact.delta > 0 ? 'text-green-600' : 'text-red-600'" x-text="formatMoney(log.impact.delta)"></span>
                                            <span class="text-gray-400 mx-1">en</span>
                                            <span class="text-gray-600 font-bold truncate max-w-[100px]" x-text="getAccountName(log.impact.account_id)"></span>
                                        </span>
                                    </template>
                                    <template x-if="!log.impact.delta">
                                        <span class="text-gray-500">Ver impacto</span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Right Panel: AI & Details (Responsive Overlay) -->
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
            <div class="absolute inset-0 bg-black/50 lg:hidden" @click="mobilePanelOpen = false"></div>

            <!-- Content Container -->
            <div class="absolute inset-x-0 bottom-0 top-10 bg-white rounded-t-2xl lg:rounded-xl shadow-2xl lg:shadow-none flex flex-col lg:h-full overflow-hidden">
                
                <!-- Mobile Handle -->
                <div class="flex-none bg-white p-4 border-b flex justify-between items-center lg:hidden">
                    <h2 class="font-bold text-gray-800 flex items-center">
                        <span class="material-icons text-indigo-600 mr-2" x-text="selectedLog ? 'visibility' : 'smart_toy'"></span>
                        <span x-text="selectedLog ? 'Detalle' : 'Asistente AI'"></span>
                    </h2>
                    <button @click="mobilePanelOpen = false" class="p-2 bg-gray-100 rounded-full text-gray-500">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                <!-- Desktop Flex Container -->
                <div class="flex-1 flex flex-col min-h-0 bg-gray-50 lg:bg-transparent lg:space-y-4">

                    <!-- Detail Panel (Scrollable if needed, shrinks) -->
                    <div x-show="selectedLog" class="bg-white lg:rounded-xl lg:shadow-sm lg:border lg:border-indigo-100 overflow-hidden flex-none max-h-[35vh] flex flex-col transition-all">
                        <div class="bg-indigo-50 px-4 py-2 border-b border-indigo-100 flex justify-between items-center shrink-0">
                            <h3 class="font-bold text-indigo-900 flex items-center text-xs md:text-sm">
                                <span class="material-icons text-xs mr-2">visibility</span>
                                Evento #<span x-text="selectedLog?.id"></span>
                            </h3>
                            <button @click="selectedLog = null" class="text-indigo-400 hover:text-indigo-600">
                                <span class="material-icons text-sm">close</span>
                            </button>
                        </div>
                        <div class="p-4 space-y-3 overflow-y-auto">
                            <!-- Mobile Note -->
                            <div class="text-sm font-bold text-gray-800 border-b pb-2">
                                <span x-text="selectedLog?.user_note"></span>
                            </div>

                            <!-- Comparison -->
                            <div class="grid grid-cols-2 gap-2 text-[10px] md:text-xs">
                                <div x-show="selectedLog?.data_before" class="bg-red-50 p-2 rounded">
                                    <p class="font-bold text-red-800 mb-1">ANTES</p>
                                    <pre class="json-tree text-red-700 whitespace-pre-wrap overflow-x-auto max-h-[100px]" x-text="JSON.stringify(selectedLog?.data_before, null, 2)"></pre>
                                </div>
                                <div x-show="selectedLog?.data_after" class="bg-green-50 p-2 rounded">
                                    <p class="font-bold text-green-800 mb-1">DESPUÉS</p>
                                    <pre class="json-tree text-green-700 whitespace-pre-wrap overflow-x-auto max-h-[100px]" x-text="JSON.stringify(selectedLog?.data_after, null, 2)"></pre>
                                </div>
                            </div>
                            
                            <!-- Impact -->
                            <div x-show="selectedLog?.impact" class="bg-yellow-50 p-2 rounded border border-yellow-200">
                                <p class="font-bold text-yellow-800 text-[10px] mb-1 uppercase">Impacto</p>
                                <pre class="json-tree text-yellow-900 whitespace-pre-wrap overflow-x-auto" x-text="JSON.stringify(selectedLog?.impact, null, 2)"></pre>
                            </div>
                        </div>
                    </div>

                    <!-- AI Chat Panel (Grows to fill remaining space) -->
                    <div class="bg-white lg:rounded-xl lg:shadow-sm lg:border lg:border-gray-100 flex flex-col flex-1 min-h-0">
                        <div class="p-3 border-b border-gray-100 bg-gradient-to-r from-indigo-600 to-purple-600 lg:rounded-t-xl shrink-0">
                            <h2 class="font-bold text-white flex items-center text-sm">
                                <span class="material-icons mr-2 text-base">smart_toy</span>
                                Chat Auditor
                            </h2>
                            <!-- Context Badge -->
                            <div x-show="selectedLog" class="mt-2 bg-white/20 px-2 py-1 rounded text-[10px] text-white flex items-center border border-white/30 backdrop-blur-sm">
                                <span class="material-icons text-[10px] mr-1">api</span>
                                <span>Analizando Evento #<span x-text="selectedLog?.id"></span></span>
                            </div>
                        </div>
                        
                        <!-- Messages -->
                        <div class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50" id="chat-container">
                            <div class="bg-white p-3 rounded-lg rounded-tl-none shadow-sm text-xs md:text-sm text-gray-700 max-w-[90%]">
                                Hola. Soy tu Auditor AI. Selecciona un evento para analizarlo o pregúntame algo general.
                            </div>

                            <template x-for="(msg, idx) in messages" :key="idx">
                                <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                                    <div class="p-3 rounded-lg text-xs md:text-sm max-w-[90%] shadow-sm whitespace-pre-wrap"
                                         :class="msg.role === 'user' ? 'bg-indigo-600 text-white rounded-tr-none' : 'bg-white text-gray-700 rounded-tl-none markdown-body'"
                                         x-html="formatMessage(msg.content)">
                                    </div>
                                </div>
                            </template>
                            
                            <div x-show="aiLoading" class="flex justify-start">
                                <div class="bg-white p-3 rounded-lg rounded-tl-none shadow-sm flex space-x-2">
                                     <div class="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce"></div>
                                     <div class="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                                     <div class="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Input -->
                        <div class="p-3 bg-white border-t border-gray-100 shrink-0 safe-bottom">
                            <form @submit.prevent="sendMessage()" class="relative flex items-center gap-2">
                                <input type="text" x-model="query" :placeholder="selectedLog ? 'Pregunta sobre este evento...' : 'Pregunta general...'" 
                                       class="w-full border border-gray-300 rounded-full pl-4 pr-10 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-shadow">
                                <button type="submit" :disabled="aiLoading || !query" 
                                        class="p-2 bg-indigo-600 text-white rounded-full hover:bg-indigo-700 disabled:opacity-50 transition shadow-md flex-shrink-0">
                                    <span class="material-icons text-sm">send</span>
                                </button>
                            </form>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Action Button for Mobile Chat -->
    <button @click="mobilePanelOpen = true" 
            x-show="!mobilePanelOpen && window.innerWidth < 1024"
            class="fixed bottom-6 right-6 w-14 h-14 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-full shadow-2xl flex items-center justify-center z-40 hover:scale-105 active:scale-95 transition-transform">
        <span class="material-icons text-2xl">smart_toy</span>
    </button>

    <!-- API Key Warning Modal -->
    <div x-show="!apiKey && showApiKeyModal" class="fixed inset-0 bg-black bg-opacity-50 z-[60] flex items-center justify-center p-4" x-cloak>
        <div class="bg-white rounded-xl p-6 max-w-sm w-full">
            <h3 class="font-bold text-gray-800 mb-2">API Key Requerida</h3>
            <p class="text-sm text-gray-600 mb-4">Para usar el diagnóstico AI, necesitas configurar tu API Key de Gemini.</p>
            <input type="text" x-model="tempApiKey" placeholder="Pegar API Key aquí" class="w-full border rounded p-2 mb-4 text-sm">
            <button @click="saveApiKey()" class="w-full bg-indigo-600 text-white py-2 rounded font-bold">Guardar y Continuar</button>
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
                    // Optional: Scroll chat to bottom or show a brief 'Context Set' toast
                },

                saveApiKey() {
                    if (this.tempApiKey) {
                        localStorage.setItem('gemini_api_key', this.tempApiKey);
                        this.apiKey = this.tempApiKey;
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
                                history: this.messages, // Send Full History
                                focusedLog: this.selectedLog // Send Active Context
                            })
                        });
                        const data = await res.json();
                        
                        if (data.status === 'success') {
                            this.messages.push({ role: 'assistant', content: data.response.content });
                        } else {
                            this.messages.push({ role: 'assistant', content: 'Error: ' + data.message });
                        }
                    } catch(e) {
                        this.messages.push({ role: 'assistant', content: 'Error de conexión.' });
                    } finally {
                        this.aiLoading = false;
                        this.scrollToBottom();
                    }
                },

                scrollToBottom() {
                    this.$nextTick(() => {
                        const container = document.getElementById('chat-container');
                        container.scrollTop = container.scrollHeight;
                    });
                },
                
                // Simple formatter for basic markdown-like bolding
                formatMessage(text) {
                    // Convert **text** to <b>text</b>
                    let formatted = text.replace(/\*\*(.*?)\*\*/g, '<b>$1</b>');
                    // Convert * points to bullets
                    formatted = formatted.replace(/^\* /gm, '• ');
                    return formatted;
                },

                getActionColor(action) {
                    const colors = {
                        'create': 'bg-green-500',
                        'update': 'bg-yellow-500',
                        'delete': 'bg-red-500',
                        'transfer': 'bg-blue-500',
                        'payment': 'bg-emerald-500'
                    };
                    return colors[action] || 'bg-gray-500';
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
                    return d.getDate() + '/' + (d.getMonth()+1) + ' ' + d.getHours() + ':' + d.getMinutes();
                },

                formatMoney(value) {
                    return new Intl.NumberFormat('es-VE', { style: 'currency', currency: 'VES' }).format(value);
                }
            }
        }
    </script>
</body>
</html>
