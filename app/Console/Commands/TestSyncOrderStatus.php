<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Bling\BlingOrderService;
use Illuminate\Console\Command;

class TestSyncOrderStatus extends Command
{
    protected $signature = 'bling:test-sync-order {order_number : Número do pedido (ex: ROD-20251205-2188)}';
    protected $description = 'Testar sincronização de status de um pedido específico';

    public function handle(BlingOrderService $orderService): int
    {
        $orderNumber = $this->argument('order_number');
        
        $this->info("Testando sincronização do pedido: {$orderNumber}");
        $this->newLine();
        
        $order = Order::where('order_number', $orderNumber)->first();
        
        if (!$order) {
            $this->error("Pedido não encontrado: {$orderNumber}");
            return Command::FAILURE;
        }
        
        $this->line("Pedido encontrado:");
        $this->line("  ID: {$order->id}");
        $this->line("  Status atual: {$order->status}");
        $this->line("  Bling Order Number: " . ($order->bling_order_number ?? 'N/A'));
        $this->newLine();
        
        if (!$order->bling_order_number) {
            $this->error("Pedido não tem bling_order_number!");
            return Command::FAILURE;
        }
        
        $this->info("Sincronizando status do Bling...");
        
        $success = $orderService->syncOrderStatus($order);
        
        if ($success) {
            $order->refresh();
            $this->info("✓ Sincronização concluída!");
            $this->line("  Novo status: {$order->status}");
        } else {
            $this->error("✗ Falha na sincronização");
            $this->line("  Verifique os logs: docker exec docker-laravel.test-1 tail -f storage/logs/laravel.log");
        }
        
        $this->newLine();
        
        return $success ? Command::SUCCESS : Command::FAILURE;
    }
}

