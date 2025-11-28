<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncProductsToWordPress extends Command
{
    protected $signature = 'products:sync-to-wordpress';
    protected $description = 'Força sincronização de todos os produtos para o WordPress';

    public function handle()
    {
        $this->info('🔄 Sincronizando produtos com WordPress...');
        
        $products = Product::all();
        $wpUrl = rtrim(config('urls.wordpress.external'), '/');
        
        $success = 0;
        $errors = 0;
        
        // Credenciais da API WordPress
        $wpUser = config('app.wordpress_api_user', 'admin');
        $wpPassword = config('app.wordpress_api_password');
        
        foreach ($products as $product) {
            try {
                $payload = [
                    'sku' => $product->sku,
                    'title' => $product->name,
                    'description' => $product->description ?? '',
                    'price' => (float) $product->price,
                    'stock' => (int) $product->stock,
                    'image_url' => $product->image ?? '',
                    'bling_id' => $product->bling_id,
                    'laravel_id' => $product->id,
                    'brand' => $product->brand ?? '',
                ];
                
                // Debug
                $this->line("  📤 Enviando: SKU={$payload['sku']}, Bling ID={$payload['bling_id']}");
                
                $response = Http::withBasicAuth($wpUser, $wpPassword)
                    ->asJson()
                    ->post("{$wpUrl}/wp-json/rodust/v1/products", $payload);

                if ($response->successful()) {
                    // Remove BOM e decodifica JSON
                    $bodyClean = preg_replace('/^\x{FEFF}/u', '', $response->body());
                    $responseData = json_decode($bodyClean, true);
                    
                    // Debug
                    $action = $responseData['action'] ?? 'unknown';
                    $actionIcon = $action === 'updated' ? '🔄' : '➕';
                    $actionText = $action === 'updated' ? 'ATUALIZADO' : 'CRIADO';
                    
                    // Salvar wordpress_post_id se retornado pela API
                    if (isset($responseData['post_id'])) {
                        $product->wordpress_post_id = $responseData['post_id'];
                        $product->save();
                        
                        $debugInfo = isset($responseData['debug']) ? 
                            " [Query encontrou: {$responseData['debug']['query_found_posts']} posts]" : '';
                        
                        $this->line("  {$actionIcon} {$actionText}: {$product->name} (Laravel ID: {$product->id}, WP Post ID: {$responseData['post_id']}){$debugInfo}");
                    } else {
                        $this->line("  ✅ {$product->name} (Laravel ID: {$product->id})");
                    }
                    
                    $success++;
                } else {
                    $this->line("  ❌ {$product->name} - Erro: {$response->status()}");
                    $this->line("     Resposta: " . $response->body());
                    $errors++;
                }
            } catch (\Exception $e) {
                $this->line("  ❌ {$product->name} - Exceção: " . $e->getMessage());
                $errors++;
            }
        }
        
        $this->newLine();
        $this->info("✅ Sincronizados: {$success}");
        if ($errors > 0) {
            $this->error("❌ Erros: {$errors}");
        }
        
        return 0;
    }
}
