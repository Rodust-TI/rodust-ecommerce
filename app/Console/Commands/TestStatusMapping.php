<?php

namespace App\Console\Commands;

use App\Services\Bling\BlingStatusService;
use Illuminate\Console\Command;

class TestStatusMapping extends Command
{
    protected $signature = 'bling:test-status-mapping {status_id : ID do status no Bling}';
    protected $description = 'Testar mapeamento de um status específico';

    public function handle(BlingStatusService $statusService): int
    {
        $statusId = (int) $this->argument('status_id');
        
        $this->info("Testando mapeamento do status ID: {$statusId}");
        $this->newLine();
        
        // Obter nome do status
        $statusName = $statusService->getStatusName($statusId);
        $this->line("Nome no Bling: {$statusName}");
        
        // Testar mapeamento
        $internalStatus = $statusService->mapBlingStatusToInternal(['id' => $statusId]);
        $this->line("Status Interno: {$internalStatus}");
        
        // Verificar detalhes do status
        $statusDetails = $statusService->getStatusDetails($statusId);
        if ($statusDetails) {
            $this->line("Cor: " . ($statusDetails['cor'] ?? 'N/A'));
            $this->line("Herdado: " . ($statusDetails['herdado'] ? 'Sim' : 'Não'));
        }
        
        $this->newLine();
        
        return Command::SUCCESS;
    }
}

