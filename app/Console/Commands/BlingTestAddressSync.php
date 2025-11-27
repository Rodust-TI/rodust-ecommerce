<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Services\BlingCustomerService;
use Illuminate\Console\Command;

class BlingTestAddressSync extends Command
{
    protected $signature = 'bling:test-address-sync {customer_id}';
    protected $description = 'Testar sincronização de endereços com Bling';

    public function handle(BlingCustomerService $blingService)
    {
        $customerId = $this->argument('customer_id');
        
        $this->info("Buscando cliente ID: {$customerId}");
        
        $customer = Customer::find($customerId);
        
        if (!$customer) {
            $this->error("Cliente não encontrado!");
            return 1;
        }
        
        $this->info("Cliente: {$customer->name}");
        $this->info("Email: {$customer->email}");
        $this->info("Bling ID: " . ($customer->bling_id ?? 'NÃO SINCRONIZADO'));
        
        if (!$customer->bling_id) {
            $this->error("Cliente não possui bling_id. Execute a sincronização do cliente primeiro.");
            return 1;
        }
        
        $this->info("\n--- ENDEREÇOS NO BANCO ---");
        
        $shippingAddress = $customer->addresses()->where('type', 'shipping')->first();
        $billingAddress = $customer->addresses()->where('type', 'billing')->first();
        
        if ($shippingAddress) {
            $this->info("\n[SHIPPING - Endereço de Entrega]");
            $this->info("  Rua: {$shippingAddress->address}");
            $this->info("  Número: {$shippingAddress->number}");
            $this->info("  Complemento: " . ($shippingAddress->complement ?? 'N/A'));
            $this->info("  Bairro: {$shippingAddress->neighborhood}");
            $this->info("  Cidade: {$shippingAddress->city}");
            $this->info("  Estado: {$shippingAddress->state}");
            $this->info("  CEP: {$shippingAddress->zipcode}");
        } else {
            $this->warn("  Nenhum endereço de entrega cadastrado");
        }
        
        if ($billingAddress) {
            $this->info("\n[BILLING - Endereço de Cobrança]");
            $this->info("  Rua: {$billingAddress->address}");
            $this->info("  Número: {$billingAddress->number}");
            $this->info("  Complemento: " . ($billingAddress->complement ?? 'N/A'));
            $this->info("  Bairro: {$billingAddress->neighborhood}");
            $this->info("  Cidade: {$billingAddress->city}");
            $this->info("  Estado: {$billingAddress->state}");
            $this->info("  CEP: {$billingAddress->zipcode}");
        } else {
            $this->warn("  Nenhum endereço de cobrança cadastrado");
        }
        
        $this->info("\n--- INICIANDO SINCRONIZAÇÃO ---");
        
        try {
            $result = $blingService->syncAddresses($customer);
            
            if ($result) {
                $this->info("\n✅ Sincronização concluída com SUCESSO!");
                $this->info("Os endereços foram enviados para o Bling.");
            } else {
                $this->error("\n❌ Sincronização FALHOU!");
                $this->error("Verifique os logs para detalhes do erro.");
            }
            
        } catch (\Exception $e) {
            $this->error("\n❌ ERRO durante sincronização:");
            $this->error($e->getMessage());
            $this->error("\nStack trace:");
            $this->error($e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
}
