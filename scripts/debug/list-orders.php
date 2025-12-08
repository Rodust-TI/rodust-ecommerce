<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;

$orders = Order::with('customer')->orderBy('id', 'desc')->take(10)->get();

echo "📋 ÚLTIMOS 10 PEDIDOS\n";
echo str_repeat('=', 120) . "\n";
echo sprintf(
    "%-4s %-20s %-20s %-30s %-15s %-10s %s\n",
    "ID",
    "Order Number",
    "Bling Number",
    "Cliente",
    "Total",
    "Status",
    "Bling Sync"
);
echo str_repeat('-', 120) . "\n";

foreach ($orders as $order) {
    echo sprintf(
        "%-4s %-20s %-20s %-30s R$ %9.2f %-10s %s\n",
        $order->id,
        $order->order_number,
        $order->bling_order_number ?? 'NÃO ENVIADO',
        substr($order->customer->name ?? 'N/A', 0, 28),
        $order->total,
        $order->status,
        $order->bling_synced_at ? '✅' : '❌'
    );
}

echo str_repeat('=', 120) . "\n";
