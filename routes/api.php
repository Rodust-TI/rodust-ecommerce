<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\CustomerAddressController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\MercadoPagoWebhookController;
use App\Http\Controllers\API\MelhorEnvioController;
use App\Http\Controllers\API\PaymentController;

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
    Route::get('/wordpress', [ProductController::class, 'wordpress']); // Endpoint completo para WordPress
    Route::get('/{id}', [ProductController::class, 'show']);
    Route::post('/sync-from-bling', [ProductController::class, 'syncFromBling']);
    // Sincronização Laravel → WordPress
    Route::post('/sync-to-wordpress', [ProductController::class, 'syncAllToWordPress']); // Todos
    Route::post('/{id}/sync-to-wordpress', [ProductController::class, 'syncOneToWordPress']); // Individual
});

// Buscar CEP (público - para preencher formulário de endereço)
Route::get('addresses/search-zipcode/{zipcode}', [CustomerAddressController::class, 'searchZipcode']);

// Webhook do Bling (recebe atualizações em tempo real)
Route::post('webhooks/bling', [WebhookController::class, 'handle'])->name('webhooks.bling');

// Melhor Envio - OAuth Callback (público)
Route::get('melhor-envio/oauth/callback', [MelhorEnvioController::class, 'oauthCallback']);

// Melhor Envio - Webhook (público)
Route::post('melhor-envio/webhook', [MelhorEnvioController::class, 'webhook']);

// Melhor Envio - Calculate Shipping (público - usado no checkout)
Route::post('shipping/calculate', [MelhorEnvioController::class, 'calculateShipping']);

// Mercado Pago - Public Key (público - usado no frontend)
Route::get('payments/mercadopago/public-key', [PaymentController::class, 'getPublicKey']);

// Mercado Pago - Webhook (público - recebe notificações de pagamento)
Route::post('webhooks/mercadopago', [MercadoPagoWebhookController::class, 'handle']);

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
            Route::post('/{id}/toggle-type', [CustomerAddressController::class, 'toggleType']);
        });
    });
    
    // Wishlist (Lista de Desejos)
    Route::prefix('wishlist')->group(function () {
        Route::get('/', [WishlistController::class, 'index']);
        Route::post('/', [WishlistController::class, 'store']);
        Route::delete('/{productId}', [WishlistController::class, 'destroy']);
        Route::get('/check/{productId}', [WishlistController::class, 'check']);
    });

    // Pagamentos (criar pedido + processar pagamento)
    Route::prefix('payments')->group(function () {
        Route::post('/pix', [PaymentController::class, 'createPixPayment']);
        Route::post('/boleto', [PaymentController::class, 'createBoletoPayment']);
        Route::post('/card', [PaymentController::class, 'createCardPayment']);
    });

    // Pedidos do cliente - requer autenticação
    Route::get('orders', [OrderController::class, 'index']); // Lista pedidos do cliente
    Route::post('orders', [OrderController::class, 'store']); // Criar pedido (checkout)
    Route::get('orders/{id}', [OrderController::class, 'show']); // Ver detalhes de um pedido

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

    // Melhor Envio - Admin routes
    Route::prefix('admin/melhor-envio')->group(function () {
        Route::get('/settings', [MelhorEnvioController::class, 'getSettings']);
        Route::post('/settings', [MelhorEnvioController::class, 'updateSettings']);
        Route::get('/auth', [MelhorEnvioController::class, 'redirectToAuth']);
    });
});

