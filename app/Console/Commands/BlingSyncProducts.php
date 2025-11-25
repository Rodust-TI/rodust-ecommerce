<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BlingSyncProducts extends Command
{
    protected $signature = 'bling:sync-products {--limit=100 : Maximum products to sync} {--force : Force sync all products}';
    
    protected $description = 'Sincronizar produtos do Bling para o Laravel e WordPress';

    protected $stats = [
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0,
    ];

    public function handle()
    {
        $this->info('🔄 Iniciando sincronização de produtos...');
        $this->newLine();

        // Verificar autenticação
        if (!Cache::has('bling_access_token')) {
            $this->error('❌ Não autenticado no Bling. Acesse http://localhost:8000/bling para autorizar.');
            return 1;
        }

        $limit = (int) $this->option('limit');
        $force = $this->option('force');

        try {
            // Buscar produtos do Bling
            $this->info('📥 Buscando produtos do Bling...');
            $blingProducts = $this->fetchBlingProducts($limit);

            if (empty($blingProducts)) {
                $this->warn('⚠️  Nenhum produto encontrado no Bling.');
                return 0;
            }

            $totalProducts = count($blingProducts);
            $this->info("✅ {$totalProducts} produtos encontrados no Bling");
            $this->newLine();

            // Criar barra de progresso
            $bar = $this->output->createProgressBar($totalProducts);
            $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
            $bar->setMessage('Processando...');

            // Processar cada produto
            foreach ($blingProducts as $blingProduct) {
                $bar->setMessage("Processando: {$blingProduct['nome']}");
                
                try {
                    $this->syncProduct($blingProduct, $force);
                } catch (\Exception $e) {
                    $this->stats['errors']++;
                    $this->newLine();
                    $this->error("Erro ao processar produto {$blingProduct['id']}: {$e->getMessage()}");
                }
                
                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            // Estatísticas
            $this->displayStats();

            // Sincronizar com WordPress
            if ($this->stats['created'] > 0 || $this->stats['updated'] > 0) {
                $this->newLine();
                $this->syncWithWordPress();
            } else {
                $this->newLine();
                $this->warn('⚠️  Nenhum produto foi criado ou atualizado. Pulando sincronização com WordPress.');
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Erro: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
    }

    protected function fetchBlingProducts(int $limit): array
    {
        $allProducts = [];
        $page = 1;
        $perPage = 100; // Limite máximo da API Bling
        $baseUrl = config('services.bling.base_url', 'https://api.bling.com.br/Api/v3');

        do {
            $response = Http::withToken(Cache::get('bling_access_token'))
                ->get($baseUrl . '/produtos', [
                    'pagina' => $page,
                    'limite' => min($perPage, $limit - count($allProducts))
                ]);

            if ($response->failed()) {
                throw new \Exception("Erro ao buscar produtos: {$response->status()} - {$response->body()}");
            }

            $data = $response->json();
            $products = $data['data'] ?? [];

            if (empty($products)) {
                break;
            }

            $allProducts = array_merge($allProducts, $products);
            $page++;

        } while (count($allProducts) < $limit && !empty($products));

        return $allProducts;
    }

    protected function syncProduct(array $blingProduct, bool $force): void
    {
        $blingId = (string) $blingProduct['id'];
        
        // Buscar produto existente
        $product = Product::where('bling_id', $blingId)->first();

        // Preparar dados
        $productData = [
            'sku' => $blingProduct['codigo'] ?? "BLING-{$blingId}",
            'name' => $blingProduct['nome'],
            'description' => $blingProduct['descricaoCurta'] ?? $blingProduct['descricao'] ?? null,
            'price' => floatval($blingProduct['preco'] ?? 0),
            'cost' => isset($blingProduct['precoCusto']) ? floatval($blingProduct['precoCusto']) : null,
            'stock' => $this->getStock($blingProduct),
            'image' => $blingProduct['imagemURL'] ?? null,
            'active' => ($blingProduct['situacao'] ?? 'A') === 'A',
            'bling_id' => $blingId,
            'bling_synced_at' => now(),
        ];

        if ($product) {
            // Atualizar apenas se forçado ou se dados mudaram
            if ($force || $this->hasChanges($product, $productData)) {
                $product->update($productData);
                $this->stats['updated']++;
            } else {
                $this->stats['skipped']++;
            }
        } else {
            // Criar novo produto
            Product::create($productData);
            $this->stats['created']++;
        }
    }

    protected function getStock(array $blingProduct): int
    {
        // API Bling v3 retorna estoque como objeto, não array
        if (isset($blingProduct['estoque']['saldoVirtualTotal'])) {
            return (int) $blingProduct['estoque']['saldoVirtualTotal'];
        }

        if (isset($blingProduct['estoque']['saldo'])) {
            return (int) $blingProduct['estoque']['saldo'];
        }

        return 0;
    }

    protected function hasChanges(Product $product, array $newData): bool
    {
        return $product->name !== $newData['name']
            || $product->price != $newData['price']
            || $product->stock != $newData['stock']
            || $product->active !== $newData['active'];
    }

    protected function displayStats(): void
    {
        $this->info('📊 Estatísticas da Sincronização:');
        $this->newLine();
        
        $this->line("  ✅ Criados:  <fg=green>{$this->stats['created']}</>");
        $this->line("  🔄 Atualizados: <fg=yellow>{$this->stats['updated']}</>");
        $this->line("  ⏭️  Ignorados: <fg=gray>{$this->stats['skipped']}</>");
        
        if ($this->stats['errors'] > 0) {
            $this->line("  ❌ Erros: <fg=red>{$this->stats['errors']}</>");
        }
        
        $this->newLine();
        $total = $this->stats['created'] + $this->stats['updated'];
        $this->info("🎉 Total processado: {$total} produtos");
    }

    protected function syncWithWordPress(): void
    {
        $this->info('🔄 Sincronizando com WordPress...');
        
        try {
            // Buscar produtos do Laravel
            $products = Product::where('active', true)->get();
            
            $wpUrl = rtrim(config('app.frontend_url', 'http://localhost/rodust.com.br/wordpress'), '/');
            
            foreach ($products as $product) {
                // Enviar para API do WordPress
                $response = Http::post("{$wpUrl}/wp-json/rodust/v1/products", [
                    'sku' => $product->sku,
                    'title' => $product->name,
                    'description' => $product->description ?? '',
                    'price' => (float) $product->price,
                    'stock' => (int) $product->stock,
                    'image_url' => $product->image ?? '',
                    'bling_id' => $product->bling_id,
                ]);

                if ($response->successful()) {
                    $this->line("  ✅ {$product->name}");
                } else {
                    $this->line("  ❌ {$product->name} - Erro: {$response->status()}");
                }
            }
            
            $this->newLine();
            $this->info('✅ Sincronização com WordPress concluída!');
            
        } catch (\Exception $e) {
            $this->error("❌ Erro ao sincronizar com WordPress: {$e->getMessage()}");
        }
    }
}
