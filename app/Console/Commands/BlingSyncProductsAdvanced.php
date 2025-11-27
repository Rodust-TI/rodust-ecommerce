<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use App\Contracts\ERPInterface;
use Illuminate\Support\Facades\DB;

class BlingSyncProductsAdvanced extends Command
{
    protected $signature = 'bling:sync-products-advanced 
                            {--full : Force full sync for all products}
                            {--limit=50 : Maximum products to sync per run}';
    
    protected $description = 'Sync products from Bling with intelligent strategy (list + individual details)';

    public function __construct(protected ERPInterface $erp)
    {
        parent::__construct();
    }

    public function handle()
    {
        $fullSync = $this->option('full');
        $limit = (int) $this->option('limit');

        $this->info('🔄 Iniciando sincronização avançada de produtos do Bling...');
        $this->newLine();

        try {
            // Passo 1: Buscar lista de produtos do Bling
            $this->info('📋 Buscando lista de produtos...');
            $blingProducts = $this->erp->getProducts(['limite' => $limit]);

            if (empty($blingProducts)) {
                $this->warn('⚠️  Nenhum produto encontrado no Bling.');
                return 0;
            }

            $productCount = count($blingProducts);
            $this->info("✅ {$productCount} produtos encontrados na lista");
            $this->newLine();

            // Passo 2: Para cada produto, verificar se precisa sincronizar detalhes
            $bar = $this->output->createProgressBar($productCount);
            $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');

            $synced = 0;
            $skipped = 0;
            $errors = 0;

            foreach ($blingProducts as $basicProduct) {
                $blingId = $basicProduct['bling_id'] ?? $basicProduct['erp_id'] ?? null;
                
                if (!$blingId) {
                    $bar->setMessage("❌ Produto sem ID");
                    $bar->advance();
                    $errors++;
                    continue;
                }

                $bar->setMessage("Processando: {$basicProduct['name']}");

                try {
                    // Verificar se produto existe no Laravel
                    $product = Product::where('bling_id', $blingId)->first();

                    // Decidir se precisa sincronizar
                    $needsSync = $fullSync || 
                                 !$product || 
                                 !$product->last_sync_at || 
                                 !$product->hasShippingDimensions();

                    if (!$needsSync) {
                        $bar->setMessage("⏭️  Já sincronizado: {$basicProduct['name']}");
                        $bar->advance();
                        $skipped++;
                        continue;
                    }

                    // Buscar detalhes completos do produto
                    $bar->setMessage("🔍 Buscando detalhes: {$basicProduct['name']}");
                    $fullProduct = $this->erp->getProduct($blingId);

                    if (!$fullProduct) {
                        $bar->setMessage("❌ Erro ao buscar: {$basicProduct['name']}");
                        $bar->advance();
                        $errors++;
                        continue;
                    }

                    // Criar ou atualizar produto no Laravel
                    if ($product) {
                        $product->update($fullProduct);
                        $bar->setMessage("✅ Atualizado: {$basicProduct['name']}");
                    } else {
                        Product::create($fullProduct);
                        $bar->setMessage("➕ Criado: {$basicProduct['name']}");
                    }

                    $synced++;

                } catch (\Exception $e) {
                    $bar->setMessage("❌ Erro: {$basicProduct['name']} - {$e->getMessage()}");
                    $errors++;
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            // Resumo
            $this->info('📊 Resumo da Sincronização:');
            $this->table(
                ['Status', 'Quantidade'],
                [
                    ['✅ Sincronizados', $synced],
                    ['⏭️  Ignorados (já atualizados)', $skipped],
                    ['❌ Erros', $errors],
                    ['📋 Total', $productCount],
                ]
            );

            $this->newLine();
            
            if ($errors > 0) {
                $this->warn("⚠️  {$errors} produto(s) tiveram erros. Verifique os logs.");
            }

            if ($synced > 0) {
                $this->info('💡 Próximos passos:');
                $this->line('   1. Sincronizar com WordPress: php artisan wordpress:sync-products');
                $this->line('   2. Verificar produtos sem dimensões: SELECT * FROM products WHERE width IS NULL');
            }

            return $errors > 0 ? 1 : 0;

        } catch (\Exception $e) {
            $this->error('❌ Erro fatal na sincronização: ' . $e->getMessage());
            $this->newLine();
            $this->line('Stack trace:');
            $this->line($e->getTraceAsString());
            return 1;
        }
    }
}
