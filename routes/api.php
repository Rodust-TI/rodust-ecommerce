<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\CustomerAddressController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\WebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// ========================================
// ROTAS PÚBLICAS (sem autenticação)
// ========================================

// Autenticação de clientes
Route::prefix('customers')->group(function () {
    Route::post('/register', [CustomerController::class, 'register']);
    Route::post('/login', [CustomerController::class, 'login']);
    Route::post('/verify-email', [CustomerController::class, 'verifyEmail']);
    Route::post('/resend-verification', [CustomerController::class, 'resendVerification']);
    Route::post('/sync-from-wordpress', [CustomerController::class, 'syncFromWordPress']);
});

// Produtos (para WordPress consumir)
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/{id}', [ProductController::class, 'show']);
    Route::post('/sync-from-bling', [ProductController::class, 'syncFromBling']);
});

// Buscar CEP (público - para preencher formulário de endereço)
Route::get('addresses/search-zipcode/{zipcode}', [CustomerAddressController::class, 'searchZipcode']);

// Webhook do Bling (recebe atualizações em tempo real)
Route::post('webhooks/bling', [WebhookController::class, 'handle'])->name('webhooks.bling');

// ========================================
// ROTAS PROTEGIDAS (requerem autenticação)
// ========================================

Route::middleware('auth:sanctum')->group(function () {
    
    // Área do cliente
    Route::prefix('customers')->group(function () {
        Route::post('/logout', [CustomerController::class, 'logout']);
        Route::get('/me', [CustomerController::class, 'me']);
        Route::put('/me', [CustomerController::class, 'updateProfile']);
        
        // Endereços do cliente
        Route::prefix('addresses')->group(function () {
            Route::get('/', [CustomerAddressController::class, 'index']);
            Route::post('/', [CustomerAddressController::class, 'store']);
            Route::get('/{id}', [CustomerAddressController::class, 'show']);
            Route::put('/{id}', [CustomerAddressController::class, 'update']);
            Route::delete('/{id}', [CustomerAddressController::class, 'destroy']);
            Route::post('/{id}/set-default', [CustomerAddressController::class, 'setDefault']);
        });
    });
    
    // Wishlist (Lista de Desejos)
    Route::prefix('wishlist')->group(function () {
        Route::get('/', [WishlistController::class, 'index']);
        Route::post('/', [WishlistController::class, 'store']);
        Route::delete('/{productId}', [WishlistController::class, 'destroy']);
        Route::get('/check/{productId}', [WishlistController::class, 'check']);
    });

    // Criar pedido (checkout) - requer autenticação
    Route::post('orders', [OrderController::class, 'store']);
    Route::get('orders/{id}', [OrderController::class, 'show']);

    // Gerenciamento de produtos (admin)
    Route::prefix('admin/products')->group(function () {
        Route::post('/', [ProductController::class, 'store']);
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
    });

    // Gerenciamento de pedidos (admin)
    Route::prefix('admin/orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::put('/{id}', [OrderController::class, 'update']);
        Route::delete('/{id}', [OrderController::class, 'destroy']);
    });
});

