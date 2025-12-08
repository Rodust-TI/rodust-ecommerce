<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Services\BlingCustomerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncCustomerToBling implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted
     */
    public int $tries = 3;

    /**
     * Number of seconds to wait before retrying
     */
    public int $backoff = 60;

    /**
     * The customer instance
     */
    public Customer $customer;

    /**
     * Create a new job instance
     */
    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    /**
     * Execute the job
     */
    public function handle(BlingCustomerService $blingService): void
    {
        // CRÍTICO: Recarregar customer com addresses para garantir dados atualizados
        $this->customer->refresh();
        $this->customer->load('addresses');
        
        Log::info('Starting customer sync to Bling', [
            'customer_id' => $this->customer->id,
            'email' => $this->customer->email,
            'phone' => $this->customer->phone,
            'addresses_count' => $this->customer->addresses->count(),
            'attempt' => $this->attempts()
        ]);

        // Verificar se já existe no Bling
        if ($this->customer->bling_id) {
            $result = $blingService->updateCustomer($this->customer);
        } else {
            // Primeiro verificar se já existe por email
            $existing = $blingService->searchCustomerByEmail($this->customer->email);
            
            if ($existing) {
                // Atualizar nosso registro com o ID do Bling
                $this->customer->update([
                    'bling_id' => $existing['id'],
                    'bling_synced_at' => now()
                ]);
                
                Log::info('Customer already exists in Bling, linked', [
                    'customer_id' => $this->customer->id,
                    'bling_id' => $existing['id']
                ]);
                
                return;
            }
            
            $result = $blingService->createCustomer($this->customer);
        }

        if ($result) {
            // Atualizar customer com ID do Bling
            $this->customer->update([
                'bling_id' => $result['id'] ?? null,
                'bling_synced_at' => now()
            ]);

            Log::info('Customer synced to Bling successfully', [
                'customer_id' => $this->customer->id,
                'bling_id' => $result['id'] ?? null
            ]);
        } else {
            Log::warning('Failed to sync customer to Bling', [
                'customer_id' => $this->customer->id,
                'attempt' => $this->attempts()
            ]);

            // Se falhou após todas as tentativas, registrar erro
            if ($this->attempts() >= $this->tries) {
                Log::error('Customer sync to Bling failed after all attempts', [
                    'customer_id' => $this->customer->id
                ]);
            }
        }
    }

    /**
     * Handle a job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Customer sync to Bling job failed', [
            'customer_id' => $this->customer->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
