<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Bling\BlingOrderService;
use Illuminate\Console\Command;

class TestBlingSyncStatus extends Command
{
    protected $signature = 'bling:test-sync-status {status_id : ID do status no Bling (ex: 9 para Atendido)} {--all : Atualizar todos os pedidos}';
    protected $description = 'Testar sincronização de status de pedidos com o Bling';

    public function __construct(
        private BlingOrderService $blingOrder
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $statusId = (int) $this->argument('status_id');
        $updateAll = $this->option('all');

        $this->info("🔄 Testando sincronização de status com Bling...");
        $this->info("   Status ID desejado: {$statusId}");
        $this->newLine();

        // Buscar pedidos que têm número do Bling
        $query = Order::whereNotNull('bling_order_number');
        
        if (!$updateAll) {
            // Apenas pedidos que não estão com o status desejado
            $this->info("📋 Buscando pedidos para atualizar...");
            $orders = $query->limit(5)->get();
        } else {
            $this->info("📋 Atualizando TODOS os pedidos...");
            $orders = $query->get();
        }

        if ($orders->isEmpty()) {
            $this->error("❌ Nenhum pedido encontrado com número do Bling.");
            return 1;
        }

        $this->info("✅ Pedidos encontrados: " . $orders->count());
        $this->newLine();

        // Mapear status ID do Bling para status interno
        $statusMap = [
            6 => 'pending',      // Em aberto
            15 => 'processing',  // Em andamento
            9 => 'processing',   // Atendido (tratado como processing)
            12 => 'cancelled',   // Cancelado
        ];

        $internalStatus = $statusMap[$statusId] ?? 'processing';

        $this->info("📝 Atualizando pedidos no Laravel para status: {$internalStatus}");
        $this->newLine();

        $successCount = 0;
        $errorCount = 0;

        foreach ($orders as $order) {
            $this->line("   🔄 Processando pedido #{$order->id} ({$order->order_number})...");
            $this->line("      Bling ID: {$order->bling_order_number}");

            try {
                // Atualizar status no Laravel
                $order->update([
                    'status' => $internalStatus,
                ]);

                $this->line("      ✅ Status atualizado no Laravel");

                // Atualizar no Bling via PUT com status ID específico
                $result = $this->blingOrder->updateOrder($order, $statusId);

                if ($result['success']) {
                    $this->line("      ✅ Status atualizado no Bling (ID {$statusId})");
                    $successCount++;
                } else {
                    $this->error("      ❌ Erro ao atualizar no Bling: " . ($result['error'] ?? 'Unknown error'));
                    $errorCount++;
                }

            } catch (\Exception $e) {
                $this->error("      ❌ Exceção: {$e->getMessage()}");
                $errorCount++;
            }

            $this->newLine();
        }

        $this->info("📊 Resumo:");
        $this->line("   ✅ Sucesso: {$successCount}");
        $this->line("   ❌ Erros: {$errorCount}");
        $this->newLine();
        $this->info("💡 Verifique no Bling se os pedidos foram atualizados corretamente!");

        return $errorCount > 0 ? 1 : 0;
    }
}
