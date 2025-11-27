<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;

$orderId = 25;
$blingOrderNumber = '24442492001';

$order = Order::find($orderId);

if (!$order) {
    echo "❌ Pedido não encontrado\n";
    exit(1);
}

echo "📝 Atualizando pedido #{$order->id}...\n";
echo "Order Number: {$order->order_number}\n";
echo "Novo Bling Order Number: {$blingOrderNumber}\n\n";

$order->update([
    'bling_order_number' => $blingOrderNumber,
    'bling_synced_at' => now()
]);

echo "✅ Pedido atualizado com sucesso!\n\n";
echo "Verificando...\n";

$order->refresh();

echo "Bling Order Number: " . ($order->bling_order_number ?? 'NULL') . "\n";
echo "Bling Synced At: " . ($order->bling_synced_at ?? 'NULL') . "\n";
