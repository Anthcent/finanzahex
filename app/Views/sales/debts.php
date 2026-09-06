<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <title>Cuentas por Cobrar | Fi-Hex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        [x-cloak] { display: none !important; }
        .safe-top { padding-top: max(.65rem, env(safe-area-inset-top)); }
        .safe-bottom { padding-bottom: max(1rem, env(safe-area-inset-bottom)); }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { scrollbar-width: none; }
        .custom-scrollbar::-webkit-scrollbar { width: 5px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }
        button, input, select, textarea { -webkit-tap-highlight-color: transparent; }
        button:focus-visible, a:focus-visible, input:focus-visible, select:focus-visible, textarea:focus-visible { outline: 3px solid rgba(16, 185, 129, .24); outline-offset: 2px; }
        @keyframes slide-up { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .animate-slide-up { animation: slide-up .22s ease-out; }
        @media print {
            body * { visibility: hidden; }
            #debt-invoice, #debt-invoice * { visibility: visible; }
            #debt-invoice { position: absolute; inset: 0; width: 100%; box-shadow: none; border: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800 antialiased" x-data="debtsApp()" @keydown.escape.window="closeTopModal()">

    <header class="sticky top-0 z-40 bg-gradient-to-r from-emerald-950 via-slate-900 to-teal-950 text-white border-b border-emerald-800/30 safe-top">
        <div class="max-w-7xl mx-auto px-3 sm:px-5 py-3 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <a href="<?= base_url('sales') ?>" class="w-11 h-11 rounded-2xl bg-white/10 hover:bg-white/20 active:scale-95 flex items-center justify-center border border-white/10 shrink-0" title="Volver a ventas">
                    <span class="material-icons text-xl">arrow_back</span>
                </a>
                <div class="min-w-0">
                    <h1 class="text-sm sm:text-lg font-black tracking-tight truncate">Cuentas por cobrar</h1>
                    <p class="text-[10px] sm:text-xs text-emerald-200/70 font-semibold truncate">Consulta, seguimiento y cobro de créditos</p>
                </div>
            </div>
            <div class="text-right shrink-0">
                <p class="text-[9px] font-black uppercase tracking-widest text-emerald-300/70">Pendiente</p>
                <p class="text-sm sm:text-lg font-black" x-text="formatUsd(metrics.totalUsd)"></p>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto p-3 sm:p-5 pb-28 space-y-4 safe-bottom">
        <section class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="col-span-2 lg:col-span-1 bg-gradient-to-br from-rose-600 to-orange-500 text-white rounded-2xl p-4 shadow-sm">
                <p class="text-[10px] font-black uppercase tracking-widest text-rose-100">Total por cobrar</p>
                <p class="text-2xl font-black mt-1" x-text="formatUsd(metrics.totalUsd)"></p>
                <p class="text-[11px] font-bold text-rose-100 mt-1" x-text="formatBs(metrics.totalBs) + ' referenciales'"></p>
            </div>
            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="material-icons text-amber-500">receipt_long</span>
                    <span class="text-[10px] font-black text-slate-400">CUENTAS</span>
                </div>
                <p class="text-2xl font-black text-slate-900 mt-2" x-text="metrics.count"></p>
                <p class="text-[10px] font-bold text-slate-400">créditos activos</p>
            </div>
            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="material-icons text-sky-600">groups</span>
                    <span class="text-[10px] font-black text-slate-400">CLIENTES</span>
                </div>
                <p class="text-2xl font-black text-slate-900 mt-2" x-text="metrics.customers"></p>
                <p class="text-[10px] font-bold text-slate-400">con saldo abierto</p>
            </div>
            <div class="bg-white rounded-2xl p-4 border shadow-xs" :class="metrics.overdue ? 'border-rose-200' : 'border-slate-200/80'">
                <div class="flex items-center justify-between">
                    <span class="material-icons" :class="metrics.overdue ? 'text-rose-600' : 'text-emerald-600'" x-text="metrics.overdue ? 'notification_important' : 'event_available'"></span>
                    <span class="text-[10px] font-black text-slate-400">VENCIDAS</span>
                </div>
                <p class="text-2xl font-black mt-2" :class="metrics.overdue ? 'text-rose-600' : 'text-slate-900'" x-text="metrics.overdue"></p>
                <p class="text-[10px] font-bold text-slate-400">con fecha límite pasada</p>
            </div>
        </section>

        <section class="bg-white rounded-2xl border border-slate-200/80 p-3 sm:p-4 shadow-xs space-y-3">
            <div class="flex flex-col md:flex-row gap-3">
                <div class="relative flex-1 min-w-0">
                    <span class="material-icons absolute left-3.5 top-3 text-slate-400 text-lg">search</span>
                    <input x-ref="search" type="search" x-model="filters.search" placeholder="Buscar cliente, producto, referencia o # de venta" class="w-full h-11 bg-slate-50 border border-slate-200 rounded-xl pl-11 pr-10 text-sm font-bold outline-none focus:bg-white focus:border-emerald-500">
                    <button type="button" x-show="filters.search" @click="filters.search = ''" class="absolute right-2 top-1.5 w-8 h-8 rounded-lg text-slate-400 hover:bg-slate-200" title="Limpiar búsqueda"><span class="material-icons text-lg">close</span></button>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 md:w-auto">
                    <select x-model="filters.age" class="h-11 bg-slate-50 border border-slate-200 rounded-xl px-3 text-xs font-black outline-none focus:border-emerald-500">
                        <option value="all">Toda antigüedad</option>
                        <option value="overdue">Vencidas</option>
                        <option value="due_soon">Vencen en 7 días</option>
                        <option value="old">Más de 30 días</option>
                        <option value="no_due">Sin fecha límite</option>
                    </select>
                    <select x-model="filters.payment" class="h-11 bg-slate-50 border border-slate-200 rounded-xl px-3 text-xs font-black outline-none focus:border-emerald-500">
                        <option value="all">Todos los pagos</option>
                        <option value="none">Sin abonos</option>
                        <option value="partial">Con abonos</option>
                    </select>
                    <select x-model="filters.sort" class="col-span-2 sm:col-span-1 h-11 bg-slate-50 border border-slate-200 rounded-xl px-3 text-xs font-black outline-none focus:border-emerald-500">
                        <option value="priority">Prioridad</option>
                        <option value="amount_desc">Mayor deuda</option>
                        <option value="oldest">Más antiguas</option>
                        <option value="recent">Más recientes</option>
                        <option value="customer">Por cliente</option>
                    </select>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
                <div class="flex bg-slate-100 rounded-xl p-1">
                    <button type="button" @click="viewMode = 'debts'" :class="viewMode === 'debts' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500'" class="h-9 px-3 rounded-lg text-[11px] font-black flex items-center gap-1.5"><span class="material-icons text-sm">view_agenda</span>Deudas</button>
                    <button type="button" @click="viewMode = 'customers'" :class="viewMode === 'customers' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500'" class="h-9 px-3 rounded-lg text-[11px] font-black flex items-center gap-1.5"><span class="material-icons text-sm">group</span>Clientes</button>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-[11px] font-bold text-slate-400" x-text="filteredDebts.length + (filteredDebts.length === 1 ? ' resultado' : ' resultados')"></span>
                    <button type="button" x-show="hasFilters" @click="resetFilters()" class="h-9 px-3 rounded-xl bg-slate-100 text-slate-600 text-[10px] font-black hover:bg-slate-200">Limpiar filtros</button>
                </div>
            </div>
        </section>

        <section x-show="viewMode === 'debts'" class="grid lg:grid-cols-2 gap-3 sm:gap-4">
            <template x-for="debt in filteredDebts" :key="debt.id">
                <article class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden hover:shadow-md transition-shadow">
                    <div class="h-1" :class="debtAccent(debt)"></div>
                    <div class="p-4 sm:p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-1.5 mb-1">
                                    <span class="text-[9px] font-black uppercase tracking-wider text-slate-400" x-text="'Venta #' + debt.id"></span>
                                    <span class="text-[9px] font-black px-2 py-0.5 rounded-md" :class="dueBadge(debt).class" x-text="dueBadge(debt).label"></span>
                                </div>
                                <h2 class="text-sm sm:text-base font-black text-slate-900 truncate" x-text="debt.customer || 'Cliente sin nombre'"></h2>
                                <p class="text-[11px] font-bold text-slate-400 mt-1 truncate" x-text="debt.product || debt.description || 'Venta a crédito'"></p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-xl font-black text-rose-600" x-text="formatUsd(remainingUsd(debt))"></p>
                                <p class="text-[10px] font-bold text-slate-400" x-text="formatBs(remainingBs(debt))"></p>
                            </div>
                        </div>

                        <div class="mt-4">
                            <div class="flex justify-between text-[10px] font-black mb-1.5">
                                <span class="text-emerald-700" x-text="formatUsd(paidUsd(debt)) + ' abonado'"></span>
                                <span class="text-slate-400" x-text="Math.round(paidPercent(debt)) + '%'"></span>
                            </div>
                            <div class="h-2 bg-slate-100 rounded-full overflow-hidden"><div class="h-full bg-gradient-to-r from-emerald-500 to-teal-600 rounded-full" :style="'width:' + paidPercent(debt) + '%' "></div></div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 mt-4 text-[10px]">
                            <div class="bg-slate-50 rounded-xl px-3 py-2">
                                <p class="font-bold text-slate-400">Fecha de venta</p>
                                <p class="font-black text-slate-700 mt-0.5" x-text="formatDate(debt.date) + ' · ' + daysOld(debt) + ' días'"></p>
                            </div>
                            <div class="bg-slate-50 rounded-xl px-3 py-2">
                                <p class="font-bold text-slate-400">Último aviso</p>
                                <p class="font-black text-slate-700 mt-0.5" x-text="debt.last_reminder_at ? formatDateTime(debt.last_reminder_at) : 'Sin recordatorios'"></p>
                            </div>
                        </div>

                        <div x-show="debt.collection_notes" class="mt-3 text-[10px] font-semibold text-slate-500 bg-amber-50/70 border border-amber-100 rounded-xl px-3 py-2 line-clamp-2" x-text="debt.collection_notes"></div>

                        <div class="grid grid-cols-[1fr_1fr_auto] sm:grid-cols-[1fr_1fr_1.25fr] gap-2 mt-4">
                            <button type="button" @click="shareWhatsApp(debt)" class="h-11 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-black text-[10px] sm:text-xs flex items-center justify-center gap-1.5"><span class="material-icons text-base">chat</span><span>WhatsApp</span></button>
                            <button type="button" @click="openDetails(debt)" class="h-11 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 font-black text-[10px] sm:text-xs flex items-center justify-center gap-1.5"><span class="material-icons text-base">visibility</span><span>Ver detalle</span></button>
                            <button type="button" @click="openPayment(debt)" class="h-11 min-w-11 px-3 rounded-xl bg-slate-950 text-white hover:bg-emerald-700 font-black text-[10px] sm:text-xs flex items-center justify-center gap-1.5"><span class="material-icons text-base">payments</span><span class="hidden sm:inline">Registrar abono</span></button>
                        </div>
                    </div>
                </article>
            </template>
        </section>

        <section x-show="viewMode === 'customers'" x-cloak class="grid md:grid-cols-2 xl:grid-cols-3 gap-3 sm:gap-4">
            <template x-for="customer in customerGroups" :key="customer.key">
                <button type="button" @click="focusCustomer(customer.name)" class="text-left bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs hover:border-emerald-300 hover:shadow-md transition-all">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-11 h-11 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center font-black text-xs shrink-0" x-text="initials(customer.name)"></div>
                            <div class="min-w-0">
                                <h2 class="font-black text-sm text-slate-900 truncate" x-text="customer.name"></h2>
                                <p class="text-[10px] font-bold text-slate-400 mt-0.5" x-text="customer.count + (customer.count === 1 ? ' cuenta pendiente' : ' cuentas pendientes')"></p>
                            </div>
                        </div>
                        <span class="material-icons text-slate-300">chevron_right</span>
                    </div>
                    <div class="flex justify-between items-end mt-4 pt-3 border-t border-slate-100">
                        <div>
                            <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Deuda acumulada</p>
                            <p class="text-lg font-black text-rose-600 mt-0.5" x-text="formatUsd(customer.totalUsd)"></p>
                        </div>
                        <div class="text-right text-[10px] font-bold">
                            <p class="text-rose-600" x-show="customer.overdue > 0" x-text="customer.overdue + ' vencida(s)'"></p>
                            <p class="text-slate-400" x-text="'Más antigua: ' + customer.oldestDays + ' días'"></p>
                        </div>
                    </div>
                </button>
            </template>
        </section>

        <div x-show="filteredDebts.length === 0" x-cloak class="bg-white rounded-3xl border border-dashed border-slate-200 py-16 px-6 text-center">
            <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto"><span class="material-icons text-2xl" x-text="debts.length ? 'search_off' : 'verified'"></span></div>
            <h2 class="font-black text-slate-800 mt-3" x-text="debts.length ? 'No encontramos coincidencias' : '¡Todo al día!'"></h2>
            <p class="text-xs font-bold text-slate-400 mt-1" x-text="debts.length ? 'Prueba con otros filtros o limpia la búsqueda.' : 'No tienes cobros pendientes de ventas.'"></p>
            <button type="button" x-show="debts.length" @click="resetFilters()" class="mt-4 h-10 px-4 rounded-xl bg-slate-900 text-white text-xs font-black">Mostrar todas</button>
        </div>
    </main>

    <!-- Detail and collection management -->
    <div x-show="detailModal.open" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4">
        <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" @click="detailModal.open = false"></div>
        <section id="debt-invoice" class="relative z-10 bg-white w-full sm:max-w-2xl max-h-[94vh] rounded-t-[2rem] sm:rounded-3xl shadow-2xl flex flex-col animate-slide-up safe-bottom">
            <header class="p-4 sm:p-5 border-b border-slate-100 flex items-start justify-between gap-3 shrink-0">
                <div>
                    <p class="text-[9px] font-black uppercase tracking-widest text-emerald-600">Cuenta por cobrar</p>
                    <h2 class="text-lg font-black text-slate-900" x-text="selectedDebt?.customer || 'Detalle de deuda'"></h2>
                    <p class="text-[10px] font-bold text-slate-400" x-text="selectedDebt ? 'Venta #' + selectedDebt.id + ' · ' + formatDate(selectedDebt.date) : ''"></p>
                </div>
                <button type="button" @click="detailModal.open = false" class="no-print w-10 h-10 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center"><span class="material-icons">close</span></button>
            </header>

            <div class="overflow-y-auto custom-scrollbar p-4 sm:p-5 space-y-5">
                <div x-show="detailModal.loading" class="py-12 text-center text-slate-400"><span class="material-icons animate-spin">refresh</span><p class="text-xs font-bold mt-2">Cargando información…</p></div>

                <template x-if="selectedDebt">
                    <div x-show="!detailModal.loading" class="space-y-5">
                        <div class="grid grid-cols-3 gap-2">
                            <div class="col-span-3 sm:col-span-1 rounded-2xl bg-rose-50 border border-rose-100 p-3">
                                <p class="text-[9px] font-black uppercase text-rose-500">Saldo pendiente</p>
                                <p class="text-xl font-black text-rose-600 mt-1" x-text="formatUsd(remainingUsd(selectedDebt))"></p>
                                <p class="text-[10px] font-bold text-rose-400" x-text="formatBs(remainingBs(selectedDebt))"></p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 border border-slate-200 p-3">
                                <p class="text-[9px] font-black uppercase text-slate-400">Total</p>
                                <p class="text-sm font-black text-slate-800 mt-1" x-text="formatUsd(totalUsd(selectedDebt))"></p>
                            </div>
                            <div class="rounded-2xl bg-emerald-50 border border-emerald-100 p-3">
                                <p class="text-[9px] font-black uppercase text-emerald-600">Abonado</p>
                                <p class="text-sm font-black text-emerald-700 mt-1" x-text="formatUsd(paidUsd(selectedDebt))"></p>
                            </div>
                        </div>

                        <section>
                            <div class="flex items-center justify-between mb-2"><h3 class="text-[10px] font-black uppercase tracking-wider text-slate-400">Productos de la venta</h3><span class="text-[10px] font-bold text-slate-400" x-text="detailModal.items.length + ' ítems'"></span></div>
                            <div class="border border-slate-200 rounded-2xl overflow-hidden">
                                <template x-for="item in detailModal.items" :key="item.id">
                                    <div class="grid grid-cols-[1fr_auto] gap-3 p-3 border-b border-slate-100 last:border-0 text-xs">
                                        <div class="min-w-0"><p class="font-black text-slate-800 truncate" x-text="item.item_name || 'Ítem manual'"></p><p class="text-[10px] font-bold text-slate-400" x-text="Number(item.quantity) + ' ' + (item.unit || 'unid') + ' × ' + formatUsd(item.price)"></p></div>
                                        <p class="font-black text-slate-900" x-text="formatUsd(item.subtotal)"></p>
                                    </div>
                                </template>
                                <p x-show="detailModal.items.length === 0" class="p-4 text-xs font-bold text-slate-400">No hay detalle de productos disponible.</p>
                            </div>
                        </section>

                        <section>
                            <h3 class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-2">Historial de abonos</h3>
                            <div class="space-y-2">
                                <template x-for="paymentRow in detailModal.payments" :key="paymentRow.id">
                                    <div class="flex items-center justify-between gap-3 bg-slate-50 border border-slate-200/80 rounded-xl p-3">
                                        <div><p class="text-xs font-black text-slate-700" x-text="formatDate(paymentRow.date)"></p><p class="text-[9px] font-bold text-slate-400" x-text="paymentRow.account_name || paymentRow.reference || 'Abono registrado'"></p></div>
                                        <div class="text-right"><p class="text-xs font-black text-emerald-700" x-text="formatUsd(paymentRow.amount_usd)"></p><p class="text-[9px] font-bold text-slate-400" x-text="formatBs(paymentRow.amount)"></p></div>
                                    </div>
                                </template>
                                <p x-show="detailModal.payments.length === 0" class="bg-slate-50 rounded-xl p-3 text-xs font-bold text-slate-400">Todavía no se han registrado abonos.</p>
                            </div>
                        </section>

                        <section class="no-print bg-slate-50 border border-slate-200 rounded-2xl p-4">
                            <div class="flex items-center gap-2 mb-3"><span class="material-icons text-slate-500 text-lg">manage_accounts</span><h3 class="text-xs font-black text-slate-800">Datos de cobranza</h3></div>
                            <div class="grid sm:grid-cols-2 gap-3">
                                <div><label class="text-[10px] font-black text-slate-500 block mb-1">Teléfono / WhatsApp</label><input type="tel" x-model="collectionForm.customer_phone" placeholder="Ej. 0412 1234567" class="w-full h-11 bg-white border border-slate-200 rounded-xl px-3 text-sm font-bold outline-none focus:border-emerald-500"></div>
                                <div><label class="text-[10px] font-black text-slate-500 block mb-1">Fecha límite</label><input type="date" x-model="collectionForm.due_date" class="w-full h-11 bg-white border border-slate-200 rounded-xl px-3 text-sm font-bold outline-none focus:border-emerald-500"></div>
                                <div class="sm:col-span-2"><label class="text-[10px] font-black text-slate-500 block mb-1">Notas de seguimiento</label><textarea x-model="collectionForm.collection_notes" rows="3" maxlength="2000" placeholder="Acuerdos, fecha prometida, observaciones…" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-sm font-semibold outline-none resize-none focus:border-emerald-500"></textarea></div>
                            </div>
                            <p x-show="detailModal.error" x-text="detailModal.error" class="text-xs font-bold text-rose-700 mt-2"></p>
                            <button type="button" @click="saveCollectionInfo()" :disabled="detailModal.saving" class="mt-3 h-11 w-full rounded-xl bg-slate-900 text-white font-black text-xs disabled:opacity-50"><span x-text="detailModal.saving ? 'Guardando…' : 'Guardar datos de cobranza'"></span></button>
                        </section>
                    </div>
                </template>
            </div>

            <footer class="no-print p-4 border-t border-slate-100 grid grid-cols-3 gap-2 shrink-0 bg-white">
                <button type="button" @click="copyInvoice(selectedDebt)" class="h-11 rounded-xl bg-slate-100 text-slate-700 text-[10px] sm:text-xs font-black flex items-center justify-center gap-1"><span class="material-icons text-base">content_copy</span>Copiar</button>
                <button type="button" @click="shareWhatsApp(selectedDebt)" class="h-11 rounded-xl bg-emerald-50 text-emerald-700 text-[10px] sm:text-xs font-black flex items-center justify-center gap-1"><span class="material-icons text-base">chat</span>WhatsApp</button>
                <button type="button" @click="openPayment(selectedDebt)" class="h-11 rounded-xl bg-emerald-700 text-white text-[10px] sm:text-xs font-black flex items-center justify-center gap-1"><span class="material-icons text-base">payments</span>Abonar</button>
            </footer>
        </section>
    </div>

    <!-- Payment modal -->
    <div x-show="paymentModal.open" x-cloak class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center sm:p-4">
        <div class="absolute inset-0 bg-slate-950/65 backdrop-blur-sm" @click="paymentModal.open = false"></div>
        <section class="relative z-10 bg-white w-full sm:max-w-md max-h-[94vh] overflow-y-auto custom-scrollbar rounded-t-[2rem] sm:rounded-3xl shadow-2xl p-5 animate-slide-up safe-bottom">
            <div class="flex items-start justify-between gap-3 pb-4 border-b border-slate-100">
                <div><p class="text-[9px] font-black uppercase tracking-widest text-emerald-600">Registrar ingreso</p><h2 class="text-lg font-black text-slate-900">Abono de deuda</h2><p class="text-[10px] font-bold text-slate-400" x-text="paymentModal.debt?.customer"></p></div>
                <button type="button" @click="paymentModal.open = false" class="w-10 h-10 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center"><span class="material-icons">close</span></button>
            </div>

            <div class="my-4 bg-rose-50 border border-rose-100 rounded-2xl p-4 text-center">
                <p class="text-[9px] font-black uppercase tracking-wider text-rose-500">Saldo pendiente</p>
                <p class="text-2xl font-black text-rose-600 mt-1" x-text="paymentModal.debt ? formatUsd(remainingUsd(paymentModal.debt)) : '$0.00'"></p>
                <button type="button" @click="useFullBalance()" class="mt-2 h-9 px-3 rounded-xl bg-white border border-rose-200 text-rose-700 text-[10px] font-black">Usar saldo completo</button>
            </div>

            <div class="space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="text-[10px] font-black text-slate-500 block mb-1">Abono USD</label><input type="number" min="0" step="0.01" x-model.number="payment.amount_usd" @input="syncPayment('usd')" class="w-full h-12 rounded-xl bg-slate-50 border border-slate-200 px-3 font-black outline-none focus:bg-white focus:border-emerald-500"></div>
                    <div><label class="text-[10px] font-black text-slate-500 block mb-1">Equivalente Bs.</label><input type="number" min="0" step="0.01" x-model.number="payment.amount" @input="syncPayment('bs')" class="w-full h-12 rounded-xl bg-slate-50 border border-slate-200 px-3 font-black outline-none focus:bg-white focus:border-emerald-500"></div>
                </div>
                <div><label class="text-[10px] font-black text-slate-500 block mb-1">Cuenta que recibe</label><select x-model="payment.account_id" class="w-full h-12 rounded-xl bg-slate-50 border border-slate-200 px-3 text-sm font-black outline-none focus:border-emerald-500"><option value="">Seleccionar cuenta</option><template x-for="account in accounts" :key="account.id"><option :value="account.id" x-text="account.name + ' (' + (account.currency || 'Bs') + ')' "></option></template></select></div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="text-[10px] font-black text-slate-500 block mb-1">Fecha</label><input type="date" x-model="payment.date" class="w-full h-11 rounded-xl bg-slate-50 border border-slate-200 px-3 text-xs font-bold outline-none focus:border-emerald-500"></div>
                    <div><label class="text-[10px] font-black text-slate-500 block mb-1">Tasa</label><input type="number" min="0.0001" step="0.0001" x-model.number="payment.rate" @input="syncPayment('rate')" class="w-full h-11 rounded-xl bg-slate-50 border border-slate-200 px-3 text-xs font-black outline-none focus:border-emerald-500"></div>
                </div>
                <div><label class="text-[10px] font-black text-slate-500 block mb-1">Referencia opcional</label><input type="text" maxlength="100" x-model="payment.reference" placeholder="Transferencia, efectivo, observación…" class="w-full h-11 rounded-xl bg-slate-50 border border-slate-200 px-3 text-xs font-bold outline-none focus:border-emerald-500"></div>
            </div>

            <p x-show="paymentModal.error" x-text="paymentModal.error" class="mt-3 text-xs font-bold text-rose-700 bg-rose-50 border border-rose-200 rounded-xl p-3"></p>
            <button type="button" @click="submitPayment()" :disabled="paymentModal.loading || !canSubmitPayment" class="mt-4 w-full h-13 py-3.5 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-700 text-white font-black text-sm shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"><span x-text="paymentModal.loading ? 'Registrando…' : 'Confirmar abono'"></span></button>
            <p x-show="accounts.length === 0" class="text-[10px] font-bold text-rose-600 text-center mt-2">Debes crear una cuenta activa antes de registrar pagos.</p>
        </section>
    </div>

    <div x-show="toast" x-cloak x-transition class="fixed left-3 right-3 sm:left-auto sm:right-5 bottom-5 z-[80] sm:w-80 bg-slate-950 text-white rounded-2xl px-4 py-3 shadow-2xl flex items-center gap-3 safe-bottom">
        <span class="material-icons text-emerald-400">check_circle</span><p class="text-xs font-bold" x-text="toast"></p>
    </div>

    <script>
        function debtsApp() {
            return {
                debts: <?= json_encode(array_values($debts ?? []), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
                accounts: <?= json_encode(array_values($accounts ?? []), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
                viewMode: 'debts',
                filters: { search: '', age: 'all', payment: 'all', sort: 'priority' },
                selectedDebt: null,
                detailModal: { open: false, loading: false, saving: false, error: '', items: [], payments: [] },
                collectionForm: { customer_phone: '', due_date: '', collection_notes: '' },
                paymentModal: { open: false, loading: false, error: '', debt: null },
                payment: { amount: '', amount_usd: '', rate: 50, date: new Date().toISOString().slice(0, 10), reference: '', account_id: '' },
                toast: '',

                init() {
                    const savedRate = Number(localStorage.getItem('exchangeRate') || 0);
                    if (savedRate > 0) this.payment.rate = savedRate;
                    if (this.accounts.length === 1) this.payment.account_id = this.accounts[0].id;
                },

                number(value) { return Number(value || 0); },
                totalUsd(debt) { return this.number(debt?.amount_usd); },
                paidUsd(debt) { return this.number(debt?.paid_amount_usd); },
                remainingUsd(debt) { return Math.max(0, this.totalUsd(debt) - this.paidUsd(debt)); },
                remainingBs(debt) { return this.remainingUsd(debt) * this.number(debt?.exchange_rate || this.payment.rate || 1); },
                paidPercent(debt) { return Math.min(100, this.totalUsd(debt) > 0 ? (this.paidUsd(debt) / this.totalUsd(debt)) * 100 : 0); },

                parseDate(value) {
                    if (!value) return null;
                    const normalized = String(value).length === 10 ? value + 'T00:00:00' : String(value).replace(' ', 'T');
                    const date = new Date(normalized);
                    return Number.isNaN(date.getTime()) ? null : date;
                },
                daysOld(debt) {
                    const date = this.parseDate(debt?.date);
                    if (!date) return 0;
                    return Math.max(0, Math.floor((new Date().setHours(0,0,0,0) - date.setHours(0,0,0,0)) / 86400000));
                },
                dueInfo(debt) {
                    if (!debt?.due_date) return { state: 'none', days: null };
                    const due = this.parseDate(debt.due_date);
                    if (!due) return { state: 'none', days: null };
                    const today = new Date(); today.setHours(0,0,0,0); due.setHours(0,0,0,0);
                    const days = Math.ceil((due - today) / 86400000);
                    if (days < 0) return { state: 'overdue', days };
                    if (days === 0) return { state: 'today', days };
                    if (days <= 7) return { state: 'soon', days };
                    return { state: 'future', days };
                },
                dueBadge(debt) {
                    const info = this.dueInfo(debt);
                    if (info.state === 'overdue') return { label: 'Vencida ' + Math.abs(info.days) + 'd', class: 'bg-rose-100 text-rose-700' };
                    if (info.state === 'today') return { label: 'Vence hoy', class: 'bg-orange-100 text-orange-700' };
                    if (info.state === 'soon') return { label: 'Vence en ' + info.days + 'd', class: 'bg-amber-100 text-amber-700' };
                    if (info.state === 'future') return { label: 'Límite ' + this.formatDate(debt.due_date), class: 'bg-sky-100 text-sky-700' };
                    return { label: this.daysOld(debt) + ' días abierta', class: 'bg-slate-100 text-slate-600' };
                },
                debtAccent(debt) {
                    const state = this.dueInfo(debt).state;
                    if (state === 'overdue') return 'bg-rose-500';
                    if (state === 'today' || state === 'soon') return 'bg-amber-500';
                    return this.paidUsd(debt) > 0 ? 'bg-emerald-500' : 'bg-slate-300';
                },

                get metrics() {
                    const source = this.debts;
                    return {
                        totalUsd: source.reduce((sum, debt) => sum + this.remainingUsd(debt), 0),
                        totalBs: source.reduce((sum, debt) => sum + this.remainingBs(debt), 0),
                        count: source.length,
                        customers: new Set(source.map(debt => String(debt.customer || 'Sin nombre').trim().toLowerCase())).size,
                        overdue: source.filter(debt => this.dueInfo(debt).state === 'overdue').length,
                    };
                },
                get hasFilters() { return this.filters.search || this.filters.age !== 'all' || this.filters.payment !== 'all' || this.filters.sort !== 'priority'; },
                get filteredDebts() {
                    const query = this.filters.search.trim().toLowerCase();
                    const list = this.debts.filter(debt => {
                        const haystack = [debt.id, debt.customer, debt.product, debt.reference, debt.description, debt.customer_phone, debt.collection_notes].join(' ').toLowerCase();
                        const searchMatch = !query || haystack.includes(query);
                        const due = this.dueInfo(debt);
                        let ageMatch = true;
                        if (this.filters.age === 'overdue') ageMatch = due.state === 'overdue';
                        if (this.filters.age === 'due_soon') ageMatch = ['today', 'soon'].includes(due.state);
                        if (this.filters.age === 'old') ageMatch = this.daysOld(debt) > 30;
                        if (this.filters.age === 'no_due') ageMatch = due.state === 'none';
                        const paymentMatch = this.filters.payment === 'all' || (this.filters.payment === 'none' ? this.paidUsd(debt) <= 0 : this.paidUsd(debt) > 0);
                        return searchMatch && ageMatch && paymentMatch;
                    });
                    return list.sort((a, b) => {
                        if (this.filters.sort === 'amount_desc') return this.remainingUsd(b) - this.remainingUsd(a);
                        if (this.filters.sort === 'oldest') return String(a.date).localeCompare(String(b.date));
                        if (this.filters.sort === 'recent') return String(b.date).localeCompare(String(a.date));
                        if (this.filters.sort === 'customer') return String(a.customer || '').localeCompare(String(b.customer || ''), 'es');
                        const priority = debt => ({ overdue: 0, today: 1, soon: 2, future: 3, none: 4 })[this.dueInfo(debt).state];
                        return priority(a) - priority(b) || this.remainingUsd(b) - this.remainingUsd(a);
                    });
                },
                get customerGroups() {
                    const groups = {};
                    this.filteredDebts.forEach(debt => {
                        const name = String(debt.customer || 'Cliente sin nombre').trim();
                        const key = name.toLowerCase();
                        if (!groups[key]) groups[key] = { key, name, count: 0, totalUsd: 0, overdue: 0, oldestDays: 0 };
                        groups[key].count++;
                        groups[key].totalUsd += this.remainingUsd(debt);
                        groups[key].overdue += this.dueInfo(debt).state === 'overdue' ? 1 : 0;
                        groups[key].oldestDays = Math.max(groups[key].oldestDays, this.daysOld(debt));
                    });
                    return Object.values(groups).sort((a, b) => b.totalUsd - a.totalUsd);
                },

                resetFilters() { this.filters = { search: '', age: 'all', payment: 'all', sort: 'priority' }; },
                focusCustomer(name) { this.filters.search = name; this.viewMode = 'debts'; this.$nextTick(() => this.$refs.search?.focus()); },
                initials(name) { return String(name || '?').split(/\s+/).slice(0,2).map(part => part[0]).join('').toUpperCase(); },
                formatUsd(value) { return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 2 }).format(this.number(value)); },
                formatBs(value) { return 'Bs. ' + new Intl.NumberFormat('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(this.number(value)); },
                formatDate(value) { const date = this.parseDate(value); return date ? date.toLocaleDateString('es-VE', { day: '2-digit', month: 'short', year: 'numeric' }) : 'Sin fecha'; },
                formatDateTime(value) { const date = this.parseDate(value); return date ? date.toLocaleDateString('es-VE', { day: '2-digit', month: 'short' }) : 'Sin avisos'; },

                syncDebt(updated) {
                    const index = this.debts.findIndex(debt => String(debt.id) === String(updated.id));
                    if (updated.status === 'paid') {
                        if (index >= 0) this.debts.splice(index, 1);
                        return;
                    }
                    if (index >= 0) this.debts[index] = { ...this.debts[index], ...updated };
                    if (this.selectedDebt?.id == updated.id) this.selectedDebt = { ...this.selectedDebt, ...updated };
                },
                notify(message) { this.toast = message; clearTimeout(this.toastTimer); this.toastTimer = setTimeout(() => this.toast = '', 3200); },
                closeTopModal() { if (this.paymentModal.open) this.paymentModal.open = false; else this.detailModal.open = false; },

                async openDetails(debt) {
                    this.selectedDebt = debt;
                    this.collectionForm = { customer_phone: debt.customer_phone || '', due_date: debt.due_date || '', collection_notes: debt.collection_notes || '' };
                    this.detailModal = { open: true, loading: true, saving: false, error: '', items: [], payments: [] };
                    try {
                        const response = await fetch('<?= base_url('sales/get-details/') ?>' + debt.id);
                        const data = await response.json();
                        if (!response.ok || data.status !== 'success') throw new Error(data.message || 'No se pudo cargar el detalle.');
                        this.selectedDebt = { ...debt, ...data.sale };
                        this.detailModal.items = data.items || [];
                        this.detailModal.payments = data.payments || [];
                    } catch (error) {
                        this.detailModal.error = error.message || 'No se pudo cargar el detalle.';
                    } finally { this.detailModal.loading = false; }
                },
                async saveCollectionInfo() {
                    if (!this.selectedDebt) return;
                    this.detailModal.saving = true; this.detailModal.error = '';
                    try {
                        const response = await fetch('<?= base_url('sales/update-debt') ?>', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ sale_id: this.selectedDebt.id, ...this.collectionForm }) });
                        const data = await response.json();
                        if (!response.ok || data.status !== 'success') throw new Error(data.messages?.error || data.message || 'No se pudo guardar.');
                        this.syncDebt(data.sale); this.notify('Datos de cobranza actualizados');
                    } catch (error) { this.detailModal.error = error.message || 'No se pudo guardar.'; }
                    finally { this.detailModal.saving = false; }
                },

                openPayment(debt) {
                    if (!debt) return;
                    this.paymentModal = { open: true, loading: false, error: '', debt };
                    this.payment = { amount: '', amount_usd: '', rate: this.number(debt.exchange_rate || localStorage.getItem('exchangeRate') || 50), date: new Date().toISOString().slice(0, 10), reference: '', account_id: this.payment.account_id || (this.accounts[0]?.id || '') };
                },
                syncPayment(source) {
                    if (this.payment.rate <= 0) return;
                    if (source === 'usd') this.payment.amount = this.payment.amount_usd === '' ? '' : (this.number(this.payment.amount_usd) * this.payment.rate).toFixed(2);
                    if (source === 'bs') this.payment.amount_usd = this.payment.amount === '' ? '' : (this.number(this.payment.amount) / this.payment.rate).toFixed(2);
                    if (source === 'rate' && this.payment.amount_usd !== '') this.payment.amount = (this.number(this.payment.amount_usd) * this.payment.rate).toFixed(2);
                    this.paymentModal.error = '';
                },
                useFullBalance() { if (!this.paymentModal.debt) return; this.payment.amount_usd = this.remainingUsd(this.paymentModal.debt).toFixed(2); this.syncPayment('usd'); },
                get canSubmitPayment() {
                    if (!this.paymentModal.debt || !this.payment.account_id || this.payment.rate <= 0 || this.number(this.payment.amount_usd) <= 0) return false;
                    return this.number(this.payment.amount_usd) <= this.remainingUsd(this.paymentModal.debt) + .01;
                },
                async submitPayment() {
                    if (!this.canSubmitPayment) { this.paymentModal.error = 'Revisa el monto y selecciona la cuenta que recibe.'; return; }
                    this.paymentModal.loading = true; this.paymentModal.error = '';
                    try {
                        const response = await fetch('<?= base_url('sales/add-payment') ?>', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ sale_id: this.paymentModal.debt.id, ...this.payment }) });
                        const data = await response.json();
                        if (!response.ok || data.status !== 'success') throw new Error(data.messages?.error || data.message || 'No se pudo registrar el abono.');
                        const wasPaid = data.sale?.status === 'paid';
                        this.syncDebt(data.sale); this.paymentModal.open = false;
                        if (wasPaid) this.detailModal.open = false;
                        else if (this.detailModal.open && this.selectedDebt?.id == data.sale.id) await this.openDetails(data.sale);
                        this.notify(data.message || 'Abono registrado');
                    } catch (error) { this.paymentModal.error = error.message || 'No se pudo registrar el abono.'; }
                    finally { this.paymentModal.loading = false; }
                },

                invoiceText(debt) {
                    if (!debt) return '';
                    const lines = [
                        '*RECORDATORIO DE PAGO*',
                        '',
                        'Hola ' + (debt.customer || 'cliente') + ',',
                        'te compartimos el estado de tu cuenta:',
                        '',
                        '*Venta:* #' + debt.id,
                        '*Fecha:* ' + this.formatDate(debt.date),
                        '*Concepto:* ' + (debt.product || debt.description || 'Venta a crédito'),
                        '*Total:* ' + this.formatUsd(this.totalUsd(debt)),
                        '*Abonado:* ' + this.formatUsd(this.paidUsd(debt)),
                        '*Saldo pendiente:* ' + this.formatUsd(this.remainingUsd(debt)) + ' (' + this.formatBs(this.remainingBs(debt)) + ')',
                    ];
                    if (debt.due_date) lines.push('*Fecha límite:* ' + this.formatDate(debt.due_date));
                    if (debt.reference) lines.push('*Referencia:* ' + debt.reference);
                    if (this.selectedDebt?.id == debt.id && this.detailModal.items.length) {
                        lines.push('', '*Detalle:*');
                        this.detailModal.items.forEach(item => lines.push('• ' + this.number(item.quantity) + ' × ' + (item.item_name || 'Ítem') + ' — ' + this.formatUsd(item.subtotal)));
                    }
                    lines.push('', 'Cuando realices el pago, por favor envíanos el comprobante. Gracias.');
                    return lines.join('\n');
                },
                whatsappPhone(phone) {
                    let digits = String(phone || '').replace(/\D/g, '');
                    if (digits.startsWith('0')) digits = '58' + digits.slice(1);
                    if (digits.length === 10 && !digits.startsWith('58')) digits = '58' + digits;
                    return digits;
                },
                shareWhatsApp(debt) {
                    if (!debt) return;
                    const phone = this.whatsappPhone(debt.customer_phone);
                    const url = 'https://wa.me/' + phone + '?text=' + encodeURIComponent(this.invoiceText(debt));
                    window.open(url, '_blank', 'noopener,noreferrer');
                    fetch('<?= base_url('sales/record-reminder') ?>', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({sale_id: debt.id}) })
                        .then(response => response.json()).then(data => { if (data.status === 'success') this.syncDebt({ ...debt, ...data.data }); }).catch(() => {});
                },
                async copyInvoice(debt) {
                    if (!debt) return;
                    try { await navigator.clipboard.writeText(this.invoiceText(debt)); this.notify('Factura de deuda copiada'); }
                    catch (error) { this.notify('No se pudo copiar el mensaje'); }
                },
            };
        }
    </script>
</body>
</html>
