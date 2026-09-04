<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Cuentas por Cobrar | Fi-Hex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        [x-cloak] { display: none !important; }
        .safe-bottom { padding-bottom: max(1.5rem, env(safe-area-inset-bottom)); }
        .safe-top { padding-top: max(0.75rem, env(safe-area-inset-top)); }
        .customize-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
        .customize-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .customize-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800 antialiased" x-data="debtsApp()">

    <!-- Executive Top Nav Header -->
    <header class="sticky top-0 z-40 bg-gradient-to-r from-emerald-950 via-slate-900 to-teal-950 text-white shadow-xl border-b border-emerald-800/30 safe-top">
        <div class="max-w-md mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="<?= base_url('sales') ?>" class="w-10 h-10 rounded-2xl bg-white/10 hover:bg-white/20 active:scale-95 flex items-center justify-center text-white transition-all border border-white/10" title="Volver">
                    <span class="material-icons text-xl">arrow_back</span>
                </a>
                <div>
                    <h1 class="text-sm sm:text-base font-black tracking-tight text-white leading-tight">Cuentas por Cobrar</h1>
                    <p class="text-[10px] text-emerald-200/70 font-semibold">Créditos pendientes y abonos</p>
                </div>
            </div>
            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-rose-500 to-amber-400 p-[1.5px] shadow-sm">
                <div class="w-full h-full bg-slate-950 rounded-[9px] flex items-center justify-center">
                    <span class="material-icons text-amber-300 text-sm">pending_actions</span>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-md mx-auto p-4 pb-24 space-y-4 safe-bottom">
        
        <?php if (empty($debts)): ?>
            <div class="flex flex-col items-center justify-center py-20 text-center bg-white rounded-3xl border border-dashed border-slate-200 p-8">
                <div class="w-16 h-16 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mb-3">
                    <span class="material-icons text-3xl">verified</span>
                </div>
                <h2 class="text-base font-black text-slate-800">¡Todo al día!</h2>
                <p class="text-xs text-slate-400 font-bold mt-1">No tienes cobros pendientes de ventas</p>
            </div>
        <?php else: ?>
            <div class="flex justify-between items-center px-1">
                <span class="text-[11px] font-black uppercase tracking-wider text-slate-400">Listado de Pendientes</span>
                <span class="text-xs font-black text-rose-700 bg-rose-50 border border-rose-100 px-2.5 py-0.5 rounded-lg"><?= count($debts) ?> cuentas</span>
            </div>

            <?php foreach ($debts as $debt): ?>
                <?php 
                    $percent = ($debt['paid_amount_usd'] / ($debt['amount_usd'] ?: 1)) * 100;
                    $remaining = $debt['amount_usd'] - $debt['paid_amount_usd'];
                ?>
                <div class="bg-white rounded-3xl p-5 shadow-xs border border-slate-100 relative overflow-hidden">
                    <!-- Accent side indicator -->
                    <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-gradient-to-b from-rose-500 to-amber-500"></div>
                    
                    <div class="flex justify-between items-start mb-3 pl-2">
                        <div class="min-w-0 flex-1 pr-2">
                            <h3 class="text-sm sm:text-base font-black text-slate-900 leading-tight truncate"><?= esc($debt['customer']) ?></h3>
                            <p class="text-xs font-bold text-slate-400 mt-0.5 truncate"><?= esc($debt['product']) ?></p>
                        </div>
                        <div class="text-right shrink-0">
                             <span class="text-[9px] font-black text-rose-600 uppercase tracking-wider bg-rose-50 px-2 py-0.5 rounded-md">Por Cobrar</span>
                             <div class="text-lg sm:text-xl font-black text-rose-600 mt-1">$<?= number_format($remaining, 2) ?></div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="pl-2 mb-3.5">
                        <div class="flex justify-between text-[10px] font-black text-slate-400 mb-1">
                            <span class="text-emerald-700">Abonado: $<?= number_format($debt['paid_amount_usd'], 2) ?></span>
                            <span>Total: $<?= number_format($debt['amount_usd'], 2) ?></span>
                        </div>
                        <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-600 rounded-full" style="width: <?= min(100, max(0, $percent)) ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="pl-2">
                        <button @click="openPaymentModal(<?= htmlspecialchars(json_encode($debt)) ?>)" 
                                class="w-full py-2.5 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 active:scale-95 text-white text-xs font-black uppercase tracking-wider shadow-md shadow-emerald-950/20 transition-all flex items-center justify-center gap-1.5">
                            <span class="material-icons text-sm">payments</span>
                            <span>Registrar Abono</span>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <!-- Payment Modal (Mobile Bottom-Sheet) -->
    <div x-show="showModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4" x-cloak>
        <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs transition-opacity" @click="showModal = false"></div>
        
        <div class="bg-white rounded-t-[2.5rem] sm:rounded-3xl p-6 w-full sm:max-w-sm shadow-2xl relative z-10 safe-bottom animate-slide-up space-y-4">
            
            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-base font-black text-slate-900">Registrar Abono</h3>
                    <p class="text-[10px] text-slate-400 font-bold" x-text="currentDebt?.customer"></p>
                </div>
                <button @click="showModal = false" class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 hover:text-slate-800 transition-colors">
                    <span class="material-icons text-sm">close</span>
                </button>
            </div>
            
            <div class="text-center bg-rose-50/60 border border-rose-100 rounded-2xl p-4">
                <p class="text-[10px] font-black text-rose-600 uppercase tracking-wider">Deuda Pendiente</p>
                <h2 class="text-3xl font-black text-slate-900 my-1 tracking-tight" x-text="formatMoney(remainingUsd, 'USD')"></h2>
                <p class="text-xs font-bold text-slate-500" x-text="'≈ ' + formatMoney(remainingBs)"></p>
            </div>

            <div class="space-y-3">
                <!-- USD Input -->
                <div class="bg-slate-50 rounded-2xl p-3 border border-slate-200 focus-within:border-emerald-500 focus-within:bg-white transition-all">
                     <label class="text-[10px] font-black text-emerald-700 uppercase tracking-wider block mb-1">Abono en Dólares ($)</label>
                     <div class="flex items-center">
                         <span class="text-emerald-700 font-black mr-2 text-lg">$</span>
                         <input type="number" step="0.01" x-model.number="payment.amount_usd" @input="calculatePayment('usd')" class="w-full bg-transparent font-black text-lg text-slate-900 outline-none" placeholder="0.00">
                     </div>
                </div>

                <!-- BS Input -->
                <div class="bg-slate-50 rounded-2xl p-3 border border-slate-200 focus-within:border-emerald-500 focus-within:bg-white transition-all">
                     <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block mb-1">Abono en Bolívares (Bs)</label>
                     <div class="flex items-center">
                         <span class="text-slate-400 font-black mr-2 text-sm">Bs.</span>
                         <input type="number" step="0.01" x-model.number="payment.amount" @input="calculatePayment('bs')" class="w-full bg-transparent font-black text-lg text-slate-900 outline-none" placeholder="0.00">
                     </div>
                </div>
                
                <!-- Exchange Rate -->
                <div class="flex items-center justify-between px-2 pt-1">
                     <span class="text-[10px] font-black text-slate-400 uppercase">Tasa de Cambio:</span>
                     <div class="flex items-center gap-1 bg-slate-100 px-2.5 py-1 rounded-xl">
                         <span class="text-xs font-bold text-slate-500">Bs.</span>
                         <input type="number" step="0.01" x-model.number="payment.rate" @input="calculatePayment('rate')" class="w-14 text-right text-xs font-black text-slate-800 bg-transparent outline-none">
                     </div>
                </div>
            </div>

            <div class="pt-2">
                <button @click="submitPayment()" :disabled="loading || !payment.amount_usd" 
                        class="w-full py-3.5 bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white rounded-2xl font-black text-sm shadow-xl shadow-emerald-950/20 active:scale-95 transition-all flex justify-center items-center gap-2 disabled:opacity-50">
                    <span x-show="!loading">Confirmar Abono</span>
                    <span x-show="loading" class="material-icons animate-spin text-sm">refresh</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        function debtsApp() {
            return {
                showModal: false,
                loading: false,
                currentDebt: null,
                remainingUsd: 0,
                remainingBs: 0,
                payment: {
                    amount: '',
                    amount_usd: '',
                    rate: 50,
                    date: new Date().toISOString().split('T')[0],
                    reference: ''
                },

                init() {
                    const savedRate = localStorage.getItem('exchangeRate');
                    if(savedRate) this.payment.rate = parseFloat(savedRate);
                },

                openPaymentModal(debt) {
                    this.currentDebt = debt;
                    this.remainingUsd = Math.max(0, parseFloat(debt.amount_usd) - parseFloat(debt.paid_amount_usd));
                    this.remainingBs = Math.max(0, (parseFloat(debt.amount) - parseFloat(debt.paid_amount)));
                    
                    this.payment.amount = '';
                    this.payment.amount_usd = '';
                    this.showModal = true;
                },

                calculatePayment(source) {
                    const rate = this.payment.rate;
                    if (!rate) return;

                    if (source === 'usd' && this.payment.amount_usd) {
                        this.payment.amount = (this.payment.amount_usd * rate).toFixed(2);
                    } else if (source === 'bs' && this.payment.amount) {
                        this.payment.amount_usd = (this.payment.amount / rate).toFixed(2);
                    } else if (source === 'rate' && this.payment.amount_usd) {
                         this.payment.amount = (this.payment.amount_usd * rate).toFixed(2);
                    }
                },

                async submitPayment() {
                    if (!this.payment.amount_usd || !this.currentDebt) return;

                    this.loading = true;
                    try {
                        let payload = {
                            sale_id: this.currentDebt.id,
                            ...this.payment
                        };

                        let res = await fetch('<?= base_url('sales/add-payment') ?>', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify(payload)
                        });
                        let data = await res.json();
                        
                        if (data.status === 'success') {
                            location.reload(); 
                        } else {
                            alert('Error al procesar pago: ' + (data.message || 'Error desconocido'));
                        }
                    } catch(e) {
                        console.error(e);
                        alert('Error de conexión');
                    } finally {
                        this.loading = false;
                    }
                },

                formatMoney(value, currency = 'Bs') {
                    if (!value) return (0).toFixed(2);
                    if (currency === 'USD') {
                        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 2 }).format(value);
                    }
                    return new Intl.NumberFormat('es-VE', { style: 'currency', currency: 'VES', minimumFractionDigits: 2 }).format(value);
                }
            }
        }
    </script>
</body>
</html>
