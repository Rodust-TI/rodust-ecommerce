<?php

namespace App\Services\Shipping;

use App\Contracts\ShippingServiceInterface;
use App\DTOs\ShippingData;
use App\Models\Order;
use App\Mail\TrackingCodeMail;
use App\Services\Bling\BlingOrderService;
use App\Services\Bling\BlingStatusService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Service: Processamento de Envios/Rastreio
 * 
 * Processa dados de envio independente do fornecedor (Melhor Envio, Bling, etc)
 */
class ShippingService implements ShippingServiceInterface
{
    public function __construct(
        private BlingOrderService $blingOrder,
        private BlingStatusService $blingStatus
    ) {}
    /**
     * Buscar código de rastreio de um envio
     * 
     * Por enquanto retorna null - será implementado quando necessário
     * Cada fornecedor tem sua própria API para buscar rastreio
     */
    public function getTrackingData(string $erpShipmentId): ?ShippingData
    {
        // TODO: Implementar busca de rastreio específica por fornecedor
        // Por enquanto retorna null
        return null;
    }

    /**
     * Processar dados de envio recebidos via webhook
     */
    public function processShipping(ShippingData $shippingData): void
    {
        try {
            // Buscar pedido pelo número
            $order = Order::where('order_number', $shippingData->orderNumber)->first();

            if (!$order) {
                Log::warning('Order not found for shipping', [
                    'order_number' => $shippingData->orderNumber,
                ]);
                return;
            }

            $hasNewTrackingCode = false;
            $previousTrackingCode = $order->tracking_code;

            // Atualizar pedido com dados de envio
            $updateData = $shippingData->toArray();
            
            // Se tem código de rastreio novo, marcar para enviar email
            if ($shippingData->hasTrackingCode() && $order->tracking_code !== $shippingData->trackingCode) {
                $hasNewTrackingCode = true;
            }

            // Atualizar status se necessário
            if ($shippingData->status) {
                $updateData['status'] = $shippingData->status;
            }

            $order->update($updateData);

            // Se tem código de rastreio novo, atualizar status no Bling para "Enviado" (se existir)
            if ($hasNewTrackingCode && $order->bling_order_number) {
                try {
                    // Buscar ID do status "Enviado" no Bling
                    $enviadoStatusId = $this->blingStatus->findStatusIdByNames(['Enviado', 'Envio', 'Transporte']);
                    
                    if ($enviadoStatusId) {
                        $this->blingOrder->updateOrderStatus($order, $enviadoStatusId);
                        Log::info('Status atualizado no Bling para "Enviado" após obtenção de código de rastreio', [
                            'order_id' => $order->id,
                            'bling_order_number' => $order->bling_order_number,
                            'status_id' => $enviadoStatusId,
                            'tracking_code' => $shippingData->trackingCode,
                        ]);
                    } else {
                        Log::info('Status "Enviado" não encontrado no Bling - pulando atualização', [
                            'order_id' => $order->id,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Erro ao atualizar status no Bling após obtenção de código de rastreio', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('Shipping data processed', [
                'order_id' => $order->id,
                'tracking_code' => $shippingData->trackingCode,
                'carrier' => $shippingData->carrier,
                'status' => $shippingData->status,
            ]);

            // Enviar email se código de rastreio foi atualizado
            if ($hasNewTrackingCode) {
                try {
                    Mail::to($order->customer->email)->send(new TrackingCodeMail($order, $shippingData));
                    Log::info('Tracking code email sent', [
                        'order_id' => $order->id,
                        'customer_email' => $order->customer->email,
                        'tracking_code' => $shippingData->trackingCode,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error sending tracking code email', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Error processing shipping data', [
                'shipping_data' => $shippingData->toArray(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

