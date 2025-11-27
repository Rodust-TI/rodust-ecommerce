<?php

namespace App\Console\Commands;

use App\Models\Customer;
use Illuminate\Console\Command;

class CleanDuplicateAddressTypes extends Command
{
    protected $signature = 'addresses:clean-duplicates';
    protected $description = 'Remove tipos duplicados, mantendo apenas o primeiro de cada tipo (shipping/billing)';

    public function handle()
    {
        $this->info('Limpando tipos de endereços duplicados...');
        
        $customers = Customer::with('addresses')->get();
        $fixed = 0;
        
        foreach ($customers as $customer) {
            // Limpar duplicados de shipping
            $shippingAddresses = $customer->addresses()->where('type', 'shipping')->get();
            if ($shippingAddresses->count() > 1) {
                $this->warn("Cliente {$customer->id} tem {$shippingAddresses->count()} endereços shipping");
                
                // Manter apenas o primeiro
                $first = $shippingAddresses->first();
                $shippingAddresses->skip(1)->each(function($address) use ($first) {
                    $this->line("  Removendo tipo de endereço ID {$address->id}");
                    $address->update(['type' => null]);
                });
                
                $fixed++;
            }
            
            // Limpar duplicados de billing
            $billingAddresses = $customer->addresses()->where('type', 'billing')->get();
            if ($billingAddresses->count() > 1) {
                $this->warn("Cliente {$customer->id} tem {$billingAddresses->count()} endereços billing");
                
                // Manter apenas o primeiro
                $first = $billingAddresses->first();
                $billingAddresses->skip(1)->each(function($address) use ($first) {
                    $this->line("  Removendo tipo de endereço ID {$address->id}");
                    $address->update(['type' => null]);
                });
                
                $fixed++;
            }
        }
        
        if ($fixed > 0) {
            $this->info("\n✅ {$fixed} cliente(s) corrigido(s)!");
        } else {
            $this->info("\n✅ Nenhum duplicado encontrado!");
        }
        
        return 0;
    }
}
