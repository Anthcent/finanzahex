<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Assistant - FinazaPersonal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .message-enter { animation: slideIn 0.3s ease-out; }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Toast Notifications */
        .toast {
            animation: toastSlide 0.3s ease-out;
        }
        @keyframes toastSlide {
            from { opacity: 0; transform: translateX(100%); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        /* Mobile optimizations */
        @media (max-width: 640px) {
            .mobile-full { width: 100vw; }
            .mobile-padding { padding-left: 1rem; padding-right: 1rem; }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 min-h-screen" x-data="aiApp()">
    
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-6xl mx-auto px-4 py-3 sm:py-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <a href="<?= base_url() ?>" class="text-gray-500 hover:text-gray-800">
                    <span class="material-icons">arrow_back</span>
                </a>
                <div>
                    <h1 class="text-xl font-bold text-gray-800 flex items-center">
                        <span class="material-icons text-indigo-600 mr-2">smart_toy</span>
                        AI Asistente
                    </h1>
                    <p class="text-xs text-gray-500">Powered by Google Gemini</p>
                </div>
            </div>
            
            <div class="flex items-center space-x-2">
                <button @click="showHistory = !showHistory" class="p-2 rounded-full bg-purple-50 text-purple-600 hover:bg-purple-100 transition" title="Historial">
                    <span class="material-icons">history</span>
                </button>
                <button @click="showApiKeyModal = true" class="p-2 rounded-full bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition" title="Configuración">
                    <span class="material-icons">settings</span>
                </button>
            </div>
        </div>
    </div>
    
    <!-- History Sidebar -->
    <div x-show="showHistory" class="fixed right-0 top-0 h-full w-full sm:w-80 bg-white shadow-2xl z-40 transform transition-transform" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" x-cloak>
        <div class="h-full flex flex-col">
            <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="font-bold text-gray-800 flex items-center text-sm sm:text-base">
                    <span class="material-icons text-purple-600 mr-2">history</span>
                    Conversaciones Guardadas
                </h2>
                <button @click="showHistory = false" class="text-gray-400 hover:text-gray-600 p-2">
                    <span class="material-icons">close</span>
                </button>
            </div>
            
            <div class="flex-1 overflow-y-auto p-4 space-y-2">
                <template x-for="conv in savedConversations" :key="conv.id">
                    <div class="bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition cursor-pointer relative group">
                        <div @click="confirmLoad(conv.id)">
                            <p class="font-bold text-gray-800 text-sm truncate pr-8" x-text="conv.title"></p>
                            <p class="text-xs text-gray-500" x-text="formatDate(conv.created_at)"></p>
                        </div>
                        <button @click="deleteConv(conv.id)" class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 text-red-400 hover:text-red-600 transition p-1">
                            <span class="material-icons text-[18px]">delete</span>
                        </button>
                    </div>
                </template>
                
                <div x-show="savedConversations.length === 0" class="text-center py-10 text-gray-400">
                    <span class="material-icons text-4xl mb-2">chat_bubble_outline</span>
                    <p class="text-sm">No hay conversaciones guardadas</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Toast Notifications -->
    <div class="fixed top-4 right-4 z-50 space-y-2 w-full sm:w-auto px-4 sm:px-0" style="max-width: 90vw;">
        <template x-for="(toast, idx) in toasts" :key="idx">
            <div class="toast bg-white rounded-lg shadow-2xl p-4 flex items-center space-x-3 border-l-4"
                 :class="{
                     'border-green-500': toast.type === 'success',
                     'border-red-500': toast.type === 'error',
                     'border-blue-500': toast.type === 'info',
                     'border-yellow-500': toast.type === 'warning'
                 }">
                <span class="material-icons text-xl" 
                      :class="{
                          'text-green-500': toast.type === 'success',
                          'text-red-500': toast.type === 'error',
                          'text-blue-500': toast.type === 'info',
                          'text-yellow-500': toast.type === 'warning'
                      }"
                      x-text="toast.type === 'success' ? 'check_circle' : toast.type === 'error' ? 'error' : toast.type === 'info' ? 'info' : 'warning'">
                </span>
                <p class="flex-1 text-sm font-medium text-gray-800" x-text="toast.message"></p>
                <button @click="removeToast(idx)" class="text-gray-400 hover:text-gray-600">
                    <span class="material-icons text-[18px]">close</span>
                </button>
            </div>
        </template>
    </div>
    

    <!-- API Key Modal -->
    <div x-show="showApiKeyModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center px-4" x-cloak>
        <div class="fixed inset-0 bg-black bg-opacity-50" @click="showApiKeyModal = false"></div>
        
        <div class="bg-white rounded-2xl p-6 w-full max-w-md relative z-10 shadow-2xl">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Configurar API Key</h2>
            <p class="text-sm text-gray-600 mb-4">Ingresa tu API key de Google Gemini. Puedes obtenerla en <a href="https://makersuite.google.com/app/apikey" target="_blank" class="text-indigo-600 underline">Google AI Studio</a>.</p>
            
            <!-- Show current key if exists -->
            <div x-show="apiKey" class="mb-3 p-2 bg-green-50 border border-green-200 rounded text-xs">
                <p class="text-green-700 font-bold">✓ API Key configurada</p>
                <p class="text-green-600 font-mono truncate" x-text="apiKey.substring(0, 20) + '...'"></p>
            </div>
            
            <input type="text" 
                   x-model="tempApiKey" 
                   @focus="tempApiKey = apiKey"
                   placeholder="AIza..." 
                   class="w-full border rounded-lg p-3 mb-4 font-mono text-sm">
            
            <div class="flex space-x-3">
                <button @click="showApiKeyModal = false" class="flex-1 py-3 rounded-lg bg-gray-100 text-gray-700 font-bold hover:bg-gray-200">Cancelar</button>
                <button @click="saveApiKey()" class="flex-1 py-3 rounded-lg bg-indigo-600 text-white font-bold hover:bg-indigo-700">Guardar</button>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="max-w-6xl mx-auto px-4 py-6">
        
        <!-- API Key Warning -->
        <div x-show="!apiKey" class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-4 flex items-start space-x-3">
            <span class="material-icons text-yellow-600">warning</span>
            <div class="flex-1">
                <p class="font-bold text-yellow-800">API Key no configurada</p>
                <p class="text-sm text-yellow-700">Configura tu API key de Gemini para comenzar a usar el asistente.</p>
                <button @click="showApiKeyModal = true" class="mt-2 text-sm font-bold text-yellow-800 underline">Configurar ahora</button>
            </div>
        </div>
        
        <!-- Chat Messages -->
        <div class="space-y-4 mb-24">
            <!-- Welcome Message -->
            <div x-show="messages.length === 0" class="bg-white rounded-2xl p-5 sm:p-8 text-center shadow-sm">
                <div class="w-20 h-20 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="material-icons text-4xl text-white">psychology</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">¡Hola! Soy tu asistente financiero</h2>
                <p class="text-gray-600 mb-6">Puedo ayudarte a analizar tus gastos, ingresos y finanzas personales.</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-w-2xl mx-auto">
                    <button @click="query = 'Dame un resumen de mis gastos del mes actual'" class="p-4 bg-indigo-50 hover:bg-indigo-100 rounded-xl text-left transition">
                        <p class="font-bold text-indigo-900 text-sm">📊 Resumen mensual</p>
                        <p class="text-xs text-indigo-700">Ver gastos del mes actual</p>
                    </button>
                    <button @click="query = 'Muéstrame mis gastos más altos de la última semana'" class="p-4 bg-purple-50 hover:bg-purple-100 rounded-xl text-left transition">
                        <p class="font-bold text-purple-900 text-sm">💰 Gastos altos</p>
                        <p class="text-xs text-purple-700">Identificar gastos grandes</p>
                    </button>
                    <button @click="query = '¿En qué categoría gasto más?'" class="p-4 bg-pink-50 hover:bg-pink-100 rounded-xl text-left transition">
                        <p class="font-bold text-pink-900 text-sm">📈 Análisis por categoría</p>
                        <p class="text-xs text-pink-700">Ver distribución de gastos</p>
                    </button>
                    <button @click="query = 'Compara mis gastos de este mes con el anterior'" class="p-4 bg-teal-50 hover:bg-teal-100 rounded-xl text-left transition">
                        <p class="font-bold text-teal-900 text-sm">🔄 Comparación</p>
                        <p class="text-xs text-teal-700">Comparar períodos</p>
                    </button>
                </div>
            </div>
            
            <!-- Messages -->
            <template x-for="(msg, idx) in messages" :key="idx">
                <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'" class="message-enter">
                    <!-- User Message -->
                    <div x-show="msg.role === 'user'" class="bg-indigo-600 text-white rounded-2xl px-4 py-3 max-w-lg shadow-md">
                        <p class="text-sm" x-text="msg.content"></p>
                    </div>
                    
                    <!-- AI Message -->
                    <div x-show="msg.role === 'assistant'" class="bg-white rounded-2xl p-6 max-w-3xl shadow-md w-full">
                        <!-- Summary Type -->
                        <div x-show="msg.data?.type === 'summary'">
                            <h3 class="text-lg font-bold text-gray-800 mb-4" x-text="msg.data.title"></h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                <div class="bg-indigo-50 rounded-xl p-4">
                                    <p class="text-xs text-indigo-600 font-bold uppercase">Total</p>
                                    <p class="text-2xl font-bold text-indigo-900" x-text="formatMoney(msg.data.data.total)"></p>
                                </div>
                                <div class="bg-purple-50 rounded-xl p-4">
                                    <p class="text-xs text-purple-600 font-bold uppercase">Cantidad</p>
                                    <p class="text-2xl font-bold text-purple-900" x-text="msg.data.data.count"></p>
                                </div>
                                <div class="bg-pink-50 rounded-xl p-4">
                                    <p class="text-xs text-pink-600 font-bold uppercase">Promedio</p>
                                    <p class="text-2xl font-bold text-pink-900" x-text="formatMoney(msg.data.data.average)"></p>
                                </div>
                                <div class="bg-teal-50 rounded-xl p-4">
                                    <p class="text-xs text-teal-600 font-bold uppercase">Período</p>
                                    <p class="text-sm font-bold text-teal-900" x-text="msg.data.data.period"></p>
                                </div>
                            </div>
                            <div x-show="msg.data.insights" class="bg-gray-50 rounded-xl p-4">
                                <p class="text-xs font-bold text-gray-500 uppercase mb-2">Insights</p>
                                <ul class="space-y-1">
                                    <template x-for="insight in msg.data.insights">
                                        <li class="text-sm text-gray-700 flex items-start">
                                            <span class="material-icons text-indigo-600 text-[16px] mr-2">lightbulb</span>
                                            <span x-text="insight"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Cards Type -->
                        <div x-show="msg.data?.type === 'cards'">
                            <h3 class="text-lg font-bold text-gray-800 mb-4" x-text="msg.data.title"></h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <template x-for="card in msg.data.data">
                                    <div class="rounded-xl p-4 border-l-4" :class="'bg-' + (card.color || 'gray') + '-50 border-' + (card.color || 'gray') + '-500'">
                                        <p class="font-bold text-gray-800 mb-1" x-text="card.title"></p>
                                        <p class="text-2xl font-bold mb-2" :class="'text-' + (card.color || 'gray') + '-900'" x-text="formatMoney(card.amount)"></p>
                                        <p class="text-xs text-gray-600" x-text="card.description"></p>
                                    </div>
                                </template>
                            </div>
                        </div>
                        
                        <!-- Table Type -->
                        <div x-show="msg.data?.type === 'table'">
                            <h3 class="text-lg font-bold text-gray-800 mb-4" x-text="msg.data.title"></h3>
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <template x-for="header in msg.data.data.headers">
                                                <th class="px-4 py-2 text-left text-xs font-bold text-gray-600 uppercase" x-text="header"></th>
                                            </template>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <template x-for="row in msg.data.data.rows">
                                            <tr class="hover:bg-gray-50">
                                                <template x-for="cell in row">
                                                    <td class="px-4 py-3 text-sm text-gray-700" x-text="cell"></td>
                                                </template>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Text/Error Type -->
                        <div x-show="msg.data?.type === 'text' || msg.data?.type === 'error'">
                            <p class="text-sm text-gray-700 whitespace-pre-wrap" x-text="msg.data.content || msg.data.message"></p>
                        </div>
                        
                        <!-- List Type -->
                        <div x-show="msg.data?.type === 'list'">
                            <h3 class="text-lg font-bold text-gray-800 mb-4" x-text="msg.data.title"></h3>
                            <div class="space-y-2">
                                <template x-for="(item, idx) in msg.data.data">
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                                        <div class="flex items-center space-x-3">
                                            <span class="w-8 h-8 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center font-bold text-sm" x-text="idx + 1"></span>
                                            <div>
                                                <p class="font-bold text-gray-800" x-text="item.title"></p>
                                                <p class="text-xs text-gray-500" x-text="item.description"></p>
                                            </div>
                                        </div>
                                        <p class="font-bold text-lg" :class="item.amount < 0 ? 'text-red-600' : 'text-green-600'" x-text="formatMoney(item.amount)"></p>
                                    </div>
                                </template>
                            </div>
                        </div>
                        
                        <!-- Comparison Type -->
                        <div x-show="msg.data?.type === 'comparison'">
                            <h3 class="text-lg font-bold text-gray-800 mb-4" x-text="msg.data.title"></h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="bg-blue-50 rounded-xl p-6 border-2 border-blue-200">
                                    <p class="text-xs text-blue-600 font-bold uppercase mb-2" x-text="msg.data.data?.period1?.label || 'Periodo 1'"></p>
                                    <p class="text-3xl font-bold text-blue-900 mb-2" x-text="formatMoney(msg.data.data?.period1?.value || 0)"></p>
                                    <p class="text-xs text-blue-700" x-text="msg.data.data?.period1?.description || ''"></p>
                                </div>
                                <div class="bg-purple-50 rounded-xl p-6 border-2 border-purple-200">
                                    <p class="text-xs text-purple-600 font-bold uppercase mb-2" x-text="msg.data.data?.period2?.label || 'Periodo 2'"></p>
                                    <p class="text-3xl font-bold text-purple-900 mb-2" x-text="formatMoney(msg.data.data?.period2?.value || 0)"></p>
                                    <p class="text-xs text-purple-700" x-text="msg.data.data?.period2?.description || ''"></p>
                                </div>
                            </div>
                            <div class="mt-4 p-4 bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl">
                                <p class="text-sm font-bold text-gray-800 mb-2">Diferencia</p>
                                <p class="text-2xl font-bold" :class="(msg.data.data?.difference || 0) >= 0 ? 'text-green-600' : 'text-red-600'" x-text="((msg.data.data?.difference || 0) >= 0 ? '+' : '') + formatMoney(msg.data.data?.difference || 0)"></p>
                                <p class="text-xs text-gray-600 mt-1" x-text="(msg.data.data?.differencePercent || 0) + '% ' + ((msg.data.data?.difference || 0) >= 0 ? 'más' : 'menos') + ' que el período anterior'"></p>
                            </div>
                        </div>
                        
                        <!-- Progress Bars Type -->
                        <div x-show="msg.data?.type === 'progress'">
                            <h3 class="text-lg font-bold text-gray-800 mb-4" x-text="msg.data.title"></h3>
                            <div class="space-y-4">
                                <template x-for="item in msg.data.data">
                                    <div>
                                        <div class="flex justify-between mb-1">
                                            <span class="text-sm font-bold text-gray-700" x-text="item.label"></span>
                                            <span class="text-sm font-bold" :class="'text-' + (item.color || 'indigo') + '-600'" x-text="formatMoney(item.value)"></span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-3">
                                            <div class="h-3 rounded-full transition-all duration-500" :class="'bg-' + (item.color || 'indigo') + '-500'" :style="'width: ' + item.percentage + '%'"></div>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1" x-text="item.percentage + '% del total'"></p>
                                    </div>
                                </template>
                            </div>
                        </div>
                        
                        <!-- Timeline Type -->
                        <div x-show="msg.data?.type === 'timeline'">
                            <h3 class="text-lg font-bold text-gray-800 mb-4" x-text="msg.data.title"></h3>
                            <div class="relative">
                                <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>
                                <div class="space-y-4">
                                    <template x-for="event in msg.data.data">
                                        <div class="relative pl-10">
                                            <div class="absolute left-2 w-4 h-4 rounded-full" :class="'bg-' + (event.color || 'indigo') + '-500'"></div>
                                            <div class="bg-white border border-gray-200 rounded-lg p-3">
                                                <p class="text-xs text-gray-500" x-text="event.date"></p>
                                                <p class="font-bold text-gray-800" x-text="event.title"></p>
                                                <p class="text-sm text-gray-600" x-text="event.description"></p>
                                                <p class="text-lg font-bold mt-1" :class="event.amount < 0 ? 'text-red-600' : 'text-green-600'" x-text="formatMoney(event.amount)"></p>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Forecast/Projection Type -->
                        <div x-show="msg.data?.type === 'forecast'">
                            <h3 class="text-lg font-bold text-gray-800 mb-4" x-text="msg.data.title"></h3>
                            
                            <!-- Current vs Projected -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                                <div class="bg-blue-50 rounded-xl p-6 border-2 border-blue-200">
                                    <p class="text-xs text-blue-600 font-bold uppercase mb-2">Actual (Histórico)</p>
                                    <p class="text-3xl font-bold text-blue-900" x-text="formatMoney(msg.data.data?.current || 0)"></p>
                                    <p class="text-xs text-blue-700 mt-1" x-text="msg.data.data?.currentPeriod || ''"></p>
                                </div>
                                <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-6 border-2 border-purple-200">
                                    <p class="text-xs text-purple-600 font-bold uppercase mb-2">Proyección</p>
                                    <p class="text-3xl font-bold text-purple-900" x-text="formatMoney(msg.data.data?.projected || 0)"></p>
                                    <p class="text-xs text-purple-700 mt-1" x-text="msg.data.data?.projectedPeriod || ''"></p>
                                </div>
                            </div>
                            
                            <!-- Trend Indicator -->
                            <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl p-4 mb-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-bold text-gray-800">Tendencia</p>
                                        <p class="text-xs text-gray-600" x-text="msg.data.data?.trend || ''"></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-2xl font-bold" :class="(msg.data.data?.change || 0) >= 0 ? 'text-red-600' : 'text-green-600'" x-text="((msg.data.data?.change || 0) >= 0 ? '+' : '') + (msg.data.data?.change || 0) + '%'"></p>
                                        <p class="text-xs text-gray-500">vs período anterior</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Breakdown if available -->
                            <div x-show="msg.data.data?.breakdown" class="space-y-2">
                                <p class="text-sm font-bold text-gray-700 mb-2">Desglose de Proyección:</p>
                                <template x-for="item in (msg.data.data?.breakdown || [])">
                                    <div class="flex justify-between items-center p-3 bg-white rounded-lg border border-gray-200">
                                        <span class="text-sm text-gray-700" x-text="item.category"></span>
                                        <span class="font-bold text-gray-800" x-text="formatMoney(item.amount)"></span>
                                    </div>
                                </template>
                            </div>
                            
                            <!-- Insights -->
                            <div x-show="msg.data.insights" class="mt-4 bg-amber-50 border border-amber-200 rounded-xl p-4">
                                <p class="text-xs font-bold text-amber-800 uppercase mb-2 flex items-center">
                                    <span class="material-icons text-[16px] mr-1">tips_and_updates</span>
                                    Recomendaciones
                                </p>
                                <ul class="space-y-1">
                                    <template x-for="insight in msg.data.insights">
                                        <li class="text-sm text-amber-900" x-text="'• ' + insight"></li>
                                    </template>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            
            <!-- Loading -->
            <div x-show="loading" class="flex justify-start message-enter">
                <div class="bg-white rounded-2xl px-6 py-4 shadow-md">
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-indigo-600 rounded-full animate-bounce"></div>
                        <div class="w-2 h-2 bg-indigo-600 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                        <div class="w-2 h-2 bg-indigo-600 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Floating Save Button -->
    <button x-show="messages.length > 0" 
            @click="showSaveModal = true; saveTitle = messages[0]?.content?.substring(0, 50) || 'Conversación'" 
            class="fixed bottom-20 sm:bottom-24 right-4 sm:right-6 bg-purple-600 text-white p-3 sm:p-4 rounded-full shadow-2xl hover:bg-purple-700 transition-all hover:scale-110 z-30"
            x-transition
            title="Guardar conversación">
        <span class="material-icons text-2xl sm:text-base">bookmark</span>
    </button>
    
    <!-- Save Conversation Modal -->
    <div x-show="showSaveModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center px-4" x-cloak>
        <div class="fixed inset-0" @click="showSaveModal = false"></div>
        <div class="bg-white rounded-2xl p-6 w-full max-w-md relative z-10 shadow-2xl">
            <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                <span class="material-icons text-purple-600 mr-2">bookmark</span>
                Guardar Conversación
            </h2>
            <p class="text-sm text-gray-600 mb-4">Esta conversación tiene <strong x-text="messages.length"></strong> mensajes. Dale un título para encontrarla después.</p>
            
            <input type="text" x-model="saveTitle" placeholder="Ej: Análisis de gastos enero" class="w-full border rounded-lg p-3 mb-4" @keyup.enter="saveConversation()">
            
            <div class="flex space-x-3">
                <button @click="showSaveModal = false" class="flex-1 py-3 rounded-lg bg-gray-100 text-gray-700 font-bold hover:bg-gray-200">Cancelar</button>
                <button @click="saveConversation()" class="flex-1 py-3 rounded-lg bg-purple-600 text-white font-bold hover:bg-purple-700 flex items-center justify-center">
                    <span class="material-icons text-sm mr-1">save</span>
                    Guardar
                </button>
            </div>
        </div>
    </div>
    
    <!-- Input Bar (Fixed Bottom) -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg">
        <div class="max-w-6xl mx-auto px-4 py-2 sm:py-4">
            <form @submit.prevent="sendMessage()" class="flex space-x-3">
                <input type="text" 
                       x-model="query" 
                       placeholder="Pregunta algo sobre tus finanzas..." 
                       class="flex-1 border border-gray-300 rounded-full px-6 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                       :disabled="!apiKey || loading">
                <button type="submit" 
                        :disabled="!apiKey || !query.trim() || loading"
                        class="bg-indigo-600 text-white px-4 sm:px-6 py-3 rounded-full font-bold hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2 transition">
                    <span x-show="!loading" class="hidden sm:inline">Enviar</span>
                    <span x-show="loading" class="hidden sm:inline">Pensando...</span>
                    <!-- Icon only on mobile -->
                    <span class="material-icons text-xl sm:text-base" x-show="!loading">send</span>
                    <div x-show="loading" class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin sm:hidden"></div>
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
                        console.log('API Key guardada:', this.apiKey.substring(0, 20) + '...');
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
                            // Scroll to bottom
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
