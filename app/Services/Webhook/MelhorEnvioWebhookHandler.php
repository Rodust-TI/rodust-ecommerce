<?php

namespace App\Services\Webhook;

use App\Models\Order;
use App\Models\WebhookLog;
use App\Services\Shipping\ShippingService;
use App\Services\MelhorEnvioService;
use App\Services\Webhook\WebhookLogService;
use App\DTOs\ShippingData;
use Illuminate\Support\Facades\Log;

/**
 * Handler de Webhooks do Melhor Envio
 * 
 * Processa eventos de envio usando DTOs para desacoplar
 */
class MelhorEnvioWebhookHandler
{
    public function __construct(
        private ShippingService $shippingService,
        private MelhorEnvioService $melhorEnvioService
    ) {}

    /**
     * Processar webhook do Melhor Envio
     */
    public function handle(array $data, string $event, WebhookLog $webhookLog): void
    {
        $orderId = $data['order_id'] ?? null;
        
        if (!$orderId) {
            Log::warning('Melhor Envio webhook without order_id', ['webhook_log_id' => $webhookLog->id]);
            app(WebhookLogService::class)->addMetadata($webhookLog, ['error' => 'Missing order_id']);
            return;
        }

        // Buscar pedido pelo ID do Melhor Envio (pode estar em diferentes campos)
        // O Melhor Envio pode enviar o ID do pedido deles ou nosso order_number
        $order = $this->findOrder($orderId);

        if (!$order) {
            Log::warning('Order not found for Melhor Envio webhook', [
                'melhor_envio_order_id' => $orderId,
                'webhook_log_id' => $webhookLog->id
            ]);
            app(WebhookLogService::class)->addMetadata($webhookLog, ['error' => 'Order not found']);
            return;
        }

        // Processar evento específico
        switch ($event) {
            case 'order.generated':
                $this->handleLabelGenerated($order, $data, $webhookLog);
                break;
            case 'order.posted':
                $this->handleOrderPosted($order, $data, $webhookLog);
                break;
            case 'order.delivered':
                $this->handleOrderDelivered($order, $data, $webhookLog);
                break;
            case 'order.canceled':
                $this->handleOrderCanceled($order, $data, $webhookLog);
                break;
            case 'order.created':
                // Apenas log - não precisa processar
                Log::info('Melhor Envio: Order created', [
                    'order_id' => $order->id,
                    'melhor_envio_order_id' => $orderId,
                    'webhook_log_id' => $webhookLog->id
                ]);
                break;
            default:
                Log::info('Melhor Envio: Unknown event', [
                    'event' => $event,
                    'order_id' => $order->id,
                    'webhook_log_id' => $webhookLog->id
                ]);
        }
    }

    /**
     * Buscar pedido pelo ID do Melhor Envio
     * 
     * O Melhor Envio pode enviar:
     * - order_id: ID do pedido no Melhor Envio
     * - protocol: Código de protocolo (que pode ser nosso order_number)
     */
    protected function findOrder(string $orderId): ?Order
    {
        // Tentar buscar pelo protocol (que pode ser nosso order_number)
        $order = Order::where('order_number', $orderId)->first();
        
        if ($order) {
            return $order;
        }

        // Se não encontrou, pode ser que o order_id seja o ID do Melhor Envio
        // Nesse caso, precisaríamos ter uma tabela de mapeamento
        // Por enquanto, tentar buscar por tracking_code se já tiver
        // TODO: Criar tabela de mapeamento MelhorEnvioOrder se necessário
        
        return null;
    }

    /**
     * Handle: Etiqueta gerada (pode ter código de rastreio)
     */
    protected function handleLabelGenerated(Order $order, array $data, WebhookLog $webhookLog): void
    {
        try {
            // Buscar dados completos do envio na API do Melhor Envio
            $orderId = $data['order_id'] ?? null;
            
            if ($orderId) {
                // Buscar dados completos do envio
                $shipmentData = $this->melhorEnvioService->getShipment($orderId);
                
                if ($shipmentData && isset($shipmentData['protocol'])) {
                    // Criar DTO a partir dos dados do Melhor Envio
                    $shippingDTO = ShippingData::fromMelhorEnvio([
                        'protocol' => $shipmentData['protocol'],
                        'tracking_code' => $shipmentData['tracking'] ?? null,
                        'carrier' => $shipmentData['service'] ?? null,
                        'status' => 'generated',
                        'order_id' => $orderId,
                    ], $order->order_number);

                    // Processar usando service (desacoplado)
                    $this->shippingService->processShipping($shippingDTO);

                    app(WebhookLogService::class)->addMetadata($webhookLog, [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'tracking_code' => $shippingDTO->trackingCode,
                        'action' => 'label_generated',
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error handling label generated', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'webhook_log_id' => $webhookLog->id
            ]);
        }
    }

    /**
     * Handle: Pedido postado (enviado)
     */
    protected function handleOrderPosted(Order $order, array $data, WebhookLog $webhookLog): void
    {
        try {
            $orderId = $data['order_id'] ?? null;
            
            if ($orderId) {
                // Buscar dados completos do envio
                $shipmentData = $this->melhorEnvioService->getShipment($orderId);
                
                if ($shipmentData) {
                    // Criar DTO
                    $shippingDTO = ShippingData::fromMelhorEnvio([
                        'protocol' => $shipmentData['protocol'] ?? null,
                        'tracking_code' => $shipmentData['tracking'] ?? null,
                        'carrier' => $shipmentData['service'] ?? null,
                        'status' => 'posted',
                        'posted_at' => $shipmentData['posted_at'] ?? now()->toIso8601String(),
                        'order_id' => $orderId,
                    ], $order->order_number);

                    // Processar usando service
                    $this->shippingService->processShipping($shippingDTO);

                    app(WebhookLogService::class)->addMetadata($webhookLog, [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'tracking_code' => $shippingDTO->trackingCode,
                        'action' => 'order_posted',
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error handling order posted', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'webhook_log_id' => $webhookLog->id
            ]);
        }
    }

    /**
     * Handle: Pedido entregue
     */
    protected function handleOrderDelivered(Order $order, array $data, WebhookLog $webhookLog): void
    {
        try {
            $orderId = $data['order_id'] ?? null;
            
            if ($orderId) {
                $shipmentData = $this->melhorEnvioService->getShipment($orderId);
                
                if ($shipmentData) {
                    $shippingDTO = ShippingData::fromMelhorEnvio([
                        'protocol' => $shipmentData['protocol'] ?? null,
                        'tracking_code' => $shipmentData['tracking'] ?? null,
                        'carrier' => $shipmentData['service'] ?? null,
                        'status' => 'delivered',
                        'delivered_at' => $shipmentData['delivered_at'] ?? now()->toIso8601String(),
                        'order_id' => $orderId,
                    ], $order->order_number);

                    $this->shippingService->processShipping($shippingDTO);

                    app(WebhookLogService::class)->addMetadata($webhookLog, [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'action' => 'order_delivered',
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error handling order delivered', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'webhook_log_id' => $webhookLog->id
            ]);
        }
    }

    /**
     * Handle: Pedido cancelado
     */
    protected function handleOrderCanceled(Order $order, array $data, WebhookLog $webhookLog): void
    {
        $order->update(['status' => 'cancelled']);
        
        Log::info('Order canceled via Melhor Envio webhook', [
            'order_id' => $order->id,
            'webhook_log_id' => $webhookLog->id
        ]);

        app(WebhookLogService::class)->addMetadata($webhookLog, [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'action' => 'order_canceled',
        ]);
    }
}

