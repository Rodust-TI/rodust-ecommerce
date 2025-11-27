<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SyncProductToWordPress implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;
    public $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $productId
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $product = Product::find($this->productId);

        if (!$product) {
            Log::warning("Produto {$this->productId} não encontrado para sync WordPress");
            return;
        }

        try {
            $wpUrl = config('services.wordpress.url');
            $wpUser = config('services.wordpress.api_user');
            $wpPassword = config('services.wordpress.api_password');

            if (!$wpUrl || !$wpUser || !$wpPassword) {
                throw new \Exception('Configuração do WordPress não encontrada em config/services.php');
            }

            // Preparar dados para WordPress (apenas metadados SEO)
            $postData = [
                'title' => $product->name,
                'content' => $product->description ?? '',
                'status' => $product->active ? 'publish' : 'draft',
                'slug' => Str::slug($product->name),
                'meta' => [
                    '_bling_id' => $product->bling_id,
                    '_laravel_id' => $product->id,
                    '_price' => $product->price, // Para exibição básica
                    '_stock' => $product->stock, // Para verificação rápida
                ]
            ];

            // Se o produto já tem post no WordPress, atualizar
            if ($product->wordpress_post_id) {
                $response = Http::withBasicAuth($wpUser, $wpPassword)
                    ->put("{$wpUrl}/wp-json/wp/v2/produtos/{$product->wordpress_post_id}", $postData);

                if ($response->successful()) {
                    Log::info("Produto {$product->name} atualizado no WordPress", [
                        'product_id' => $product->id,
                        'wp_post_id' => $product->wordpress_post_id
                    ]);
                } else {
                    throw new \Exception("Erro ao atualizar no WordPress: {$response->body()}");
                }
            } else {
                // Criar novo post no WordPress
                $response = Http::withBasicAuth($wpUser, $wpPassword)
                    ->post("{$wpUrl}/wp-json/wp/v2/produtos", $postData);

                if ($response->successful()) {
                    $wpPostId = $response->json()['id'];
                    $product->update(['wordpress_post_id' => $wpPostId]);

                    Log::info("Produto {$product->name} criado no WordPress", [
                        'product_id' => $product->id,
                        'wp_post_id' => $wpPostId
                    ]);

                    // Associar marca (taxonomia) se existir
                    if ($product->brand) {
                        $this->syncBrandTaxonomy($product, $wpPostId, $wpUrl, $wpUser, $wpPassword);
                    }
                } else {
                    throw new \Exception("Erro ao criar no WordPress: {$response->body()}");
                }
            }

        } catch (\Exception $e) {
            Log::error("Erro ao sincronizar produto {$product->id} com WordPress: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Criar/associar marca na taxonomia product_brand do WordPress
     */
    protected function syncBrandTaxonomy(Product $product, int $wpPostId, string $wpUrl, string $wpUser, string $wpPassword): void
    {
        try {
            $brandSlug = Str::slug($product->brand);

            // Buscar se a marca já existe
            $response = Http::withBasicAuth($wpUser, $wpPassword)
                ->get("{$wpUrl}/wp-json/wp/v2/product_brand", [
                    'slug' => $brandSlug
                ]);

            if ($response->successful() && count($response->json()) > 0) {
                // Marca existe, pegar ID
                $brandId = $response->json()[0]['id'];
            } else {
                // Criar nova marca
                $createResponse = Http::withBasicAuth($wpUser, $wpPassword)
                    ->post("{$wpUrl}/wp-json/wp/v2/product_brand", [
                        'name' => $product->brand,
                        'slug' => $brandSlug
                    ]);

                if ($createResponse->successful()) {
                    $brandId = $createResponse->json()['id'];
                } else {
                    Log::warning("Não foi possível criar marca {$product->brand} no WordPress");
                    return;
                }
            }

            // Associar marca ao produto
            Http::withBasicAuth($wpUser, $wpPassword)
                ->put("{$wpUrl}/wp-json/wp/v2/produtos/{$wpPostId}", [
                    'product_brand' => [$brandId]
                ]);

            Log::info("Marca {$product->brand} associada ao produto {$product->name}");

        } catch (\Exception $e) {
            Log::warning("Erro ao sincronizar marca: {$e->getMessage()}");
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Falha final ao sincronizar produto {$this->productId} com WordPress após {$this->tries} tentativas", [
            'exception' => $exception->getMessage()
        ]);
    }
}
