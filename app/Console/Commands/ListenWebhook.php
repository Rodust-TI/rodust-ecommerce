<?php

namespace App\Console\Commands;

use App\Models\WebhookLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ListenWebhook extends Command
{
    protected $signature = 'webhook:listen {source=mercadopago : Source do webhook (mercadopago ou bling)} {--timeout=30 : Tempo em segundos para escutar}';
    protected $description = 'Escuta webhooks por um período determinado e exibe no terminal em tempo real';

    public function handle()
    {
        $source = $this->argument('source');
        $timeout = (int) $this->option('timeout');
        
        $this->info("🔍 Escutando webhooks do {$source} por {$timeout} segundos...");
        $this->info("📡 Aguardando eventos...");
        $this->newLine();
        
        // Obter último ID de log antes de começar
        $lastLogId = WebhookLog::where('source', $source)
            ->orderBy('id', 'desc')
            ->value('id') ?? 0;
        
        $this->info("📊 Último log ID antes de iniciar: {$lastLogId}");
        $this->newLine();
        
        // Mostrar URL esperada
        if ($source === 'mercadopago') {
            $this->info("🌐 URL do webhook esperada:");
            $this->line("   https://sanozukez-mercadopago.ultrahook.com/api/webhooks/mercadopago");
        } elseif ($source === 'bling') {
            $this->info("🌐 URL do webhook esperada:");
            $this->line("   https://sanozukez-rodust-ecommerce.ultrahook.com/api/webhooks/bling");
        }
        $this->newLine();
        
        $startTime = time();
        $endTime = $startTime + $timeout;
        $receivedCount = 0;
        
        $this->info("⏱️  Iniciando escuta... (pressione Ctrl+C para parar antes do tempo)");
        $this->newLine();
        
        while (time() < $endTime) {
            $remaining = $endTime - time();
            $this->output->write("\r⏳ Aguardando... ({$remaining}s restantes)");
            
            // Buscar novos logs
            $newLogs = WebhookLog::where('source', $source)
                ->where('id', '>', $lastLogId)
                ->orderBy('id', 'asc')
                ->get();
            
            foreach ($newLogs as $log) {
                $this->newLine(2);
                $this->displayWebhookLog($log);
                $lastLogId = $log->id;
                $receivedCount++;
            }
            
            // Verificar também no cache (para webhooks muito recentes)
            $cacheKey = "webhook_recent_{$source}";
            $recentWebhook = Cache::get($cacheKey);
            if ($recentWebhook && isset($recentWebhook['id']) && $recentWebhook['id'] > $lastLogId) {
                $this->newLine(2);
                $this->info("⚡ Webhook recebido (via cache):");
                $this->line("   Tipo: {$recentWebhook['type']}");
                $this->line("   Timestamp: {$recentWebhook['timestamp']}");
                $lastLogId = $recentWebhook['id'];
                $receivedCount++;
                Cache::forget($cacheKey);
            }
            
            usleep(500000); // Verificar a cada 0.5 segundos
        }
        
        $this->newLine(2);
        $this->info("⏱️  Tempo esgotado ({$timeout}s)");
        
        if ($receivedCount > 0) {
            $this->info("✅ Total de webhooks recebidos durante o período: {$receivedCount}");
        } else {
            $this->warn("⚠️  Nenhum webhook recebido durante o período.");
            $this->newLine();
            $this->info("💡 Verificações:");
            $this->line("   1. Verifique se o UltraHook está rodando:");
            $this->line("      Get-Process | Where-Object {\$_.ProcessName -like '*ruby*' -or \$_.ProcessName -eq 'powershell'}");
            $this->line("   2. Inicie o UltraHook se necessário:");
            $this->line("      .\ultrahook-start.ps1");
            $this->line("   3. Verifique a URL do webhook no Mercado Pago/Bling");
            $this->line("   4. Verifique os logs do Laravel:");
            $this->line("      docker exec docker-laravel.test-1 tail -f storage/logs/laravel.log");
            $this->line("   5. Teste o endpoint diretamente:");
            if ($source === 'mercadopago') {
                $this->line("      curl -X POST http://localhost:8000/api/webhooks/mercadopago -H 'Content-Type: application/json' -d '{\"type\":\"payment\",\"data\":{\"id\":\"test123\"}}'");
            }
        }
        
        return Command::SUCCESS;
    }
    
    protected function displayWebhookLog(WebhookLog $log)
    {
        $this->newLine();
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("📥 WEBHOOK RECEBIDO!");
        $this->line("   ID: {$log->id}");
        $this->line("   Source: {$log->source}");
        $this->line("   Event Type: " . ($log->event_type ?? 'N/A'));
        $this->line("   Event ID: " . ($log->event_id ?? 'N/A'));
        $this->line("   Resource: " . ($log->resource ?? 'N/A'));
        $this->line("   Action: " . ($log->action ?? 'N/A'));
        $this->line("   Status: {$log->status}");
        
        if ($log->response_code) {
            $this->line("   HTTP Code: {$log->response_code}");
        }
        
        if ($log->error_message) {
            $this->error("   ❌ Erro: {$log->error_message}");
        }
        
        if ($log->metadata) {
            $this->line("   Metadata:");
            foreach ($log->metadata as $key => $value) {
                if (is_array($value)) {
                    $this->line("      {$key}: " . json_encode($value));
                } else {
                    $this->line("      {$key}: {$value}");
                }
            }
        }
        
        $this->line("   Recebido em: " . $log->created_at->format('d/m/Y H:i:s'));
        
        if ($log->processed_at) {
            $this->line("   Processado em: " . $log->processed_at->format('d/m/Y H:i:s'));
        }
        
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    }
}
