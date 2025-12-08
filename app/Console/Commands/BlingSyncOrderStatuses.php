<?php

namespace App\Console\Commands;

use App\Services\Bling\BlingOrderService;
use Illuminate\Console\Command;

class BlingSyncOrderStatuses extends Command
{
    protected $signature = 'bling:sync-orders {--limit= : Número máximo de pedidos (deixe vazio para todos)} {--all : Sincronizar TODOS os pedidos}';
    protected $description = 'Sincronizar status dos pedidos com o Bling';

    public function handle(BlingOrderService $orderService): int
    {
        $this->info("═══════════════════════════════════════════════");
        $this->info("      SINCRONIZAR STATUS DE PEDIDOS - BLING");
        $this->info("═══════════════════════════════════════════════\n");

        // Determinar limite
        $limit = null;
        if ($this->option('all')) {
            $limit = null;
            $this->info("Sincronizando TODOS os pedidos pendentes...\n");
        } elseif ($this->option('limit')) {
            $limit = (int) $this->option('limit');
            $this->info("Sincronizando até {$limit} pedidos pendentes...\n");
        } else {
            $limit = 50; // Padrão
            $this->info("Sincronizando até {$limit} pedidos pendentes (padrão)...\n");
        }
        
        $result = $orderService->syncAllPendingOrders($limit);

        $this->newLine(2);

        $this->info("═══════════════════════════════════════════════");
        $this->info("              RESULTADO DA SINCRONIZAÇÃO");
        $this->info("═══════════════════════════════════════════════\n");

        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Total de pedidos pendentes', "<fg=cyan>{$result['total']}</>"],
                ['Sincronizados com sucesso', "<fg=green>{$result['synced']}</>"],
                ['Falhas', $result['failed'] > 0 ? "<fg=red>{$result['failed']}</>" : "<fg=gray>{$result['failed']}</>"],
            ]
        );

        if ($result['synced'] > 0) {
            $this->info("\n✓ Sincronização concluída com sucesso!");
        } else {
            $this->warn("\n⚠ Nenhum pedido foi sincronizado. Verifique os logs.");
        }

        $this->newLine();
        $this->line("Para ver detalhes, consulte: <fg=cyan>storage/logs/laravel.log</>\n");

        return Command::SUCCESS;
    }
}
