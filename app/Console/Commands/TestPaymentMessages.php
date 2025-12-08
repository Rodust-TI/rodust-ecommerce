<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Payment\MercadoPagoErrorMapper;

class TestPaymentMessages extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'payment:test-messages 
                            {scenario? : Nome do cenário de teste (APRO, SECU, FUND, etc.)}
                            {--list : Listar todos os cenários disponíveis}
                            {--all : Testar todos os cenários}';

    /**
     * The console command description.
     */
    protected $description = 'Testa as mensagens de erro do MercadoPago para diferentes cenários';

    protected MercadoPagoErrorMapper $mapper;

    /**
     * Mapeamento de cartões de teste do MercadoPago
     */
    protected array $testScenarios = [
        'APRO' => [
            'name' => 'APRO - Pagamento Aprovado',
            'status_detail' => 'accredited',
            'status' => 'approved',
            'description' => 'Teste de pagamento aprovado com sucesso'
        ],
        'SECU' => [
            'name' => 'SECU - Código de Segurança Inválido',
            'status_detail' => 'cc_rejected_bad_filled_security_code',
            'status' => 'rejected',
            'description' => 'CVV incorreto ou inválido'
        ],
        'EXPI' => [
            'name' => 'EXPI - Data de Vencimento Inválida',
            'status_detail' => 'cc_rejected_bad_filled_date',
            'status' => 'rejected',
            'description' => 'Data de validade incorreta'
        ],
        'FORM' => [
            'name' => 'FORM - Erro no Formulário',
            'status_detail' => 'cc_rejected_bad_filled_other',
            'status' => 'rejected',
            'description' => 'Dados do cartão incorretos'
        ],
        'FUND' => [
            'name' => 'FUND - Saldo Insuficiente',
            'status_detail' => 'cc_rejected_insufficient_amount',
            'status' => 'rejected',
            'description' => 'Cartão sem saldo suficiente'
        ],
        'OTHE' => [
            'name' => 'OTHE - Erro Geral',
            'status_detail' => 'cc_rejected_other_reason',
            'status' => 'rejected',
            'description' => 'Banco recusou o pagamento'
        ],
        'CALL' => [
            'name' => 'CALL - Autorização Necessária',
            'status_detail' => 'cc_rejected_call_for_authorize',
            'status' => 'rejected',
            'description' => 'Requer autorização do banco'
        ],
        'INST' => [
            'name' => 'INST - Parcelamento Inválido',
            'status_detail' => 'cc_rejected_invalid_installments',
            'status' => 'rejected',
            'description' => 'Número de parcelas não aceito'
        ],
        'DUPL' => [
            'name' => 'DUPL - Pagamento Duplicado',
            'status_detail' => 'cc_rejected_duplicated_payment',
            'status' => 'rejected',
            'description' => 'Pagamento já realizado'
        ],
        'LOCK' => [
            'name' => 'LOCK - Cartão Desabilitado',
            'status_detail' => 'cc_rejected_card_disabled',
            'status' => 'rejected',
            'description' => 'Cartão bloqueado ou desabilitado'
        ],
        'BLAC' => [
            'name' => 'BLAC - Lista Negra',
            'status_detail' => 'cc_rejected_blacklist',
            'status' => 'rejected',
            'description' => 'Cartão em lista negra'
        ],
        'CONT' => [
            'name' => 'CONT - Pagamento Pendente',
            'status_detail' => 'pending_contingency',
            'status' => 'pending',
            'description' => 'Pagamento em análise'
        ],
    ];

    public function __construct(MercadoPagoErrorMapper $mapper)
    {
        parent::__construct();
        $this->mapper = $mapper;
    }

    public function handle(): int
    {
        $this->info('🧪 Teste de Mensagens de Pagamento - MercadoPago');
        $this->newLine();

        // Listar cenários
        if ($this->option('list')) {
            $this->listScenarios();
            return 0;
        }

        // Testar todos
        if ($this->option('all')) {
            $this->testAllScenarios();
            return 0;
        }

        // Testar cenário específico
        $scenario = strtoupper($this->argument('scenario') ?? '');
        
        if (empty($scenario)) {
            $this->error('❌ Por favor, especifique um cenário ou use --list para ver opções');
            $this->info('Exemplo: php artisan payment:test-messages SECU');
            return 1;
        }

        if (!isset($this->testScenarios[$scenario])) {
            $this->error("❌ Cenário '$scenario' não encontrado");
            $this->info('Use --list para ver os cenários disponíveis');
            return 1;
        }

        $this->testScenario($scenario);
        return 0;
    }

    protected function listScenarios(): void
    {
        $this->info('📋 Cenários de Teste Disponíveis:');
        $this->newLine();

        $headers = ['Código', 'Nome', 'Descrição', 'Status'];
        $rows = [];

        foreach ($this->testScenarios as $code => $scenario) {
            $icon = $scenario['status'] === 'approved' ? '✅' : 
                   ($scenario['status'] === 'pending' ? '⏳' : '❌');
            
            $rows[] = [
                $code,
                $scenario['name'],
                $scenario['description'],
                $icon . ' ' . $scenario['status']
            ];
        }

        $this->table($headers, $rows);
        
        $this->newLine();
        $this->info('💡 Para testar um cenário:');
        $this->comment('   php artisan payment:test-messages SECU');
        $this->newLine();
        $this->info('💡 Para testar todos:');
        $this->comment('   php artisan payment:test-messages --all');
    }

    protected function testAllScenarios(): void
    {
        $this->info('🚀 Testando todos os cenários...');
        $this->newLine();

        foreach ($this->testScenarios as $code => $scenario) {
            $this->testScenario($code, false);
            $this->newLine();
        }

        $this->info('✅ Todos os cenários testados!');
    }

    protected function testScenario(string $code, bool $detailed = true): void
    {
        $scenario = $this->testScenarios[$code];
        
        if ($detailed) {
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("📋 Cenário: {$scenario['name']}");
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->newLine();
            
            $this->comment("📝 Descrição: {$scenario['description']}");
            $this->comment("🔖 Status Detail: {$scenario['status_detail']}");
            $this->comment("📊 Status Geral: {$scenario['status']}");
            $this->newLine();
        } else {
            $this->line("Testing: <fg=cyan>{$code}</> - {$scenario['name']}");
        }

        // Obter mensagem mapeada
        $result = $this->mapper->mapStatusDetailToMessage(
            $scenario['status_detail'],
            $scenario['status']
        );

        // Exibir resultado
        $this->displayResult($result, $detailed);

        // Exibir ações recomendadas
        if ($detailed) {
            $this->displayActions($result, $scenario['status_detail']);
        }
    }

    protected function displayResult(array $result, bool $detailed = true): void
    {
        $icon = match($result['type']) {
            'success' => '✅',
            'error' => '❌',
            'warning' => '⚠️',
            'info' => 'ℹ️',
            default => '📋'
        };

        $color = match($result['type']) {
            'success' => 'green',
            'error' => 'red',
            'warning' => 'yellow',
            'info' => 'blue',
            default => 'white'
        };

        if ($detailed) {
            $this->newLine();
            $this->line("$icon <fg=$color;options=bold>{$result['title']}</>");
            $this->line("<fg=$color>{$result['message']}</>");
            $this->newLine();
            
            $this->info("📦 Dados Retornados:");
            $this->line("   Type: <fg=$color>{$result['type']}</>");
            $this->line("   Action: <fg=cyan>{$result['action']}</>");
            
            if (isset($result['fix'])) {
                $this->line("   Fix: <fg=yellow>{$result['fix']}</>");
            }
        } else {
            $this->line("  $icon <fg=$color>{$result['title']}</>");
        }
    }

    protected function displayActions(array $result, string $statusDetail): void
    {
        $canRetry = $this->mapper->canRetry($statusDetail);
        $shouldChange = $this->mapper->shouldChangePaymentMethod($statusDetail);

        $this->info("🎯 Ações Recomendadas:");
        
        if ($canRetry) {
            $this->line("   ✅ Permitir nova tentativa (can_retry: true)");
            $this->comment("      → Mostrar botão 'Tentar Novamente'");
        } else {
            $this->line("   ❌ Não permitir retry (can_retry: false)");
        }
        
        $this->newLine();
        
        if ($shouldChange) {
            $this->line("   ✅ Sugerir mudança de pagamento (should_change_payment: true)");
            $this->comment("      → Destacar PIX e Boleto como alternativas");
        } else {
            $this->line("   ❌ Não sugerir mudança (should_change_payment: false)");
        }

        $this->newLine();
        $this->info("📱 Resposta JSON da API:");
        $this->line("<fg=gray>" . json_encode([
            'success' => $result['type'] === 'success',
            'title' => $result['title'],
            'message' => $result['message'],
            'message_type' => $result['type'],
            'can_retry' => $canRetry,
            'should_change_payment' => $shouldChange
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</>");
    }
}
