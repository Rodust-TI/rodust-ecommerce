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

    public function __construct()
    {
        $this->baseUrl = config('services.bling.base_url', 'https://api.bling.com.br/Api/v3');
        $this->clientId = config('services.bling.client_id');
        $this->clientSecret = config('services.bling.client_secret');
        
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 10,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        // Carregar tokens do cache
        $this->loadTokens();
    }

    /**
     * Obter access token válido (com refresh automático)
     */
    protected function getAccessToken(): string
    {
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
     * Fazer requisição autenticada
     */
    protected function request(string $method, string $endpoint, array $options = []): array
    {
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
            if (method_exists($e, 'getResponse') && $e->getResponse()) {
                $errorBody = json_decode($e->getResponse()->getBody()->getContents(), true);
            }
            
            Log::error("Bling API Error [{$method} {$endpoint}]", [
                'message' => $e->getMessage(),
                'error_body' => $errorBody
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_details' => $errorBody,
            ];
        }
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

        // Bling retorna o ID do pedido em data.data.id
        return isset($result['data']['data']['id']) ? (string) $result['data']['data']['id'] : null;
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
     */
    public function getStatuses(): array
    {
        $result = $this->request('GET', 'situacoes/modulos');
        
        if (!$result['success']) {
            return [];
        }

        return $result['data']['data'] ?? [];
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
        
        $payload = [
            'numero' => $orderData['order_number'] ?? '', // Número do pedido na loja
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
            'parcelas' => [
                [
                    'dataVencimento' => now()->addDays(1)->format('Y-m-d'), // Vencimento em 1 dia
                    'valor' => ($orderData['shipping'] + array_sum(array_map(function($item) {
                        return $item['price'] * $item['quantity'];
                    }, $orderData['items']))) - ($orderData['discount'] ?? 0),
                    'observacoes' => 'Pagamento via ' . ($orderData['payment_method'] ?? 'PIX'),
                    'formaPagamento' => [
                        'id' => config('services.bling.payment_method_id', 6061520) // ID da forma de pagamento no Bling (Dinheiro)
                    ]
                ]
            ],
            'transporte' => [
                'frete' => $orderData['shipping'] ?? 0,
            ],
        ];

        // Adicionar desconto se houver
        if (!empty($orderData['discount']) && $orderData['discount'] > 0) {
            $payload['desconto'] = [
                'valor' => $orderData['discount']
            ];
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
}
