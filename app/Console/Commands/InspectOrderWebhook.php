<?php

namespace App\Console\Commands;

use App\Models\WebhookLog;
use Illuminate\Console\Command;

class InspectOrderWebhook extends Command
{
    protected $signature = 'webhook:inspect-order {--limit=5 : Número de webhooks recentes para inspecionar}';
    protected $description = 'Inspecionar webhooks de pedidos do Bling para debug';

    public function handle()
    {
        $limit = (int) $this->option('limit');
        
        $this->info("🔍 Buscando últimos {$limit} webhooks de pedidos do Bling...");
        $this->newLine();

        $webhooks = WebhookLog::where('source', 'bling')
            ->where('resource', 'order')
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get();

        if ($webhooks->isEmpty()) {
            $this->warn('⚠️  Nenhum webhook de pedido encontrado');
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
                    $this->line("   Número do pedido (Bling): " . ($data['numero'] ?? $data['id'] ?? 'N/A'));
                    
                    $situacao = $data['situacao'] ?? null;
                    if ($situacao) {
                        $this->line("   Status ID: " . ($situacao['id'] ?? 'N/A'));
                        $this->line("   Status Valor: " . ($situacao['valor'] ?? 'N/A'));
                    } else {
                        $this->warn("   ⚠️  SEM INFORMAÇÃO DE STATUS (situacao) no payload!");
                        $this->line("   Chaves disponíveis: " . implode(', ', array_keys($data)));
                    }
                }
            }

            // Mostrar metadata
            if ($webhook->metadata) {
                $this->newLine();
                $this->line("📋 Metadata:");
                if (isset($webhook->metadata['order_id'])) {
                    $this->line("   ✅ Pedido encontrado: ID {$webhook->metadata['order_id']}");
                    $this->line("   Número: " . ($webhook->metadata['order_number'] ?? 'N/A'));
                    $this->line("   Status ID Bling: " . ($webhook->metadata['bling_status_id'] ?? 'N/A'));
                    $this->line("   Status Nome Bling: " . ($webhook->metadata['bling_status_name'] ?? 'N/A'));
                    $this->line("   Status Interno: " . ($webhook->metadata['new_internal_status'] ?? 'N/A'));
                    if (isset($webhook->metadata['old_internal_status'])) {
                        $this->line("   Status Anterior: {$webhook->metadata['old_internal_status']} → {$webhook->metadata['new_internal_status']}");
                    }
                } else {
                    $this->warn("   ⚠️  Pedido não encontrado ou não processado");
                    if (isset($webhook->metadata['error'])) {
                        $this->error("   Erro: {$webhook->metadata['error']}");
                    }
                }
            }

            $this->newLine();
        }

        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        
        return Command::SUCCESS;
    }
}

