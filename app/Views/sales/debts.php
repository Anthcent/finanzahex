<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuentas por Cobrar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen" x-data="debtsApp()">

    <!-- Header -->
    <div class="bg-white/90 backdrop-blur-md border-b border-slate-100 sticky top-0 z-40">
        <div class="max-w-md mx-auto flex items-center justify-between p-4">
            <a href="<?= base_url('sales') ?>" class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-600 hover:bg-slate-100 transition-colors">
                <span class="material-icons">arrow_back</span>
            </a>
            <h1 class="text-lg font-bold text-slate-800">Cuentas por Cobrar</h1>
            <div class="w-10"></div> 
        </div>
    </div>

    <div class="max-w-md mx-auto p-4 pb-20 space-y-4">
        
        <?php if (empty($debts)): ?>
            <div class="flex flex-col items-center justify-center py-20 opacity-50">
                <div class="w-24 h-24 bg-indigo-50 rounded-full flex items-center justify-center mb-4">
                    <span class="material-icons text-5xl text-indigo-300">check_circle</span>
                </div>
                <h2 class="text-xl font-bold text-slate-700">Todo al día</h2>
                <p class="text-sm text-slate-400">No tienes cobros pendientes</p>
            </div>
        <?php else: ?>
            <?php foreach ($debts as $debt): ?>
                <?php 
                    $percent = ($debt['paid_amount_usd'] / $debt['amount_usd']) * 100;
                    $remaining = $debt['amount_usd'] - $debt['paid_amount_usd'];
                ?>
                <div class="bg-white rounded-[1.5rem] p-5 shadow-[0_10px_30px_-10px_rgba(0,0,0,0.08)] border border-slate-50 relative overflow-hidden group">
                    <!-- Deco Line -->
                    <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-gradient-to-b from-amber-400 to-orange-500"></div>
                    
                    <div class="flex justify-between items-start mb-3 pl-3">
                        <div>
                            <h3 class="text-lg font-bold text-slate-800 leading-tight"><?= esc($debt['customer']) ?></h3>
                            <p class="text-xs font-medium text-slate-400 mt-1"><?= esc($debt['product']) ?></p>
                        </div>
                        <div class="text-right">
                             <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Restante</div>
                             <div class="text-xl font-black text-amber-600">$<?= number_format($remaining, 2) ?></div>
                        </div>
                    </div>

                    <!-- Progress -->
                    <div class="pl-3 mb-4">
                        <div class="flex justify-between text-[10px] font-bold text-slate-400 mb-1">
                            <span>Pagado: $<?= number_format($debt['paid_amount_usd'], 2) ?></span>
                            <span>Total: $<?= number_format($debt['amount_usd'], 2) ?></span>
                        </div>
                        <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-amber-400 to-orange-500 rounded-full" style="width: <?= $percent ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="pl-3">
                        <button @click="openPaymentModal(<?= htmlspecialchars(json_encode($debt)) ?>)" class="w-full py-2.5 rounded-xl bg-slate-50 text-slate-700 text-xs font-bold uppercase tracking-wider hover:bg-slate-100 active:bg-slate-200 transition-colors flex items-center justify-center gap-2">
                            <span class="material-icons text-sm">payments</span>
                            Registrar Abono
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Payment Modal -->
    <div x-show="showModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:px-4" x-cloak>
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showModal = false"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
        
        <div class="bg-white rounded-t-[2rem] sm:rounded-[2rem] p-6 w-full max-w-sm shadow-2xl relative z-10 transform transition-transform"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-full sm:scale-95 sm:translate-y-10 opacity-0" x-transition:enter-end="translate-y-0 sm:scale-100 sm:translate-y-0 opacity-100"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-y-0 opacity-100" x-transition:leave-end="translate-y-full opacity-0">
            
            <div class="w-12 h-1.5 bg-slate-200 rounded-full mx-auto mb-6"></div>
            
            <div class="text-center mb-6">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Deuda Pendiente</p>
                <h2 class="text-4xl font-black text-slate-800 my-1" x-text="formatMoney(remainingUsd, 'USD')"></h2>
                <p class="text-sm font-medium text-slate-400" x-text="'≈ ' + formatMoney(remainingBs)"></p>
            </div>

            <div class="space-y-4 mb-8">
                <!-- USD Input -->
                <div class="bg-slate-50 rounded-2xl p-3 border border-slate-100 focus-within:border-indigo-200 focus-within:bg-indigo-50/30 transition-colors">
                     <label class="text-[10px] font-bold text-emerald-500 uppercase tracking-wider block mb-1">Abono en Dólares</label>
                     <div class="flex items-center">
                         <span class="text-emerald-500 font-bold mr-2">$</span>
                         <input type="number" step="0.01" x-model.number="payment.amount_usd" @input="calculatePayment('usd')" class="w-full bg-transparent font-bold text-xl text-slate-800 outline-none placeholder-slate-300" placeholder="0.00">
                     </div>
                </div>

                <!-- BS Input -->
                <div class="bg-slate-50 rounded-2xl p-3 border border-slate-100 focus-within:border-indigo-200 focus-within:bg-indigo-50/30 transition-colors">
                     <label class="text-[10px] font-bold text-indigo-400 uppercase tracking-wider block mb-1">Abono en Bolívares</label>
                     <div class="flex items-center">
                         <span class="text-indigo-400 font-bold mr-2">Bs</span>
                         <input type="number" step="0.01" x-model.number="payment.amount" @input="calculatePayment('bs')" class="w-full bg-transparent font-bold text-xl text-slate-800 outline-none placeholder-slate-300" placeholder="0.00">
                     </div>
                </div>
                
                <!-- Rate -->
                <div class="flex items-center justify-end gap-2 px-2">
                     <span class="text-[10px] font-bold text-slate-400 uppercase">Tasa:</span>
                     <input type="number" step="0.01" x-model.number="payment.rate" @input="calculatePayment('rate')" class="w-16 text-right text-xs font-bold text-slate-600 bg-transparent border-b border-slate-200 outline-none">
                </div>
            </div>

            <button @click="submitPayment()" :disabled="loading" class="w-full py-4 bg-indigo-900 text-white rounded-2xl font-bold shadow-lg shadow-indigo-200 active:scale-95 transition-all flex justify-center items-center gap-2">
                <span x-show="!loading">Confirmar Abono</span>
                <span x-show="loading" class="material-icons animate-spin text-sm">refresh</span>
            </button>
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
                    this.remainingUsd = parseFloat(debt.amount_usd) - parseFloat(debt.paid_amount_usd);
                    this.remainingBs = (parseFloat(debt.amount) - parseFloat(debt.paid_amount)); // Keep simple
                    
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
                            alert('Error al procesar pago');
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
