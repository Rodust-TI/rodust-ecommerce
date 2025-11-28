<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Services\BlingCustomerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestBlingCustomerSync extends Command
{
    protected $signature = 'bling:test-customer {customer_id?}';
    protected $description = 'Testa o envio de um cliente para o Bling e exibe a resposta';

    public function handle()
    {
        $customerId = $this->argument('customer_id');
        
        if ($customerId) {
            $customer = Customer::find($customerId);
            
            if (!$customer) {
                $this->error("Cliente #{$customerId} não encontrado!");
                return 1;
            }
        } else {
            // Pegar o último cliente cadastrado
            $customer = Customer::latest()->first();
            
            if (!$customer) {
                $this->error("Nenhum cliente encontrado no banco de dados!");
                return 1;
            }
        }

        $this->info("==================================================");
        $this->info("TESTE DE SINCRONIZAÇÃO DE CLIENTE COM BLING");
        $this->info("==================================================\n");
        
        $this->info("Cliente selecionado:");
        $this->line("  ID: {$customer->id}");
        $this->line("  Nome: {$customer->name}");
        $this->line("  Email: {$customer->email}");
        $this->line("  CPF: {$customer->cpf}");
        $this->line("  CNPJ: {$customer->cnpj}");
        $this->line("  Bling ID: " . ($customer->bling_id ?? 'Não sincronizado'));
        
        $this->newLine();
        $this->info("Iniciando envio para o Bling...\n");

        // Ativar logs detalhados
        Log::info('=== TESTE MANUAL DE SINCRONIZAÇÃO BLING ===', [
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
        ]);

        $blingService = new BlingCustomerService();
        
        try {
            if ($customer->bling_id) {
                $this->warn("Cliente já possui Bling ID. Tentando atualizar...");
                $result = $blingService->updateCustomer($customer);
            } else {
                $this->info("Cliente não possui Bling ID. Criando novo...");
                $result = $blingService->createCustomer($customer);
            }

            $this->newLine();
            
            if ($result) {
                $this->info("✅ SUCESSO! Resposta do Bling:");
                $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                
                if (isset($result['id'])) {
                    $customer->bling_id = $result['id'];
                    $customer->save();
                    $this->info("\n✅ Bling ID salvo no banco: {$result['id']}");
                }
                
                return 0;
            } else {
                $this->error("❌ ERRO! Não foi possível sincronizar o cliente.");
                $this->newLine();
                $this->warn("Verifique os logs do Laravel para mais detalhes:");
                $this->line("  tail -f storage/logs/laravel.log");
                $this->newLine();
                $this->warn("Possíveis causas:");
                $this->line("  1. Token de acesso do Bling expirado ou inválido");
                $this->line("  2. Dados do cliente inválidos (CPF/CNPJ, endereço, etc)");
                $this->line("  3. Erro de comunicação com a API do Bling");
                $this->line("  4. Falta de autenticação OAuth (acesse http://localhost:8000/bling)");
                
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->error("❌ EXCEÇÃO: " . $e->getMessage());
            $this->line($e->getTraceAsString());
            
            return 1;
        }
    }
}
