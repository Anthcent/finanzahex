<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Configuración - FinazaPersonal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col" x-data="configApp()">

    <!-- Header -->
    <div class="bg-white p-4 shadow-sm z-10 flex items-center mb-4">
        <a href="<?= base_url() ?>" class="mr-3 text-gray-500 hover:text-gray-800">
            <span class="material-icons">arrow_back</span>
        </a>
        <h1 class="text-xl font-bold text-gray-800">Configuración</h1>
    </div>

    <div class="flex-1 p-4 space-y-6 max-w-lg mx-auto w-full">

        
        <!-- Accounts Section -->
        <div class="bg-white rounded-lg shadow-sm p-4">
            <h2 class="text-lg font-bold text-gray-800 mb-3 border-b pb-2">Cuentas y Fondos</h2>
            <div class="space-y-3">
                <template x-for="acc in accounts" :key="acc.id">
                    <div class="flex items-center justify-between bg-gray-50 p-2 rounded">
                        <div>
                            <span class="font-medium text-gray-700" x-text="acc.name"></span>
                        </div>
                        <div class="flex items-center space-x-2">
                             <!-- Balance Input -->
                             <div class="flex items-center bg-white border rounded px-2">
                                <span class="text-xs text-gray-400 mr-1">Bs.</span>
                                <input type="number" x-model="acc.balance" @change="updateBalance(acc)" class="w-24 text-right text-sm font-bold text-gray-800 focus:outline-none py-1">
                             </div>
                             <button @click="deleteAccount(acc.id)" class="text-gray-300 hover:text-red-500">
                                <span class="material-icons text-sm">delete</span>
                             </button>
                        </div>
                    </div>
                </template>
            </div>
            <div class="mt-4 flex space-x-2">
                <input type="text" x-model="newAccount" placeholder="Nueva Cuenta (ej. Banesco)" class="flex-1 border rounded p-2 text-sm">
                <button @click="addAccount()" class="bg-indigo-600 text-white px-4 rounded font-bold text-sm">+</button>
            </div>
        </div>

        <!-- Categories Section -->
        <div class="bg-white rounded-lg shadow-sm p-4">
            <h2 class="text-lg font-bold text-gray-800 mb-3 border-b pb-2">Categorías</h2>
            <div class="space-y-2 max-h-60 overflow-y-auto">
                <template x-for="cat in categories" :key="cat.id">
                    <div class="flex justify-between items-center bg-gray-50 p-2 rounded">
                         <span class="text-sm text-gray-700" x-text="cat.name"></span>
                         <button @click="deleteCategory(cat.id)" class="text-gray-300 hover:text-red-500">
                            <span class="material-icons text-sm">delete</span>
                         </button>
                    </div>
                </template>
            </div>
             <div class="mt-4 flex space-x-2">
                <input type="text" x-model="newCategory" placeholder="Nueva Categoría" class="flex-1 border rounded p-2 text-sm">
                <button @click="addCategory()" class="bg-indigo-600 text-white px-4 rounded font-bold text-sm">+</button>
            </div>
        </div>

        <!-- Data Export Section -->
        <div class="bg-white rounded-lg shadow-sm p-4">
            <h2 class="text-lg font-bold text-gray-800 mb-3 border-b pb-2">Gestión de Datos</h2>
            <div class="space-y-2">
                <a href="<?= base_url('config/export') ?>" target="_blank" class="flex items-center justify-between p-3 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center group-hover:bg-white text-indigo-600">
                             <span class="material-icons text-sm">download</span>
                        </div>
                        <div>
                             <h3 class="text-sm font-bold text-gray-800">Exportar Transacciones</h3>
                             <p class="text-xs text-gray-500">Descarga todo en formato CSV</p>
                        </div>
                    </div>
                    <span class="material-icons text-gray-300 group-hover:text-indigo-500">chevron_right</span>
                </a>
            </div>
        </div>

    </div>

    <script>
        function configApp() {
            return {
                accounts: [],
                categories: [],
                newAccount: '',
                newCategory: '',

                init() {
                    this.fetchData();
                },

                async fetchData() {
                    try {
                        let res = await fetch('<?= base_url('config/get-data') ?>');
                        let data = await res.json();
                        this.accounts = data.accounts;
                        this.categories = data.categories;
                    } catch(e) {}
                },

                async addAccount() {
                    if(!this.newAccount) return;
                    await fetch('<?= base_url('config/add-account') ?>', {
                        method: 'POST',
                        body: JSON.stringify({ name: this.newAccount })
                    });
                    this.newAccount = '';
                    this.fetchData();
                },

                async deleteAccount(id) {
                    if(!confirm('¿Borrar cuenta?')) return;
                    await fetch('<?= base_url('config/delete-account/') ?>' + id);
                    this.fetchData();
                },

                async updateBalance(acc) {
                    await fetch('<?= base_url('config/update-balance') ?>', {
                        method: 'POST',
                        body: JSON.stringify({ id: acc.id, balance: acc.balance })
                    });
                    // Optional: Show success toast
                },

                async addCategory() {
                    if(!this.newCategory) return;
                    await fetch('<?= base_url('config/add-category') ?>', {
                        method: 'POST',
                        body: JSON.stringify({ name: this.newCategory })
                    });
                    this.newCategory = '';
                    this.fetchData();
                },
                
                async deleteCategory(id) {
                    if(!confirm('¿Borrar categoría?')) return;
                    await fetch('<?= base_url('config/delete-category/') ?>' + id);
                    this.fetchData();
                }
            }
        }
    </script>
</body>
</html>
