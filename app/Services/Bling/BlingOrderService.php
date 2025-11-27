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
        private BlingV3Adapter $bling
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

            // Criar pedido no Bling
            $blingOrderNumber = $this->bling->createOrder($orderData);

            if (!$blingOrderNumber) {
                throw new \Exception('Bling não retornou número do pedido');
            }

            Log::info('Pedido criado no Bling com sucesso', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'bling_order_number' => $blingOrderNumber
            ]);

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
     * Formatar dados do pedido para o formato esperado pelo Bling
     */
    protected function formatOrderData(Order $order): array
    {
        return [
            'order_number' => $order->order_number, // Número do pedido na loja
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
            // TODO: Implementar busca de pedido no Bling quando necessário
            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao buscar pedido no Bling', [
                'bling_order_number' => $blingOrderNumber,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
