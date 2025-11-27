<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Service: Criação de Pedidos
 * Responsabilidade: Criar pedido a partir dos dados do checkout
 */
class OrderCreationService
{
    /**
     * Criar pedido a partir dos dados do request
     */
    public function createFromRequest(Request $request, Customer $customer, string $paymentMethod): Order
    {
        // Calcular valores
        $subtotal = $this->calculateSubtotal($request->items);
        $shippingCost = (float) $request->shipping_cost;
        $total = $subtotal + $shippingCost;

        Log::info('OrderCreationService - Calculando valores', [
            'items' => $request->items,
            'subtotal' => $subtotal,
            'shipping_cost' => $shippingCost,
            'total' => $total
        ]);

        // Criar pedido
        $order = Order::create([
            'customer_id' => $customer->id,
            'status' => 'pending',
            'payment_method' => $paymentMethod,
            'payment_status' => 'pending',
            'subtotal' => $subtotal,
            'shipping' => $shippingCost,
            'total' => $total,
            'shipping_address' => $request->shipping_address,
            'shipping_method_name' => $request->shipping_method['name'] ?? null,
            'notes' => $this->formatShippingAddressNotes($request->shipping_address),
        ]);

        Log::info('OrderCreationService - Pedido criado', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'subtotal' => $order->subtotal,
            'shipping' => $order->shipping,
            'total' => $order->total
        ]);

        // Criar itens do pedido
        $this->createOrderItems($order, $request->items);

        return $order;
    }

    /**
     * Calcular subtotal dos itens
     */
    private function calculateSubtotal(array $items): float
    {
        $subtotal = 0;

        foreach ($items as $item) {
            $productId = $item['product_id'] ?? $item['id'] ?? null;
            
            if (!$productId) {
                Log::warning('Item sem product_id', ['item' => $item]);
                continue;
            }

            $product = Product::find($productId);
            
            if (!$product) {
                Log::warning('Produto não encontrado no calculateSubtotal', ['product_id' => $productId]);
                continue;
            }

            $price = (float) ($item['price'] ?? $product->price);
            $quantity = (int) ($item['quantity'] ?? 0);
            
            $subtotal += $price * $quantity;
        }

        return $subtotal;
    }

    /**
     * Criar itens do pedido
     */
    private function createOrderItems(Order $order, array $items): void
    {
        foreach ($items as $item) {
            // Buscar dados do produto
            $product = Product::find($item['product_id'] ?? $item['id']);
            
            if (!$product) {
                Log::warning('Produto não encontrado', ['item' => $item]);
                continue;
            }

            $quantity = (int) $item['quantity'];
            $unitPrice = (float) ($item['price'] ?? $product->price);
            $totalPrice = $unitPrice * $quantity;

            $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
            ]);
        }
    }

    /**
     * Formatar endereço de entrega para campo notes
     */
    private function formatShippingAddressNotes(array $address): string
    {
        $parts = [];
        
        if (!empty($address['street'])) {
            $parts[] = $address['street'];
        }
        if (!empty($address['number'])) {
            $parts[] = 'Nº ' . $address['number'];
        }
        if (!empty($address['complement'])) {
            $parts[] = $address['complement'];
        }
        if (!empty($address['neighborhood'])) {
            $parts[] = $address['neighborhood'];
        }
        if (!empty($address['city']) && !empty($address['state'])) {
            $parts[] = $address['city'] . '/' . $address['state'];
        }
        if (!empty($address['postal_code'])) {
            $parts[] = 'CEP: ' . $address['postal_code'];
        }
        
        return implode(' - ', $parts);
    }
}
