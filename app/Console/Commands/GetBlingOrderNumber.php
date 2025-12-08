<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Bling\BlingOrderService;
use Illuminate\Console\Command;

class GetBlingOrderNumber extends Command
{
    protected $signature = 'bling:get-order-number {order_id : ID do pedido no Laravel}';
    protected $description = 'Buscar número do pedido no Bling e atualizar no Laravel';

    public function __construct(
        private BlingOrderService $blingOrder
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $orderId = $this->argument('order_id');
        $order = Order::find($orderId);

        if (!$order) {
            $this->error("❌ Pedido #{$orderId} não encontrado.");
            return 1;
        }

        $this->info("🔍 Buscando pedido no Bling pelo número: {$order->order_number}");

        $this->info("💡 Para encontrar o pedido no Bling:");
        $this->info("   1. Acesse o Bling e encontre o pedido com número: {$order->order_number}");
        $this->info("   2. Copie o ID do pedido (número grande, ex: 17799649302)");
        $this->info("   3. Execute: php artisan bling:set-order-number {$orderId} {bling_id}");
        $this->newLine();
        
        // Tentar buscar pelo número usando a API
        $bling = app(\App\Services\ERP\BlingV3Adapter::class);
        
        try {
            // Buscar pedidos recentes e procurar pelo número
            $this->info("🔍 Tentando buscar pedidos no Bling...");
            $result = $bling->getOrders([
                'dataInicial' => now()->subDays(7)->format('Y-m-d'),
                'dataFinal' => now()->format('Y-m-d'),
            ]);

            $this->info("📋 Pedidos encontrados no Bling: " . count($result));
            
            // Procurar pelo número do pedido
            $found = null;
            foreach ($result as $blingOrder) {
                if (isset($blingOrder['numero']) && $blingOrder['numero'] === $order->order_number) {
                    $found = $blingOrder;
                    break;
                }
            }

            if ($found) {
                $blingId = (string) ($found['id'] ?? '');
                $this->info("✅ Pedido encontrado no Bling!");
                $this->info("   Bling ID: {$blingId}");
                $this->info("   Status no Bling: " . ($found['situacao']['nome'] ?? 'N/A'));

                // Atualizar no Laravel
                $order->update([
                    'bling_order_number' => $blingId,
                    'bling_synced_at' => now(),
                    'last_bling_sync' => now(),
                ]);

                $this->info("✅ Número do Bling salvo no Laravel!");
                $this->info("   Agora você pode testar o PUT com: php artisan bling:test-update-order {$orderId}");
            } else {
                $this->warn("⚠️  Pedido não encontrado no Bling pelos últimos 7 dias.");
                $this->info("   Use o comando: php artisan bling:set-order-number {$orderId} {bling_id}");
            }
        } catch (\Exception $e) {
            $this->error("❌ Erro: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }
}
