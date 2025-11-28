<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Mail\CustomerVerificationMail;
use App\Jobs\SyncCustomerToBling;
use App\Http\Requests\UpdateCustomerProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller
{
    /**
     * Cadastro de novo cliente
     * POST /api/customers/register
     */
    public function register(Request $request)
    {
        // Validação com mensagens customizadas
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email',
            'cpf' => 'required|string|size:11|unique:customers,cpf',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'name.required' => 'O nome é obrigatório.',
            'email.required' => 'O email é obrigatório.',
            'email.email' => 'Digite um email válido.',
            'email.unique' => 'Este email já está cadastrado. Faça login ou recupere sua senha.',
            'cpf.required' => 'O CPF é obrigatório.',
            'cpf.size' => 'O CPF deve ter 11 dígitos.',
            'cpf.unique' => 'Este CPF já está cadastrado.',
            'password.required' => 'A senha é obrigatória.',
            'password.min' => 'A senha deve ter no mínimo 6 caracteres.',
            'password.confirmed' => 'As senhas não conferem.',
        ]);

        // Validar CPF
        if (!Customer::isValidCPF($validated['cpf'])) {
            throw ValidationException::withMessages([
                'cpf' => ['CPF inválido. Verifique os números digitados.']
            ]);
        }

        // Gerar token de verificação
        $verificationToken = Str::random(64);

        // Criar cliente (sem email verificado)
        $customer = Customer::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'cpf' => $validated['cpf'],
            'password' => $validated['password'], // Auto-hash via cast
            'verification_token' => $verificationToken,
            'verification_token_expires_at' => now()->addHours(24),
        ]);

        // URL de verificação (WordPress)
        $verificationUrl = config('urls.wordpress.verify_email') . '?token=' . $verificationToken;

        // Enviar email de verificação
        try {
            Mail::to($customer->email)->send(new CustomerVerificationMail($customer, $verificationUrl));
        } catch (\Exception $e) {
            // Silenciar erro de email - não bloqueia cadastro
            // Em produção, usar serviço de monitoramento como Sentry
        }

        // Sincronizar cliente com Bling
        SyncCustomerToBling::dispatch($customer);

        return response()->json([
            'success' => true,
            'message' => 'Cadastro realizado! Verifique seu email para confirmar.',
            'data' => [
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'cpf' => $customer->cpf,
                    'cnpj' => $customer->cnpj,
                    'email_verified' => false,
                ],
            ]
        ], 201)
        ->header('Access-Control-Allow-Origin', $request->header('Origin') ?? 'http://localhost')
        ->header('Access-Control-Allow-Credentials', 'true');
    }

    /**
     * Login de cliente
     * POST /api/customers/login
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Buscar cliente
        $customer = Customer::where('email', $validated['email'])->first();

        // Verificar senha
        if (!$customer || !Hash::check($validated['password'], $customer->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email ou senha incorretos.']
            ]);
        }

        // BLOQUEAR se email não verificado
        if (!$customer->email_verified_at) {
            throw ValidationException::withMessages([
                'email' => ['Por favor, confirme seu email antes de fazer login. Verifique sua caixa de entrada.']
            ]);
        }

        // Revogar tokens antigos (opcional - single device)
        // $customer->tokens()->delete();

        // Gerar novo token
        $token = $customer->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login realizado com sucesso!',
            'data' => [
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'cpf' => $customer->cpf_cnpj,
                ],
                'token' => $token,
            ]
        ]);
    }

    /**
     * Logout
     * POST /api/customers/logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout realizado com sucesso!'
        ]);
    }

    /**
     * Verificar email do cliente
     * POST /api/customers/verify-email
     */
    public function verifyEmail(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string|size:64',
        ]);

        $customer = Customer::where('verification_token', $validated['token'])
            ->where('verification_token_expires_at', '>', now())
            ->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido ou expirado.'
            ], 400);
        }

        // Marcar email como verificado
        $customer->update([
            'email_verified_at' => now(),
            'verification_token' => null,
            'verification_token_expires_at' => null,
        ]);

        // Disparar sincronização com Bling (assíncrono)
        SyncCustomerToBling::dispatch($customer);

        // Gerar token de login automático
        $token = $customer->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Email verificado com sucesso! Você já está logado.',
            'data' => [
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'cpf' => $customer->cpf,
                    'cnpj' => $customer->cnpj,
                ],
                'token' => $token,
            ]
        ]);
    }

    /**
     * Reenviar email de verificação
     * POST /api/customers/resend-verification
     */
    public function resendVerification(Request $request)
    {
        try {
            Log::info('Tentativa de reenvio de verificação para: ' . $request->input('email'));
            
            $validated = $request->validate([
                'email' => 'required|email|exists:customers,email',
            ]);

            $customer = Customer::where('email', $validated['email'])->first();

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente não encontrado.'
                ], 404);
            }

            // Já verificado
            if ($customer->email_verified_at) {
                Log::info('Email já verificado: ' . $customer->email);
                return response()->json([
                    'success' => false,
                    'message' => 'Este email já foi verificado.'
                ], 400);
            }

            // Gerar novo token
            $verificationToken = Str::random(64);
            $customer->update([
                'verification_token' => $verificationToken,
                'verification_token_expires_at' => now()->addHours(24),
            ]);

            // URL de verificação
            $verificationUrl = config('urls.wordpress.verify_email') . '?token=' . $verificationToken;

            // Enviar email
            Log::info('Tentando reenviar email para: ' . $customer->email);
            Mail::to($customer->email)->send(new CustomerVerificationMail($customer, $verificationUrl));
            Log::info('Email reenviado com sucesso para: ' . $customer->email);

        return response()->json([
            'success' => true,
            'message' => 'Email de verificação reenviado com sucesso! Verifique sua caixa de entrada.'
        ])
        ->header('Access-Control-Allow-Origin', $request->header('Origin') ?? config('urls.cors.allowed_origins')[0])
        ->header('Access-Control-Allow-Credentials', 'true');
        
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos.',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('ERRO CRÍTICO no reenvio: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao enviar email: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Buscar perfil do cliente autenticado
     * GET /api/customers/me
     */
    public function me(Request $request)
    {
        $customer = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'cpf' => $customer->cpf,
                'cnpj' => $customer->cnpj,
                'phone' => $customer->phone,
                'person_type' => $customer->person_type,
                'birth_date' => $customer->birth_date?->format('Y-m-d'),
                'fantasy_name' => $customer->fantasy_name,
                'state_registration' => $customer->state_registration,
                'state_uf' => $customer->state_uf,
                'nfe_email' => $customer->nfe_email,
                'phone_commercial' => $customer->phone_commercial,
                'taxpayer_type' => $customer->taxpayer_type,
                'email_verified' => !is_null($customer->email_verified_at),
                'bling_id' => $customer->bling_id,
                'bling_synced_at' => $customer->bling_synced_at?->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    /**
     * Atualizar perfil do cliente autenticado
     * PUT /api/customers/me
     */
    public function updateProfile(UpdateCustomerProfileRequest $request)
    {
        $customer = $request->user();

        try {
            // Atualizar dados
            $customer->update($request->validated());

            // Se alterou dados relevantes para Bling, sincronizar
            $blingFields = ['name', 'cpf', 'cnpj', 'phone', 'person_type', 'birth_date', 
                           'fantasy_name', 'state_registration', 'state_uf', 'nfe_email', 
                           'phone_commercial', 'taxpayer_type'];
            
            $hasChangedBlingData = collect($request->validated())
                ->keys()
                ->intersect($blingFields)
                ->isNotEmpty();

            if ($hasChangedBlingData && $customer->bling_id) {
                // Cliente já tem bling_id, atualizar no Bling
                SyncCustomerToBling::dispatch($customer);
            } elseif ($hasChangedBlingData && !$customer->bling_id) {
                // Cliente não tem bling_id ainda, criar no Bling
                SyncCustomerToBling::dispatch($customer);
            }

            return response()->json([
                'success' => true,
                'message' => 'Perfil atualizado com sucesso!',
                'data' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'cpf' => $customer->cpf,
                    'cnpj' => $customer->cnpj,
                    'phone' => $customer->phone,
                    'person_type' => $customer->person_type,
                    'birth_date' => $customer->birth_date?->format('Y-m-d'),
                    'fantasy_name' => $customer->fantasy_name,
                    'state_registration' => $customer->state_registration,
                    'state_uf' => $customer->state_uf,
                    'nfe_email' => $customer->nfe_email,
                    'phone_commercial' => $customer->phone_commercial,
                    'taxpayer_type' => $customer->taxpayer_type,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar perfil do cliente: ' . $e->getMessage(), [
                'customer_id' => $customer->id,
                'data' => $request->validated()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar perfil.',
                'error' => config('app.debug') ? $e->getMessage() : 'Erro interno'
            ], 500);
        }
    }

    /**
     * Sincronizar clientes WordPress → Laravel → Bling
     * POST /api/customers/sync-from-wordpress
     */
    public function syncFromWordPress(Request $request)
    {
        try {
            $customers = $request->input('customers', []);
            $stats = ['created' => 0, 'updated' => 0, 'synced_to_bling' => 0, 'errors' => 0];

            Log::info('Iniciando syncFromWordPress', ['total_customers' => count($customers)]);

            foreach ($customers as $customerData) {
                try {
                    // Converter strings vazias em null
                    $cleanData = array_map(function($value) {
                        return (is_string($value) && trim($value) === '') ? null : $value;
                    }, $customerData);

                    Log::info('Processando cliente', ['email' => $cleanData['email'], 'data' => $cleanData]);

                    // Buscar cliente existente por email, CPF ou CNPJ
                    $customer = Customer::where('email', $cleanData['email'])->first();
                    
                    if (!$customer && isset($cleanData['cpf'])) {
                        $customer = Customer::where('cpf', $cleanData['cpf'])->first();
                    }
                    
                    if (!$customer && isset($cleanData['cnpj'])) {
                        $customer = Customer::where('cnpj', $cleanData['cnpj'])->first();
                    }

                    if ($customer) {
                        // Atualizar cliente existente (apenas campos não-vazios)
                        $updateData = [];
                        if (!empty($cleanData['name'])) $updateData['name'] = $cleanData['name'];
                        if (isset($cleanData['phone'])) $updateData['phone'] = $cleanData['phone'];
                        if (isset($cleanData['person_type'])) $updateData['person_type'] = $cleanData['person_type'];
                        if (isset($cleanData['birth_date'])) $updateData['birth_date'] = $cleanData['birth_date'];
                        if (isset($cleanData['fantasy_name'])) $updateData['fantasy_name'] = $cleanData['fantasy_name'];
                        if (isset($cleanData['state_registration'])) $updateData['state_registration'] = $cleanData['state_registration'];
                        if (isset($cleanData['state_uf'])) $updateData['state_uf'] = $cleanData['state_uf'];
                        if (isset($cleanData['nfe_email'])) $updateData['nfe_email'] = $cleanData['nfe_email'];
                        if (isset($cleanData['phone_commercial'])) $updateData['phone_commercial'] = $cleanData['phone_commercial'];
                        if (isset($cleanData['taxpayer_type'])) $updateData['taxpayer_type'] = $cleanData['taxpayer_type'];
                        if (isset($cleanData['cpf'])) $updateData['cpf'] = $cleanData['cpf'];
                        if (isset($cleanData['cnpj'])) $updateData['cnpj'] = $cleanData['cnpj'];
                        
                        if (!empty($updateData)) {
                            Log::info('Atualizando cliente', ['customer_id' => $customer->id, 'update_data' => $updateData]);
                            $customer->update($updateData);
                        }
                        $stats['updated']++;

                        // Sincronizar com Bling (sempre que atualizar)
                        if ($customer->bling_id) {
                            Log::info('Disparando sync para Bling (atualização)', ['customer_id' => $customer->id, 'bling_id' => $customer->bling_id]);
                            SyncCustomerToBling::dispatch($customer);
                            $stats['synced_to_bling']++;
                        }
                    } else {
                        // Criar novo cliente
                        $customer = Customer::create([
                            'name' => $cleanData['name'],
                            'email' => $cleanData['email'],
                            'cpf' => $cleanData['cpf'] ?? null,
                            'cnpj' => $cleanData['cnpj'] ?? null,
                            'phone' => $cleanData['phone'] ?? null,
                            'password' => Hash::make(Str::random(16)), // Senha aleatória
                            'email_verified_at' => now(), // Já verificado no WordPress
                            'person_type' => $cleanData['person_type'] ?? 'F',
                            'birth_date' => $cleanData['birth_date'] ?? null,
                            'fantasy_name' => $cleanData['fantasy_name'] ?? null,
                            'state_registration' => $cleanData['state_registration'] ?? null,
                            'state_uf' => $cleanData['state_uf'] ?? null,
                            'nfe_email' => $cleanData['nfe_email'] ?? null,
                            'phone_commercial' => $cleanData['phone_commercial'] ?? null,
                            'taxpayer_type' => $cleanData['taxpayer_type'] ?? 9,
                        ]);
                        $stats['created']++;

                        // Sincronizar com Bling (novo cliente)
                        Log::info('Disparando sync para Bling (novo)', ['customer_id' => $customer->id]);
                        SyncCustomerToBling::dispatch($customer);
                        $stats['synced_to_bling']++;
                    }

                } catch (\Exception $e) {
                    Log::error('Erro ao sincronizar cliente: ' . $e->getMessage(), [
                        'customer_data' => $customerData,
                        'trace' => $e->getTraceAsString()
                    ]);
                    $stats['errors']++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Sincronização concluída',
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Erro na sincronização de clientes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao sincronizar clientes',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}


