<?php

namespace App\Jobs;

use App\Models\Order;
use App\Contracts\ERPInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncOrderToBling implements ShouldQueue
{
    use Queueable;

    protected Order $order;

    /**
     * Create a new job instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order->load(['customer', 'items']);
    }

    /**
     * Execute the job.
     */
    public function handle(ERPInterface $erp): void
    {
        try {
            // Recarregar dados do pedido para garantir informações atualizadas
            $this->order->refresh();
            
            $orderData = [
                'order_number' => $this->order->order_number,
                'status' => $this->order->status,
                'paid_at' => $this->order->paid_at ? $this->order->paid_at->toIso8601String() : null,
                'customer' => [
                    'id' => $this->order->customer->bling_id ?? null,
                    'name' => $this->order->customer->name,
                    'email' => $this->order->customer->email,
                    'phone' => $this->order->customer->phone,
                ],
                'items' => $this->order->items->map(function ($item) {
                    return [
                        'bling_id' => $item->product->bling_id ?? null,
                        'sku' => $item->product_sku,
                        'name' => $item->product_name,
                        'quantity' => $item->quantity,
                        'price' => $item->unit_price,
                    ];
                })->toArray(),
                'shipping' => $this->order->shipping ?? 0,
                'discount' => $this->order->discount ?? 0,
                'payment_method' => $this->order->payment_method,
                'payment_fee' => $this->order->payment_fee ?? 0,
                'net_amount' => $this->order->net_amount ?? $this->order->total,
                'installments' => $this->order->installments ?? 1,
                'shipping_address' => $this->order->shipping_address ?? [],
                'shipping_method_name' => $this->order->shipping_method_name,
                'shipping_carrier' => $this->order->shipping_carrier,
            ];

            $erpOrderNumber = $erp->createOrder($orderData);

            if ($erpOrderNumber) {
                $this->order->update([
                    'bling_order_number' => $erpOrderNumber,
                    'bling_synced_at' => now(),
                    'last_bling_sync' => now(),
                ]);

                Log::info("Order {$this->order->id} synced to ERP with number {$erpOrderNumber}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to sync order {$this->order->id} to ERP: " . $e->getMessage());
            throw $e;
        }
    }
}
