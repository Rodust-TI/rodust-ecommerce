<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ListenMercadoPagoWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhook:listen-mp {--timeout=60}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Escuta eventos do webhook do Mercado Pago (útil para testes)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $timeout = (int) $this->option('timeout');
        $cacheKey = 'mp_webhook_test_event';
        
        // Limpar eventos anteriores
        Cache::forget($cacheKey);
        
        $this->info("🎧 Escutando webhook do Mercado Pago...");
        $this->info("📡 URL: https://rodust-ecommerce-dev.loca.lt/api/webhooks/mercadopago");
        $this->line("");
        $this->warn("⏱️  Timeout: {$timeout} segundos");
        $this->warn("💡 Vá no painel do Mercado Pago e clique em 'Simular Notificação'");
        $this->line("");
        
        $startTime = time();
        $dots = 0;
        
        while (true) {
            // Verificar se recebeu algum evento
            $event = Cache::get($cacheKey);
            
            if ($event) {
                $this->line("");
                $this->info("✅ EVENTO RECEBIDO!");
                $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
                $this->line("");
                
                $this->table(
                    ['Campo', 'Valor'],
                    [
                        ['Tipo', $event['type'] ?? 'N/A'],
                        ['Ação', $event['action'] ?? 'N/A'],
                        ['ID', $event['data']['id'] ?? 'N/A'],
                        ['Recebido em', $event['received_at'] ?? 'N/A'],
                    ]
                );
                
                $this->line("");
                $this->line("📦 Payload completo:");
                $this->line(json_encode($event, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                $this->line("");
                $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
                
                // Limpar o cache
                Cache::forget($cacheKey);
                
                return 0;
            }
            
            // Verificar timeout
            $elapsed = time() - $startTime;
            if ($elapsed >= $timeout) {
                $this->line("");
                $this->error("⏱️  Timeout atingido ({$timeout}s). Nenhum evento recebido.");
                $this->line("");
                $this->warn("Verifique:");
                $this->warn("  • LocalTunnel está rodando (https://rodust-ecommerce-dev.loca.lt)");
                $this->warn("  • URL configurada no Mercado Pago está correta");
                $this->warn("  • Você clicou em 'Simular Notificação' no painel do MP");
                
                return 1;
            }
            
            // Animação de aguardando
            $dots = ($dots + 1) % 4;
            $animation = str_repeat('.', $dots) . str_repeat(' ', 3 - $dots);
            $remaining = $timeout - $elapsed;
            
            echo "\r⏳ Aguardando{$animation} ({$remaining}s restantes)";
            
            usleep(500000); // 0.5 segundos
        }
    }
}
