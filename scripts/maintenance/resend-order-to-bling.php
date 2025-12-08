<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Services\Bling\BlingOrderService;
use App\Services\ERP\BlingV3Adapter;

$orderId = $argv[1] ?? null;

if (!$orderId) {
    echo "❌ Uso: php resend-order-to-bling.php <order_id>\n";
    exit(1);
}

echo "🔄 Reenviando pedido #{$orderId} ao Bling...\n\n";

$order = Order::with(['customer', 'items.product'])->find($orderId);

if (!$order) {
    echo "❌ Pedido #{$orderId} não encontrado\n";
    exit(1);
}

echo "📦 Pedido: {$order->order_number}\n";
echo "👤 Cliente: {$order->customer->name}\n";
echo "💰 Total: R$ " . number_format($order->total, 2, ',', '.') . "\n";
echo "🔢 Items: " . $order->items->count() . "\n\n";

if ($order->bling_order_number) {
    echo "⚠️  Pedido já possui número Bling: {$order->bling_order_number}\n";
    $resposta = readline("Deseja reenviar mesmo assim? (s/n): ");
    if (strtolower($resposta) !== 's') {
        echo "Operação cancelada.\n";
        exit(0);
    }
}

$blingAdapter = new BlingV3Adapter();
$blingService = new BlingOrderService($blingAdapter);

try {
    $result = $blingService->createOrder($order);
    echo "✅ Pedido enviado com sucesso ao Bling!\n";
    echo "📋 Número Bling: {$result['bling_order_number']}\n";
} catch (\Exception $e) {
    echo "❌ Erro ao enviar pedido ao Bling:\n";
    echo $e->getMessage() . "\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
