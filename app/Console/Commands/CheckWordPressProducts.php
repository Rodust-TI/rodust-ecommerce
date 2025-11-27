<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckWordPressProducts extends Command
{
    protected $signature = 'products:check-wordpress';
    protected $description = 'Verifica produtos no WordPress e seus meta fields';

    public function handle()
    {
        $this->info('🔍 Verificando produtos no WordPress...');
        $this->newLine();
        
        try {
            // Buscar produtos
            $posts = DB::connection('wordpress')
                ->table('wp_posts')
                ->where('post_type', 'rodust_product')
                ->where('post_status', 'publish')
                ->get(['ID', 'post_title']);
            
            if ($posts->isEmpty()) {
                $this->error('❌ Nenhum produto encontrado no WordPress!');
                return 1;
            }
            
            $this->info("✅ Encontrados {$posts->count()} produtos:");
            $this->newLine();
            
            foreach ($posts as $post) {
                $this->line("📦 {$post->post_title} (WordPress ID: {$post->ID})");
                
                // Buscar meta fields
                $metas = DB::connection('wordpress')
                    ->table('wp_postmeta')
                    ->where('post_id', $post->ID)
                    ->whereIn('meta_key', ['_laravel_id', 'sku', 'price', 'stock', 'bling_id'])
                    ->get(['meta_key', 'meta_value']);
                
                foreach ($metas as $meta) {
                    $this->line("   • {$meta->meta_key}: {$meta->meta_value}");
                }
                
                $this->newLine();
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Erro: " . $e->getMessage());
            return 1;
        }
    }
}
