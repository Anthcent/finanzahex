<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Centro de limpieza - Finanzahex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>[x-cloak]{display:none!important}body{font-family:'Plus Jakarta Sans',sans-serif}.safe-bottom{padding-bottom:max(18px,env(safe-area-inset-bottom))}</style>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100" x-data="dataManager()" x-init="init()">
    <header class="sticky top-0 z-30 bg-slate-950/90 backdrop-blur-xl border-b border-slate-800">
        <div class="max-w-4xl mx-auto h-16 px-4 flex items-center justify-between">
            <div class="flex items-center gap-3 min-w-0">
                <a href="<?= base_url('config') ?>" class="w-9 h-9 rounded-xl bg-slate-800 flex items-center justify-center text-slate-300"><span class="material-icons text-lg">arrow_back</span></a>
                <div><h1 class="text-sm sm:text-base font-black">Centro de limpieza</h1><p class="text-[9px] text-slate-500 font-bold">Administración protegida de registros</p></div>
            </div>
            <button x-show="unlocked" @click="logout()" class="text-[10px] font-black text-slate-400 px-3 py-2 rounded-xl bg-slate-900 border border-slate-800">Bloquear</button>
        </div>
    </header>

    <main class="max-w-4xl mx-auto p-4 sm:p-6 safe-bottom">
        <section x-show="!unlocked" class="max-w-sm mx-auto mt-12 bg-slate-900 border border-slate-800 rounded-3xl p-6 text-center shadow-2xl">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-emerald-500/15 text-emerald-400 flex items-center justify-center"><span class="material-icons text-3xl">lock</span></div>
            <h2 class="mt-4 font-black text-lg">Acceso protegido</h2>
            <p class="text-xs text-slate-400 mt-1">Ingresa el PIN administrativo para gestionar eliminaciones.</p>
            <input x-ref="pin" x-model="pin" @keyup.enter="login()" type="password" inputmode="numeric" maxlength="4" placeholder="••••" class="mt-5 w-full h-14 text-center tracking-[0.7em] text-xl font-black bg-slate-950 border border-slate-700 rounded-2xl outline-none focus:border-emerald-500">
            <p x-show="error" x-text="error" class="text-xs text-rose-400 mt-2"></p>
            <button @click="login()" :disabled="busy" class="mt-4 w-full py-3.5 rounded-2xl bg-emerald-600 disabled:opacity-50 text-white text-sm font-black">Ingresar</button>
        </section>

        <div x-cloak x-show="unlocked" class="space-y-5">
            <section class="rounded-3xl bg-gradient-to-br from-rose-950/70 to-slate-900 border border-rose-900/50 p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div><span class="text-[9px] font-black uppercase tracking-widest text-rose-400">Zona sensible</span><h2 class="font-black text-lg mt-1">Limpieza total operativa</h2><p class="text-xs text-slate-400 max-w-xl mt-1">Borra movimientos, facturas, ventas, impresiones, divisas, inventario, IA y bitácora. Conserva configuraciones, categorías, productos y cuentas principales, pero reinicia sus saldos.</p></div>
                <button @click="openConfirm('all', [])" class="shrink-0 px-4 py-3 rounded-2xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-black flex items-center gap-2"><span class="material-icons text-base">delete_sweep</span>Limpiar todo</button>
            </section>

            <section>
                <div class="flex items-end justify-between mb-3"><div><h2 class="font-black">Áreas registradas</h2><p class="text-[10px] text-slate-500">Selecciona un área para revisar sus registros.</p></div><button @click="loadSummary()" class="w-8 h-8 rounded-xl bg-slate-900 text-slate-400"><span class="material-icons text-base">refresh</span></button></div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                    <template x-for="group in groups" :key="group.key">
                        <button @click="selectGroup(group)" class="text-left p-3 rounded-2xl border transition" :class="activeGroup === group.key ? 'bg-emerald-950/60 border-emerald-600' : 'bg-slate-900 border-slate-800 hover:border-slate-700'">
                            <div class="flex justify-between"><span class="material-icons text-lg" :class="activeGroup === group.key ? 'text-emerald-400' : 'text-slate-500'" x-text="group.icon"></span><b class="text-xs text-slate-300" x-text="group.count"></b></div>
                            <p class="mt-2 text-[10px] sm:text-xs font-black leading-tight" x-text="group.label"></p>
                        </button>
                    </template>
                </div>
            </section>

            <section x-show="activeGroup" class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden">
                <div class="p-4 border-b border-slate-800 flex flex-col sm:flex-row gap-2 sm:items-center">
                    <div class="relative flex-1"><span class="material-icons absolute left-3 top-2.5 text-base text-slate-600">search</span><input x-model="search" @input.debounce.350ms="loadRecords()" placeholder="Buscar registros..." class="w-full bg-slate-950 border border-slate-800 rounded-xl py-2.5 pl-9 pr-3 text-xs outline-none focus:border-emerald-600"></div>
                    <div class="flex gap-2">
                        <button @click="toggleAll()" class="flex-1 sm:flex-none px-3 py-2.5 rounded-xl bg-slate-800 text-[10px] font-black" x-text="allSelected ? 'Quitar selección' : 'Seleccionar todos'"></button>
                        <button @click="openConfirm(activeGroup, selected)" :disabled="selected.length === 0" class="flex-1 sm:flex-none px-3 py-2.5 rounded-xl bg-rose-600 disabled:opacity-30 text-[10px] font-black">Eliminar (<span x-text="selected.length"></span>)</button>
                        <button @click="openConfirm(activeGroup, [], true)" class="px-3 py-2.5 rounded-xl border border-rose-800 text-rose-400 text-[10px] font-black">Vaciar área</button>
                    </div>
                </div>
                <div class="max-h-[48vh] overflow-y-auto divide-y divide-slate-800">
                    <template x-for="record in records" :key="record.id">
                        <label class="p-3.5 flex items-center gap-3 hover:bg-slate-800/40 cursor-pointer">
                            <input type="checkbox" :value="record.id" x-model="selected" class="w-4 h-4 accent-emerald-500">
                            <div class="min-w-0 flex-1"><p class="text-xs font-bold truncate" x-text="record.title || ('Registro #' + record.id)"></p><div class="flex gap-2 mt-0.5 text-[9px] text-slate-500"><span x-text="record.subtitle"></span><span x-show="record.date" x-text="formatDate(record.date)"></span></div></div>
                            <span x-show="record.amount !== null" class="text-[10px] font-black text-slate-300" x-text="formatNumber(record.amount)"></span>
                        </label>
                    </template>
                    <div x-show="!loading && records.length === 0" class="py-12 text-center text-xs text-slate-500">No hay registros en esta área.</div>
                    <div x-show="loading" class="py-12 text-center text-xs text-emerald-400">Cargando…</div>
                </div>
                <p class="px-4 py-2.5 text-[9px] text-slate-600 border-t border-slate-800">Se muestran como máximo los 200 registros más recientes.</p>
            </section>
        </div>
    </main>

    <div x-cloak x-show="confirmOpen" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="confirmOpen=false"></div>
        <div class="relative w-full sm:max-w-sm bg-slate-900 border border-rose-900/60 rounded-t-3xl sm:rounded-3xl p-6 safe-bottom">
            <div class="w-12 h-12 rounded-2xl bg-rose-500/15 text-rose-400 flex items-center justify-center"><span class="material-icons">warning</span></div>
            <h3 class="font-black text-lg mt-4">Confirmar eliminación</h3>
            <p class="text-xs text-slate-400 mt-1" x-text="confirmGroup === 'all' ? 'Esta acción reiniciará todos los datos operativos y saldos.' : (purgeArea ? 'Se eliminará toda el área seleccionada.' : `Se eliminarán ${confirmIds.length} registros.`)"></p>
            <label class="block text-[9px] uppercase font-black text-rose-400 mt-4 mb-1">Escribe <span x-text="confirmGroup === 'all' ? 'LIMPIAR TODO' : 'ELIMINAR'"></span></label>
            <input x-model="confirmation" class="w-full bg-slate-950 border border-rose-900/60 rounded-xl px-3 py-3 text-sm font-black outline-none focus:border-rose-500">
            <div class="flex gap-2 mt-4"><button @click="confirmOpen=false" class="flex-1 py-3 rounded-xl bg-slate-800 text-xs font-bold">Cancelar</button><button @click="executeDelete()" :disabled="busy" class="flex-1 py-3 rounded-xl bg-rose-600 disabled:opacity-50 text-xs font-black">Eliminar</button></div>
        </div>
    </div>

    <script>
        function dataManager() { return {
            unlocked:false,pin:'',error:'',busy:false,loading:false,groups:[],activeGroup:'',records:[],selected:[],search:'',confirmOpen:false,confirmGroup:'',confirmIds:[],purgeArea:false,confirmation:'',
            init(){ this.loadSummary(true) },
            async api(url, options={}) { const res=await fetch(url,{cache:'no-store',...options}); const data=await res.json().catch(()=>({status:'error',message:'Respuesta inválida'})); if(res.status===401){this.unlocked=false;throw new Error('Acceso bloqueado')} if(!res.ok||data.status!=='success')throw new Error(data.message||'No se pudo completar'); return data },
            async login(){this.busy=true;this.error='';try{await this.api('<?= base_url('config/data-manager/login') ?>',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({pin:this.pin})});this.pin='';this.unlocked=true;await this.loadSummary()}catch(e){this.error=e.message}finally{this.busy=false}},
            async logout(){await fetch('<?= base_url('config/data-manager/logout') ?>',{method:'POST'});this.unlocked=false;this.groups=[];this.activeGroup=''},
            async loadSummary(silent=false){try{const data=await this.api('<?= base_url('config/data-manager/summary') ?>?t='+Date.now());this.unlocked=true;this.groups=data.groups}catch(e){if(!silent)this.error=e.message}},
            async selectGroup(group){this.activeGroup=group.key;this.search='';this.selected=[];await this.loadRecords()},
            async loadRecords(){if(!this.activeGroup)return;this.loading=true;try{const data=await this.api('<?= base_url('config/data-manager/records') ?>?group='+encodeURIComponent(this.activeGroup)+'&search='+encodeURIComponent(this.search)+'&t='+Date.now());this.records=data.records;this.selected=[]}catch(e){alert(e.message)}finally{this.loading=false}},
            get allSelected(){return this.records.length>0&&this.selected.length===this.records.length},
            toggleAll(){this.selected=this.allSelected?[]:this.records.map(r=>r.id)},
            openConfirm(group,ids,purge=false){if(!purge&&group!=='all'&&ids.length===0)return;this.confirmGroup=group;this.confirmIds=[...ids];this.purgeArea=purge;this.confirmation='';this.confirmOpen=true},
            async executeDelete(){const required=this.confirmGroup==='all'?'LIMPIAR TODO':'ELIMINAR';if(this.confirmation.trim().toUpperCase()!==required)return alert(`Escribe ${required}`);this.busy=true;try{const purge=this.confirmGroup==='all'||this.purgeArea;await this.api(purge?'<?= base_url('config/data-manager/purge') ?>':'<?= base_url('config/data-manager/delete') ?>',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({group:this.confirmGroup,ids:this.confirmIds,confirmation:required})});this.confirmOpen=false;this.selected=[];await this.loadSummary();if(this.activeGroup)await this.loadRecords()}catch(e){alert(e.message)}finally{this.busy=false}},
            formatDate(v){if(!v)return'';const d=new Date(String(v).replace(' ','T'));return isNaN(d)?v:d.toLocaleString('es-VE',{dateStyle:'short',timeStyle:'short'})},
            formatNumber(v){return new Intl.NumberFormat('es-VE',{maximumFractionDigits:2}).format(Number(v||0))}
        }}
    </script>
</body>
</html>
