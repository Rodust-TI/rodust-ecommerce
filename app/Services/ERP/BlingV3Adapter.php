<?php

namespace App\Services\ERP;

use App\Contracts\ERPInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

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
        $this->accessToken = Cache::get('bling_access_token');
        $this->refreshToken = Cache::get('bling_refresh_token');
    }

    /**
     * Salvar tokens no cache
     */
    protected function saveTokens(string $accessToken, string $refreshToken, int $expiresIn): void
    {
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;

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
            $options['headers']['Authorization'] = 'Bearer ' . $this->getAccessToken();
            
            $response = $this->client->request($method, $endpoint, $options);
            
            return [
                'success' => true,
                'data' => json_decode($response->getBody()->getContents(), true),
                'headers' => $response->getHeaders(),
            ];
        } catch (GuzzleException $e) {
            Log::error("Bling API Error [{$method} {$endpoint}]: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getProducts(array $filters = []): array
    {
        $result = $this->request('GET', '/produtos', [
            'query' => $filters,
        ]);

        if (!$result['success']) {
            return [];
        }

        return array_map([$this, 'normalizeProduct'], $result['data']['data'] ?? []);
    }

    /**
     * {@inheritDoc}
     */
    public function getProduct(string $erpId): ?array
    {
        $result = $this->request('GET', "/produtos/{$erpId}");

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
        
        $result = $this->request('POST', '/produtos', [
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
        
        $result = $this->request('PUT', "/produtos/{$erpId}", [
            'json' => $blingData,
        ]);

        return $result['success'];
    }

    /**
     * {@inheritDoc}
     */
    public function deleteProduct(string $erpId): bool
    {
        $result = $this->request('DELETE', "/produtos/{$erpId}");
        return $result['success'];
    }

    /**
     * {@inheritDoc}
     */
    public function createOrder(array $orderData): ?string
    {
        $blingData = $this->denormalizeOrder($orderData);
        
        $result = $this->request('POST', '/pedidos/vendas', [
            'json' => $blingData,
        ]);

        return $result['data']['data']['numero'] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function updateStock(string $erpId, int $quantity): bool
    {
        $result = $this->request('PATCH', "/produtos/{$erpId}/estoques", [
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
        $result = $this->request('GET', '/situacoes/modulos');
        
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
            $result = $this->request('GET', '/produtos', ['query' => ['limite' => 1]]);
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

        return [
            'sku' => $blingProduct['codigo'] ?? '',
            'name' => $blingProduct['nome'] ?? '',
            'description' => $blingProduct['descricao'] ?? '',
            'price' => $blingProduct['preco'] ?? 0,
            'cost' => $blingProduct['precoCusto'] ?? 0,
            'stock' => $blingProduct['estoqueAtual'] ?? 0,
            'image' => $blingProduct['imagemURL'] ?? null,
            'active' => ($blingProduct['situacao'] ?? 'A') === 'A',
            'erp_id' => $blingProduct['id'] ?? null,
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
        return [
            'contato' => [
                'nome' => $orderData['customer']['name'],
                'email' => $orderData['customer']['email'],
                'telefone' => $orderData['customer']['phone'] ?? '',
            ],
            'itens' => array_map(function ($item) {
                return [
                    'codigo' => $item['sku'],
                    'descricao' => $item['name'],
                    'quantidade' => $item['quantity'],
                    'valor' => $item['price'],
                ];
            }, $orderData['items']),
            'transporte' => [
                'frete' => $orderData['shipping'] ?? 0,
            ],
            'desconto' => $orderData['discount'] ?? 0,
        ];
    }
}
