<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Contracts\ERPInterface;

class BlingListProducts extends Command
{
    protected $signature = 'bling:list-products {--limit=10 : Number of products to list}';
    
    protected $description = 'List products from Bling ERP';

    public function __construct(protected ERPInterface $erp)
    {
        parent::__construct();
    }

    public function handle()
    {
        $limit = (int) $this->option('limit');

        $this->info('📦 Buscando produtos do Bling...');

        try {
            $products = $this->erp->getProducts(['limite' => $limit]);

            if (empty($products)) {
                $this->warn('⚠️  Nenhum produto encontrado no Bling.');
                return 0;
            }

            $this->newLine();
            $this->info("✅ {$products->count()} produtos encontrados:");
            $this->newLine();

            $this->table(
                ['ID Bling', 'SKU', 'Nome', 'Preço', 'Estoque'],
                $products->map(function ($product) {
                    return [
                        $product['bling_id'] ?? $product['id'] ?? 'N/A',
                        $product['sku'] ?? 'N/A',
                        \Illuminate\Support\Str::limit($product['name'], 40),
                        'R$ ' . number_format($product['price'] ?? 0, 2, ',', '.'),
                        $product['stock'] ?? 'N/A',
                    ];
                })->toArray()
            );

            $this->newLine();
            $this->info('💡 Para importar esses produtos para o Laravel, use:');
            $this->line('   php artisan bling:import-products');

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Erro ao buscar produtos: ' . $e->getMessage());
            $this->newLine();
            $this->line('Stack trace:');
            $this->line($e->getTraceAsString());
            return 1;
        }
    }
}
