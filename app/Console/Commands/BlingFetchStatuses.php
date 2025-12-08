<?php

namespace App\Console\Commands;

use App\Services\Bling\BlingStatusService;
use Illuminate\Console\Command;

class BlingFetchStatuses extends Command
{
    protected $signature = 'bling:fetch-statuses {--clear-cache : Limpar cache antes de buscar}';
    protected $description = 'Buscar e exibir os status do módulo de Vendas no Bling';

    public function handle(BlingStatusService $statusService): int
    {
        $this->info("═══════════════════════════════════════════════");
        $this->info("    BUSCAR STATUS DO BLING - MÓDULO VENDAS");
        $this->info("═══════════════════════════════════════════════\n");

        // Limpar cache se solicitado
        if ($this->option('clear-cache')) {
            $statusService->clearCache();
            $this->warn("✓ Cache limpo\n");
        }

        // Passo 1: Buscar ID do módulo de Vendas
        $this->info("📋 Passo 1: Buscando ID do módulo de Vendas...");
        
        try {
            $moduleId = $statusService->getSalesModuleId();
        } catch (\Exception $e) {
            $this->error("✗ Erro ao buscar módulos: " . $e->getMessage());
            return Command::FAILURE;
        }
        
        if (!$moduleId) {
            $this->error("✗ Não foi possível encontrar o módulo de Vendas");
            $this->line("  Verifique:");
            $this->line("  - Token de acesso do Bling está válido");
            $this->line("  - Permissões da aplicação no Bling");
            $this->newLine();
            $this->warn("💡 DEMO: Como funcionaria com token válido:\n");
            $this->showDemoOutput();
            return Command::FAILURE;
        }

        $this->line("  ✓ Módulo encontrado: ID = <fg=green>{$moduleId}</>\n");

        // Passo 2: Buscar lista de status
        $this->info("📊 Passo 2: Buscando situações do módulo de Vendas...");
        
        $statuses = $statusService->getSalesStatuses();
        
        if (empty($statuses)) {
            $this->error("✗ Nenhum status encontrado");
            return Command::FAILURE;
        }

        $this->line("  ✓ " . count($statuses) . " situações encontradas\n");

        // Exibir tabela de status
        $this->info("═══════════════════════════════════════════════");
        $this->info("           SITUAÇÕES CADASTRADAS NO BLING");
        $this->info("═══════════════════════════════════════════════\n");

        $rows = [];
        foreach ($statuses as $id => $details) {
            $internalStatus = $statusService->mapBlingStatusToInternal(['id' => $id]);
            
            $rows[] = [
                $id,
                $details['nome'],
                $internalStatus,
                $details['herdado'] ? 'Sim' : 'Não',
                $details['cor'] ?? 'N/A',
            ];
        }

        $this->table(
            ['ID', 'Nome no Bling', 'Status Interno', 'Herdado', 'Cor'],
            $rows
        );

        $this->newLine();
        $this->info("═══════════════════════════════════════════════");
        $this->info("Mapeamento para status internos:");
        $this->info("═══════════════════════════════════════════════");
        $this->line("  pending     → Aguardando/Em aberto");
        $this->line("  processing  → Em andamento/Processando");
        $this->line("  invoiced    → Faturado");
        $this->line("  shipped     → Enviado/Em transporte");
        $this->line("  delivered   → Entregue");
        $this->line("  cancelled   → Cancelado");
        $this->newLine();

        $this->info("✓ Status carregados e armazenados em cache por 24 horas");
        $this->line("  Use --clear-cache para forçar atualização\n");

        return Command::SUCCESS;
    }

    /**
     * Mostrar saída de demonstração
     */
    protected function showDemoOutput(): void
    {
        $this->info("═══════════════════════════════════════════════");
        $this->info("  EXEMPLO DE SAÍDA COM TOKEN VÁLIDO");
        $this->info("═══════════════════════════════════════════════\n");

        $this->line("  ✓ Módulo encontrado: ID = <fg=green>123456</>\n");
        $this->line("  ✓ 8 situações encontradas\n");

        $this->info("═══════════════════════════════════════════════");
        $this->info("           SITUAÇÕES CADASTRADAS NO BLING");
        $this->info("═══════════════════════════════════════════════\n");

        $demoStatuses = [
            ['ID' => '101', 'Nome no Bling' => 'Em aberto', 'Status Interno' => 'pending', 'Herdado' => 'Não', 'Cor' => '#FFD700'],
            ['ID' => '102', 'Nome no Bling' => 'Em andamento', 'Status Interno' => 'processing', 'Herdado' => 'Não', 'Cor' => '#1E90FF'],
            ['ID' => '103', 'Nome no Bling' => 'Faturado', 'Status Interno' => 'invoiced', 'Herdado' => 'Não', 'Cor' => '#9370DB'],
            ['ID' => '104', 'Nome no Bling' => 'Enviado', 'Status Interno' => 'shipped', 'Herdado' => 'Não', 'Cor' => '#4B0082'],
            ['ID' => '105', 'Nome no Bling' => 'Entregue', 'Status Interno' => 'delivered', 'Herdado' => 'Não', 'Cor' => '#32CD32'],
            ['ID' => '106', 'Nome no Bling' => 'Cancelado', 'Status Interno' => 'cancelled', 'Herdado' => 'Não', 'Cor' => '#DC143C'],
        ];

        $this->table(
            ['ID', 'Nome no Bling', 'Status Interno', 'Herdado', 'Cor'],
            $demoStatuses
        );

        $this->newLine();
        $this->info("═══════════════════════════════════════════════");
        $this->info("Mapeamento para status internos:");
        $this->info("═══════════════════════════════════════════════");
        $this->line("  pending     → Aguardando/Em aberto");
        $this->line("  processing  → Em andamento/Processando");
        $this->line("  invoiced    → Faturado");
        $this->line("  shipped     → Enviado/Em transporte");
        $this->line("  delivered   → Entregue");
        $this->line("  cancelled   → Cancelado");
        $this->newLine();

        $this->info("✓ Status carregados e armazenados em cache por 24 horas\n");
    }
}
