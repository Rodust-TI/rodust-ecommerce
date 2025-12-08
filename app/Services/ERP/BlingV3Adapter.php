<?php

namespace App\Services\ERP;

use App\Contracts\ERPInterface;
use App\Models\Integration;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Adapter para Bling API v3
 * 
 * Implementa integração com Bling usando OAuth2 e endpoints v3.
 * Segue o padrão Adapter para isolar detalhes da API específica.
 */
class BlingV3Adapter implements ERPInterface
{
    protected Client $client;
    protected string $baseUrl;
    protected string $clientId;
    protected string $clientSecret;
    protected ?string $accessToken = null;
    protected ?string $refreshToken = null;
    protected bool $tokensLoaded = false;

    public function __construct()
    {
        $this->baseUrl = config('services.bling.base_url', 'https://api.bling.com.br/Api/v3');
        $this->clientId = config('services.bling.client_id');
        $this->clientSecret = config('services.bling.client_secret');
        
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 10,
            'verify' => false, // Desabilitar verificação SSL (apenas para desenvolvimento)
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        // NÃO carregar tokens aqui - lazy loading apenas quando necessário
    }

    /**
     * Obter access token válido (com refresh automático)
     */
    protected function getAccessToken(): string
    {
        // Lazy loading - carregar tokens apenas na primeira vez que for usado
        if (!$this->tokensLoaded) {
            $this->loadTokens();
            $this->tokensLoaded = true;
        }
        
        if ($this->accessToken && !$this->isTokenExpired()) {
            return $this->accessToken;
        }

        if ($this->refreshToken) {
            return $this->refreshAccessToken();
        }

        throw new \RuntimeException('No valid access token available. Please authenticate first.');
    }

    /**
     * Carregar tokens do cache
     */
    protected function loadTokens(): void
    {
        try {
            // Verificar se tabela existe antes de consultar
            if (!Schema::hasTable('integrations')) {
                // Fallback para cache durante migrations
                $this->accessToken = Cache::get('bling_access_token');
                $this->refreshToken = Cache::get('bling_refresh_token');
                return;
            }
            
            // Tentar carregar do banco primeiro
            $integration = Integration::where('service', 'bling')->first();
            
            if ($integration && $integration->is_active) {
                $this->accessToken = $integration->access_token;
                $this->refreshToken = $integration->refresh_token;
                
                Log::debug('Tokens carregados do banco de dados', [
                    'has_access_token' => !empty($this->accessToken),
                    'has_refresh_token' => !empty($this->refreshToken),
                    'expires_at' => $integration->token_expires_at?->format('Y-m-d H:i:s'),
                    'is_expired' => $integration->token_expires_at ? $integration->token_expires_at->isPast() : null
                ]);
                
                // Manter cache sincronizado para performance
                if ($this->accessToken && $integration->token_expires_at) {
                    $expiresIn = $integration->token_expires_at->diffInSeconds(now());
                    if ($expiresIn > 0) {
                        Cache::put('bling_access_token', $this->accessToken, $expiresIn);
                        Cache::put('bling_refresh_token', $this->refreshToken, now()->addDays(30));
                    }
                }
            } else {
                // Fallback para cache (retrocompatibilidade)
                Log::warning('Nenhuma integração ativa encontrada no banco, usando cache');
                $this->accessToken = Cache::get('bling_access_token');
                $this->refreshToken = Cache::get('bling_refresh_token');
            }
        } catch (\Exception $e) {
            // Se houver qualquer erro de DB, usar cache
            Log::warning('Erro ao carregar tokens do banco, usando cache', [
                'error' => $e->getMessage()
            ]);
            $this->accessToken = Cache::get('bling_access_token');
            $this->refreshToken = Cache::get('bling_refresh_token');
        }
    }

    /**
     * Salvar tokens no cache
     */
    protected function saveTokens(string $accessToken, string $refreshToken, int $expiresIn): void
    {
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
        
        $expiresAt = now()->addSeconds($expiresIn);

        // Salvar no banco (persistente)
        Integration::updateOrCreate(
            ['service' => 'bling'],
            [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_expires_at' => $expiresAt,
                'is_active' => true,
                'last_sync_at' => now(),
            ]
        );

        // Manter cache para performance
        Cache::put('bling_access_token', $accessToken, $expiresIn - 60); // 1 min de margem
        Cache::put('bling_refresh_token', $refreshToken, now()->addDays(30));
    }

    /**
     * Verificar se token expirou
     */
    protected function isTokenExpired(): bool
    {
        return !Cache::has('bling_access_token');
    }

    /**
     * Renovar access token usando refresh token
     */
    protected function refreshAccessToken(): string
    {
        try {
            $response = $this->client->post('/oauth/token', [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $this->refreshToken,
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            $this->saveTokens(
                $data['access_token'],
                $data['refresh_token'] ?? $this->refreshToken,
                $data['expires_in']
            );

            return $this->accessToken;
        } catch (GuzzleException $e) {
            Log::error('Bling - Failed to refresh token: ' . $e->getMessage());
            throw new \RuntimeException('Failed to refresh access token');
        }
    }

    /**
     * Fazer requisição autenticada com rate limiting
     * 
     * Limites do Bling:
     * - 3 requisições por segundo
     * - 120.000 requisições por dia
     */
    protected function request(string $method, string $endpoint, array $options = []): array
    {
        // Rate limiting: máximo 3 requisições por segundo
        $this->enforceRateLimit();
        
        try {
            // Garantir que headers seja um array
            if (!isset($options['headers'])) {
                $options['headers'] = [];
            }
            
            $options['headers']['Authorization'] = 'Bearer ' . $this->getAccessToken();
            $options['headers']['Accept'] = 'application/json';
            $options['headers']['Content-Type'] = 'application/json';
            
            // Construir URL completa (Guzzle tem problemas com base_uri e endpoints relativos)
            $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
            
            Log::debug("BlingV3Adapter - Request", [
                'method' => $method, 
                'url' => $url,
                'payload' => isset($options['json']) ? json_encode($options['json'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : null
            ]);
            
            $response = $this->client->request($method, $url, $options);
            
            return [
                'success' => true,
                'data' => json_decode($response->getBody()->getContents(), true),
                'headers' => $response->getHeaders(),
            ];
        } catch (GuzzleException $e) {
            $errorBody = null;
            $responseBody = null;
            $statusCode = null;
            
            if (method_exists($e, 'getResponse') && $e->getResponse()) {
                $statusCode = $e->getResponse()->getStatusCode();
                $responseBody = $e->getResponse()->getBody()->getContents();
                $errorBody = json_decode($responseBody, true);
                
                // IMPORTANTE: Mesmo com erro, o Bling pode ter criado o pedido
                // ou o pedido pode já existir. Verificar se há ID na resposta
                if (isset($errorBody['data']['data']['id'])) {
                    Log::warning('Bling retornou erro mas pedido foi criado/encontrado', [
                        'error' => $e->getMessage(),
                        'bling_order_id' => $errorBody['data']['data']['id'],
                        'error_body' => $errorBody
                    ]);
                }
                
                // Verificar se o erro é "informações idênticas" - significa que o pedido já existe
                if (isset($errorBody['error']['fields'])) {
                    foreach ($errorBody['error']['fields'] as $field) {
                        if (isset($field['code']) && $field['code'] == 3) {
                            Log::warning('Bling: Pedido já existe (informações idênticas)', [
                                'error' => $field['msg'] ?? 'Pedido duplicado',
                                'endpoint' => $endpoint
                            ]);
                        }
                    }
                }
                
                // Tratar erro 429 (Too Many Requests) - rate limit atingido
                if ($statusCode === 429) {
                    Log::warning('Bling rate limit atingido (429 Too Many Requests)', [
                        'endpoint' => $endpoint,
                        'message' => 'Aguardando 1 segundo antes de retornar erro...'
                    ]);
                    
                    // Aguardar 1 segundo antes de retornar erro
                    sleep(1);
                }
            }
            
            Log::error("Bling API Error [{$method} {$endpoint}]", [
                'message' => $e->getMessage(),
                'error_body' => $errorBody,
                'response_body' => $responseBody
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_details' => $errorBody,
                'data' => $errorBody['data'] ?? null, // Incluir data mesmo em erro para capturar ID
            ];
        }
    }

    /**
     * Enforce rate limiting: máximo 3 requisições por segundo
     * 
     * Usa cache para rastrear requisições recentes e aguarda se necessário
     * 
     * Limites do Bling:
     * - 3 requisições por segundo
     * - 120.000 requisições por dia
     */
    protected function enforceRateLimit(): void
    {
        $cacheKey = 'bling_rate_limit_requests';
        $now = microtime(true);
        
        // Obter requisições dos últimos segundos
        $requests = Cache::get($cacheKey, []);
        
        // Filtrar apenas requisições do último segundo
        $recentRequests = array_filter($requests, function($timestamp) use ($now) {
            return ($now - $timestamp) < 1.0;
        });
        
        // Se já temos 3 requisições no último segundo, aguardar
        if (count($recentRequests) >= 3) {
            $oldestRequest = min($recentRequests);
            $waitTime = 1.0 - ($now - $oldestRequest) + 0.1; // +0.1s de margem de segurança
            
            if ($waitTime > 0 && $waitTime < 2.0) { // Não aguardar mais de 2 segundos
                Log::debug('Bling rate limit: aguardando antes de fazer requisição', [
                    'wait_seconds' => round($waitTime, 3),
                    'recent_requests' => count($recentRequests)
                ]);
                usleep((int)($waitTime * 1000000)); // Converter para microsegundos
            }
        }
        
        // Registrar esta requisição
        $requests[] = $now;
        // Manter apenas últimas 10 requisições (otimização de memória)
        $requests = array_slice($requests, -10);
        Cache::put($cacheKey, $requests, 5); // Cache por 5 segundos
    }

    /**
     * {@inheritDoc}
     */
    public function getProducts(array $filters = []): array
    {
        Log::info('BlingV3Adapter - getProducts chamado', ['filters' => $filters]);
        
        $result = $this->request('GET', 'produtos', [
            'query' => $filters,
        ]);

        Log::info('BlingV3Adapter - resultado request', [
            'success' => $result['success'],
            'has_data' => isset($result['data']),
            'data_keys' => isset($result['data']) ? array_keys($result['data']) : []
        ]);

        if (!$result['success']) {
            Log::error('BlingV3Adapter - request falhou', ['error' => $result['error'] ?? 'unknown']);
            return [];
        }

        $products = $result['data']['data'] ?? [];
        Log::info('BlingV3Adapter - produtos encontrados', ['count' => count($products)]);

        // Normalizar lista resumida de produtos (sem detalhes completos)
        return array_map([$this, 'normalizeProductListItem'], $products);
    }
    
    /**
     * Normalizar item da lista resumida de produtos
     * A listagem /produtos retorna dados básicos, não os detalhes completos
     */
    protected function normalizeProductListItem(array $blingProduct): array
    {
        return [
            'sku' => $blingProduct['codigo'] ?? '',
            'name' => $blingProduct['nome'] ?? '',
            'description' => $blingProduct['descricaoCurta'] ?? $blingProduct['descricao'] ?? '',
            'price' => $blingProduct['preco'] ?? 0,
            'cost' => $blingProduct['precoCusto'] ?? 0,
            'stock' => $blingProduct['estoque']['saldoVirtualTotal'] ?? 0,
            'image' => $blingProduct['imagemURL'] ?? null,
            'active' => ($blingProduct['situacao'] ?? 'A') === 'A',
            'bling_id' => (string) $blingProduct['id'],
            'erp_id' => (string) $blingProduct['id'], // Alias para compatibilidade
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getProduct(string $erpId): ?array
    {
        $result = $this->request('GET', "produtos/{$erpId}");

        if (!$result['success']) {
            return null;
        }

        return $this->normalizeProduct($result['data']['data'] ?? null);
    }

    /**
     * {@inheritDoc}
     */
    public function createProduct(array $productData): ?string
    {
        $blingData = $this->denormalizeProduct($productData);
        
        $result = $this->request('POST', 'produtos', [
            'json' => $blingData,
        ]);

        return $result['data']['data']['id'] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function updateProduct(string $erpId, array $productData): bool
    {
        $blingData = $this->denormalizeProduct($productData);
        
        $result = $this->request('PUT', "produtos/{$erpId}", [
            'json' => $blingData,
        ]);

        return $result['success'];
    }

    /**
     * {@inheritDoc}
     */
    public function deleteProduct(string $erpId): bool
    {
        $result = $this->request('DELETE', "produtos/{$erpId}");
        return $result['success'];
    }

    /**
     * {@inheritDoc}
     */
    public function createOrder(array $orderData): ?string
    {
        $blingData = $this->denormalizeOrder($orderData);
        
        $result = $this->request('POST', 'pedidos/vendas', [
            'json' => $blingData,
        ]);

        Log::info('Bling createOrder result', [
            'success' => $result['success'] ?? false,
            'result' => $result
        ]);

        // Verificar se o erro é de duplicação (pedido já existe)
        $isDuplicateError = false;
        if (!$result['success'] && isset($result['error_details']['error']['fields'])) {
            foreach ($result['error_details']['error']['fields'] as $field) {
                if (isset($field['code']) && $field['code'] == 3) {
                    $isDuplicateError = true;
                    Log::warning('Bling: Pedido duplicado detectado', [
                        'order_number' => $orderData['order_number'] ?? 'N/A',
                        'error_msg' => $field['msg'] ?? 'Pedido duplicado'
                    ]);
                    break;
                }
            }
        }
        
        // Bling retorna o ID do pedido em data.data.id
        // IMPORTANTE: Mesmo se houver erro de validação (ex: status inválido),
        // o Bling pode criar o pedido e retornar o ID na resposta
        $orderId = null;
        
        // Tentar extrair ID de várias formas possíveis
        if (isset($result['data']['data']['id'])) {
            $orderId = (string) $result['data']['data']['id'];
            Log::info('Bling pedido criado - ID extraído de data.data.id', ['bling_order_id' => $orderId]);
        } elseif (isset($result['error_details']['data']['data']['id'])) {
            // Às vezes o ID vem dentro de error_details mesmo com erro
            $orderId = (string) $result['error_details']['data']['data']['id'];
            Log::info('Bling pedido criado - ID extraído de error_details.data.data.id', ['bling_order_id' => $orderId]);
        } elseif (isset($result['error_details']['error']['id'])) {
            $orderId = (string) $result['error_details']['error']['id'];
            Log::info('Bling pedido criado - ID extraído de error_details.error.id', ['bling_order_id' => $orderId]);
        }
        
        // Se é erro de duplicação e não conseguimos extrair o ID, lançar exceção especial
        if ($isDuplicateError && !$orderId) {
            throw new \App\Exceptions\BlingDuplicateOrderException(
                'Pedido já existe no Bling (duplicado)',
                $orderData['order_number'] ?? 'N/A'
            );
        }
        
        if (!$orderId) {
            Log::warning('Bling não retornou ID do pedido', [
                'success' => $result['success'] ?? false,
                'has_data' => isset($result['data']),
                'has_error_details' => isset($result['error_details']),
                'is_duplicate' => $isDuplicateError,
                'result_keys' => array_keys($result)
            ]);
        }
        
        return $orderId;
    }

    /**
     * Atualizar pedido no Bling (PUT - requer todos os campos)
     * 
     * @param string $erpOrderId ID do pedido no Bling
     * @param array $orderData Dados completos do pedido (PUT substitui todos os campos)
     * @return bool Sucesso da operação
     */
    public function updateOrder(string $erpOrderId, array $orderData): bool
    {
        $blingData = $this->denormalizeOrder($orderData);
        
        $result = $this->request('PUT', "pedidos/vendas/{$erpOrderId}", [
            'json' => $blingData,
        ]);

        Log::info('Bling updateOrder result', [
            'order_id' => $erpOrderId,
            'success' => $result['success'] ?? false,
            'result' => $result
        ]);

        // Verificar se há warnings na resposta (o Bling pode retornar success mas com warnings)
        if (isset($result['data']['data']['warnings']) && !empty($result['data']['data']['warnings'])) {
            Log::warning('Bling retornou warnings na atualização do pedido', [
                'order_id' => $erpOrderId,
                'warnings' => $result['data']['data']['warnings']
            ]);
        }

        // Se um status ID específico foi fornecido, tentar atualizar via endpoint específico
        if (isset($orderData['bling_status_id'])) {
            $statusUpdateResult = $this->updateOrderStatus($erpOrderId, $orderData['bling_status_id']);
            if ($statusUpdateResult) {
                Log::info('Status do pedido atualizado via endpoint específico', [
                    'order_id' => $erpOrderId,
                    'status_id' => $orderData['bling_status_id']
                ]);
            }
        }

        return $result['success'] ?? false;
    }

    /**
     * Atualizar apenas a situação (status) de um pedido no Bling
     * 
     * Endpoint: PATCH /pedidos/vendas/{idPedidoVenda}/situacoes/{idSituacao}
     * 
     * @param string $erpOrderId ID do pedido no Bling
     * @param int $statusId ID da situação no Bling
     * @return bool Sucesso da operação
     */
    public function updateOrderStatus(string $erpOrderId, int $statusId): bool
    {
        // Usar PATCH conforme documentação oficial da API v3 do Bling
        $result = $this->request('PATCH', "pedidos/vendas/{$erpOrderId}/situacoes/{$statusId}", [
            'json' => [], // Body vazio conforme documentação
        ]);

        Log::info('Bling updateOrderStatus result', [
            'order_id' => $erpOrderId,
            'status_id' => $statusId,
            'success' => $result['success'] ?? false,
            'result' => $result
        ]);

        // Verificar se o erro é porque o pedido já possui a mesma situação (code 50)
        // Isso deve ser tratado como sucesso, pois significa que o status já está correto
        if (!$result['success'] && isset($result['error_details']['error']['fields'])) {
            foreach ($result['error_details']['error']['fields'] as $field) {
                if (isset($field['code']) && $field['code'] == 50) {
                    Log::info('Bling: Pedido já possui a situação desejada (code 50)', [
                        'order_id' => $erpOrderId,
                        'status_id' => $statusId,
                        'message' => $field['msg'] ?? 'A venda possui a mesma situação'
                    ]);
                    return true; // Tratar como sucesso
                }
            }
        }

        // Verificar se o status foi realmente atualizado
        if ($result['success']) {
            try {
                // Aguardar um pouco para o Bling processar
                sleep(1);
                
                // Buscar pedido do Bling para verificar se o status foi atualizado
                $getResult = $this->request('GET', "pedidos/vendas/{$erpOrderId}");
                if ($getResult['success'] && isset($getResult['data']['data'])) {
                    $updatedOrder = $getResult['data']['data'];
                    if (isset($updatedOrder['situacao'])) {
                        $newStatusId = $updatedOrder['situacao']['id'] ?? null;
                        
                        if ($newStatusId == $statusId) {
                            Log::info('Bling confirmou atualização do status via endpoint específico', [
                                'order_id' => $erpOrderId,
                                'status_id' => $newStatusId
                            ]);
                            return true;
                        } else {
                            Log::warning('Bling não atualizou o status do pedido via endpoint específico', [
                                'order_id' => $erpOrderId,
                                'expected_status_id' => $statusId,
                                'actual_status_id' => $newStatusId
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Não foi possível verificar se o status foi atualizado', [
                    'order_id' => $erpOrderId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $result['success'] ?? false;
    }

    /**
     * {@inheritDoc}
     */
    public function updateStock(string $erpId, int $quantity): bool
    {
        $result = $this->request('PATCH', "produtos/{$erpId}/estoques", [
            'json' => [
                'deposito' => ['id' => config('services.bling.default_warehouse_id', 1)],
                'operacao' => 'B', // Balanço (ajuste absoluto)
                'quantidade' => $quantity,
            ],
        ]);

        return $result['success'];
    }

    /**
     * {@inheritDoc}
     * Get status modules (deprecated - use getModules())
     */
    public function getStatuses(): array
    {
        return $this->getModules();
    }

    /**
     * Obter formas de pagamento cadastradas no Bling
     */
    public function getPaymentMethods(): array
    {
        $result = $this->request('GET', 'formas-pagamentos');
        
        if (!$result['success']) {
            return [];
        }

        return $result['data']['data'] ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function testConnection(): bool
    {
        try {
            $result = $this->request('GET', 'produtos', ['query' => ['limite' => 1]]);
            return $result['success'];
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Normalizar produto do formato Bling para formato padrão da aplicação
     */
    protected function normalizeProduct(?array $blingProduct): ?array
    {
        if (!$blingProduct) {
            return null;
        }

        // Extrair dimensões físicas (usado para cálculo de frete)
        // IMPORTANTE: Bling retorna dimensões em CENTÍMETROS e peso em QUILOS
        // Mantemos os valores originais sem conversão
        $dimensoes = $blingProduct['dimensoes'] ?? [];
        $width = isset($dimensoes['largura']) ? (float) $dimensoes['largura'] : null;
        $height = isset($dimensoes['altura']) ? (float) $dimensoes['altura'] : null;
        $length = isset($dimensoes['profundidade']) ? (float) $dimensoes['profundidade'] : null;
        
        // Usar peso BRUTO (não líquido) para cálculo de frete
        $weight = isset($blingProduct['pesoBruto']) ? (float) $blingProduct['pesoBruto'] : null;

        // Extrair múltiplas imagens
        $images = [];
        if (isset($blingProduct['midia']['imagens'])) {
            // Imagens externas (ex: Shutterstock)
            if (isset($blingProduct['midia']['imagens']['externas'])) {
                foreach ($blingProduct['midia']['imagens']['externas'] as $img) {
                    if (isset($img['link'])) {
                        $images[] = $img['link'];
                    }
                }
            }
            // Imagens internas do Bling
            if (isset($blingProduct['midia']['imagens']['internas'])) {
                foreach ($blingProduct['midia']['imagens']['internas'] as $img) {
                    if (isset($img['link'])) {
                        $images[] = $img['link'];
                    }
                }
            }
        }

        // Marca/Fabricante
        $brand = $blingProduct['marca'] ?? null;

        // Categoria do Bling
        $blingCategoryId = null;
        if (isset($blingProduct['categoria']['id'])) {
            $blingCategoryId = (string) $blingProduct['categoria']['id'];
        }

        // Preço promocional (não existe no Bling diretamente, mas podemos usar precoCusto como base)
        // Você precisará adicionar esse campo customizado no Bling se quiser usar
        $promotionalPrice = null;

        // Frete grátis
        $freeShipping = $blingProduct['freteGratis'] ?? false;

        // Imagem principal (priorizar imagemURL, depois primeira da lista)
        $mainImage = $blingProduct['imagemURL'] ?? ($images[0] ?? null);

        // Descrição (priorizar descricaoComplementar, depois descricaoCurta)
        $description = $blingProduct['descricaoComplementar'] 
            ?? $blingProduct['descricaoCurta'] 
            ?? $blingProduct['observacoes'] 
            ?? '';

        return [
            'sku' => $blingProduct['codigo'] ?? '',
            'name' => $blingProduct['nome'] ?? '',
            'description' => $description,
            'price' => $blingProduct['preco'] ?? 0,
            'promotional_price' => $promotionalPrice,
            'cost' => $blingProduct['fornecedor']['precoCusto'] ?? 0,
            'stock' => $blingProduct['estoque']['saldoVirtualTotal'] ?? 0,
            'image' => $mainImage,
            'images' => $images,
            'active' => ($blingProduct['situacao'] ?? 'A') === 'A',
            'bling_id' => $blingProduct['id'] ?? null,
            'bling_category_id' => $blingCategoryId,
            // Dimensões físicas para frete
            'width' => $width,
            'height' => $height,
            'length' => $length,
            'weight' => $weight,
            // Informações comerciais
            'brand' => $brand,
            'free_shipping' => $freeShipping,
            // Controle de sincronização
            'last_sync_at' => now(),
            'sync_status' => 'synced',
        ];
    }

    /**
     * Desnormalizar produto do formato padrão para formato Bling
     */
    protected function denormalizeProduct(array $productData): array
    {
        return [
            'nome' => $productData['name'],
            'codigo' => $productData['sku'],
            'preco' => $productData['price'],
            'tipo' => 'P', // Produto
            'situacao' => $productData['active'] ? 'A' : 'I',
            'formato' => 'S', // Simples
            'descricaoCurta' => $productData['description'] ?? '',
            'precoCusto' => $productData['cost'] ?? 0,
        ];
    }

    /**
     * Desnormalizar pedido do formato padrão para formato Bling
     */
    protected function denormalizeOrder(array $orderData): array
    {
        $now = now()->format('Y-m-d');
        
        // Determinar forma de pagamento no Bling baseado no método usado
        $paymentMethod = $orderData['payment_method'] ?? 'pix';
        $paymentMethodMap = [
            'pix' => config('services.bling.payment_methods.pix'),
            'credit_card' => config('services.bling.payment_methods.credit_card'),
            'debit_card' => config('services.bling.payment_methods.debit_card'),
            'boleto' => config('services.bling.payment_methods.boleto'),
        ];
        $paymentMethodId = $paymentMethodMap[$paymentMethod] ?? config('services.bling.payment_methods.default', 6061520);
        
        // Calcular valor total das parcelas
        $itemsTotal = array_sum(array_map(function($item) {
            return $item['price'] * $item['quantity'];
        }, $orderData['items']));
        
        $totalAmount = $itemsTotal + ($orderData['shipping'] ?? 0) - ($orderData['discount'] ?? 0);
        
        // Determinar número de parcelas
        $installments = $orderData['installments'] ?? 1;
        $installmentValue = $installments > 0 ? $totalAmount / $installments : $totalAmount;
        
        // Criar parcelas
        $parcelas = [];
        for ($i = 1; $i <= $installments; $i++) {
            $parcelas[] = [
                'dataVencimento' => now()->addDays($i * 30)->format('Y-m-d'), // Vencimento a cada 30 dias
                'valor' => $installmentValue,
                'observacoes' => "Parcela {$i}/{$installments} - Pagamento via " . strtoupper($paymentMethod),
                'formaPagamento' => [
                    'id' => $paymentMethodId
                ]
            ];
        }
        
        $payload = [
            'numero' => $orderData['order_number'] ?? '', // Número do pedido na loja
            'numeroPedidoCompra' => $orderData['order_number'] ?? '', // Número único para evitar duplicação
            'data' => $now, // Data do pedido (obrigatório)
            'dataSaida' => $now, // Data de saída (obrigatório)
            'dataPrevista' => $now, // Data prevista (obrigatório)
            'itens' => array_map(function ($item) {
                $itemData = [
                    'descricao' => $item['name'], // Descrição do item (obrigatório)
                    'quantidade' => $item['quantity'],
                    'valor' => $item['price'],
                ];
                
                // Se tem bling_id, usa produto.id (melhor opção)
                if (!empty($item['bling_id'])) {
                    $itemData['produto'] = ['id' => (int) $item['bling_id']];
                } else {
                    // Senão, usa codigo (Bling tentará encontrar o produto pelo SKU)
                    $itemData['codigo'] = $item['sku'];
                }
                
                return $itemData;
            }, $orderData['items']),
            'parcelas' => $parcelas,
            'transporte' => [
                'frete' => $orderData['shipping'] ?? 0,
            ],
        ];
        
        // Adicionar transportadora no transporte
        // NOTA: O endereço de entrega NÃO vai aqui - ele já está no contato do pedido
        // Apenas a transportadora e informações de frete vão no transporte
        if (!empty($orderData['shipping_carrier'])) {
            // A transportadora deve ser o nome exato cadastrado no Bling
            // Ex: "Correios", "Jadlog", etc.
            $payload['transporte']['transportadora'] = $orderData['shipping_carrier'];
        }
        
        // Adicionar método de envio nas observações se disponível
        if (!empty($orderData['shipping_method_name'])) {
            if (!isset($payload['transporte']['observacoes'])) {
                $payload['transporte']['observacoes'] = '';
            }
            $payload['transporte']['observacoes'] .= ($payload['transporte']['observacoes'] ? ' | ' : '') . 
                'Método: ' . $orderData['shipping_method_name'];
        }
        
        // Adicionar situação (status) do pedido
        $statusId = null;
        
        // Se um status ID específico foi fornecido (ex: para atualização via PUT), usar ele
        if (isset($orderData['bling_status_id'])) {
            $statusId = (int) $orderData['bling_status_id'];
            Log::info('Bling: Usando status ID específico fornecido', ['status_id' => $statusId]);
        } else {
            // Verificar se pedido foi pago (paid_at não nulo) OU status é processing
            // paid_at pode ser DateTime, string ou null
            $paidAt = $orderData['paid_at'] ?? null;
            $hasPaidAt = $paidAt !== null && $paidAt !== '';
            $isProcessing = ($orderData['status'] ?? '') === 'processing';
            $isPaid = $hasPaidAt || $isProcessing;
            
            Log::info('Bling denormalizeOrder - Determinando status', [
                'order_number' => $orderData['order_number'] ?? 'N/A',
                'status' => $orderData['status'] ?? 'N/A',
                'paid_at' => $paidAt ? (is_object($paidAt) ? $paidAt->format('Y-m-d H:i:s') : $paidAt) : 'null',
                'has_paid_at' => $hasPaidAt,
                'is_processing' => $isProcessing,
                'is_paid' => $isPaid,
                'processing_id' => config('services.bling.order_statuses.processing', 15),
                'open_id' => config('services.bling.order_statuses.open', 6),
            ]);
            
            if ($isPaid) {
                // Pedido pago - enviar como "Em andamento" (ID 15)
                $statusId = config('services.bling.order_statuses.processing', 15);
                Log::info('Bling: Enviando pedido com status "Em andamento" (ID ' . $statusId . ')');
            } else {
                // IMPORTANTE: O Bling não permite criar pedidos com status diferente de "Em aberto" (ID 6)
                // Por isso, vamos criar sempre como "Em aberto" e atualizar depois via PUT se necessário
                $statusId = config('services.bling.order_statuses.open', 6);
                Log::info('Bling: Criando pedido sempre como "Em aberto" (ID ' . $statusId . ')', [
                    'is_paid' => $isPaid,
                    'note' => 'Status será atualizado via PUT após criação se pedido estiver pago'
                ]);
            }
        }
        
        if ($statusId !== null) {
            $payload['situacao'] = ['id' => (int) $statusId];
        }
        
        // Adicionar desconto se houver
        if (!empty($orderData['discount']) && $orderData['discount'] > 0) {
            $payload['desconto'] = [
                'valor' => $orderData['discount']
            ];
        }
        
        // Adicionar taxas do gateway de pagamento (ex: Mercado Pago)
        // Isso será registrado como despesa no Bling
        if (!empty($orderData['payment_fee']) && $orderData['payment_fee'] > 0) {
            $payload['observacoes'] = "Taxa de pagamento: R$ " . number_format($orderData['payment_fee'], 2, ',', '.') . 
                                    " | Valor líquido: R$ " . number_format($orderData['net_amount'] ?? $totalAmount, 2, ',', '.');
        }

        // Se o cliente tem ID no Bling, usar o ID. Senão, enviar dados completos
        if (!empty($orderData['customer']['id'])) {
            $payload['contato'] = [
                'id' => $orderData['customer']['id']
            ];
        } else {
            $payload['contato'] = [
                'nome' => $orderData['customer']['name'],
                'email' => $orderData['customer']['email'],
                'telefone' => $orderData['customer']['phone'] ?? '',
            ];
        }

        return $payload;
    }

    /**
     * Obter lista de módulos do Bling
     * 
     * @return array Lista de módulos disponíveis
     */
    public function getModules(): array
    {
        try {
            Log::info('BlingV3Adapter - Buscando módulos');
            
            $result = $this->request('GET', '/situacoes/modulos');
            
            if (!$result['success']) {
                Log::error('Erro ao obter módulos do Bling', [
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
                return [];
            }
            
            $modules = $result['data']['data'] ?? [];
            
            Log::info('Módulos do Bling obtidos com sucesso', [
                'count' => count($modules),
                'modules' => $modules
            ]);
            
            return $modules;

        } catch (GuzzleException $e) {
            Log::error('Erro ao buscar módulos do Bling', [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            return [];
        }
    }

    /**
     * Obter lista de situações (status) de um módulo específico
     * 
     * @param int $moduleId ID do módulo
     * @return array Lista de situações
     */
    public function getSituations(int $moduleId): array
    {
        try {
            Log::info('BlingV3Adapter - Buscando situações', [
                'module_id' => $moduleId
            ]);
            
            // O endpoint correto é /situacoes/modulos/{idModuloSistema}
            $result = $this->request('GET', "/situacoes/modulos/{$moduleId}");
            
            if (!$result['success']) {
                Log::error('Erro ao obter situações do Bling', [
                    'module_id' => $moduleId,
                    'error' => $result['error'] ?? 'Unknown error',
                    'error_details' => $result['error_details'] ?? null
                ]);
                return [];
            }
            
            $statuses = $result['data']['data'] ?? [];
            
            Log::info('Situações do Bling obtidas com sucesso', [
                'module_id' => $moduleId,
                'count' => count($statuses),
                'statuses' => $statuses
            ]);
            
            return $statuses;

        } catch (GuzzleException $e) {
            Log::error('Erro ao buscar situações do Bling', [
                'module_id' => $moduleId,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            return [];
        }
    }

    /**
     * Obter detalhes de um pedido de venda específico pelo ID
     * 
     * @param string $orderId ID do pedido no Bling
     * @return array|null Dados do pedido ou null se não encontrado
     */
    public function getOrderById(string $orderId): ?array
    {
        try {
            Log::info('BlingV3Adapter - Buscando pedido', ['order_id' => $orderId]);
            
            $result = $this->request('GET', "/pedidos/vendas/{$orderId}");
            
            if (!$result['success']) {
                // Se erro 404, retornar null (pedido não encontrado)
                if (str_contains($result['error'] ?? '', '404')) {
                    Log::warning('Pedido não encontrado no Bling', ['order_id' => $orderId]);
                    return null;
                }
                
                Log::error('Erro ao buscar pedido do Bling', [
                    'order_id' => $orderId,
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
                return null;
            }
            
            $orderData = $result['data']['data'] ?? null;
            
            Log::debug('Pedido do Bling obtido', [
                'order_id' => $orderId,
                'status' => $orderData['situacao'] ?? 'N/A'
            ]);

            return $orderData;

        } catch (GuzzleException $e) {
            if ($e->getCode() === 404) {
                Log::warning('Pedido não encontrado no Bling', ['order_id' => $orderId]);
                return null;
            }

            Log::error('Erro ao buscar pedido do Bling', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            return null;
        }
    }

    /**
     * Obter lista de pedidos de venda
     * 
     * @param array $filters Filtros opcionais (dataInicial, dataFinal, situacao, etc)
     * @return array Lista de pedidos
     */
    public function getOrders(array $filters = []): array
    {
        // Usar o método request() para garantir autenticação correta
        $result = $this->request('GET', 'pedidos/vendas', [
            'query' => $filters,
        ]);

        if (!$result['success']) {
            Log::error('Erro ao buscar lista de pedidos do Bling', [
                'filters' => $filters,
                'error' => $result['error'] ?? 'unknown'
            ]);
            throw new \RuntimeException('Falha ao buscar lista de pedidos do Bling: ' . ($result['error'] ?? 'unknown'));
        }

        $orders = $result['data']['data'] ?? [];
        
        Log::debug('Lista de pedidos do Bling obtida', [
            'filters' => $filters,
            'count' => count($orders)
        ]);

        return $orders;
    }
}
