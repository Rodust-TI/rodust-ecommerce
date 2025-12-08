<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Bling\BlingOrderService;
use Illuminate\Console\Command;

class TestBlingUpdateOrder extends Command
{
    protected $signature = 'bling:test-update-order {order_id : ID do pedido no Laravel}';
    protected $description = 'Testar atualização de pedido no Bling (PUT)';

    public function __construct(
        private BlingOrderService $blingOrder
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $orderId = $this->argument('order_id');
        $order = Order::with(['customer', 'items.product'])->find($orderId);

        if (!$order) {
            $this->error("❌ Pedido #{$orderId} não encontrado.");
            return 1;
        }

        if (!$order->bling_order_number) {
            $this->error("❌ Pedido #{$orderId} não tem número do Bling.");
            $this->info("💡 Crie o pedido no Bling primeiro com: php artisan payment:approve-pix {$orderId}");
            return 1;
        }

        $this->info("🔄 Testando atualização do pedido #{$order->order_number} no Bling...");
        $this->info("   Bling Order ID: {$order->bling_order_number}");
        $this->info("   Status atual: {$order->status}");
        $this->info("   Paid at: " . ($order->paid_at ? $order->paid_at->format('Y-m-d H:i:s') : 'null'));

        // Garantir que o pedido está com status processing
        if ($order->status !== 'processing') {
            $this->warn("⚠️  Pedido não está com status 'processing'. Atualizando...");
            $order->update([
                'status' => 'processing',
                'paid_at' => $order->paid_at ?? now(),
            ]);
            $this->info("✅ Status atualizado para 'processing'");
        }

        try {
            $result = $this->blingOrder->updateOrder($order);

            if ($result['success']) {
                $this->info("✅ Pedido atualizado no Bling com sucesso!");
                $this->info("   Verifique no Bling se o status foi alterado para 'Em andamento' (ID 1)");
            } else {
                $this->error("❌ Erro ao atualizar pedido: {$result['error']}");
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("❌ Exceção: {$e->getMessage()}");
            $this->error("   Trace: {$e->getTraceAsString()}");
            return 1;
        }

        return 0;
    }
}
