<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
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

// Rotas públicas de produtos (para WordPress consumir)
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/{id}', [ProductController::class, 'show']);
});

// Rotas de pedidos (checkout)
Route::prefix('orders')->group(function () {
    Route::post('/', [OrderController::class, 'store']); // Criar pedido (checkout)
    Route::get('/{id}', [OrderController::class, 'show']); // Ver detalhes do pedido
});

// Webhook do Bling (recebe atualizações em tempo real)
Route::post('webhooks/bling', [WebhookController::class, 'handle'])->name('webhooks.bling');

// Rotas protegidas (requerem autenticação via Sanctum)
Route::middleware('auth:sanctum')->group(function () {
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
