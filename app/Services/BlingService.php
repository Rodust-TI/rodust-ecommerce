<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class BlingService
{
    protected Client $client;
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.bling.key');
        $this->baseUrl = config('services.bling.base_url', 'https://bling.com.br/Api/v2');
        
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Buscar produtos do Bling
     */
    public function getProducts(array $filters = []): array
    {
        try {
            $response = $this->client->get('/produto/json/', [
                'query' => array_merge(['apikey' => $this->apiKey], $filters),
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            return $data['retorno']['produtos'] ?? [];
        } catch (GuzzleException $e) {
            Log::error('Bling API Error - Get Products: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Buscar um produto específico por ID
     */
    public function getProduct(string $blingId): ?array
    {
        try {
            $response = $this->client->get("/produto/{$blingId}/json/", [
                'query' => ['apikey' => $this->apiKey],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            return $data['retorno']['produtos'][0] ?? null;
        } catch (GuzzleException $e) {
            Log::error('Bling API Error - Get Product: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Criar ou atualizar produto no Bling
     */
    public function syncProduct(array $productData): ?string
    {
        try {
            $xml = $this->buildProductXml($productData);
            
            $response = $this->client->post('/produto/json/', [
                'query' => ['apikey' => $this->apiKey, 'xml' => $xml],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            return $data['retorno']['produtos'][0]['produto']['id'] ?? null;
        } catch (GuzzleException $e) {
            Log::error('Bling API Error - Sync Product: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Criar pedido no Bling
     */
    public function createOrder(array $orderData): ?string
    {
        try {
            $xml = $this->buildOrderXml($orderData);
            
            $response = $this->client->post('/pedido/json/', [
                'query' => ['apikey' => $this->apiKey, 'xml' => $xml],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            return $data['retorno']['pedidos'][0]['pedido']['numero'] ?? null;
        } catch (GuzzleException $e) {
            Log::error('Bling API Error - Create Order: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Atualizar estoque de um produto
     */
    public function updateStock(string $blingId, int $quantity): bool
    {
        try {
            $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                    <produto>
                        <id>{$blingId}</id>
                        <estoqueAtual>{$quantity}</estoqueAtual>
                    </produto>";
            
            $response = $this->client->put("/produto/{$blingId}/json/", [
                'query' => ['apikey' => $this->apiKey, 'xml' => $xml],
            ]);

            return $response->getStatusCode() === 200;
        } catch (GuzzleException $e) {
            Log::error('Bling API Error - Update Stock: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Construir XML de produto para enviar ao Bling
     */
    protected function buildProductXml(array $data): string
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                <produto>
                    <codigo>{$data['sku']}</codigo>
                    <descricao>{$data['name']}</descricao>";
        
        if (isset($data['price'])) {
            $xml .= "<vlr_unit>{$data['price']}</vlr_unit>";
        }
        
        if (isset($data['stock'])) {
            $xml .= "<estoqueAtual>{$data['stock']}</estoqueAtual>";
        }
        
        $xml .= "</produto>";
        
        return $xml;
    }

    /**
     * Construir XML de pedido para enviar ao Bling
     */
    protected function buildOrderXml(array $data): string
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                <pedido>
                    <cliente>
                        <nome>{$data['customer']['name']}</nome>
                        <email>{$data['customer']['email']}</email>";
        
        if (isset($data['customer']['phone'])) {
            $xml .= "<fone>{$data['customer']['phone']}</fone>";
        }
        
        $xml .= "</cliente>
                    <itens>";
        
        foreach ($data['items'] as $item) {
            $xml .= "<item>
                        <codigo>{$item['sku']}</codigo>
                        <descricao>{$item['name']}</descricao>
                        <qtde>{$item['quantity']}</qtde>
                        <vlr_unit>{$item['price']}</vlr_unit>
                    </item>";
        }
        
        $xml .= "</itens>
                    <vlr_frete>{$data['shipping']}</vlr_frete>
                    <vlr_desconto>{$data['discount']}</vlr_desconto>
                </pedido>";
        
        return $xml;
    }

    /**
     * Buscar situações de pedido disponíveis
     */
    public function getOrderStatuses(): array
    {
        try {
            $response = $this->client->get('/situacao/json/', [
                'query' => ['apikey' => $this->apiKey],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            return $data['retorno']['situacoes'] ?? [];
        } catch (GuzzleException $e) {
            Log::error('Bling API Error - Get Order Statuses: ' . $e->getMessage());
            return [];
        }
    }
}
