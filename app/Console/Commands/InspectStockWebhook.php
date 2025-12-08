<?php

namespace App\Console\Commands;

use App\Models\WebhookLog;
use Illuminate\Console\Command;

class InspectStockWebhook extends Command
{
    protected $signature = 'webhook:inspect-stock {--limit=5 : Número de webhooks recentes para inspecionar}';
    protected $description = 'Inspecionar webhooks de stock do Bling para debug';

    public function handle()
    {
        $limit = (int) $this->option('limit');
        
        $this->info("🔍 Buscando últimos {$limit} webhooks de stock do Bling...");
        $this->newLine();

        $webhooks = WebhookLog::where('source', 'bling')
            ->where('resource', 'stock')
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get();

        if ($webhooks->isEmpty()) {
            $this->warn('⚠️  Nenhum webhook de stock encontrado');
            return Command::SUCCESS;
        }

        foreach ($webhooks as $webhook) {
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->line("📥 Webhook ID: {$webhook->id}");
            $this->line("   Evento: {$webhook->event_type}");
            $this->line("   Status: {$webhook->status}");
            $this->line("   Recebido em: {$webhook->created_at->format('d/m/Y H:i:s')}");
            
            if ($webhook->error_message) {
                $this->error("   ❌ Erro: {$webhook->error_message}");
            }

            // Mostrar payload completo
            $payload = json_decode($webhook->payload, true);
            if ($payload) {
                $this->newLine();
                $this->line("📦 Payload completo:");
                $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                
                // Extrair dados relevantes
                $data = $payload['data'] ?? [];
                if (!empty($data)) {
                    $this->newLine();
                    $this->line("📊 Dados extraídos:");
                    $this->line("   ID do produto (Bling): " . ($data['id'] ?? 'N/A'));
                    $this->line("   Código (SKU): " . ($data['codigo'] ?? 'N/A'));
                    $this->line("   Estoque Atual: " . ($data['estoqueAtual'] ?? 'N/A'));
                    
                    if (isset($data['depositos']) && is_array($data['depositos']) && !empty($data['depositos'])) {
                        $deposito = $data['depositos'][0];
                        $this->line("   Depósito[0].saldo: " . ($deposito['saldo'] ?? 'N/A'));
                        $this->line("   Depósito[0].saldoVirtual: " . ($deposito['saldoVirtual'] ?? 'N/A'));
                    }
                }
            }

            // Mostrar metadata
            if ($webhook->metadata) {
                $this->newLine();
                $this->line("📋 Metadata:");
                if (isset($webhook->metadata['product_id'])) {
                    $this->line("   ✅ Produto encontrado: ID {$webhook->metadata['product_id']}");
                    $this->line("   SKU: " . ($webhook->metadata['product_sku'] ?? 'N/A'));
                    
                    if (isset($webhook->metadata['stock_updated'])) {
                        $stock = $webhook->metadata['stock_updated'];
                        $this->line("   Estoque anterior: {$stock['old']}");
                        $this->line("   Estoque novo: {$stock['new']}");
                        $this->line("   Diferença: " . ((int)$stock['new'] - (int)$stock['old']));
                    }
                } else {
                    $this->warn("   ⚠️  Produto não encontrado ou não processado");
                }
            }

            $this->newLine();
        }

        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        
        return Command::SUCCESS;
    }
}

