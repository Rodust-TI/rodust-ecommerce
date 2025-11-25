<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BlingController;

Route::get('/', function () {
    return view('welcome');
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
    Route::post('/api/sync-customers', [BlingController::class, 'apiSyncCustomers'])->name('bling.api.sync-customers');
    Route::get('/api/contact-types', [BlingController::class, 'apiListContactTypes'])->name('bling.api.contact-types');
});
