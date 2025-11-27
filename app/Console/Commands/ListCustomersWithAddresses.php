<?php

namespace App\Console\Commands;

use App\Models\Customer;
use Illuminate\Console\Command;

class ListCustomersWithAddresses extends Command
{
    protected $signature = 'customers:list-with-addresses';
    protected $description = 'Listar clientes com endereços';

    public function handle()
    {
        $customers = Customer::with('addresses')->get();
        
        $this->info("Total de clientes: " . $customers->count());
        $this->info("");
        
        foreach ($customers as $customer) {
            $addressCount = $customer->addresses->count();
            
            if ($addressCount > 0) {
                $this->info("ID: {$customer->id}");
                $this->info("Nome: {$customer->name}");
                $this->info("Email: {$customer->email}");
                $this->info("Bling ID: " . ($customer->bling_id ?? 'não sincronizado'));
                $this->info("Endereços: {$addressCount}");
                
                foreach ($customer->addresses as $address) {
                    $types = [];
                    if ($address->is_shipping) $types[] = 'entrega';
                    if ($address->is_billing) $types[] = 'cobrança';
                    if (empty($types)) $types[] = 'adicional';
                    
                    $typeStr = '[' . implode(' + ', $types) . ']';
                    $this->line("  - {$typeStr} {$address->address}, {$address->number} - {$address->city}/{$address->state}");
                }
                
                $this->info("");
            }
        }
        
        return 0;
    }
}
