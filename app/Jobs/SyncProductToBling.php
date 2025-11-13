<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\BlingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncProductToBling implements ShouldQueue
{
    use Queueable;

    protected Product $product;

    /**
     * Create a new job instance.
     */
    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    /**
     * Execute the job.
     */
    public function handle(BlingService $bling): void
    {
        try {
            $productData = [
                'sku' => $this->product->sku,
                'name' => $this->product->name,
                'price' => $this->product->price,
                'stock' => $this->product->stock,
            ];

            $blingId = $bling->syncProduct($productData);

            if ($blingId) {
                $this->product->update([
                    'bling_id' => $blingId,
                    'bling_synced_at' => now(),
                ]);

                Log::info("Product {$this->product->id} synced to Bling with ID {$blingId}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to sync product {$this->product->id} to Bling: " . $e->getMessage());
            throw $e;
        }
    }
}
