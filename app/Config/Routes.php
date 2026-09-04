<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'TransactionController::index');
$routes->match(['get', 'head'], 'health', static function() {
    return 'OK';
});
$routes->post('transaction/save', 'TransactionController::save');
$routes->post('transaction/update/(:num)', 'TransactionController::update/$1');
$routes->get('transaction/stats', 'TransactionController::stats');
$routes->get('currency/get-rate', 'CurrencyController::getBCVRate');

// History
$routes->get('history', 'HistoryController::index');
$routes->post('history/fetch', 'HistoryController::fetch');
$routes->get('history/delete/(:num)', 'HistoryController::delete/$1');
$routes->get('history/items/(:num)', 'HistoryController::getItems/$1');


// Metrics
$routes->get('metrics', 'MetricsController::index');
$routes->post('metrics/fetch', 'MetricsController::fetch');
$routes->get('metrics/export', 'MetricsController::export');

// Routes for Printing Module
$routes->get('printing', 'PrintingController::index');
$routes->get('printing/settings', 'PrintingController::settings');
$routes->post('printing/store', 'PrintingController::store');
$routes->get('printing/history', 'PrintingController::getHistory');
$routes->get('printing/movements', 'PrintingController::getMovements');
$routes->post('printing/add-payment', 'PrintingController::addPayment');
$routes->post('printing/save-product', 'PrintingController::saveProduct');
$routes->get('printing/delete-product/(:num)', 'PrintingController::deleteProduct/$1');
$routes->post('printing/delete-order/(:num)', 'PrintingController::deleteOrder/$1');
$routes->post('printing/update-order', 'PrintingController::updateOrder');
$routes->get('printing/payments/(:num)', 'PrintingController::getPayments/$1');
$routes->get('printing/customers', 'PrintingController::getCustomers');
$routes->post('printing/toggle-favorite', 'PrintingController::toggleFavorite');
$routes->post('printing/delete-transaction/(:num)', 'PrintingController::deleteTransaction/$1');
$routes->get('printing/debug-payments', 'PrintingController::debugPayments');

$routes->get('printing/fix-db', 'PrintingController::fixDb');

// Config
$routes->get('config', 'ConfigController::index');
$routes->post('config/save', 'ConfigController::saveSetting');
$routes->get('config/get-data', 'ConfigController::getData');
$routes->post('config/add-category', 'ConfigController::addCategory');
$routes->get('config/delete-category/(:num)', 'ConfigController::deleteCategory/$1');
$routes->post('config/add-account', 'ConfigController::addAccount');
$routes->get('config/delete-account/(:num)', 'ConfigController::deleteAccount/$1');
$routes->post('config/update-balance', 'ConfigController::updateBalance');
$routes->get('config/export', 'ConfigController::export');

// Accounts (Dedicated)
$routes->get('accounts', 'AccountController::index');
$routes->get('accounts/fetch', 'AccountController::fetch');
$routes->post('accounts/add', 'AccountController::add');
$routes->get('accounts/delete/(:num)', 'AccountController::delete/$1');
$routes->post('accounts/update-balance', 'AccountController::updateBalance');
$routes->post('accounts/transfer', 'AccountController::transfer');
$routes->post('/accounts/create-temp', 'AccountController::createTemporary');
$routes->get('/accounts/close-temp/(:num)', 'AccountController::closeTemporary/$1');

// Admin repair route
$routes->get('/admin/fix-compras', 'AdminRepair::fixCompras');
$routes->get('migrate', 'Migrate::index');

// AI Assistant
$routes->get('ai', 'AIController::index');
$routes->post('ai/chat', 'AIController::chat');
$routes->post('ai/save-conversation', 'AIController::saveConversation');
$routes->get('ai/conversations', 'AIController::getConversations');
$routes->get('ai/conversation/(:num)', 'AIController::loadConversation/$1');
$routes->delete('ai/conversation/(:num)', 'AIController::deleteConversation/$1');

// Audit Log / Bitácora
$routes->get('audit', 'AuditLogController::index');
$routes->post('audit/fetch', 'AuditLogController::fetch');
$routes->post('audit/chat', 'AuditLogController::chat');


// Sales Module
$routes->get('sales', 'SalesController::index');
$routes->get('sales/create', 'SalesController::create');
$routes->post('sales/store', 'SalesController::store');
$routes->get('sales/debts', 'SalesController::debts');
$routes->post('sales/add-payment', 'SalesController::addPayment');
$routes->get('sales/history', 'SalesController::history');
$routes->get('sales/get-details/(:num)', 'SalesController::getSaleDetails/$1');
$routes->get('sales/get-statuses', 'SalesController::getStatuses');
$routes->get('sales/get-active-orders', 'SalesController::getActiveOrders');
$routes->get('sales/get-accounts', 'SalesController::getAccounts');
$routes->get('sales/get-categories', 'SalesController::getCategories');
$routes->post('sales/update-status', 'SalesController::updateStatus');

// Inventory Routes
$routes->get('inventory', 'InventoryController::index');
$routes->get('inventory/items', 'InventoryController::items');
$routes->get('inventory/movements', 'InventoryController::movements'); // Placeholder
$routes->get('inventory/get-items', 'InventoryController::getItems');
$routes->get('inventory/get-categories', 'InventoryController::getCategories');
$routes->post('inventory/save-item', 'InventoryController::saveItem');
$routes->get('inventory/delete-item/(:num)', 'InventoryController::deleteItem/$1');
$routes->post('inventory/save-category', 'InventoryController::saveCategory');
$routes->post('inventory/quick-create', 'InventoryController::quickCreate');
$routes->get('inventory/search', 'InventoryController::search');

