<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BlingController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\PasswordResetController;

Route::get('/', function () {
    return view('welcome');
});

// Rotas Google OAuth
Route::prefix('auth/google')->group(function () {
    Route::get('/redirect', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
    Route::get('/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');
    Route::post('/logout', [GoogleAuthController::class, 'logout'])->name('google.logout');
});

// Rotas de reset de senha obrigatório
Route::middleware(['auth'])->group(function () {
    Route::get('/password/force-reset', [PasswordResetController::class, 'showResetForm'])->name('password.force-reset');
    Route::post('/password/force-reset', [PasswordResetController::class, 'resetPassword'])->name('password.force-reset.store');
    Route::get('/password/reset-status', [PasswordResetController::class, 'checkStatus'])->name('password.reset-status');
});

// Rotas Admin
Route::prefix('admin')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/api/sales-chart', [\App\Http\Controllers\Admin\AdminController::class, 'salesChart'])->name('admin.api.sales-chart');
    
    // Clientes
    Route::prefix('customers')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\CustomerController::class, 'index'])->name('admin.customers.index');
        Route::get('/{customer}', [\App\Http\Controllers\Admin\CustomerController::class, 'show'])->name('admin.customers.show');
        Route::get('/{customer}/edit', [\App\Http\Controllers\Admin\CustomerController::class, 'edit'])->name('admin.customers.edit');
        Route::put('/{customer}', [\App\Http\Controllers\Admin\CustomerController::class, 'update'])->name('admin.customers.update');
        Route::delete('/{customer}', [\App\Http\Controllers\Admin\CustomerController::class, 'destroy'])->name('admin.customers.destroy');
    });

    // Pedidos
    Route::prefix('orders')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\OrderController::class, 'index'])->name('admin.orders.index');
        Route::get('/{order}', [\App\Http\Controllers\Admin\OrderController::class, 'show'])->name('admin.orders.show');
        Route::get('/{order}/edit', [\App\Http\Controllers\Admin\OrderController::class, 'edit'])->name('admin.orders.edit');
        Route::put('/{order}', [\App\Http\Controllers\Admin\OrderController::class, 'update'])->name('admin.orders.update');
        Route::delete('/{order}', [\App\Http\Controllers\Admin\OrderController::class, 'destroy'])->name('admin.orders.destroy');
    });
    
    // Backups
    Route::prefix('backups')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\BackupController::class, 'index'])->name('admin.backups.index');
        Route::post('/create', [\App\Http\Controllers\Admin\BackupController::class, 'create'])->name('admin.backups.create');
        Route::get('/history', [\App\Http\Controllers\Admin\BackupController::class, 'history'])->name('admin.backups.history');
        Route::post('/refresh-database', [\App\Http\Controllers\Admin\BackupController::class, 'refreshDatabase'])->name('admin.backups.refresh-database');
        Route::get('/{backup}/download', [\App\Http\Controllers\Admin\BackupController::class, 'download'])->name('admin.backups.download');
        Route::get('/{backup}/validate', [\App\Http\Controllers\Admin\BackupController::class, 'validate'])->name('admin.backups.validate');
        Route::post('/{backup}/restore', [\App\Http\Controllers\Admin\BackupController::class, 'restore'])->name('admin.backups.restore');
        Route::delete('/{backup}', [\App\Http\Controllers\Admin\BackupController::class, 'destroy'])->name('admin.backups.destroy');
        Route::get('/settings', [\App\Http\Controllers\Admin\BackupController::class, 'settings'])->name('admin.backups.settings');
    });
});

// Rotas Bling OAuth
Route::prefix('bling')->group(function () {
    Route::get('/', [BlingController::class, 'dashboard'])->name('bling.dashboard');
    Route::get('/authorize', [BlingController::class, 'authorize'])->name('bling.authorize');
    Route::get('/callback', [BlingController::class, 'callback'])->name('bling.callback');
    Route::get('/status', [BlingController::class, 'status'])->name('bling.status');
    Route::post('/revoke', [BlingController::class, 'revoke'])->name('bling.revoke');
    Route::post('/refresh', [BlingController::class, 'refreshToken'])->name('bling.refresh');
    
    // API endpoints
    Route::get('/api/products', [BlingController::class, 'apiProducts'])->name('bling.api.products');
    Route::post('/api/sync-products', [BlingController::class, 'apiSyncProducts'])->name('bling.api.sync-products');
    Route::post('/api/sync-products-advanced', [BlingController::class, 'apiSyncProductsAdvanced'])->name('bling.api.sync-products-advanced');
    Route::post('/api/sync-product/{blingId}', [BlingController::class, 'apiSyncSingleProduct'])->name('bling.api.sync-single-product');
    Route::post('/api/sync-customers', [BlingController::class, 'apiSyncCustomers'])->name('bling.api.sync-customers');
    Route::post('/api/get-customers-from-bling', [BlingController::class, 'apiGetCustomersFromBling'])->name('bling.api.get-customers-from-bling');
    Route::post('/api/sync-orders', [BlingController::class, 'apiSyncOrders'])->name('bling.api.sync-orders');
    Route::get('/api/contact-types', [BlingController::class, 'apiListContactTypes'])->name('bling.api.contact-types');
    Route::get('/api/payment-methods', [BlingController::class, 'apiListPaymentMethods'])->name('bling.api.payment-methods');
    
    // Status do Bling
    Route::post('/api/fetch-statuses', [BlingController::class, 'fetchStatuses'])->name('bling.api.fetch-statuses');
    Route::post('/api/sync-order-statuses', [BlingController::class, 'syncOrderStatuses'])->name('bling.api.sync-order-statuses');
    Route::post('/api/clear-status-cache', [BlingController::class, 'clearStatusCache'])->name('bling.api.clear-status-cache');
    
    // Webhook Logs
    Route::get('/api/webhook-logs', [BlingController::class, 'apiWebhookLogs'])->name('bling.api.webhook-logs');
});
