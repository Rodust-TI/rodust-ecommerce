<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class ResetOrderStatus extends Command
{
    protected $signature = 'order:reset {order_id : ID do pedido a resetar}';
    protected $description = 'Resetar status de um pedido para pending (para testes)';

    public function handle()
    {
        $orderId = $this->argument('order_id');
        $order = Order::find($orderId);

        if (!$order) {
            $this->error("❌ Pedido #{$orderId} não encontrado.");
            return 1;
        }

        $order->update([
            'status' => 'pending',
            'payment_status' => 'pending',
            'paid_at' => null,
            'bling_order_number' => null,
            'bling_synced_at' => null,
            'last_bling_sync' => null
        ]);

        $this->info("✅ Pedido #{$orderId} resetado para pending");
        return 0;
    }
}
