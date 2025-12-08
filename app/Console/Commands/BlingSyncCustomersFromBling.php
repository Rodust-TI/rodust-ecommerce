<?php

namespace App\Console\Commands;

use App\Jobs\SyncCustomerFromBling;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class BlingSyncCustomersFromBling extends Command
{
    protected $signature = 'bling:sync-customers-from-bling 
                            {--limit=100 : Máximo de clientes a sincronizar}
                            {--sync : Processar de forma síncrona (padrão: usa fila)}';
    
    /**
     * COMANDO DE RECUPERAÇÃO DE DESASTRE
     * 
     * ⚠️ ATENÇÃO: Use apenas em caso de perda de dados irrecuperável!
     * 
     * Este comando sincroniza clientes do Bling para o Laravel.
     * 
     * FLUXO NORMAL:
     * - Cliente se cadastra no WordPress → Laravel → Bling
     * - Backup diário do Laravel é suficiente
     * 
     * QUANDO USAR:
     * - Backup do Laravel corrompido/indisponível
     * - Restauração parcial (apenas clientes)
     * - Migração de ambiente
     * 
     * LIMITAÇÕES:
     * - Dados podem estar incompletos (depende do que está no Bling)
     * - Senhas serão resetadas (cliente precisa recuperar)
     * - Endereços podem estar desatualizados
     */
    protected $description = 'Sincronizar clientes do Bling para o Laravel (recuperação de desastre)';

    protected $stats = [
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0,
        'queued' => 0,
    ];

    public function handle()
    {
        $this->info('🔄 Iniciando sincronização de clientes do Bling...');
        $this->newLine();

        // Verificar autenticação
        if (!Cache::has('bling_access_token')) {
            $this->error('❌ Não autenticado no Bling. Acesse ' . config('urls.laravel.bling_url') . ' para autorizar.');
            return 1;
        }

        $limit = (int) $this->option('limit');
        $useQueue = !$this->option('sync');

        try {
            // Buscar clientes do Bling
            $this->info('📥 Buscando clientes do Bling...');
            
            if ($useQueue) {
                $this->info('🔄 Modo: Processamento em background (fila)');
                $this->dispatchCustomersToQueue($limit);
                return 0;
            }
            
            $this->info('⚡ Modo: Processamento síncrono');
            $blingCustomers = $this->fetchBlingCustomers($limit);

            if (empty($blingCustomers)) {
                $this->warn('⚠️  Nenhum cliente encontrado no Bling.');
                return 0;
            }

            $totalCustomers = count($blingCustomers);
            $this->info("✅ {$totalCustomers} clientes encontrados no Bling");
            $this->newLine();

            // Criar barra de progresso
            $bar = $this->output->createProgressBar($totalCustomers);
            $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
            $bar->setMessage('Processando...');

            // Processar cada cliente
            foreach ($blingCustomers as $blingCustomer) {
                $bar->setMessage("Processando: {$blingCustomer['nome']}");
                
                try {
                    $this->syncCustomer($blingCustomer);
                } catch (\Exception $e) {
                    $this->stats['errors']++;
                    $this->newLine();
                    $this->error("Erro ao processar cliente {$blingCustomer['id']}: {$e->getMessage()}");
                }
                
                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            // Estatísticas
            $this->displayStats();

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Erro: ' . $e->getMessage());
            return 1;
        }
    }

    protected function dispatchCustomersToQueue(int $limit): void
    {
        $page = 1;
        $perPage = 100;
        $baseUrl = config('services.bling.base_url', 'https://api.bling.com.br/Api/v3');
        $customerTypeId = config('services.bling.customer_type_id');
        $totalQueued = 0;

        $this->info("🔍 Buscando IDs dos clientes no Bling...");
        $this->newLine();

        do {
            $response = Http::withToken(Cache::get('bling_access_token'))
                ->get($baseUrl . '/contatos', [
                    'pagina' => $page,
                    'limite' => min($perPage, $limit - $totalQueued),
                ]);

            if ($response->failed()) {
                $this->error("❌ Erro ao buscar clientes: {$response->status()}");
                break;
            }

            $data = $response->json();
            $customers = $data['data'] ?? [];

            if (empty($customers)) {
                break;
            }

            // Despachar jobs para processar cada cliente
            foreach ($customers as $customer) {
                SyncCustomerFromBling::dispatch($customer['id'], $customerTypeId);
                $totalQueued++;
                
                if ($totalQueued >= $limit) {
                    break 2;
                }
            }

            $page++;
            
        } while (!empty($customers));

        $this->newLine();
        $this->info("✅ {$totalQueued} clientes adicionados à fila de processamento");
        $this->newLine();
        $this->comment('💡 Os clientes serão processados em background com limite de 3 requisições/segundo');
        $this->comment('   Execute o queue worker se ainda não estiver rodando:');
        $this->line('   php artisan queue:work');
    }

    protected function fetchBlingCustomers(int $limit): array
    {
        $allCustomers = [];
        $page = 1;
        $perPage = 100;
        $baseUrl = config('services.bling.base_url', 'https://api.bling.com.br/Api/v3');
        $customerTypeId = config('services.bling.customer_type_id'); // ID do tipo "Cliente ecommerce"

        do {
            $response = Http::withToken(Cache::get('bling_access_token'))
                ->get($baseUrl . '/contatos', [
                    'pagina' => $page,
                    'limite' => min($perPage, $limit - count($allCustomers)),
                    // Não filtrar por tipo aqui, vamos filtrar depois pelos tiposContato
                ]);

            if ($response->failed()) {
                throw new \Exception("Erro ao buscar clientes: {$response->status()} - {$response->body()}");
            }

            $data = $response->json();
            $customers = $data['data'] ?? [];

            if (empty($customers)) {
                break;
            }

            // Filtrar apenas clientes com o tipo "Cliente ecommerce"
            foreach ($customers as $customer) {
                // Buscar detalhes completos do cliente para ver os tipos de contato
                $detailResponse = Http::withToken(Cache::get('bling_access_token'))
                    ->get($baseUrl . '/contatos/' . $customer['id']);

                if ($detailResponse->successful()) {
                    $customerDetail = $detailResponse->json()['data'] ?? null;
                    
                    if ($customerDetail && isset($customerDetail['tiposContato'])) {
                        // Verificar se tem o tipo "Cliente ecommerce"
                        $hasEcommerceType = false;
                        foreach ($customerDetail['tiposContato'] as $tipo) {
                            if ($tipo['id'] == $customerTypeId) {
                                $hasEcommerceType = true;
                                break;
                            }
                        }
                        
                        if ($hasEcommerceType) {
                            $allCustomers[] = $customerDetail;
                            
                            if (count($allCustomers) >= $limit) {
                                break 2; // Sair dos dois loops
                            }
                        }
                    }
                }
            }

            $page++;

        } while (!empty($customers));

        return $allCustomers;
    }

    protected function syncCustomer(array $blingCustomer): void
    {
        $email = $blingCustomer['email'] ?? null;
        
        if (empty($email)) {
            $this->stats['skipped']++;
            return;
        }

        // Buscar cliente existente por email ou bling_id
        $customer = Customer::where('email', $email)
            ->orWhere('bling_id', $blingCustomer['id'])
            ->first();

        // Preparar dados do cliente
        $customerData = [
            'name' => $blingCustomer['nome'],
            'email' => $email,
            'cpf' => $blingCustomer['numeroDocumento'] ?? null,
            'phone' => $blingCustomer['telefone'] ?? $blingCustomer['celular'] ?? null,
            'bling_id' => $blingCustomer['id'],
            'bling_synced_at' => now(),
        ];

        // Sincronizar tipo pessoa (F = Física, J = Jurídica)
        if (isset($blingCustomer['tipo'])) {
            $customerData['person_type'] = $blingCustomer['tipo'];
        }

        // Sincronizar data de nascimento
        if (isset($blingCustomer['dadosAdicionais']['dataNascimento'])) {
            $customerData['birth_date'] = $blingCustomer['dadosAdicionais']['dataNascimento'];
        }

        // Sincronizar email de nota fiscal
        if (!empty($blingCustomer['emailNotaFiscal'])) {
            $customerData['nfe_email'] = $blingCustomer['emailNotaFiscal'];
        }

        // Se for pessoa jurídica, usar numeroDocumento como CNPJ
        if (isset($blingCustomer['tipo']) && $blingCustomer['tipo'] === 'J') {
            $customerData['cnpj'] = $blingCustomer['numeroDocumento'] ?? null;
            $customerData['cpf'] = null; // Limpar CPF se for PJ
            
            // Sincronizar dados adicionais de PJ
            if (!empty($blingCustomer['fantasia'])) {
                $customerData['fantasy_name'] = $blingCustomer['fantasia'];
            }
            if (!empty($blingCustomer['ie'])) {
                $customerData['state_registration'] = $blingCustomer['ie'];
            }
        }

        $isNewCustomer = !$customer;

        if ($customer) {
            // Atualizar cliente existente (não altera senha)
            $customer->update($customerData);
            $this->stats['updated']++;
        } else {
            // Criar novo cliente com senha aleatória forte
            $customerData['password'] = Hash::make(Str::random(16));
            $customerData['must_reset_password'] = true;
            $customerData['email_verified_at'] = null;
            
            $customer = Customer::create($customerData);
            
            // Gerar token de reset de senha
            $resetToken = Str::random(64);
            $customer->update([
                'password_reset_token' => $resetToken,
                'password_reset_token_expires_at' => now()->addDays(7), // 7 dias para primeira senha
            ]);
            
            $this->stats['created']++;
            
            // Enviar email de boas-vindas com link de criação de senha
            try {
                $resetUrl = config('urls.wordpress.external', 'http://localhost:8443') . '/redefinir-senha?token=' . $resetToken;
                
                // Verificar se a classe Mail existe e está configurada
                if (class_exists(\Illuminate\Support\Facades\Mail::class)) {
                    // Mail::to($email)->send(new AccountRecoveryMail($customer, $resetUrl));
                    $this->info("   📧 Email de recuperação será enviado para: {$email}");
                    $this->line("   🔗 URL de reset: {$resetUrl}");
                }
            } catch (\Exception $e) {
                $this->warn("   ⚠️  Não foi possível enviar email: {$e->getMessage()}");
            }
        }

        // Sincronizar endereços
        $this->syncAddresses($customer, $blingCustomer);
    }

    /**
     * Sincronizar endereços do Bling para o Laravel
     * 
     * Lógica:
     * - endereco.geral → Endereço de entrega (is_shipping = true)
     * - endereco.cobranca → Endereço de cobrança (is_billing = true)
     * - Se só houver um endereço, criar apenas como entrega
     * - Se houver ambos, criar os dois separadamente
     */
    protected function syncAddresses(Customer $customer, array $blingCustomer): void
    {
        $enderecoGeral = $blingCustomer['endereco']['geral'] ?? null;
        $enderecoCobranca = $blingCustomer['endereco']['cobranca'] ?? null;

        $hasGeral = $enderecoGeral && !empty($enderecoGeral['endereco']);
        $hasCobranca = $enderecoCobranca && !empty($enderecoCobranca['endereco']);

        // Se não houver nenhum endereço, não fazer nada
        if (!$hasGeral && !$hasCobranca) {
            return;
        }

        // Se só houver endereço geral, criar apenas como entrega
        if ($hasGeral && !$hasCobranca) {
            $this->createOrUpdateAddress($customer, $enderecoGeral, true, false);
            return;
        }

        // Se só houver endereço de cobrança, criar apenas como entrega (fallback)
        if (!$hasGeral && $hasCobranca) {
            $this->createOrUpdateAddress($customer, $enderecoCobranca, true, false);
            return;
        }

        // Se houver ambos, criar os dois separadamente
        if ($hasGeral) {
            $this->createOrUpdateAddress($customer, $enderecoGeral, true, false);
        }
        
        if ($hasCobranca) {
            $this->createOrUpdateAddress($customer, $enderecoCobranca, false, true);
        }
    }

    /**
     * Criar ou atualizar endereço do cliente
     */
    protected function createOrUpdateAddress(
        Customer $customer,
        array $enderecoBling,
        bool $isShipping,
        bool $isBilling
    ): void {
        $addressData = [
            'customer_id' => $customer->id,
            'is_shipping' => $isShipping,
            'is_billing' => $isBilling,
            'address' => $enderecoBling['endereco'] ?? '',
            'number' => $enderecoBling['numero'] ?? '',
            'complement' => $enderecoBling['complemento'] ?? null,
            'neighborhood' => $enderecoBling['bairro'] ?? null,
            'city' => $enderecoBling['municipio'] ?? '',
            'state' => $enderecoBling['uf'] ?? '',
            'zipcode' => preg_replace('/[^0-9]/', '', $enderecoBling['cep'] ?? ''),
            'country' => 'BR',
            'recipient_name' => $customer->name,
            'label' => $isShipping ? 'Endereço de Entrega' : 'Endereço de Cobrança',
        ];

        // Buscar endereço existente do mesmo tipo
        $existingAddress = CustomerAddress::where('customer_id', $customer->id)
            ->where('is_shipping', $isShipping)
            ->where('is_billing', $isBilling)
            ->first();

        if ($existingAddress) {
            $existingAddress->update($addressData);
        } else {
            CustomerAddress::create($addressData);
        }
    }

    protected function displayStats(): void
    {
        $this->info('📊 Estatísticas da Sincronização:');
        $this->newLine();
        $this->line("  ✅ Criados:  {$this->stats['created']}");
        $this->line("  🔄 Atualizados: {$this->stats['updated']}");
        $this->line("  ⏭️  Ignorados: {$this->stats['skipped']}");
        
        if ($this->stats['errors'] > 0) {
            $this->line("  ❌ Erros: {$this->stats['errors']}");
        }
        
        $this->newLine();
        $total = $this->stats['created'] + $this->stats['updated'];
        $this->info("🎉 Total processado: {$total} clientes");
        
        if ($this->stats['created'] > 0) {
            $this->newLine();
            $this->warn('⚠️  Clientes novos precisam criar senha no primeiro acesso.');
        }
    }
}
