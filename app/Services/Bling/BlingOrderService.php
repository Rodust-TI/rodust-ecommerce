<?php

namespace App\Services\Bling;

use App\Models\Order;
use App\Services\ERP\BlingV3Adapter;
use Illuminate\Support\Facades\Log;

/**
 * Service: Integração de Pedidos com Bling
 * Responsabilidade: Criar pedidos no Bling ERP
 */
class BlingOrderService
{
    public function __construct(
        private BlingV3Adapter $bling,
        private BlingStatusService $statusService
    ) {}

    /**
     * Criar pedido no Bling
     * 
     * @param Order $order Pedido do Laravel
     * @return array ['success' => bool, 'bling_order_id' => ?string, 'bling_order_number' => ?string, 'error' => ?string]
     */
    public function createOrder(Order $order): array
    {
        try {
            // Carregar relacionamentos necessários
            $order->load(['customer', 'items.product']);

            // Validar dados obrigatórios
            if (!$order->customer) {
                throw new \Exception('Pedido sem cliente associado');
            }

            if ($order->items->isEmpty()) {
                throw new \Exception('Pedido sem itens');
            }

            // Preparar dados no formato esperado pelo BlingV3Adapter
            $orderData = $this->formatOrderData($order);

            Log::info('Criando pedido no Bling', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer' => $order->customer->name,
                'order_data' => $orderData
            ]);

            // Criar pedido no Bling (sempre como "Em aberto" - ID 6)
            // Se der erro de duplicação, a exceção será lançada e propagada
            try {
                $blingOrderNumber = $this->bling->createOrder($orderData);
            } catch (\App\Exceptions\BlingDuplicateOrderException $e) {
                // Re-lançar a exceção para que o controller possa tratá-la
                throw $e;
            }

            if (!$blingOrderNumber) {
                throw new \Exception('Bling não retornou número do pedido');
            }

            Log::info('Pedido criado no Bling com sucesso', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'bling_order_number' => $blingOrderNumber,
                'status_criado' => 'Em aberto (ID 6)'
            ]);

            // ESTRATÉGIA: Criar sempre como "Em aberto" e atualizar para "Em andamento" se pago
            // Isso permite que o Bling faça automaticamente os lançamentos de estoque e financeiros
            // quando há a transição de status, evitando 2 requisições adicionais (estoque + contas)
            
            // Se o pedido está pago, atualizar status para "Em andamento" imediatamente
            // Usar PATCH no endpoint específico que é mais eficiente
            if ($order->status === 'processing' || $order->paid_at) {
                Log::info('Pedido está pago - atualizando status no Bling para "Em andamento" imediatamente', [
                    'order_id' => $order->id,
                    'bling_order_number' => $blingOrderNumber,
                    'strategy' => 'Criar como Em aberto e atualizar para Em andamento (Bling faz lançamentos automaticamente)'
                ]);
                
                // Aguardar 1 segundo para garantir que o Bling processou a criação
                // Isso evita race conditions e garante que o pedido existe antes de atualizar
                sleep(1);
                
                // Usar o endpoint específico PATCH que é mais eficiente que PUT completo
                // ID 15 = "Em andamento" (status que dispara lançamentos automáticos no Bling)
                $statusId = 15; // Em andamento
                try {
                    $statusUpdated = $this->bling->updateOrderStatus($blingOrderNumber, $statusId);
                    if ($statusUpdated) {
                        Log::info('Status do pedido atualizado no Bling para "Em andamento" - Bling fará lançamentos automaticamente', [
                            'order_id' => $order->id,
                            'bling_order_number' => $blingOrderNumber,
                            'status_id' => $statusId
                        ]);
                    } else {
                        Log::warning('Falha ao atualizar status do pedido no Bling via PATCH', [
                            'order_id' => $order->id,
                            'error' => 'PATCH falhou - endpoint específico não funcionou'
                        ]);
                        // NOTA: Não tentamos PUT completo pois não funciona para alterar status
                        // O pedido foi criado com sucesso, apenas o status não foi atualizado
                        // Pode ser atualizado manualmente ou via webhook do Bling
                    }
                } catch (\Exception $e) {
                    Log::error('Erro ao atualizar status do pedido no Bling', [
                        'error' => $e->getMessage()
                    ]);
                    // Não falhar a criação do pedido se a atualização falhar
                    // O pedido foi criado com sucesso, apenas o status não foi atualizado
                }
            } else {
                // Pedido ainda não está pago - será atualizado quando o pagamento for aprovado
                Log::info('Pedido criado no Bling como "Em aberto" - será atualizado quando pagamento for aprovado', [
                    'order_id' => $order->id,
                    'bling_order_number' => $blingOrderNumber
                ]);
            }

            return [
                'success' => true,
                'bling_order_number' => $blingOrderNumber,
                'error' => null
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao criar pedido no Bling', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'bling_order_number' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Atualizar pedido existente no Bling (PUT - requer todos os campos)
     * 
     * IMPORTANTE: PUT substitui TODOS os campos. Campos não enviados serão limpos no Bling.
     * Por isso, precisamos buscar o pedido atual do Bling e mesclar com os dados atualizados.
     * 
     * ⚠️ NOTA: Este método NÃO deve ser usado para alterar apenas o status do pedido.
     * Para alterar status, use updateOrderStatus() que usa PATCH no endpoint específico.
     * 
     * @param Order $order Pedido do Laravel
     * @param int|null $forceStatusId Forçar um status ID específico no Bling (opcional - DESATIVADO)
     * @return array ['success' => bool, 'error' => ?string]
     */
    public function updateOrder(Order $order, ?int $forceStatusId = null): array
    {
        try {
            if (!$order->bling_order_number) {
                throw new \Exception('Pedido não tem número do Bling');
            }

            // Carregar relacionamentos necessários
            $order->load(['customer', 'items.product']);

            // Buscar pedido atual do Bling para preservar campos que não queremos alterar
            $blingOrder = $this->getOrder($order->bling_order_number);
            
            // Preparar dados no formato esperado pelo BlingV3Adapter
            $orderData = $this->formatOrderData($order);
            
            // Se um status ID específico foi fornecido, usar ele
            if ($forceStatusId !== null) {
                $orderData['bling_status_id'] = $forceStatusId;
                Log::info('Forçando status ID específico no Bling', ['status_id' => $forceStatusId]);
            }
            
            // Se encontrou o pedido no Bling, mesclar dados para preservar campos importantes
            if ($blingOrder) {
                // Preservar campos que não devem ser alterados
                // (ex: observações internas, campos customizados, etc)
                // O denormalizeOrder já prepara todos os campos necessários, então não precisamos mesclar
                // Mas podemos adicionar observações existentes se necessário
                Log::debug('Pedido encontrado no Bling - atualizando com dados completos', [
                    'bling_order_number' => $order->bling_order_number,
                    'status_atual_bling' => $blingOrder['situacao'] ?? null
                ]);
            }

            Log::info('Atualizando pedido no Bling', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'bling_order_number' => $order->bling_order_number,
                'status' => $order->status,
                'order_data' => $orderData
            ]);

            // ⚠️ DESATIVADO: Não usar PUT para alterar status
            // Se um status ID específico foi fornecido, remover do orderData
            // O método updateOrder() não deve ser usado para alterar status
            // Use updateOrderStatus() que usa PATCH no endpoint específico
            if (isset($orderData['bling_status_id'])) {
                Log::warning('updateOrder() chamado com bling_status_id - desativado, use updateOrderStatus()', [
                    'order_id' => $order->id,
                    'status_id' => $orderData['bling_status_id'],
                    'note' => 'PUT não funciona para alterar status, use PATCH /pedidos/vendas/{id}/situacoes/{statusId}'
                ]);
                
                // Remover bling_status_id do orderData - PUT não altera status
                unset($orderData['bling_status_id']);
            }
            
            // Atualizar pedido no Bling (PUT requer todos os campos)
            // NOTA: PUT não altera status - use updateOrderStatus() para isso
            $success = $this->bling->updateOrder($order->bling_order_number, $orderData);

            if (!$success) {
                throw new \Exception('Bling não confirmou atualização do pedido');
            }

            Log::info('Pedido atualizado no Bling com sucesso', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'bling_order_number' => $order->bling_order_number,
                'status' => $order->status
            ]);

            return [
                'success' => true,
                'error' => null
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar pedido no Bling', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'bling_order_number' => $order->bling_order_number ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Atualizar status do pedido no Bling
     * 
     * @param Order $order Pedido do Laravel
     * @param int $statusId ID do status no Bling
     * @return bool Sucesso da operação
     */
    public function updateOrderStatus(Order $order, int $statusId): bool
    {
        if (!$order->bling_order_number) {
            Log::warning('Tentativa de atualizar status no Bling sem bling_order_number', [
                'order_id' => $order->id,
            ]);
            return false;
        }

        try {
            $success = $this->bling->updateOrderStatus($order->bling_order_number, $statusId);
            
            if ($success) {
                Log::info('Status do pedido atualizado no Bling', [
                    'order_id' => $order->id,
                    'bling_order_number' => $order->bling_order_number,
                    'status_id' => $statusId,
                ]);
            }
            
            return $success;
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar status do pedido no Bling', [
                'order_id' => $order->id,
                'bling_order_number' => $order->bling_order_number,
                'status_id' => $statusId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Formatar dados do pedido para o formato esperado pelo Bling
     */
    protected function formatOrderData(Order $order): array
    {
        // Extrair endereço de entrega se disponível
        $shippingAddress = $order->shipping_address ?? [];
        
        return [
            'order_number' => $order->order_number, // Número do pedido na loja
            'status' => $order->status, // Status do pedido (pending, processing, etc)
            'paid_at' => $order->paid_at ? $order->paid_at->toIso8601String() : null, // Data de pagamento
            'customer' => [
                'id' => $order->customer->bling_id, // ID do cliente no Bling
                'name' => $order->customer->name,
                'email' => $order->customer->email,
                'phone' => $order->customer->phone ?? '',
            ],
            'items' => $order->items->map(function ($item) {
                return [
                    'bling_id' => $item->product->bling_id ?? null, // ID do produto no Bling
                    'sku' => $item->product_sku ?? $item->product->sku ?? '',
                    'name' => $item->product_name ?? $item->product->name ?? '',
                    'quantity' => $item->quantity,
                    'price' => (float) $item->unit_price,
                ];
            })->toArray(),
            'shipping' => (float) $order->shipping,
            'discount' => (float) $order->discount,
            'payment_method' => $order->payment_method, // Método de pagamento
            'payment_fee' => (float) ($order->payment_fee ?? 0), // Taxa do gateway
            'net_amount' => (float) ($order->net_amount ?? $order->total), // Valor líquido
            'installments' => (int) ($order->installments ?? 1), // Parcelas
            'shipping_address' => $shippingAddress, // Endereço de entrega
            'shipping_method_name' => $order->shipping_method_name, // Nome do método (ex: SEDEX)
            'shipping_carrier' => $order->shipping_carrier, // Transportadora (ex: Correios, Jadlog)
        ];
    }

    /**
     * Buscar pedido no Bling por número
     * 
     * @param string $blingOrderNumber Número do pedido no Bling
     * @return array|null Dados do pedido ou null se não encontrado
     */
    public function getOrder(string $blingOrderNumber): ?array
    {
        try {
            $blingOrder = $this->bling->getOrderById($blingOrderNumber);
            
            if (!$blingOrder) {
                Log::warning('Pedido não encontrado no Bling', [
                    'bling_order_number' => $blingOrderNumber
                ]);
                return null;
            }

            Log::info('Pedido encontrado no Bling', [
                'bling_order_number' => $blingOrderNumber,
                'situacao' => $blingOrder['situacao'] ?? null
            ]);

            return $blingOrder;

        } catch (\Exception $e) {
            Log::error('Erro ao buscar pedido no Bling', [
                'bling_order_number' => $blingOrderNumber,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Atualizar status do pedido local com base nos dados do Bling
     * 
     * @param Order $order Pedido local
     * @return bool Sucesso ou falha na atualização
     */
    public function syncOrderStatus(Order $order): bool
    {
        try {
            if (!$order->bling_order_number) {
                Log::warning('Pedido não tem número do Bling', [
                    'order_id' => $order->id
                ]);
                return false;
            }

            // Buscar pedido no Bling
            $blingOrder = $this->getOrder($order->bling_order_number);
            
            if (!$blingOrder) {
                return false;
            }

            // Mapear status do Bling para status interno
            $blingStatus = $blingOrder['situacao'] ?? null;
            
            if (!$blingStatus) {
                Log::warning('Pedido do Bling sem situação', [
                    'order_id' => $order->id,
                    'bling_order_number' => $order->bling_order_number,
                    'bling_order_keys' => array_keys($blingOrder),
                ]);
                return false;
            }

            $blingStatusId = $blingStatus['id'] ?? null;
            $blingStatusName = $this->statusService->getStatusName($blingStatusId);
            $internalStatus = $this->statusService->mapBlingStatusToInternal($blingStatus);
            
            Log::debug('Status mapeado do Bling', [
                'order_id' => $order->id,
                'bling_status_id' => $blingStatusId,
                'bling_status_name' => $blingStatusName,
                'internal_status' => $internalStatus,
            ]);

            // Atualizar apenas se o status mudou
            if ($order->status !== $internalStatus) {
                $oldStatus = $order->status;
                $order->update([
                    'status' => $internalStatus,
                    'last_bling_sync' => now()
                ]);

                Log::info('Status do pedido atualizado via sincronização manual', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'bling_order_number' => $order->bling_order_number,
                    'old_status' => $oldStatus,
                    'new_status' => $internalStatus,
                    'bling_status_id' => $blingStatusId,
                    'bling_status_name' => $blingStatusName
                ]);
            } else {
                $order->update(['last_bling_sync' => now()]);
                
                Log::debug('Status do pedido já está atualizado', [
                    'order_id' => $order->id,
                    'status' => $internalStatus,
                    'bling_status_id' => $blingStatusId,
                    'bling_status_name' => $blingStatusName
                ]);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Erro ao sincronizar status do pedido', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Sincronizar status de todos os pedidos pendentes no Bling
     * 
     * @param int|null $limit Número máximo de pedidos a sincronizar (null = todos)
     * @return array ['synced' => int, 'failed' => int, 'total' => int]
     */
    public function syncAllPendingOrders(?int $limit = null): array
    {
        $synced = 0;
        $failed = 0;

        // Contar total de pedidos que precisam sincronização
        // Sincronizar TODOS os pedidos com bling_order_number (exceto os que já estão finalizados)
        // Isso inclui: pending, processing, invoiced, shipped
        $query = Order::whereNotNull('bling_order_number')
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->orderBy('created_at', 'desc');

        $total = $query->count();

        Log::info('Iniciando sincronização de status de pedidos', [
            'total_pendentes' => $total,
            'limite' => $limit ?? 'Todos'
        ]);

        // Se não há limite, processar todos em chunks para não sobrecarregar memória
        if ($limit === null) {
            $query->chunk(50, function ($orders) use (&$synced, &$failed) {
                foreach ($orders as $order) {
                    if ($this->syncOrderStatus($order)) {
                        $synced++;
                    } else {
                        $failed++;
                    }
                }
            });
        } else {
            // Com limite, buscar de uma vez
            $orders = $query->limit($limit)->get();
            
            foreach ($orders as $order) {
                if ($this->syncOrderStatus($order)) {
                    $synced++;
                } else {
                    $failed++;
                }
            }
        }

        Log::info('Sincronização de status concluída', [
            'total_processados' => $synced + $failed,
            'synced' => $synced,
            'failed' => $failed
        ]);

        return [
            'synced' => $synced,
            'failed' => $failed,
            'total' => $total
        ];
    }
}
