<?php

namespace App\Console\Commands;

use App\Services\ERP\BlingV3Adapter;
use App\Services\Bling\BlingStatusService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BlingListOrderStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bling:list-order-statuses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lista todas as situações (status) de pedidos de venda do Bling';

    /**
     * Execute the console command.
     */
    public function handle(BlingStatusService $statusService): int
    {
        $this->info('🔍 Buscando situações de pedidos no Bling...');
        
        try {
            // Obter ID do módulo de vendas
            $moduleId = $statusService->getSalesModuleId();
            
            if (!$moduleId) {
                $this->error('❌ Não foi possível obter o ID do módulo de Vendas');
                return Command::FAILURE;
            }
            
            $this->info("📦 Módulo de Vendas ID: {$moduleId}");
            $this->newLine();
            
            // Obter situações
            $statuses = $statusService->getSalesStatuses();
            
            if (empty($statuses)) {
                $this->warn('⚠️  Nenhuma situação encontrada. Usando mapeamento padrão.');
                $statuses = $statusService->getDefaultStatusMapping();
            }
            
            $this->info('✅ ' . count($statuses) . ' situações encontradas:');
            $this->newLine();
            
            // Preparar dados para tabela
            $tableData = [];
            foreach ($statuses as $id => $status) {
                $tableData[] = [
                    'ID' => $id,
                    'Nome' => $status['nome'] ?? 'N/A',
                    'Cor' => $status['cor'] ?? 'N/A',
                    'Herdado' => isset($status['herdado']) && $status['herdado'] ? 'Sim' : 'Não',
                ];
            }
            
            $this->table(
                ['ID', 'Nome', 'Cor', 'Herdado'],
                $tableData
            );
            
            $this->newLine();
            $this->info('💡 Situações recomendadas para configurar no .env:');
            $this->newLine();
            
            // Tentar encontrar automaticamente baseado nos nomes
            $openStatus = null;
            $processingStatus = null;
            $shippedStatus = null;
            $completedStatus = null;
            $cancelledStatus = null;
            
            foreach ($statuses as $id => $status) {
                $nome = strtolower($status['nome'] ?? '');
                
                if ((str_contains($nome, 'aberto') || str_contains($nome, 'pendente')) && !$openStatus) {
                    $openStatus = $id;
                }
                if ((str_contains($nome, 'andamento') || str_contains($nome, 'processando') || str_contains($nome, 'prepara')) && !$processingStatus) {
                    $processingStatus = $id;
                }
                if ((str_contains($nome, 'enviado') || str_contains($nome, 'transporte') || str_contains($nome, 'despachado')) && !$shippedStatus) {
                    $shippedStatus = $id;
                }
                if ((str_contains($nome, 'entregue') || str_contains($nome, 'concluído') || str_contains($nome, 'finalizado')) && !$completedStatus) {
                    $completedStatus = $id;
                }
                if (str_contains($nome, 'cancelado') && !$cancelledStatus) {
                    $cancelledStatus = $id;
                }
            }
            
            $this->line("# Status de pedidos no Bling");
            if ($openStatus) {
                $this->line("BLING_ORDER_STATUS_OPEN={$openStatus}");
            } else {
                $this->comment("# BLING_ORDER_STATUS_OPEN=??? (Em aberto - não encontrado)");
            }
            
            if ($processingStatus) {
                $this->line("BLING_ORDER_STATUS_PROCESSING={$processingStatus}");
            } else {
                $this->comment("# BLING_ORDER_STATUS_PROCESSING=??? (Em andamento - não encontrado)");
            }
            
            if ($shippedStatus) {
                $this->line("BLING_ORDER_STATUS_SHIPPED={$shippedStatus}");
            } else {
                $this->comment("# BLING_ORDER_STATUS_SHIPPED=??? (Enviado - não encontrado)");
            }
            
            if ($completedStatus) {
                $this->line("BLING_ORDER_STATUS_COMPLETED={$completedStatus}");
            } else {
                $this->comment("# BLING_ORDER_STATUS_COMPLETED=??? (Concluído - não encontrado)");
            }
            
            if ($cancelledStatus) {
                $this->line("BLING_ORDER_STATUS_CANCELLED={$cancelledStatus}");
            } else {
                $this->comment("# BLING_ORDER_STATUS_CANCELLED=??? (Cancelado - não encontrado)");
            }
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('❌ Erro ao buscar situações: ' . $e->getMessage());
            Log::error('Erro no comando bling:list-order-statuses', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }
}
