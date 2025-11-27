<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Contracts\ERPInterface;

class BlingShowProduct extends Command
{
    protected $signature = 'bling:show-product {id : Bling Product ID}';
    
    protected $description = 'Show complete product details from Bling API including all fields';

    public function __construct(protected ERPInterface $erp)
    {
        parent::__construct();
    }

    public function handle()
    {
        $blingId = $this->argument('id');

        $this->info("📦 Buscando produto {$blingId} do Bling...");

        try {
            // Fazer requisição direta para ver TODOS os campos retornados
            $result = $this->erp->getProduct($blingId);

            if (!$result) {
                $this->error('❌ Produto não encontrado no Bling.');
                return 1;
            }

            $this->newLine();
            $this->info('✅ Produto encontrado! Dados completos:');
            $this->newLine();

            // Exibir JSON completo para análise
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            $this->newLine();
            $this->info('💡 Analise os campos acima para identificar:');
            $this->line('   - Dimensões físicas (largura, altura, comprimento, peso)');
            $this->line('   - Marca/fabricante');
            $this->line('   - Preço promocional');
            $this->line('   - Frete grátis');
            $this->line('   - Múltiplas imagens');
            $this->line('   - Categoria do Bling');

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Erro ao buscar produto: ' . $e->getMessage());
            $this->newLine();
            $this->line('Stack trace:');
            $this->line($e->getTraceAsString());
            return 1;
        }
    }
}
