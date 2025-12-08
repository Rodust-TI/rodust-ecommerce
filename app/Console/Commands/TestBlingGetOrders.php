<?php

namespace App\Console\Commands;

use App\Services\ERP\BlingV3Adapter;
use Illuminate\Console\Command;

class TestBlingGetOrders extends Command
{
    protected $signature = 'bling:test-get-orders {--days=7 : Número de dias para buscar}';
    protected $description = 'Testar busca de pedidos do Bling via API';

    public function handle(BlingV3Adapter $bling)
    {
        $days = (int) $this->option('days');
        
        $this->info("🔍 Buscando pedidos do Bling dos últimos {$days} dias...");
        
        try {
            $filters = [
                'dataInicial' => now()->subDays($days)->format('Y-m-d'),
                'dataFinal' => now()->format('Y-m-d'),
            ];
            
            $this->info("📋 Filtros: " . json_encode($filters, JSON_PRETTY_PRINT));
            
            $orders = $bling->getOrders($filters);
            
            $this->info("✅ Pedidos encontrados: " . count($orders));
            $this->newLine();
            
            if (empty($orders)) {
                $this->warn("⚠️  Nenhum pedido encontrado.");
                return 0;
            }
            
            // Debug: mostrar estrutura completa do primeiro pedido
            if (!empty($orders)) {
                $this->line("🔍 Estrutura do primeiro pedido (JSON):");
                $this->line(json_encode($orders[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                $this->newLine();
            }
            
            // Exibir os primeiros 10 pedidos
            $displayOrders = array_slice($orders, 0, 10);
            
            $headers = ['ID', 'Número', 'Data', 'Cliente', 'Status', 'Status ID', 'Total'];
            $data = [];
            
            foreach ($displayOrders as $order) {
                // Tentar diferentes caminhos para os campos
                $numero = $order['numero'] ?? $order['numeroPedido'] ?? 'N/A';
                $clienteNome = $order['contato']['nome'] ?? $order['contato']['fantasia'] ?? $order['cliente']['nome'] ?? 'N/A';
                $statusNome = $order['situacao']['nome'] ?? $order['situacao']['descricao'] ?? $order['status']['nome'] ?? 'N/A';
                $statusId = $order['situacao']['id'] ?? $order['status']['id'] ?? 'N/A';
                $total = $order['total'] ?? $order['valorTotal'] ?? 0;
                
                $data[] = [
                    $order['id'] ?? 'N/A',
                    $numero,
                    isset($order['data']) ? date('d/m/Y', strtotime($order['data'])) : 'N/A',
                    $clienteNome,
                    $statusNome,
                    $statusId,
                    'R$ ' . number_format($total, 2, ',', '.'),
                ];
            }
            
            $this->table($headers, $data);
            
            // A API do Bling retorna apenas id e valor no campo situacao, não tem nome
            // Vamos exibir apenas o ID do status
            $data = [];
            foreach ($displayOrders as $order) {
                $statusId = $order['situacao']['id'] ?? 'N/A';
                $statusValor = $order['situacao']['valor'] ?? 'N/A';
                
                $numero = $order['numero'] ?? $order['numeroPedido'] ?? 'N/A';
                $clienteNome = $order['contato']['nome'] ?? $order['contato']['fantasia'] ?? $order['cliente']['nome'] ?? 'N/A';
                $total = $order['total'] ?? $order['valorTotal'] ?? 0;
                
                $data[] = [
                    $order['id'] ?? 'N/A',
                    $numero,
                    isset($order['data']) ? date('d/m/Y', strtotime($order['data'])) : 'N/A',
                    $clienteNome,
                    "ID: {$statusId} (valor: {$statusValor})",
                    $statusId,
                    'R$ ' . number_format($total, 2, ',', '.'),
                ];
            }
            
            $this->table($headers, $data);
            
            // Verificar se há pedido com status ID 15 (que parece ser "Em andamento" no seu Bling)
            $processingOrders = array_filter($orders, function($order) {
                return isset($order['situacao']['id']) && $order['situacao']['id'] == 15;
            });
            
            if (!empty($processingOrders)) {
                $this->newLine();
                $this->info("✅ Pedidos com status ID 15 (provavelmente 'Em andamento'): " . count($processingOrders));
                foreach ($processingOrders as $order) {
                    $this->line("   - ID Bling: {$order['id']} | Número: {$order['numero']} | Cliente: " . ($order['contato']['nome'] ?? 'N/A'));
                    $this->line("     Total: R$ " . number_format($order['total'] ?? 0, 2, ',', '.'));
                }
            } else {
                $this->newLine();
                $this->warn("⚠️  Nenhum pedido encontrado com status ID 15");
            }
            
            // Verificar todos os IDs de status únicos
            $uniqueStatusIds = array_unique(array_map(function($order) {
                return $order['situacao']['id'] ?? null;
            }, $orders));
            
            $this->newLine();
            $this->info("📊 Status IDs encontrados nos pedidos:");
            foreach ($uniqueStatusIds as $statusId) {
                $count = count(array_filter($orders, function($order) use ($statusId) {
                    return ($order['situacao']['id'] ?? null) === $statusId;
                }));
                $this->line("   - ID {$statusId}: {$count} pedido(s)");
            }
            
            // Verificar se há pedido com número 2 (que pode ser o pedido #4)
            // O pedido #4 no Laravel tem número ROD-20251205-7749, mas no Bling pode ser apenas "2"
            $order4 = array_filter($orders, function($order) {
                $numero = $order['numero'] ?? '';
                // Verificar se é o número 2 (que pode ser o pedido #4)
                return $numero == 2 || $numero == '2';
            });
            
            if (!empty($order4)) {
                $this->newLine();
                $this->info("📦 Pedido com número 2 encontrado (possivelmente o pedido #4):");
                $order = reset($order4);
                $statusId = $order['situacao']['id'] ?? 'N/A';
                $statusValor = $order['situacao']['valor'] ?? 'N/A';
                $this->line("   ID Bling: {$order['id']}");
                $this->line("   Número: " . ($order['numero'] ?? 'N/A'));
                $this->line("   Status ID: {$statusId} (valor: {$statusValor})");
                $this->line("   Total: R$ " . number_format($order['total'] ?? 0, 2, ',', '.'));
                
                if ($statusId == 15) {
                    $this->info("   ✅ Este pedido está com status ID 15 (Em andamento)!");
                }
            } else {
                $this->newLine();
                $this->warn("⚠️  Pedido com número 2 não encontrado na lista");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Erro: {$e->getMessage()}");
            $this->error("   Trace: {$e->getTraceAsString()}");
            return 1;
        }
        
        return 0;
    }
}
