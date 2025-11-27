<?php

namespace App\Jobs;

use App\Models\Product;
use App\Contracts\ERPInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SyncProductDetailFromBling implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;
    public $backoff = [10, 30, 60]; // Retry delays in seconds

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $blingId
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(ERPInterface $erp): void
    {
        try {
            Log::info("Sincronizando detalhes do produto Bling ID: {$this->blingId}");

            // Buscar detalhes completos do Bling
            $productData = $erp->getProduct($this->blingId);

            if (!$productData) {
                Log::warning("Produto {$this->blingId} não encontrado no Bling");
                return;
            }

            // Criar ou atualizar no Laravel
            $product = Product::updateOrCreate(
                ['bling_id' => $this->blingId],
                $productData
            );

            Log::info("Produto {$product->name} sincronizado com sucesso", [
                'id' => $product->id,
                'bling_id' => $this->blingId,
                'has_dimensions' => $product->hasShippingDimensions()
            ]);

        } catch (\Exception $e) {
            Log::error("Erro ao sincronizar produto {$this->blingId}: " . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            // Relanjar exceção para retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job falhou definitivamente para produto Bling ID {$this->blingId}", [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }
}
