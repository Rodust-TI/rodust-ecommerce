<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;

$orderId = 25;

$order = Order::find($orderId);

if (!$order) {
    echo "Pedido não encontrado\n";
    exit(1);
}

echo "Pedido #{$order->id}\n";
echo "Order Number: {$order->order_number}\n";
echo "Bling Order Number: " . ($order->bling_order_number ?? 'NULL') . "\n";
echo "Status: {$order->status}\n";
echo "Payment Status: {$order->payment_status}\n";
echo "Total: R$ " . number_format($order->total, 2, ',', '.') . "\n";
echo "Created: {$order->created_at}\n";
