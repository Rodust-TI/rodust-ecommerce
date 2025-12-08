<?php

namespace App\Http\Controllers;

use App\Models\Integration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BlingController extends Controller
{
    /**
     * Redirecionar para autorização OAuth do Bling
     */
    public function authorize()
    {
        $state = bin2hex(random_bytes(16));
        session(['bling_oauth_state' => $state]);

        // Escopos necessários:
        // 20480 = Contatos (clientes)
        // 12288 = Produtos
        // 28672 = Pedidos
        // 14336 = Notas Fiscais
        // 32768 = Situações
        $scopes = '12288 20480 28672 32768'; // Produtos, Contatos, Pedidos, Situações

        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => config('services.bling.client_id'),
            'state' => $state,
            'scope' => $scopes,
        ]);

        $authUrl = config('services.bling.base_url') . '/oauth/authorize?' . $params;

        return redirect($authUrl);
    }

    /**
     * Callback OAuth - Recebe o código e troca por tokens
     */
    public function callback(Request $request)
    {
        // Validar state apenas se foi iniciado pelo nosso authorize
        // (permite usar link direto de convite do Bling)
        if (session()->has('bling_oauth_state')) {
            if ($request->state !== session('bling_oauth_state')) {
                Log::error('Bling OAuth: State mismatch', [
                    'expected' => session('bling_oauth_state'),
                    'received' => $request->state
                ]);
                
                return response()->json([
                    'error' => 'Invalid state parameter',
                    'message' => 'Possível ataque CSRF detectado. Tente novamente.'
                ], 400);
            }
        }

        // Verificar se código foi recebido
        if (!$request->has('code')) {
            Log::error('Bling OAuth: No code received', $request->all());
            
            return response()->json([
                'error' => 'No authorization code',
                'message' => 'Código de autorização não recebido do Bling.'
            ], 400);
        }

        $code = $request->code;

        try {
            // Trocar código por tokens
            $response = Http::asForm()
                ->withBasicAuth(
                    config('services.bling.client_id'),
                    config('services.bling.client_secret')
                )
                ->post(config('services.bling.base_url') . '/oauth/token', [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => config('services.bling.redirect_uri'),
                ]);

            if ($response->failed()) {
                Log::error('Bling OAuth: Token exchange failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return response()->json([
                    'error' => 'Token exchange failed',
                    'message' => 'Erro ao trocar código por token: ' . $response->body(),
                    'status' => $response->status()
                ], 500);
            }

            $data = $response->json();

            // Salvar tokens no banco E cache
            $expiresAt = now()->addSeconds($data['expires_in']);
            
            Integration::updateOrCreate(
                ['service' => 'bling'],
                [
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'],
                    'token_expires_at' => $expiresAt,
                    'is_active' => true,
                    'last_sync_at' => now(),
                ]
            );
            
            // Manter cache para performance
            Cache::put('bling_access_token', $data['access_token'], now()->addSeconds($data['expires_in'] - 60));
            Cache::put('bling_refresh_token', $data['refresh_token'], now()->addDays(30));

            // Limpar state da sessão
            session()->forget('bling_oauth_state');

            Log::info('Bling OAuth: Tokens obtained successfully', [
                'expires_in' => $data['expires_in'],
                'token_type' => $data['token_type'] ?? 'Bearer'
            ]);

            // Retornar página de sucesso
            return view('bling.success', [
                'access_token' => substr($data['access_token'], 0, 20) . '...',
                'expires_in' => $data['expires_in'],
                'expires_hours' => round($data['expires_in'] / 3600, 1)
            ]);

        } catch (\Exception $e) {
            Log::error('Bling OAuth: Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Internal error',
                'message' => 'Erro interno: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar status da autenticação
     */
    public function status()
    {
        $hasAccessToken = Cache::has('bling_access_token');
        $hasRefreshToken = Cache::has('bling_refresh_token');

        return response()->json([
            'authenticated' => $hasAccessToken || $hasRefreshToken,
            'access_token_valid' => $hasAccessToken,
            'refresh_token_valid' => $hasRefreshToken,
            'message' => $hasAccessToken 
                ? 'Autenticado com Bling API v3' 
                : ($hasRefreshToken ? 'Access token expirado, mas refresh disponível' : 'Não autenticado')
        ]);
    }

    /**
     * Painel administrativo
     */
    public function dashboard()
    {
        return view('bling.dashboard');
    }

    /**
     * Revogar autenticação (limpar tokens)
     */
    public function revoke()
    {
        try {
            Cache::forget('bling_access_token');
            Cache::forget('bling_refresh_token');

            Log::info('Bling: Tokens revoked manually');

            return response()->json([
                'success' => true,
                'message' => 'Autenticação revogada com sucesso'
            ]);
        } catch (\Exception $e) {
            Log::error('Bling: Error revoking tokens', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao revogar tokens: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Renovar access token usando refresh token
     */
    public function refreshToken()
    {
        try {
            $refreshToken = Cache::get('bling_refresh_token');

            if (!$refreshToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Refresh token não encontrado. Faça login novamente.'
                ], 401);
            }

            // Solicitar novo access token
            $response = Http::asForm()
                ->withBasicAuth(
                    config('services.bling.client_id'),
                    config('services.bling.client_secret')
                )
                ->post(config('services.bling.base_url') . '/oauth/token', [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                ]);

            if ($response->failed()) {
                Log::error('Bling: Token refresh failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                // Se refresh token inválido, limpar tudo
                Cache::forget('bling_access_token');
                Cache::forget('bling_refresh_token');

                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao renovar token. Faça login novamente.',
                    'error' => $response->body()
                ], 401);
            }

            $data = $response->json();

            // Salvar novos tokens no banco E cache
            $expiresAt = now()->addSeconds($data['expires_in']);
            
            $updateData = [
                'access_token' => $data['access_token'],
                'token_expires_at' => $expiresAt,
                'is_active' => true,
                'last_sync_at' => now(),
            ];
            
            // Atualizar refresh token se vier um novo
            if (isset($data['refresh_token'])) {
                $updateData['refresh_token'] = $data['refresh_token'];
            }
            
            Integration::updateOrCreate(
                ['service' => 'bling'],
                $updateData
            );
            
            // Manter cache para performance
            Cache::put('bling_access_token', $data['access_token'], now()->addSeconds($data['expires_in'] - 60));
            
            if (isset($data['refresh_token'])) {
                Cache::put('bling_refresh_token', $data['refresh_token'], now()->addDays(30));
            }

            Log::info('Bling: Token refreshed successfully', [
                'expires_in' => $data['expires_in']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Token renovado com sucesso',
                'expires_in' => $data['expires_in']
            ]);

        } catch (\Exception $e) {
            Log::error('Bling: Token refresh exception', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao renovar token: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Listar produtos
     */
    public function apiProducts(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);
            
            $response = Http::withToken(Cache::get('bling_access_token'))
                ->get(config('services.bling.base_url') . '/produtos', [
                    'limite' => $limit
                ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'data' => $response->json()['data'] ?? []
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar produtos: ' . $response->status()
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Sincronizar produtos do Bling para Laravel
     */
    public function apiSyncProducts(Request $request)
    {
        try {
            $limit = $request->input('limit', 100);
            
            // Executar comando de sincronização
            \Illuminate\Support\Facades\Artisan::call('bling:sync-products', [
                '--limit' => $limit,
                '--force' => $request->boolean('force', false)
            ]);
            
            $output = \Illuminate\Support\Facades\Artisan::output();
            
            return response()->json([
                'success' => true,
                'message' => 'Sincronização iniciada com sucesso',
                'output' => $output
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Sincronizar detalhes completos de produtos (enfileirado)
     * Busca lista de produtos e enfileira job para cada um com rate limiting
     */
    public function apiSyncProductsAdvanced(Request $request)
    {
        try {
            $limit = $request->input('limit', 100);
            $full = $request->boolean('full', false);
            
            // Buscar lista de produtos do Bling
            $erp = app(\App\Contracts\ERPInterface::class);
            $products = $erp->getProducts(['limite' => $limit]);
            
            \Illuminate\Support\Facades\Log::info('Bling - Produtos retornados:', [
                'count' => count($products),
                'sample' => array_slice($products, 0, 2) // Primeiros 2 para debug
            ]);
            
            if (empty($products)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum produto encontrado no Bling'
                ], 404);
            }
            
            // Enfileirar cada produto com delay para respeitar rate limit
            // Bling permite 3 req/s, então 1 job a cada ~350ms
            $delaySeconds = 0;
            $queued = 0;
            
            foreach ($products as $product) {
                $blingId = $product['bling_id'] ?? $product['erp_id'] ?? null;
                
                if (!$blingId) {
                    \Illuminate\Support\Facades\Log::warning('Bling - Produto sem ID:', $product);
                    continue;
                }
                
                // Enfileirar job com delay crescente
                \App\Jobs\SyncProductDetailFromBling::dispatch($blingId)
                    ->delay(now()->addMilliseconds($delaySeconds * 350));
                
                $delaySeconds++;
                $queued++;
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Sincronização avançada iniciada',
                'queued' => $queued,
                'total_found' => count($products),
                'estimated_time' => ceil($queued * 0.35) . ' segundos',
                'note' => 'Jobs enfileirados respeitando limite de 3 req/s do Bling'
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erro na sincronização avançada', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Sincronizar um produto específico pelo ID do Bling
     */
    public function apiSyncSingleProduct(Request $request, string $blingId)
    {
        try {
            // Disparar job imediatamente (sem delay)
            \App\Jobs\SyncProductDetailFromBling::dispatch($blingId);
            
            return response()->json([
                'success' => true,
                'message' => "Sincronização do produto {$blingId} enfileirada",
                'bling_id' => $blingId
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Sincronizar clientes para o Bling
     */
    public function apiSyncCustomers(Request $request)
    {
        try {
            $limit = $request->input('limit', 100);
            $onlyVerified = $request->boolean('only_verified', true);
            
            // Executar comando de sincronização
            \Illuminate\Support\Facades\Artisan::call('bling:sync-customers', [
                '--limit' => $limit,
                '--only-verified' => $onlyVerified,
                '--force' => $request->boolean('force', false)
            ]);
            
            $output = \Illuminate\Support\Facades\Artisan::output();
            
            // Contar clientes
            $query = \App\Models\Customer::query();
            if ($onlyVerified) {
                $query->whereNotNull('email_verified_at');
            }
            $totalCustomers = $query->count();
            
            return response()->json([
                'success' => true,
                'message' => 'Sincronização de clientes iniciada',
                'output' => $output,
                'total_customers' => $totalCustomers
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Listar tipos de contato do Bling
     */
    public function apiListContactTypes()
    {
        try {
            $token = Cache::get('bling_access_token');

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token de acesso não encontrado. Reconecte ao Bling.'
                ], 401);
            }

            $response = Http::withToken($token)
                ->timeout(30)
                ->get(config('services.bling.base_url') . '/contatos/tipos');

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao consultar tipos de contato',
                    'status' => $response->status()
                ], $response->status());
            }

            $data = $response->json();
            $tipos = $data['data'] ?? [];

            // Verificar tipo configurado
            $configuredId = config('services.bling.customer_type_id');
            $clienteEcommerce = collect($tipos)->firstWhere('descricao', 'Cliente ecommerce');

            return response()->json([
                'success' => true,
                'tipos' => $tipos,
                'configured_id' => $configuredId,
                'cliente_ecommerce' => $clienteEcommerce
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * API: Listar formas de pagamento do Bling
     */
    public function apiListPaymentMethods()
    {
        try {
            $token = Cache::get('bling_access_token');

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token de acesso não encontrado. Reconecte ao Bling.'
                ], 401);
            }

            // Usar o BlingV3Adapter para obter as formas de pagamento
            $adapter = app(\App\Services\ERP\BlingV3Adapter::class);
            $paymentMethods = $adapter->getPaymentMethods();

            if (empty($paymentMethods)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma forma de pagamento encontrada no Bling.'
                ]);
            }

            // Obter IDs configurados no .env
            $configuredMethods = [
                'pix' => config('services.bling.payment_methods.pix'),
                'credit_card' => config('services.bling.payment_methods.credit_card'),
                'debit_card' => config('services.bling.payment_methods.debit_card'),
                'boleto' => config('services.bling.payment_methods.boleto'),
                'default' => config('services.bling.payment_methods.default'),
            ];

            // Remover valores nulos
            $configuredMethods = array_filter($configuredMethods);

            return response()->json([
                'success' => true,
                'payment_methods' => $paymentMethods,
                'configured_methods' => $configuredMethods
            ]);

        } catch (\Exception $e) {
            \Log::error('Erro ao listar formas de pagamento: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * API: Sincronizar pedidos Laravel → Bling
     * Envia pedidos pendentes de sincronização para o Bling
     */
    public function apiSyncOrders(Request $request)
    {
        try {
            $limit = $request->input('limit', 50);
            $orderId = $request->input('order_id'); // ID específico (opcional)
            
            // Se tem order_id específico, sincronizar só ele
            if ($orderId) {
                $order = \App\Models\Order::with(['customer', 'items.product'])
                    ->findOrFail($orderId);
                
                $blingService = app(\App\Services\Bling\BlingOrderService::class);
                $result = $blingService->createOrder($order);
                
                if ($result['success']) {
                    $order->update([
                        'bling_order_number' => $result['bling_order_number'],
                        'bling_synced_at' => now(),
                    ]);
                    
                    return response()->json([
                        'success' => true,
                        'message' => "Pedido {$order->order_number} sincronizado com sucesso",
                        'data' => [
                            'order_number' => $order->order_number,
                            'bling_order_number' => $result['bling_order_number'],
                        ]
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => "Erro ao sincronizar pedido: {$result['error']}"
                    ], 400);
                }
            }
            
            // Sincronização em massa: buscar pedidos não sincronizados
            $orders = \App\Models\Order::with(['customer', 'items.product'])
                ->whereNull('bling_synced_at')
                ->where('payment_status', 'approved') // Só pedidos pagos
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
            
            if ($orders->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Nenhum pedido pendente de sincronização',
                    'synced' => 0
                ]);
            }
            
            $blingService = app(\App\Services\Bling\BlingOrderService::class);
            $synced = 0;
            $errors = [];
            
            foreach ($orders as $order) {
                $result = $blingService->createOrder($order);
                
                if ($result['success']) {
                    $order->update([
                        'bling_order_number' => $result['bling_order_number'],
                        'bling_synced_at' => now(),
                    ]);
                    $synced++;
                } else {
                    // Verificar se o erro é "pedido já existe" - tentar buscar no Bling
                    $errorMsg = $result['error'] ?? '';
                    if (strpos($errorMsg, 'idênticas') !== false || strpos($errorMsg, 'duplicado') !== false) {
                        // Pedido pode já existir no Bling - tentar buscar pelo número
                        Log::info('Tentando buscar pedido existente no Bling', [
                            'order_number' => $order->order_number
                        ]);
                        
                        // Buscar pedidos recentes no Bling e procurar pelo número
                        try {
                            $bling = app(\App\Services\ERP\BlingV3Adapter::class);
                            $blingOrders = $bling->getOrders([
                                'dataInicial' => $order->created_at->subDays(1)->format('Y-m-d'),
                                'dataFinal' => now()->format('Y-m-d'),
                            ]);
                            
                            foreach ($blingOrders as $blingOrder) {
                                // O número no Bling pode ser diferente (ex: apenas "3" ao invés de "ROD-20251205-2188")
                                // Verificar pelo total e data
                                $blingTotal = $blingOrder['total'] ?? 0;
                                $blingDate = $blingOrder['data'] ?? '';
                                
                                if (abs($blingTotal - $order->total) < 0.01 && 
                                    $blingDate === $order->created_at->format('Y-m-d')) {
                                    // Provavelmente é o mesmo pedido
                                    $order->update([
                                        'bling_order_number' => (string) $blingOrder['id'],
                                        'bling_synced_at' => now(),
                                    ]);
                                    $synced++;
                                    Log::info('Pedido encontrado no Bling e associado', [
                                        'order_number' => $order->order_number,
                                        'bling_order_id' => $blingOrder['id']
                                    ]);
                                    continue 2; // Pular para próximo pedido
                                }
                            }
                        } catch (\Exception $e) {
                            Log::error('Erro ao buscar pedido no Bling', [
                                'order_number' => $order->order_number,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                    
                    $errors[] = [
                        'order_number' => $order->order_number,
                        'error' => $result['error']
                    ];
                }
                
                // Delay para não sobrecarregar API
                usleep(500000); // 0.5 segundos
            }
            
            return response()->json([
                'success' => true,
                'message' => "{$synced} pedido(s) sincronizado(s)",
                'synced' => $synced,
                'total' => $orders->count(),
                'errors' => $errors
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao sincronizar pedidos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obter clientes do Bling e criar/atualizar no Laravel
     * Executa o comando bling:sync-customers-from-bling
     */
    public function apiGetCustomersFromBling(Request $request)
    {
        try {
            $limit = $request->input('limit', 20);
            
            // Executar o comando Artisan e capturar output
            \Artisan::call('bling:sync-customers-from-bling', [
                '--limit' => $limit
            ]);
            
            $output = \Artisan::output();
            
            // Extrair estatísticas do output (simples parsing de texto)
            $created = 0;
            $updated = 0;
            $skipped = 0;
            $errors = 0;
            
            if (preg_match('/Criados:\s+(\d+)/', $output, $matches)) {
                $created = (int) $matches[1];
            }
            if (preg_match('/Atualizados:\s+(\d+)/', $output, $matches)) {
                $updated = (int) $matches[1];
            }
            if (preg_match('/Ignorados:\s+(\d+)/', $output, $matches)) {
                $skipped = (int) $matches[1];
            }
            if (preg_match('/Erros:\s+(\d+)/', $output, $matches)) {
                $errors = (int) $matches[1];
            }
            
            $total = $created + $updated;
            
            return response()->json([
                'success' => true,
                'message' => "Sincronização concluída: {$total} cliente(s) processado(s)",
                'stats' => [
                    'created' => $created,
                    'updated' => $updated,
                    'skipped' => $skipped,
                    'errors' => $errors,
                ],
                'output' => $output
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao obter clientes do Bling', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar e armazenar status do Bling
     */
    public function fetchStatuses()
    {
        try {
            $statusService = app(\App\Services\Bling\BlingStatusService::class);
            
            // Buscar módulo de vendas
            $moduleId = $statusService->getSalesModuleId();
            
            if (!$moduleId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não foi possível encontrar o módulo de Vendas no Bling'
                ], 404);
            }

            // Buscar situações
            $statuses = $statusService->getSalesStatuses();
            
            if (empty($statuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum status encontrado no Bling'
                ], 404);
            }

            // Formatar resposta
            $formattedStatuses = [];
            foreach ($statuses as $id => $details) {
                $formattedStatuses[$id] = [
                    'nome' => $details['nome'],
                    'cor' => $details['cor'],
                    'herdado' => $details['herdado'],
                    'internal_status' => $statusService->mapBlingStatusToInternal(['id' => $id])
                ];
            }

            return response()->json([
                'success' => true,
                'module_id' => $moduleId,
                'count' => count($formattedStatuses),
                'statuses' => $formattedStatuses
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar status do Bling', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sincronizar status de pedidos
     */
    public function syncOrderStatuses(Request $request)
    {
        try {
            $orderService = app(\App\Services\Bling\BlingOrderService::class);
            
            // Sempre sincronizar TODOS os pedidos (sem limite)
            $result = $orderService->syncAllPendingOrders(null);

            return response()->json([
                'success' => true,
                'total' => $result['total'],
                'synced' => $result['synced'],
                'failed' => $result['failed']
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao sincronizar status de pedidos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Limpar cache de status
     */
    public function clearStatusCache()
    {
        try {
            $statusService = app(\App\Services\Bling\BlingStatusService::class);
            $statusService->clearCache();

            return response()->json([
                'success' => true,
                'message' => 'Cache de status limpo com sucesso'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao limpar cache de status', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Buscar logs de webhooks recentes
     */
    public function apiWebhookLogs(Request $request)
    {
        try {
            $limit = $request->input('limit', 50);
            $source = $request->input('source'); // 'bling', 'mercadopago', etc
            
            $query = \App\Models\WebhookLog::query();
            
            if ($source) {
                $query->where('source', $source);
            }
            
            // Ordenar por ID desc para polling eficiente (logs mais recentes primeiro)
            $logs = $query->orderBy('id', 'desc')->limit($limit)->get();
            
            return response()->json([
                'success' => true,
                'logs' => $logs->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'source' => $log->source,
                        'event_id' => $log->event_id,
                        'event_type' => $log->event_type,
                        'resource' => $log->resource,
                        'action' => $log->action,
                        'status' => $log->status,
                        'response_code' => $log->response_code,
                        'error_message' => $log->error_message,
                        'metadata' => $log->metadata,
                        'created_at' => $log->created_at,
                        'processed_at' => $log->processed_at,
                        'created_at' => $log->created_at->toIso8601String(),
                        'processed_at' => $log->processed_at?->toIso8601String(),
                    ];
                }),
                'total' => $logs->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar logs de webhooks', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
