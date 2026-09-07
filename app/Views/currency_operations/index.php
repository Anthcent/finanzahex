<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Operaciones en Divisas - Fi-Hex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <meta name="theme-color" content="#0f172a">
    <style>body{font-family:'Plus Jakarta Sans',sans-serif}[x-cloak]{display:none!important}.safe-bottom{padding-bottom:env(safe-area-inset-bottom,1rem)}</style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased" x-data="currencyOperations()">
    <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/90 backdrop-blur-xl">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-3 px-4">
            <div class="flex min-w-0 items-center gap-2.5">
                <a href="<?= base_url() ?>" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-slate-100 text-slate-600 active:scale-95" title="Volver al inicio"><span class="material-icons text-xl">arrow_back</span></a>
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-700 text-white shadow-md"><span class="material-icons text-lg">currency_exchange</span></div>
                <div class="min-w-0 leading-tight">
                    <h1 class="truncate text-sm font-black tracking-tight text-slate-900 sm:text-base">Operaciones en Divisas</h1>
                    <p class="hidden text-[9px] font-bold text-slate-400 sm:block">Compra, traslado y trazabilidad del dinero</p>
                </div>
            </div>
            <a href="#historial" class="flex items-center gap-1 rounded-xl bg-slate-100 px-3 py-2 text-xs font-black text-slate-700"><span class="material-icons text-base">history</span><span class="hidden sm:inline">Historial</span></a>
        </div>
    </header>

    <main class="mx-auto w-full max-w-6xl space-y-5 p-4 pb-24 sm:p-6">
        <section class="overflow-hidden rounded-3xl bg-gradient-to-br from-slate-950 via-indigo-950 to-slate-900 p-5 text-white shadow-xl sm:p-6">
            <div class="grid gap-4 sm:grid-cols-[1fr_auto] sm:items-center">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[.2em] text-indigo-300">Centro de divisas</p>
                    <h2 class="mt-1 text-2xl font-black tracking-tight sm:text-3xl">Cada dólar, completamente trazable</h2>
                    <p class="mt-2 max-w-2xl text-xs font-medium leading-relaxed text-slate-300">Las compras descuentan el total con comisión y acreditan los USD recibidos. Los traslados solo mueven saldo entre cuentas USD: no se registran como ingreso ni como gasto.</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-right">
                    <span class="block text-[9px] font-black uppercase tracking-wider text-slate-400">Tasa de referencia</span>
                    <span class="text-xl font-black" x-text="money(rate, 'Bs') + ' / USD'"></span>
                </div>
            </div>
        </section>

        <section class="grid gap-5 lg:grid-cols-[minmax(0,1.15fr)_minmax(300px,.85fr)]">
            <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                <div class="mb-5 grid grid-cols-2 rounded-2xl bg-slate-100 p-1">
                    <button type="button" @click="mode='purchase'; resetAccounts()" :class="mode==='purchase'?'bg-white text-indigo-700 shadow-sm':'text-slate-500'" class="rounded-xl px-3 py-2.5 text-xs font-black transition">Compra de USD</button>
                    <button type="button" @click="mode='transfer'; resetAccounts()" :class="mode==='transfer'?'bg-white text-emerald-700 shadow-sm':'text-slate-500'" class="rounded-xl px-3 py-2.5 text-xs font-black transition">Retiro / traslado</button>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="space-y-1.5"><span class="text-[10px] font-black uppercase tracking-wider text-slate-500" x-text="mode==='purchase'?'Cuenta origen (Bs)':'Origen digital (USD)'"></span>
                            <select x-model="form.source_account_id" required class="w-full rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm font-bold outline-none focus:border-indigo-400">
                                <option value="">Seleccionar cuenta</option>
                                <template x-for="account in sourceAccounts" :key="account.id"><option :value="account.id" x-text="account.name + ' · ' + money(account.balance, account.currency)"></option></template>
                            </select>
                        </label>
                        <label class="space-y-1.5"><span class="text-[10px] font-black uppercase tracking-wider text-slate-500" x-text="mode==='purchase'?'Destino (USD)':'Destino físico / USD'"></span>
                            <select x-model="form.destination_account_id" required class="w-full rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm font-bold outline-none focus:border-indigo-400">
                                <option value="">Seleccionar cuenta</option>
                                <template x-for="account in destinationAccounts" :key="account.id"><option :value="account.id" x-text="account.name + ' · ' + money(account.balance, 'USD') + (account.tenure_type==='physical'?' · Físico':'')"></option></template>
                            </select>
                        </label>
                    </div>

                    <div x-show="mode==='purchase'" x-cloak class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="space-y-1.5"><span class="text-[10px] font-black uppercase tracking-wider text-slate-500">Monto destinado a compra</span><input x-model.number="form.subtotal_bs" @input="suggestUsd" type="number" min="0.01" step="0.01" class="w-full rounded-xl border border-slate-200 bg-slate-50 p-3 text-lg font-black outline-none focus:border-indigo-400" placeholder="Bs. 0,00"></label>
                            <label class="space-y-1.5"><span class="text-[10px] font-black uppercase tracking-wider text-slate-500">Tasa acordada</span><input x-model.number="form.quoted_rate" @input="suggestUsd" type="number" min="0.0001" step="0.0001" class="w-full rounded-xl border border-slate-200 bg-slate-50 p-3 text-lg font-black outline-none focus:border-indigo-400" placeholder="Bs/USD"></label>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="space-y-1.5"><span class="text-[10px] font-black uppercase tracking-wider text-slate-500">Comisión cobrada</span><div class="relative"><input x-model.number="form.commission_percent" type="number" min="0" max="100" step="0.0001" class="w-full rounded-xl border border-slate-200 bg-slate-50 p-3 pr-9 text-lg font-black outline-none focus:border-indigo-400" placeholder="0"><span class="absolute right-3 top-3.5 font-black text-slate-400">%</span></div></label>
                            <label class="space-y-1.5"><span class="text-[10px] font-black uppercase tracking-wider text-slate-500">USD realmente recibidos</span><input x-model.number="form.amount_usd" type="number" min="0.01" step="0.01" class="w-full rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-lg font-black text-emerald-800 outline-none focus:border-emerald-500" placeholder="$ 0.00"></label>
                        </div>
                    </div>

                    <label x-show="mode==='transfer'" x-cloak class="block space-y-1.5"><span class="text-[10px] font-black uppercase tracking-wider text-slate-500">USD a mover</span><input x-model.number="form.amount_usd" type="number" min="0.01" step="0.01" class="w-full rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xl font-black text-emerald-800 outline-none focus:border-emerald-500" placeholder="$ 0.00"></label>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="space-y-1.5"><span class="text-[10px] font-black uppercase tracking-wider text-slate-500">Referencia</span><input x-model="form.reference" maxlength="120" class="w-full rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm font-bold outline-none focus:border-indigo-400" placeholder="Banco, recibo o comprobante"></label>
                        <label class="space-y-1.5"><span class="text-[10px] font-black uppercase tracking-wider text-slate-500">Fecha y hora</span><input x-model="form.operation_date" type="datetime-local" class="w-full rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm font-bold outline-none focus:border-indigo-400"></label>
                    </div>
                    <label class="block space-y-1.5"><span class="text-[10px] font-black uppercase tracking-wider text-slate-500">Notas</span><textarea x-model="form.notes" rows="2" class="w-full resize-none rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm font-medium outline-none focus:border-indigo-400" placeholder="Detalles útiles para conciliar esta operación"></textarea></label>

                    <button :disabled="saving" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-700 py-3.5 text-sm font-black text-white shadow-lg shadow-indigo-200 transition active:scale-[.99] disabled:opacity-60"><span class="material-icons text-lg" x-text="saving?'hourglass_top':'check_circle'"></span><span x-text="saving?'Procesando…':(mode==='purchase'?'Registrar compra':'Registrar traslado')"></span></button>
                </form>
            </div>

            <aside class="space-y-4">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-500">Resumen antes de confirmar</h3>
                    <div class="mt-4 space-y-3">
                        <div class="flex justify-between text-sm"><span class="font-semibold text-slate-500" x-text="mode==='purchase'?'Base de compra':'Monto trasladado'"></span><b x-text="mode==='purchase'?money(subtotal,'Bs'):money(amountUsd,'USD')"></b></div>
                        <div x-show="mode==='purchase'" class="flex justify-between text-sm"><span class="font-semibold text-slate-500">Comisión</span><b class="text-amber-700" x-text="money(commission,'Bs')"></b></div>
                        <div x-show="mode==='purchase'" class="flex justify-between border-t border-slate-100 pt-3"><span class="font-black text-slate-700">Total a debitar</span><b class="text-lg text-rose-700" x-text="money(total,'Bs')"></b></div>
                        <div class="flex justify-between rounded-2xl bg-emerald-50 p-3"><span class="text-sm font-black text-emerald-700" x-text="mode==='purchase'?'USD a acreditar':'USD en destino'"></span><b class="text-lg text-emerald-800" x-text="money(amountUsd,'USD')"></b></div>
                        <div x-show="mode==='purchase'" class="flex justify-between text-xs"><span class="font-semibold text-slate-400">Tasa efectiva con comisión</span><b x-text="effectiveRate ? money(effectiveRate,'Bs') + '/USD' : '—'"></b></div>
                    </div>
                </div>
                <div class="rounded-3xl border border-blue-100 bg-blue-50 p-5 text-xs font-medium leading-relaxed text-blue-900"><div class="mb-2 flex items-center gap-2 font-black"><span class="material-icons text-base">verified_user</span>Registro seguro</div>Cada envío tiene una clave única para impedir duplicados. Una anulación conserva el historial y solo se permite si el saldo USD de destino puede devolverse.</div>
            </aside>
        </section>

        <section id="historial" class="scroll-mt-20 space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div><p class="text-[10px] font-black uppercase tracking-widest text-indigo-600">Auditoría</p><h2 class="text-xl font-black text-slate-900">Historial de operaciones</h2></div>
                <form method="get" class="grid grid-cols-2 gap-2 sm:flex">
                    <input name="q" value="<?= esc($filters['q']) ?>" class="col-span-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold sm:w-48" placeholder="Buscar referencia o nota">
                    <select name="type" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold"><option value="">Todos los tipos</option><option value="purchase" <?= $filters['type'] === 'purchase' ? 'selected' : '' ?>>Compras</option><option value="transfer" <?= $filters['type'] === 'transfer' ? 'selected' : '' ?>>Traslados</option></select>
                    <select name="status" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold"><option value="">Todos</option><option value="completed" <?= $filters['status'] === 'completed' ? 'selected' : '' ?>>Completados</option><option value="reversed" <?= $filters['status'] === 'reversed' ? 'selected' : '' ?>>Anulados</option></select>
                    <button class="col-span-2 rounded-xl bg-slate-900 px-4 py-2 text-xs font-black text-white sm:col-span-1">Filtrar</button>
                </form>
            </div>

            <?php if (empty($operations)): ?>
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center"><span class="material-icons text-4xl text-slate-300">currency_exchange</span><p class="mt-2 text-sm font-black text-slate-700">Aún no hay operaciones con estos filtros</p></div>
            <?php else: ?>
                <div class="grid gap-3">
                    <?php foreach ($operations as $operation): ?>
                        <?php $isPurchase = $operation['operation_type'] === 'purchase'; $isReversed = $operation['status'] === 'reversed'; ?>
                        <article class="rounded-2xl border bg-white p-4 shadow-sm <?= $isReversed ? 'border-slate-200 opacity-70' : ($isPurchase ? 'border-indigo-100' : 'border-emerald-100') ?>">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex min-w-0 items-start gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl <?= $isPurchase ? 'bg-indigo-50 text-indigo-700' : 'bg-emerald-50 text-emerald-700' ?>"><span class="material-icons text-xl"><?= $isPurchase ? 'currency_exchange' : 'sync_alt' ?></span></div>
                                    <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><h3 class="text-sm font-black text-slate-900"><?= $isPurchase ? 'Compra de USD' : 'Retiro / traslado USD' ?></h3><span class="rounded-full px-2 py-0.5 text-[9px] font-black uppercase <?= $isReversed ? 'bg-slate-100 text-slate-500' : 'bg-emerald-100 text-emerald-700' ?>"><?= $isReversed ? 'Anulada' : 'Completada' ?></span></div><p class="mt-1 truncate text-xs font-semibold text-slate-500"><?= esc($operation['source_name']) ?> <span class="material-icons align-middle text-xs">arrow_forward</span> <?= esc($operation['destination_name']) ?></p><p class="mt-1 text-[10px] font-medium text-slate-400"><?= date('d/m/Y · h:i A', strtotime($operation['operation_date'])) ?><?= $operation['reference'] ? ' · ' . esc($operation['reference']) : '' ?></p></div>
                                </div>
                                <div class="flex items-center justify-between gap-4 sm:justify-end">
                                    <div class="text-right"><b class="block text-base text-emerald-700">$ <?= number_format((float) $operation['amount_usd'], 2, ',', '.') ?></b><?php if ($isPurchase): ?><span class="text-[10px] font-bold text-slate-500">Total Bs. <?= number_format((float) $operation['total_bs'], 2, ',', '.') ?> · Comisión Bs. <?= number_format((float) $operation['commission_bs'], 2, ',', '.') ?></span><?php else: ?><span class="text-[10px] font-bold text-slate-400">Movimiento interno, sin ingreso/gasto</span><?php endif; ?></div>
                                    <?php if (!$isReversed): ?><button type="button" @click="reverse(<?= (int) $operation['id'] ?>)" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-rose-100 bg-rose-50 text-rose-600" title="Anular operación"><span class="material-icons text-base">undo</span></button><?php endif; ?>
                                </div>
                            </div>
                            <?php if ($operation['notes']): ?><p class="mt-3 border-t border-slate-100 pt-3 text-xs font-medium text-slate-500"><?= esc($operation['notes']) ?></p><?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <div x-show="message" x-cloak class="fixed bottom-5 left-1/2 z-50 -translate-x-1/2 rounded-full px-4 py-2 text-xs font-black text-white shadow-xl" :class="error?'bg-rose-700':'bg-emerald-700'" x-text="message"></div>

    <script>
        function currencyOperations() {
            const now = new Date(); now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            const requestId = (crypto.randomUUID ? crypto.randomUUID() : Date.now()+'-'+Math.random().toString(16).slice(2)).replace(/[^a-zA-Z0-9_-]/g,'');
            return {
                accounts: <?= json_encode($accounts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                rate: <?= json_encode($exchangeRate) ?>,
                mode: 'purchase', saving: false, message: '', error: false, requestId,
                form: { source_account_id:'', destination_account_id:'', subtotal_bs:'', commission_percent:0, amount_usd:'', quoted_rate:<?= json_encode($exchangeRate) ?>, reference:'', notes:'', owner:'Negocio', operation_date:now.toISOString().slice(0,16) },
                get sourceAccounts(){ return this.accounts.filter(a => this.mode==='purchase' ? String(a.currency).toUpperCase()!=='USD' : String(a.currency).toUpperCase()==='USD' && a.tenure_type==='digital'); },
                get destinationAccounts(){ return this.accounts.filter(a => String(a.currency).toUpperCase()==='USD' && String(a.id)!==String(this.form.source_account_id)).sort((a,b)=>(b.tenure_type==='physical')-(a.tenure_type==='physical')); },
                get subtotal(){ return Number(this.form.subtotal_bs)||0; },
                get amountUsd(){ return Number(this.form.amount_usd)||0; },
                get commission(){ return this.subtotal*(Number(this.form.commission_percent)||0)/100; },
                get total(){ return this.subtotal+this.commission; },
                get effectiveRate(){ return this.amountUsd>0 ? this.total/this.amountUsd : 0; },
                resetAccounts(){ this.form.source_account_id=''; this.form.destination_account_id=''; this.form.subtotal_bs=''; this.form.amount_usd=''; },
                suggestUsd(){ const base=Number(this.form.subtotal_bs)||0, rate=Number(this.form.quoted_rate)||0; if(base>0&&rate>0)this.form.amount_usd=(base/rate).toFixed(2); },
                money(value,currency){ return new Intl.NumberFormat(currency==='USD'?'en-US':'es-VE',{style:'currency',currency:currency==='USD'?'USD':'VES'}).format(Number(value)||0); },
                notify(text,isError=false){ this.message=text; this.error=isError; setTimeout(()=>this.message='',3500); },
                async submit(){
                    if(this.saving)return; this.saving=true;
                    const payload={...this.form,operation_type:this.mode,request_id:this.requestId};
                    try { const response=await fetch('<?= base_url('divisas/store') ?>',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)}); const data=await response.json(); if(!response.ok||data.status!=='success')throw new Error(data.message||'No se pudo registrar'); this.notify('Operación registrada correctamente'); setTimeout(()=>location.href='<?= base_url('divisas') ?>',650); }
                    catch(e){ this.notify(e.message,true); } finally { this.saving=false; }
                },
                async reverse(id){
                    if(!confirm('¿Anular esta operación? Los saldos volverán a su estado anterior y el registro se conservará.'))return;
                    try { const response=await fetch('<?= base_url('divisas/reverse') ?>/'+id,{method:'POST'}); const data=await response.json(); if(!response.ok||data.status!=='success')throw new Error(data.message||'No se pudo anular'); location.reload(); } catch(e){ this.notify(e.message,true); }
                }
            }
        }
    </script>
</body>
</html>
