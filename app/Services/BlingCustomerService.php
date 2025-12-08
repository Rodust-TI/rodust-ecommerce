<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BlingCustomerService
{
    private string $baseUrl;
    
    public function __construct()
    {
        $this->baseUrl = 'https://www.bling.com.br/Api/v3';
    }

    /**
     * Get access token from cache or refresh
     */
    private function getAccessToken(): ?string
    {
        $token = Cache::get('bling_access_token');
        
        if ($token) {
            return $token;
        }
        
        // Tentar renovar com refresh token
        $refreshToken = Cache::get('bling_refresh_token');
        
        if (!$refreshToken) {
            Log::warning('Bling: No access token or refresh token available');
            return null;
        }
        
        Log::info('Bling: Access token expired, attempting refresh');
        
        try {
            $response = Http::asForm()
                ->withBasicAuth(
                    config('services.bling.client_id'),
                    config('services.bling.client_secret')
                )
                ->post("{$this->baseUrl}/oauth/token", [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Salvar novos tokens
                Cache::put('bling_access_token', $data['access_token'], now()->addSeconds($data['expires_in'] - 60));
                
                if (isset($data['refresh_token'])) {
                    Cache::put('bling_refresh_token', $data['refresh_token'], now()->addDays(30));
                }
                
                Log::info('Bling: Token refreshed automatically');
                
                return $data['access_token'];
            }
            
            Log::error('Bling: Token refresh failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            // Limpar tokens inválidos
            Cache::forget('bling_access_token');
            Cache::forget('bling_refresh_token');
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Bling: Token refresh exception', [
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Create customer in Bling
     * 
     * @param Customer $customer
     * @return array|null
     */
    public function createCustomer(Customer $customer): ?array
    {
        $token = $this->getAccessToken();
        
        if (!$token) {
            Log::error('Cannot sync customer to Bling: no access token', [
                'customer_id' => $customer->id
            ]);
            return null;
        }

        try {
            $payload = $this->prepareCustomerPayload($customer);
            
            Log::info('Sending customer to Bling', [
                'customer_id' => $customer->id,
                'email' => $customer->email,
                'payload' => $payload // LOG DO PAYLOAD COMPLETO
            ]);
            
            $response = Http::withToken($token)
                ->timeout(30)
                ->post("{$this->baseUrl}/contatos", $payload);            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Customer created in Bling successfully', [
                    'customer_id' => $customer->id,
                    'bling_id' => $data['data']['id'] ?? null
                ]);

                return $data['data'] ?? null;
            }

            Log::error('Failed to create customer in Bling', [
                'customer_id' => $customer->id,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Exception creating customer in Bling', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Update customer in Bling
     * 
     * @param Customer $customer
     * @return array|null
     */
    public function updateCustomer(Customer $customer): ?array
    {
        if (!$customer->bling_id) {
            return $this->createCustomer($customer);
        }

        $token = $this->getAccessToken();
        
        if (!$token) {
            return null;
        }

        try {
            $payload = $this->prepareCustomerPayload($customer);
            
            Log::info('Updating customer in Bling', [
                'customer_id' => $customer->id,
                'bling_id' => $customer->bling_id,
                'payload' => $payload // LOG DO PAYLOAD COMPLETO
            ]);
            
            $response = Http::withToken($token)
                ->timeout(30)
                ->put("{$this->baseUrl}/contatos/{$customer->bling_id}", $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Customer updated in Bling', [
                    'customer_id' => $customer->id,
                    'bling_id' => $customer->bling_id
                ]);

                return $data['data'] ?? null;
            }

            Log::error('Failed to update customer in Bling', [
                'customer_id' => $customer->id,
                'bling_id' => $customer->bling_id,
                'status' => $response->status(),
                'response' => $response->body(),
                'payload' => $payload
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Exception updating customer in Bling', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Prepare customer payload for Bling API
     * 
     * @param Customer $customer
     * @return array
     */
    private function prepareCustomerPayload(Customer $customer): array
    {
        // Separar nome em primeiro nome e sobrenome
        $nameParts = explode(' ', $customer->name, 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

        // Tipo de pessoa: usar person_type do banco ou 'F' como padrão
        $tipoPessoa = $customer->person_type ?? 'F';
        
        // Documento: usar CPF se PF, CNPJ se PJ
        $numeroDocumento = null;
        if ($tipoPessoa === 'F' && $customer->cpf) {
            $numeroDocumento = preg_replace('/\D/', '', $customer->cpf);
        } elseif ($tipoPessoa === 'J' && $customer->cnpj) {
            $numeroDocumento = preg_replace('/\D/', '', $customer->cnpj);
        }

        // Tipo de contribuinte: usar taxpayer_type do banco (1, 2 ou 9)
        $contribuinte = $customer->taxpayer_type ?? 9;
        $indicadorIe = $contribuinte === 1 ? 1 : 9; // Se contribuinte ICMS = 1, senão 9

        $payload = [
            'nome' => $customer->name,
            'codigo' => (string) $customer->id,
            'situacao' => 'A', // A = Ativo
            'numeroDocumento' => $numeroDocumento,
            'tipo' => $tipoPessoa,
            'indicadorIe' => $indicadorIe,
            'ie' => $tipoPessoa === 'J' && $customer->state_registration ? $customer->state_registration : null,
            'rg' => null,
            'orgaoEmissor' => null,
            'dataNascimento' => $tipoPessoa === 'F' && $customer->birth_date ? $customer->birth_date->format('Y-m-d') : null,
            'email' => $customer->nfe_email ?: $customer->email, // Usa nfe_email se disponível
            'celular' => $customer->phone ? preg_replace('/\D/', '', $customer->phone) : null,
            'fone' => null,
            'foneComercial' => $customer->phone_commercial ? preg_replace('/\D/', '', $customer->phone_commercial) : null,
            'fantasia' => $tipoPessoa === 'J' && $customer->fantasy_name ? $customer->fantasy_name : null,
            'contribuinte' => $contribuinte,
        ];

        // Adicionar endereços se existirem
        $shippingAddress = $customer->addresses()->where('is_shipping', true)->first();
        $billingAddress = $customer->addresses()->where('is_billing', true)->first();
        
        // Se não houver endereços tipados, usar o primeiro disponível para geral
        $fallbackAddress = $shippingAddress ?? $customer->addresses()->first();

        if ($shippingAddress || $billingAddress || $fallbackAddress || $tipoPessoa === 'J') {
            $payload['endereco'] = [];
            
            // Endereço geral (shipping ou fallback)
            $geralAddress = $shippingAddress ?? $fallbackAddress;
            if ($geralAddress || $tipoPessoa === 'J') {
                $payload['endereco']['geral'] = [
                    'endereco' => $geralAddress?->address ?? '',
                    'numero' => $geralAddress?->number ?? '',
                    'complemento' => $geralAddress?->complement ?? '',
                    'bairro' => $geralAddress?->neighborhood ?? '',
                    'cep' => $geralAddress?->zipcode ? preg_replace('/\D/', '', $geralAddress->zipcode) : '',
                    'municipio' => $geralAddress?->city ?? '',
                    'uf' => $geralAddress?->state ?? $customer->state_uf ?? 'SP',
                    'pais' => 'Brasil',
                ];
            }
            
            // Endereço de cobrança (billing)
            if ($billingAddress) {
                $payload['endereco']['cobranca'] = [
                    'endereco' => $billingAddress->address,
                    'numero' => $billingAddress->number,
                    'complemento' => $billingAddress->complement ?? '',
                    'bairro' => $billingAddress->neighborhood,
                    'cep' => preg_replace('/\D/', '', $billingAddress->zipcode),
                    'municipio' => $billingAddress->city,
                    'uf' => $billingAddress->state,
                    'pais' => 'Brasil',
                ];
            }
        }

        // Adicionar tipo de contato e observações
        $payload['vendedor'] = null;
        
        // Tipo de contato (Cliente ecommerce)
        $customerTypeId = config('services.bling.customer_type_id');
        if ($customerTypeId) {
            $payload['tiposContato'] = [
                ['id' => (int) $customerTypeId]
            ];
        }
        
        $payload['dadosAdicionais'] = [
            'observacoes' => 'Cliente Ecommerce - Cadastrado via site'
        ];

        // Remover campos null para evitar erros na API
        return array_filter($payload, function($value) {
            return $value !== null && $value !== '';
        });
    }

    /**
     * Sync customer addresses to Bling
     * Updates shipping address (geral) and billing address (cobranca)
     * 
     * @param Customer $customer
     * @return bool
     */
    public function syncAddresses(Customer $customer): bool
    {
        $token = $this->getAccessToken();
        
        if (!$token) {
            Log::error('Cannot sync addresses to Bling: no access token', [
                'customer_id' => $customer->id
            ]);
            return false;
        }

        if (!$customer->bling_id) {
            Log::warning('Cannot sync addresses: customer not synced to Bling yet', [
                'customer_id' => $customer->id
            ]);
            return false;
        }

        try {
            // Verificar se há endereços para sincronizar
            $shippingAddress = $customer->addresses()->where('is_shipping', true)->first();
            $billingAddress = $customer->addresses()->where('is_billing', true)->first();

            if (!$shippingAddress && !$billingAddress) {
                Log::info('No shipping or billing addresses to sync', [
                    'customer_id' => $customer->id
                ]);
                return true; // Não é erro, apenas não há o que sincronizar
            }

            // PUT no Bling sobrescreve tudo, então enviamos o payload completo do cliente
            // O prepareCustomerPayload já inclui os endereços shipping/billing atualizados
            $payload = $this->prepareCustomerPayload($customer);

            // Log do payload que será enviado
            Log::info('Sending full customer payload with addresses to Bling', [
                'customer_id' => $customer->id,
                'bling_id' => $customer->bling_id,
                'payload' => $payload,
                'url' => "{$this->baseUrl}/contatos/{$customer->bling_id}"
            ]);

            // Atualizar cliente no Bling com os endereços
            $response = Http::withToken($token)
                ->timeout(30)
                ->put("{$this->baseUrl}/contatos/{$customer->bling_id}", $payload);

            // Log da resposta completa
            Log::info('Bling API response for address sync', [
                'customer_id' => $customer->id,
                'bling_id' => $customer->bling_id,
                'status_code' => $response->status(),
                'response_body' => $response->json(),
                'response_headers' => $response->headers()
            ]);

            if ($response->successful()) {
                Log::info('Addresses synced to Bling successfully', [
                    'customer_id' => $customer->id,
                    'bling_id' => $customer->bling_id,
                    'has_shipping' => (bool) $shippingAddress,
                    'has_billing' => (bool) $billingAddress,
                ]);
                return true;
            }

            Log::error('Failed to sync addresses to Bling', [
                'customer_id' => $customer->id,
                'bling_id' => $customer->bling_id,
                'status' => $response->status(),
                'response' => $response->json()
            ]);
            
            return false;

        } catch (\Exception $e) {
            Log::error('Exception syncing addresses to Bling', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }

    /**
     * Search customer in Bling by email
     * 
     * @param string $email
     * @return array|null
     */
    public function searchCustomerByEmail(string $email): ?array
    {
        $token = $this->getAccessToken();
        
        if (!$token) {
            return null;
        }

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->get("{$this->baseUrl}/contatos", [
                    'criterio' => 3, // 3 = Buscar por email
                    'email' => $email,
                    'tipo' => 'C', // C = Cliente
                    'limite' => 1
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (!empty($data['data'])) {
                    return $data['data'][0];
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Exception searching customer in Bling', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }
}
