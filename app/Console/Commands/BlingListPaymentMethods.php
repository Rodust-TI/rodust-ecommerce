<?php

namespace App\Console\Commands;

use App\Services\ERP\BlingV3Adapter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BlingListPaymentMethods extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bling:list-payment-methods';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lista todas as formas de pagamento cadastradas no Bling';

    /**
     * Execute the console command.
     */
    public function handle(BlingV3Adapter $bling): int
    {
        $this->info('🔍 Buscando formas de pagamento no Bling...');
        
        try {
            $paymentMethods = $bling->getPaymentMethods();
            
            if (empty($paymentMethods)) {
                $this->warn('⚠️  Nenhuma forma de pagamento encontrada');
                return Command::FAILURE;
            }
            
            $this->info('✅ ' . count($paymentMethods) . ' formas de pagamento encontradas:');
            $this->newLine();
            
            // Preparar dados para tabela
            $tableData = [];
            foreach ($paymentMethods as $method) {
                $tableData[] = [
                    'ID' => $method['id'],
                    'Descrição' => $method['descricao'] ?? 'N/A',
                    'Tipo' => $method['tipoPagamento'] ?? 'N/A',
                    'Situação' => $method['situacao'] ?? 'A',
                    'Padrão' => isset($method['padrao']) && $method['padrao'] ? 'Sim' : 'Não',
                    'Fixa' => isset($method['fixa']) && $method['fixa'] ? 'Sim' : 'Não',
                ];
            }
            
            $this->table(
                ['ID', 'Descrição', 'Tipo', 'Situação', 'Padrão', 'Fixa'],
                $tableData
            );
            
            $this->newLine();
            $this->info('💡 Sugestão de mapeamento para config/services.php:');
            $this->newLine();
            
            // Tentar encontrar automaticamente baseado nos nomes
            $suggestions = [
                'pix' => null,
                'credit_card' => null,
                'debit_card' => null,
                'boleto' => null,
            ];
            
            foreach ($paymentMethods as $method) {
                $desc = strtolower($method['descricao'] ?? '');
                
                if (str_contains($desc, 'pix') && !$suggestions['pix']) {
                    $suggestions['pix'] = $method['id'];
                }
                if ((str_contains($desc, 'cartão') || str_contains($desc, 'cartao') || str_contains($desc, 'crédito') || str_contains($desc, 'credito')) && !$suggestions['credit_card']) {
                    $suggestions['credit_card'] = $method['id'];
                }
                if (str_contains($desc, 'débito') || str_contains($desc, 'debito') && !$suggestions['debit_card']) {
                    $suggestions['debit_card'] = $method['id'];
                }
                if (str_contains($desc, 'boleto') && !$suggestions['boleto']) {
                    $suggestions['boleto'] = $method['id'];
                }
            }
            
            $this->line("'bling' => [");
            $this->line("    // ... configurações existentes ...");
            $this->line("    'payment_methods' => [");
            foreach ($suggestions as $key => $id) {
                if ($id) {
                    $this->line("        '{$key}' => {$id},");
                } else {
                    $this->line("        '{$key}' => null, // Não encontrado automaticamente");
                }
            }
            $this->line("    ],");
            $this->line("],");
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('❌ Erro ao buscar formas de pagamento: ' . $e->getMessage());
            Log::error('Erro no comando bling:list-payment-methods', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }
}
