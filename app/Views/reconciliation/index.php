<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Conciliación financiera | Finanzahex</title>
<script src="https://cdn.tailwindcss.com"></script><script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<style>[x-cloak]{display:none!important}body{font-family:Inter,system-ui}.scrollbar::-webkit-scrollbar{width:5px}.scrollbar::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:9px}</style>
</head>
<body class="bg-slate-100 text-slate-900 min-h-screen" x-data="reconciliationApp()" x-init="init()">
<header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-slate-200"><div class="max-w-7xl mx-auto h-16 px-3 sm:px-6 flex items-center gap-3">
<a href="<?= base_url('/') ?>" class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center"><span class="material-icons text-lg">arrow_back</span></a><div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center"><span class="material-icons">fact_check</span></div>
<div class="min-w-0"><h1 class="font-black text-base sm:text-lg leading-tight">Conciliación financiera</h1><p class="text-[10px] sm:text-xs text-slate-500 font-semibold truncate">Comprobantes, bancos y deudas en un solo lugar</p></div>
<button @click="settingsOpen=true" class="ml-auto w-10 h-10 rounded-xl border bg-white flex items-center justify-center" title="Clave Gemini"><span class="material-icons text-lg" :class="apiKey?'text-emerald-600':'text-amber-500'">key</span></button><a href="<?= base_url('history') ?>" class="hidden sm:flex h-10 px-3 rounded-xl bg-slate-900 text-white items-center gap-1 text-xs font-black"><span class="material-icons text-base">receipt_long</span>Registro</a>
</div></header>
<main class="max-w-7xl mx-auto p-3 sm:p-6 grid lg:grid-cols-[310px_1fr] gap-4 pb-24">
<aside class="space-y-4">
<section class="bg-white rounded-3xl border border-slate-200 p-4 shadow-sm">
<div class="grid grid-cols-2 bg-slate-100 rounded-2xl p-1 mb-4"><button @click="importType='payment_capture';files=[]" :class="importType==='payment_capture'?'bg-white text-emerald-700 shadow-sm':'text-slate-500'" class="rounded-xl py-2.5 text-[10px] font-black"><span class="material-icons text-base align-middle">photo_camera</span> Comprobante</button><button @click="importType='bank_statement';files=[]" :class="importType==='bank_statement'?'bg-white text-blue-700 shadow-sm':'text-slate-500'" class="rounded-xl py-2.5 text-[10px] font-black"><span class="material-icons text-base align-middle">account_balance</span> Estado bancario</button></div>
<h2 class="font-black text-sm" x-text="importType==='payment_capture'?'Conciliar un pago':'Importar movimientos'"></h2><p class="text-[11px] text-slate-500 mt-1 mb-4" x-text="importType==='payment_capture'?'Pago móvil, transferencia, Zelle o depósito.':'PDF, CSV o capturas del estado de cuenta.'"></p>
<label class="block text-[10px] font-black text-slate-500 uppercase mb-1.5">Cuenta relacionada</label><select x-model="accountId" class="w-full h-11 bg-slate-50 border rounded-xl px-3 text-xs font-bold"><option value="">Seleccionar cuenta</option><template x-for="a in accounts" :key="a.id"><option :value="a.id" x-text="a.name+' · '+a.currency"></option></template></select>
<label class="mt-3 border-2 border-dashed border-slate-200 hover:border-emerald-400 bg-slate-50 rounded-2xl min-h-32 flex flex-col items-center justify-center cursor-pointer px-4 text-center"><span class="material-icons text-3xl text-emerald-600" x-text="importType==='payment_capture'?'add_a_photo':'upload_file'"></span><span class="text-xs font-black mt-2" x-text="files.length?files.length+' archivo(s) listo(s)':'Toca para seleccionar'"></span><span class="text-[10px] text-slate-400 mt-1">JPG, PNG, PDF o CSV</span><input type="file" class="hidden" :multiple="importType==='bank_statement'" :accept="importType==='payment_capture'?'image/*':'image/*,.pdf,.csv'" @change="pickFiles($event)"></label>
<template x-if="files.length"><div class="mt-2 space-y-1"><div class="flex items-center justify-between"><span class="text-[9px] font-black text-slate-400 uppercase">Selección temporal</span><button type="button" @click="clearFiles()" class="text-[10px] font-black text-rose-600 hover:text-rose-700">Vaciar selección</button></div><template x-for="f in files" :key="f.name"><div class="flex gap-2 bg-slate-50 rounded-lg px-2 py-2 text-[10px] font-bold"><span class="material-icons text-sm">description</span><span class="truncate" x-text="f.name"></span></div></template></div></template>
<button @click="scan()" :disabled="loading||!files.length||!accountId" class="w-full h-12 mt-4 rounded-2xl bg-emerald-600 disabled:bg-slate-300 text-white font-black text-xs flex justify-center items-center gap-2"><span x-show="loading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span><span class="material-icons text-lg" x-show="!loading">document_scanner</span><span x-text="loading?'Analizando...':'Analizar y preparar'"></span></button><p class="text-[9px] text-slate-400 text-center mt-2">Nada afecta saldos hasta confirmar.</p>
</section>
<section class="bg-white rounded-3xl border p-4"><div class="flex justify-between mb-3"><h3 class="text-xs font-black">Importaciones recientes</h3><button @click="loadBatches()" class="material-icons text-base text-slate-400">refresh</button></div><div class="space-y-2 max-h-64 overflow-y-auto scrollbar"><template x-for="b in batches" :key="b.id"><div :class="Number(activeBatch)===Number(b.id)?'border-emerald-400 bg-emerald-50':'border-slate-100 bg-slate-50'" class="flex items-stretch border rounded-xl overflow-hidden"><button @click="openBatch(b.id)" class="min-w-0 flex-1 p-3 text-left"><div class="flex gap-2"><span class="material-icons text-base" :class="b.import_type==='payment_capture'?'text-emerald-600':'text-blue-600'" x-text="b.import_type==='payment_capture'?'payments':'account_balance'"></span><span class="text-[10px] font-black truncate" x-text="b.source_name"></span><span class="ml-auto text-[9px] font-black" x-text="Number(b.pending_count||0)+Number(b.duplicate_count||0)>0?'Pendiente':'Lista'"></span></div><p class="text-[9px] text-slate-400 mt-1"><span x-text="dateLabel(b.created_at)"></span> · <span x-text="b.applied_count||0"></span>/<span x-text="b.item_count"></span></p></button><button x-show="Number(b.applied_count||0)===0" @click="deleteBatch(b)" class="w-10 border-l text-rose-500 hover:bg-rose-50" title="Eliminar importación"><span class="material-icons text-base">delete_outline</span></button></div></template><p x-show="!batches.length" class="text-[11px] text-slate-400 text-center py-4">Aún no hay importaciones.</p></div></section>
</aside>
<section class="min-w-0 space-y-4">
<div class="bg-white border rounded-2xl p-1 grid grid-cols-2 gap-1"><button @click="setView('review')" :class="viewMode==='review'?'bg-slate-900 text-white shadow-sm':'text-slate-500'" class="h-11 rounded-xl text-xs font-black">Por revisar <span class="ml-1 opacity-70" x-text="reviewCount"></span></button><button @click="setView('history')" :class="viewMode==='history'?'bg-emerald-600 text-white shadow-sm':'text-slate-500'" class="h-11 rounded-xl text-xs font-black">Historial <span class="ml-1 opacity-70" x-text="historyCount"></span></button></div>
<div class="bg-white border rounded-3xl overflow-hidden"><div class="p-4 border-b flex flex-col sm:flex-row gap-3 sm:items-center"><div><h2 class="font-black text-sm" x-text="viewMode==='review'?'Bandeja de revisión':'Movimientos procesados'"></h2><p class="text-[10px] text-slate-400" x-text="viewMode==='review'?'Solo aparece lo que requiere una decisión.':'Consulta lo aplicado o ignorado de esta importación.'"></p></div><div class="sm:ml-auto flex flex-wrap gap-2"><button x-show="activeBatch&&currentBatch&&Number(currentBatch.applied_count||0)===0" @click="deleteBatch(currentBatch)" class="h-10 px-3 rounded-xl bg-rose-50 text-rose-600 text-[10px] font-black"><span class="material-icons text-sm align-middle">delete_outline</span> Eliminar importación</button><input x-model="search" placeholder="Buscar..." class="h-10 min-w-0 w-full sm:w-44 px-3 bg-slate-50 border rounded-xl text-xs"><select x-show="viewMode==='review'" x-model="filter" class="h-10 bg-slate-50 border rounded-xl px-2 text-[10px] font-black"><option value="all">Todos</option><option value="pending">Pendientes</option><option value="duplicate">Duplicados</option></select><select x-show="viewMode==='history'" x-model="filter" class="h-10 bg-slate-50 border rounded-xl px-2 text-[10px] font-black"><option value="all">Todos</option><option value="applied">Aplicados</option><option value="ignored">Ignorados</option></select></div></div>
<div class="divide-y"><template x-for="item in filteredItems" :key="item.id"><article class="p-3 sm:p-4">
<div class="flex gap-3"><div class="w-11 h-11 shrink-0 rounded-2xl flex items-center justify-center" :class="item.direction==='credit'?'bg-emerald-100 text-emerald-700':item.direction==='debit'?'bg-rose-100 text-rose-700':'bg-amber-100 text-amber-700'"><span class="material-icons" x-text="item.direction==='credit'?'south_west':item.direction==='debit'?'north_east':'help_outline'"></span></div><div class="min-w-0 flex-1"><div class="flex flex-wrap gap-1"><p class="font-black text-sm truncate" x-text="originalTitle(item)||item.counterparty||item.description||'Movimiento bancario'"></p><span class="text-[9px] font-black rounded-md px-1.5 py-1" :class="statusClass(item.status)" x-text="statusLabel(item.status)"></span><span x-show="item.direction==='unknown'" class="text-[9px] font-black rounded-md px-1.5 py-1 bg-amber-100 text-amber-800">Define ingreso o egreso</span><span x-show="item.suggested_action==='transfer'" class="text-[9px] font-black rounded-md px-1.5 py-1 bg-blue-100 text-blue-700">Posible traslado</span></div><div class="mt-1 text-[10px] text-slate-500 font-semibold"><span x-text="dateLabel(item.movement_date)"></span><span x-show="item.reference"> · Ref. <b x-text="item.reference"></b></span><span x-show="item.bank"> · <span x-text="item.bank"></span></span></div><div x-show="item.matched_order_id" class="mt-2 rounded-xl bg-emerald-50 border border-emerald-100 p-2 text-[10px] font-black"><span class="text-emerald-600">Deuda sugerida:</span> Orden #<span x-text="item.matched_order_id"></span> · <span x-text="item.customer_name"></span></div></div><div class="text-right shrink-0"><p class="font-black text-sm" :class="item.direction==='credit'?'text-emerald-700':item.direction==='debit'?'text-rose-600':'text-amber-700'" x-text="(item.direction==='credit'?'+':item.direction==='debit'?'−':'? ')+money(item.amount,item.currency)"></p><p class="text-[9px] text-slate-400 font-bold" x-text="item.account_name"></p></div></div>
<div x-show="viewMode==='review'&&(item.status==='pending'||item.status==='duplicate')" class="mt-3 flex flex-wrap items-center gap-2"><div class="flex rounded-xl bg-slate-100 p-1"><button @click="setDirection(item,'credit')" :class="item.direction==='credit'?'bg-emerald-600 text-white shadow-sm':'text-slate-500'" class="h-9 px-3 rounded-lg text-[9px] font-black flex items-center gap-1"><span class="material-icons text-sm">south_west</span> Entró dinero</button><button @click="setDirection(item,'debit')" :class="item.direction==='debit'?'bg-rose-600 text-white shadow-sm':'text-slate-500'" class="h-9 px-3 rounded-lg text-[9px] font-black flex items-center gap-1"><span class="material-icons text-sm">north_east</span> Salió dinero</button></div><select x-model="item.suggested_action" @change="setSuggestedAction(item)" class="h-11 rounded-xl border bg-white px-2 text-[10px] font-black"><option value="transaction">Ingreso / gasto normal</option><option value="payment">Abono recibido</option><option value="transfer">Entre mis cuentas</option></select><div class="ml-auto flex gap-2"><button @click="openReview(item)" class="h-10 px-3 rounded-xl border text-[10px] font-black">Revisar</button><button @click="ignore(item)" class="h-10 px-3 rounded-xl bg-slate-100 text-[10px] font-black">Ignorar</button><button @click="prepareQuickApply(item)" class="h-10 px-3 rounded-xl bg-slate-900 text-white text-[10px] font-black" x-text="quickApplyLabel(item)"></button></div>
<div x-cloak x-show="Number(confirmingItemId)===Number(item.id)" class="w-full mt-1 rounded-2xl border p-3 flex flex-col sm:flex-row sm:items-center gap-3" :class="item.direction==='credit'?'bg-emerald-50 border-emerald-200':'bg-rose-50 border-rose-200'"><div class="min-w-0"><p class="text-[11px] font-black" x-text="item.direction==='credit'?'¿Confirmar este ingreso?':'¿Confirmar este egreso?'"></p><p class="text-[9px] text-slate-500">Esta acción actualizará el saldo de la cuenta.</p></div><div class="sm:ml-auto flex gap-2"><button @click="confirmingItemId=null" class="h-9 px-3 rounded-xl bg-white border text-[10px] font-black">Cancelar</button><button @click="confirmQuickApply(item)" class="h-9 px-4 rounded-xl text-white text-[10px] font-black" :class="item.direction==='credit'?'bg-emerald-600':'bg-rose-600'" x-text="item.direction==='credit'?'Sí, registrar ingreso':'Sí, registrar egreso'"></button></div></div></div>
</article></template><div x-show="!filteredItems.length" class="py-20 px-4 text-center"><span class="material-icons text-5xl text-slate-200" x-text="viewMode==='review'?'task_alt':'history'"></span><p class="text-sm font-black text-slate-500" x-text="viewMode==='review'?'No hay movimientos por revisar':'No hay movimientos procesados en esta importación'"></p><p x-show="viewMode==='review'" class="text-[10px] text-slate-400 mt-1">Escanea un archivo nuevo o abre una importación pendiente.</p></div></div>
</div></section></main>
<aside x-cloak x-show="review.open" @keydown.escape.window="review.open=false" class="fixed top-20 right-3 left-3 sm:left-auto z-50 sm:w-[430px] max-h-[calc(100vh-6rem)] bg-white rounded-3xl border border-slate-200 shadow-2xl overflow-y-auto scrollbar">
<div class="sticky top-0 bg-white/95 backdrop-blur px-4 py-3 border-b flex z-10"><div class="min-w-0"><h3 class="font-black text-sm" x-text="originalTitle(review.item)||'Detalle del movimiento'"></h3><p class="text-[10px] text-slate-400 truncate" x-text="currentBatch?currentBatch.source_name:'Datos extraídos del comprobante'"></p></div><button @click="review.open=false" class="ml-auto w-9 h-9 shrink-0 rounded-xl bg-slate-100"><span class="material-icons text-base">close</span></button></div>
<div class="p-4 space-y-3" x-show="review.item">
<div x-show="review.item.direction==='unknown'" class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-[11px] text-amber-900 font-bold">No fue posible saber si el dinero entró o salió. Confírmalo manualmente antes de registrar.</div>
<div class="grid grid-cols-2 gap-3"><label class="text-[10px] font-black">Monto<input type="number" step=".01" x-model.number="review.item.amount" class="mt-1 w-full h-10 rounded-xl border px-3 font-black"></label><label class="text-[10px] font-black">Moneda<select x-model="review.item.currency" class="mt-1 w-full h-10 rounded-xl border px-3 font-black"><option>BS</option><option>USD</option><option>EUR</option></select></label></div>
<div><p class="text-[10px] font-black mb-2">¿Qué ocurrió con el dinero?</p><div class="grid grid-cols-2 gap-2"><button type="button" @click="setReviewDirection('credit')" :class="review.item.direction==='credit'?'bg-emerald-600 border-emerald-600 text-white shadow-md':'bg-emerald-50 border-emerald-200 text-emerald-800'" class="rounded-2xl border p-3 text-left transition"><span class="material-icons text-xl">south_west</span><span class="block text-xs font-black">Entró dinero</span><span class="block text-[9px] opacity-75">Ingreso, cobro o pago recibido</span></button><button type="button" @click="setReviewDirection('debit')" :class="review.item.direction==='debit'?'bg-rose-600 border-rose-600 text-white shadow-md':'bg-rose-50 border-rose-200 text-rose-800'" class="rounded-2xl border p-3 text-left transition"><span class="material-icons text-xl">north_east</span><span class="block text-xs font-black">Salió dinero</span><span class="block text-[9px] opacity-75">Gasto, compra o pago realizado</span></button></div></div><label class="block text-[10px] font-black">Fecha y hora<input type="datetime-local" x-model="review.item.movement_date_local" class="mt-1 w-full h-10 rounded-xl border px-2 text-[11px]"></label>
<div class="grid grid-cols-2 gap-3"><label class="text-[10px] font-black">Banco<input x-model="review.item.bank" class="mt-1 w-full h-10 rounded-xl border px-3 text-xs"></label><label class="text-[10px] font-black">Tasa<input type="number" step=".0001" x-model.number="review.item.exchange_rate" class="mt-1 w-full h-10 rounded-xl border px-3 text-xs"></label></div>
<label class="block text-[10px] font-black">Referencia<input x-model="review.item.reference" class="mt-1 w-full h-10 rounded-xl border px-3 text-xs"></label><label class="block text-[10px] font-black">Contraparte<input x-model="review.item.counterparty" class="mt-1 w-full h-10 rounded-xl border px-3 text-xs"></label><label class="block text-[10px] font-black">Descripción completa<textarea x-model="review.item.description" rows="3" class="mt-1 w-full rounded-xl border p-3 text-xs"></textarea></label>
<div><p class="text-[10px] font-black mb-2">¿Cómo deseas registrarlo?</p><div class="grid grid-cols-3 gap-2"><template x-for="a in [{v:'transaction',i:'receipt_long',l:'Ingreso / gasto'},{v:'payment',i:'payments',l:'Abono recibido'},{v:'transfer',i:'sync_alt',l:'Entre cuentas'}]"><button type="button" @click="setReviewAction(a.v)" :class="review.action===a.v?'bg-slate-900 text-white ring-2 ring-slate-300':'bg-slate-100 text-slate-600'" class="py-3 px-1 rounded-xl text-[9px] font-black"><span class="material-icons text-lg block" x-text="a.i"></span><span x-text="a.l"></span></button></template></div></div>
<label x-show="review.action==='payment'" class="block text-[10px] font-black">Deuda de impresiones<select x-model="review.orderId" class="mt-1 w-full h-11 rounded-xl border border-emerald-200 bg-emerald-50 px-3 text-xs font-black"><option value="">Seleccionar deuda</option><template x-for="d in debts" :key="d.id"><option :value="d.id" x-text="'#'+d.id+' · '+d.customer_name+' · '+money(d.remaining_bs,'BS')"></option></template></select></label>
<label x-show="review.action==='transaction'" class="block text-[10px] font-black">Categoría<select x-model="review.categoryId" class="mt-1 w-full h-11 rounded-xl border bg-slate-50 px-3 text-xs font-black"><option value="">Categoría automática</option><template x-for="c in categories.filter(c=>c.type===(review.item.direction==='credit'?'income':'expense'))" :key="c.id"><option :value="c.id" x-text="c.name"></option></template></select></label>
<label x-show="review.action==='transfer'" class="block text-[10px] font-black">Otra cuenta del traslado<select x-model="review.destinationId" class="mt-1 w-full h-11 rounded-xl border border-blue-200 bg-blue-50 px-3 text-xs font-black"><option value="">Seleccionar la otra cuenta</option><template x-for="a in accounts.filter(a=>Number(a.id)!==Number(review.item.account_id))" :key="a.id"><option :value="a.id" x-text="a.name+' · '+a.currency"></option></template></select><span class="block mt-1 text-[9px] text-slate-400" x-text="review.item.direction==='credit'?'Esta será la cuenta de origen.':'Esta será la cuenta destino.'"></span></label>
<details class="rounded-xl bg-slate-950 text-slate-200 p-3"><summary class="cursor-pointer text-[10px] font-black">Todos los datos extraídos</summary><pre class="mt-2 text-[9px] whitespace-pre-wrap break-words" x-text="prettyMeta(review.item.meta_json)"></pre></details>
<div class="text-[9px] text-slate-400">Confianza de lectura: <b x-text="Number(review.item.confidence||0).toFixed(0)+'%'"></b></div>
<div x-show="review.item.status==='duplicate'" class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-[11px] text-amber-800 font-bold">Coincide con un movimiento importado. Confirma solo si verificaste que es distinto.</div><button @click="saveAndApply()" :disabled="review.loading||review.item.direction==='unknown'" :class="review.item.direction==='debit'?'bg-rose-600':'bg-emerald-600'" class="w-full h-11 rounded-2xl disabled:bg-slate-300 text-white font-black text-xs" x-text="review.loading?'Guardando...':review.item.direction==='unknown'?'Selecciona qué ocurrió':review.item.direction==='credit'?'Confirmar ingreso':'Confirmar egreso'"></button>
</div></aside>
<div x-cloak x-show="settingsOpen" class="fixed inset-0 z-[60] flex items-center justify-center p-4"><div class="absolute inset-0 bg-slate-950/60" @click="settingsOpen=false"></div><div class="relative bg-white rounded-3xl p-5 w-full max-w-sm"><h3 class="font-black">Conexión Gemini</h3><p class="text-xs text-slate-500 mt-1">Se guarda solo en este dispositivo. CSV no necesita clave.</p><input type="password" x-model="tempKey" placeholder="AIza..." class="mt-4 w-full h-11 rounded-xl border px-3 text-xs"><div class="flex gap-2 mt-3"><button @click="settingsOpen=false" class="flex-1 h-10 rounded-xl bg-slate-100 text-xs font-black">Cancelar</button><button @click="saveKey()" class="flex-1 h-10 rounded-xl bg-emerald-600 text-white text-xs font-black">Guardar</button></div></div></div>
<div x-cloak x-show="toast" class="fixed bottom-5 left-1/2 -translate-x-1/2 z-[80] max-w-[90vw] px-4 py-3 rounded-2xl text-white text-xs font-black shadow-xl" :class="toastType==='error'?'bg-rose-600':'bg-slate-900'" x-text="toast"></div>
<script>
function reconciliationApp() {
    return {
        accounts: <?= json_encode($accounts, JSON_UNESCAPED_UNICODE) ?>,
        categories: <?= json_encode($categories, JSON_UNESCAPED_UNICODE) ?>,
        batches: <?= json_encode($batches, JSON_UNESCAPED_UNICODE) ?>,
        items: [], debts: [], activeBatch: null, viewMode: 'review',
        importType: 'payment_capture', accountId: '', files: [], loading: false,
        apiKey: localStorage.getItem('gemini_api_key') || '', tempKey: '', settingsOpen: false,
        search: '', filter: 'all', toast: '', toastType: 'success', confirmingItemId: null,
        review: {open:false,item:null,action:'transaction',orderId:'',destinationId:'',categoryId:'',loading:false},

        init() {
            this.tempKey = this.apiKey;
            this.loadDebts();
        },
        get currentBatch() {
            return this.batches.find(b => Number(b.id) === Number(this.activeBatch)) || null;
        },
        get reviewCount() {
            return this.items.filter(i => i.status === 'pending' || i.status === 'duplicate').length;
        },
        get historyCount() {
            return this.items.filter(i => i.status === 'applied' || i.status === 'ignored').length;
        },
        get filteredItems() {
            const allowed = this.viewMode === 'review' ? ['pending','duplicate'] : ['applied','ignored'];
            const q = this.search.toLowerCase().trim();
            return this.items.filter(i => allowed.includes(i.status)
                && (this.filter === 'all' || i.status === this.filter)
                && (!q || [i.reference,i.counterparty,i.description,i.bank,i.customer_name].join(' ').toLowerCase().includes(q)));
        },
        setView(mode) {
            this.viewMode = mode;
            this.filter = 'all';
        },
        clearFiles() {
            this.files = [];
            const input = document.querySelector('input[type="file"]');
            if (input) input.value = '';
        },
        async pickFiles(e) {
            this.files = [];
            for (const f of [...e.target.files]) {
                if (f.size > 12 * 1024 * 1024) {
                    this.notify(f.name + ' supera 12 MB', 'error');
                    continue;
                }
                this.files.push({
                    name: f.name,
                    mime: f.type || 'application/octet-stream',
                    data: await new Promise((ok, no) => {
                        const reader = new FileReader();
                        reader.onload = () => ok(reader.result);
                        reader.onerror = no;
                        reader.readAsDataURL(f);
                    })
                });
            }
            e.target.value = '';
        },
        async scan() {
            if (this.files.some(f => !f.mime.includes('csv') && !f.name.toLowerCase().endsWith('.csv')) && !this.apiKey) {
                this.settingsOpen = true;
                return this.notify('Agrega tu clave Gemini.', 'error');
            }
            this.loading = true;
            try {
                const d = await this.request('<?= base_url('reconciliation/scan') ?>', {
                    import_type: this.importType, account_id: this.accountId, files: this.files, api_key: this.apiKey
                });
                this.activeBatch = d.batch_id;
                this.items = d.data || [];
                this.viewMode = 'review';
                this.filter = 'all';
                this.clearFiles();
                await this.loadBatches();
                this.notify('Documento analizado. Revisa las sugerencias.');
            } catch (e) {
                this.notify(e.message, 'error');
            } finally {
                this.loading = false;
            }
        },
        async loadBatches() {
            try { this.batches = (await this.get('<?= base_url('reconciliation/batches') ?>')).data || []; } catch (e) {}
        },
        async loadItems() {
            if (!this.activeBatch) { this.items = []; return; }
            this.items = (await this.get('<?= base_url('reconciliation/items') ?>?batch_id=' + this.activeBatch)).data || [];
        },
        async openBatch(id) {
            this.activeBatch = id;
            try {
                await this.loadItems();
                this.setView(this.reviewCount > 0 ? 'review' : 'history');
            } catch (e) { this.notify(e.message, 'error'); }
        },
        async deleteBatch(batch) {
            if (!batch || !confirm('¿Eliminar esta importación y todos sus datos extraídos?')) return;
            try {
                const d = await this.request('<?= base_url('reconciliation/delete-batch/') ?>' + batch.id, {});
                if (Number(this.activeBatch) === Number(batch.id)) {
                    this.activeBatch = null;
                    this.items = [];
                    this.setView('review');
                }
                await this.loadBatches();
                this.notify(d.message || 'Importación eliminada.');
            } catch (e) { this.notify(e.message, 'error'); }
        },
        async loadDebts() {
            try { this.debts = (await this.get('<?= base_url('reconciliation/debts') ?>')).data || []; } catch (e) {}
        },
        openReview(i) {
            const c = JSON.parse(JSON.stringify(i));
            c.movement_date_local = (c.movement_date || '').replace(' ', 'T').slice(0,16);
            this.review = {open:true,item:c,action:c.suggested_action||'transaction',orderId:c.matched_order_id||'',destinationId:'',categoryId:'',loading:false};
        },
        originalData(item) {
            try { return JSON.parse(item?.meta_json || '{}') || {}; } catch (e) { return {}; }
        },
        originalTitle(item) {
            const data = this.originalData(item);
            return data.title || data.titulo || '';
        },
        prettyMeta(value) {
            try { return JSON.stringify(JSON.parse(value || '{}'), null, 2); } catch (e) { return value || 'Sin datos adicionales'; }
        },
        async setDirection(item, direction) {
            if (item.direction === direction) return;
            this.confirmingItemId = null;
            try {
                await this.request('<?= base_url('reconciliation/item/') ?>' + item.id, {direction});
                item.direction = direction;
                if (direction !== 'credit' && item.suggested_action === 'payment') {
                    item.suggested_action = 'transaction';
                    item.matched_order_id = null;
                }
                this.notify(direction === 'credit' ? 'Marcado como ingreso.' : 'Marcado como egreso.');
            } catch (e) { this.notify(e.message, 'error'); }
        },
        async setSuggestedAction(item) {
            this.confirmingItemId = null;
            if (item.suggested_action === 'payment' && item.direction !== 'credit') {
                item.suggested_action = 'transaction';
                try { await this.request('<?= base_url('reconciliation/item/') ?>' + item.id, {suggested_action:'transaction'}); } catch (e) {}
                return this.notify('Un abono recibido debe estar marcado como ingreso.', 'error');
            }
            try {
                await this.request('<?= base_url('reconciliation/item/') ?>' + item.id, {suggested_action:item.suggested_action});
                this.notify('Acción actualizada.');
            } catch (e) { this.notify(e.message, 'error'); }
        },
        setReviewDirection(direction) {
            this.review.item.direction = direction;
            if (direction === 'debit' && this.review.action === 'payment') {
                this.review.action = 'transaction';
                this.review.orderId = '';
            }
        },
        setReviewAction(action) {
            if (action === 'payment' && this.review.item.direction !== 'credit') {
                return this.notify('Selecciona “Entró dinero” para registrar un abono recibido.', 'error');
            }
            this.review.action = action;
        },
        async saveAndApply() {
            const r = this.review;
            if (r.item.direction === 'unknown') return this.notify('Define si es un ingreso o un egreso.', 'error');
            if (r.action === 'payment' && r.item.direction !== 'credit') return this.notify('Un abono recibido debe ser un ingreso.', 'error');
            if (r.action === 'payment' && !r.orderId) return this.notify('Selecciona una deuda.', 'error');
            if (r.action === 'transfer' && !r.destinationId) return this.notify('Selecciona el destino.', 'error');
            r.loading = true;
            try {
                await this.request('<?= base_url('reconciliation/item/') ?>' + r.item.id, {amount:r.item.amount,currency:r.item.currency,direction:r.item.direction,exchange_rate:r.item.exchange_rate,movement_date:r.item.movement_date_local,reference:r.item.reference,bank:r.item.bank,counterparty:r.item.counterparty,description:r.item.description,suggested_action:r.action,matched_order_id:r.orderId||null});
                await this.applyItem(r.item.id,r.action,r.orderId,r.destinationId,r.categoryId);
                r.open = false;
            } catch (e) { this.notify(e.message, 'error'); } finally { r.loading = false; }
        },
        prepareQuickApply(i) {
            if (i.direction==='unknown'||i.status==='duplicate'||i.suggested_action==='transfer'||(i.suggested_action==='payment'&&!i.matched_order_id)) return this.openReview(i);
            this.confirmingItemId = Number(i.id);
        },
        async confirmQuickApply(i) {
            if (Number(this.confirmingItemId) !== Number(i.id)) return;
            this.confirmingItemId = null;
            await this.applyItem(i.id,i.suggested_action,i.matched_order_id,'','');
        },
        async ignore(i) { this.confirmingItemId = null; await this.applyItem(i.id,'ignore','','',''); },
        async applyItem(id,action,orderId,destinationId,categoryId) {
            const d = await this.request('<?= base_url('reconciliation/apply/') ?>'+id,{action,order_id:orderId||null,destination_account_id:destinationId||null,category_id:categoryId||null});
            this.notify(d.message || 'Confirmado.');
            await Promise.all([this.loadItems(),this.loadBatches(),this.loadDebts()]);
        },
        actionLabel(a) { return a==='payment'?'Aplicar abono':a==='transfer'?'Revisar traslado':'Registrar'; },
        quickApplyLabel(i) { if(i.suggested_action==='transfer')return'Configurar traslado';if(i.suggested_action==='payment')return'Confirmar abono';return i.direction==='credit'?'Registrar ingreso':i.direction==='debit'?'Registrar egreso':'Definir tipo'; },
        statusLabel(s) { return ({pending:'Pendiente',duplicate:'Duplicado',applied:'Aplicado',ignored:'Ignorado'})[s]||s; },
        statusClass(s) { return ({pending:'bg-blue-100 text-blue-700',duplicate:'bg-amber-100 text-amber-700',applied:'bg-emerald-100 text-emerald-700',ignored:'bg-slate-100 text-slate-500'})[s]; },
        money(v,c) { return(c==='USD'?'$ ':c==='EUR'?'€ ':'Bs. ')+Number(v||0).toLocaleString('es-VE',{minimumFractionDigits:2,maximumFractionDigits:2}); },
        dateLabel(v) { if(!v)return'—';const d=new Date(v.replace(' ','T'));return isNaN(d)?v:d.toLocaleString('es-VE',{dateStyle:'short',timeStyle:'short'}); },
        saveKey() { this.apiKey=this.tempKey.trim();localStorage.setItem('gemini_api_key',this.apiKey);this.settingsOpen=false;this.notify('Clave guardada.'); },
        notify(m,t='success') { this.toast=m;this.toastType=t;clearTimeout(this._tt);this._tt=setTimeout(()=>this.toast='',3500); },
        async get(u) { const r=await fetch(u),d=await r.json();if(!r.ok||d.status==='error')throw Error(d.message||'No se pudo completar.');return d; },
        async request(u,b) { const r=await fetch(u,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(b)}),d=await r.json();if(!r.ok||d.status==='error')throw Error(d.message||'No se pudo completar.');return d; }
    };
}
</script></body></html>
