<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class SetBlingOrderNumber extends Command
{
    protected $signature = 'bling:set-order-number {order_id : ID do pedido no Laravel} {bling_id : ID do pedido no Bling}';
    protected $description = 'Definir manualmente o número do pedido no Bling';

    public function handle()
    {
        $orderId = $this->argument('order_id');
        $blingId = $this->argument('bling_id');
        
        $order = Order::find($orderId);

        if (!$order) {
            $this->error("❌ Pedido #{$orderId} não encontrado.");
            return 1;
        }

        $this->info("🔄 Atualizando pedido #{$order->order_number}...");
        $this->info("   Bling ID: {$blingId}");

        $order->update([
            'bling_order_number' => $blingId,
            'bling_synced_at' => now(),
            'last_bling_sync' => now(),
        ]);

        $this->info("✅ Número do Bling salvo!");
        $this->info("   Agora você pode testar o PUT com: php artisan bling:test-update-order {$orderId}");

        return 0;
    }
}
