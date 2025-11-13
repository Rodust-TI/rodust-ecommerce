<?php

namespace App\Jobs;

use App\Models\Product;
use App\Contracts\ERPInterface;
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
    public function handle(ERPInterface $erp): void
    {
        try {
            $productData = [
                'sku' => $this->product->sku,
                'name' => $this->product->name,
                'description' => $this->product->description,
                'price' => $this->product->price,
                'cost' => $this->product->cost,
                'stock' => $this->product->stock,
                'active' => $this->product->active,
            ];

            // Se já tem bling_id, atualiza. Senão, cria.
            if ($this->product->bling_id) {
                $success = $erp->updateProduct($this->product->bling_id, $productData);
                
                if ($success) {
                    $this->product->update(['bling_synced_at' => now()]);
                    Log::info("Product {$this->product->id} updated in ERP");
                }
            } else {
                $erpId = $erp->createProduct($productData);

                if ($erpId) {
                    $this->product->update([
                        'bling_id' => $erpId,
                        'bling_synced_at' => now(),
                    ]);
                    Log::info("Product {$this->product->id} created in ERP with ID {$erpId}");
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to sync product {$this->product->id} to ERP: " . $e->getMessage());
            throw $e;
        }
    }
}
