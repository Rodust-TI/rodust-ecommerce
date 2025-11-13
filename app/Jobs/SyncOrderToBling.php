<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\BlingService;
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
    public function handle(BlingService $bling): void
    {
        try {
            $orderData = [
                'customer' => [
                    'name' => $this->order->customer->name,
                    'email' => $this->order->customer->email,
                    'phone' => $this->order->customer->phone,
                ],
                'items' => $this->order->items->map(function ($item) {
                    return [
                        'sku' => $item->product_sku,
                        'name' => $item->product_name,
                        'quantity' => $item->quantity,
                        'price' => $item->unit_price,
                    ];
                })->toArray(),
                'shipping' => $this->order->shipping ?? 0,
                'discount' => $this->order->discount ?? 0,
            ];

            $blingOrderNumber = $bling->createOrder($orderData);

            if ($blingOrderNumber) {
                $this->order->update([
                    'bling_id' => $blingOrderNumber,
                    'bling_synced_at' => now(),
                ]);

                Log::info("Order {$this->order->id} synced to Bling with number {$blingOrderNumber}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to sync order {$this->order->id} to Bling: " . $e->getMessage());
            throw $e;
        }
    }
}
