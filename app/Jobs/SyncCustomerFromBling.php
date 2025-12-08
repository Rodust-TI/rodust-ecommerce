<?php

namespace App\Jobs;

use App\Mail\AccountRecoveryMail;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SyncCustomerFromBling implements ShouldQueue
{
    use Queueable;

    public $customerId;
    public $customerTypeId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $customerId, int $customerTypeId)
    {
        $this->customerId = $customerId;
        $this->customerTypeId = $customerTypeId;
        
        // Limitar a 3 requisições por segundo (delay de ~350ms entre jobs)
        $this->delay(now()->addMilliseconds(350));
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $baseUrl = config('services.bling.base_url', 'https://api.bling.com.br/Api/v3');
            
            // Buscar detalhes do cliente
            $response = Http::withToken(Cache::get('bling_access_token'))
                ->get($baseUrl . '/contatos/' . $this->customerId);

            if ($response->failed()) {
                Log::error("Erro ao buscar cliente {$this->customerId} do Bling", [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return;
            }

            $customerDetail = $response->json()['data'] ?? null;
            
            if (!$customerDetail) {
                return;
            }

            // Verificar se tem o tipo "Cliente ecommerce"
            $hasEcommerceType = false;
            if (isset($customerDetail['tiposContato'])) {
                foreach ($customerDetail['tiposContato'] as $tipo) {
                    if ($tipo['id'] == $this->customerTypeId) {
                        $hasEcommerceType = true;
                        break;
                    }
                }
            }

            if (!$hasEcommerceType) {
                return;
            }

            // Sincronizar cliente
            $this->syncCustomer($customerDetail);

        } catch (\Exception $e) {
            Log::error("Erro no job de sincronização do cliente {$this->customerId}", [
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function syncCustomer(array $blingCustomer): void
    {
        $email = $blingCustomer['email'] ?? null;
        
        if (empty($email)) {
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

        if ($customer) {
            // Atualizar cliente existente (não altera senha)
            $customer->update($customerData);
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
            
            // Enviar email de boas-vindas com link de criação de senha
            $resetUrl = config('urls.wordpress.external', 'https://localhost:8443') . '/redefinir-senha?token=' . $resetToken;
            
            try {
                Mail::to($email)->send(new AccountRecoveryMail($customer, $resetUrl));
                Log::info("Email de recuperação enviado para {$email}");
            } catch (\Exception $e) {
                Log::error("Erro ao enviar email de recuperação para {$email}", [
                    'error' => $e->getMessage()
                ]);
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
}
