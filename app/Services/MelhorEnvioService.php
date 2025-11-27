<?php

namespace App\Services;

use App\Models\MelhorEnvioSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MelhorEnvioService
{
    private ?MelhorEnvioSetting $settings;
    private string $baseUrl;

    public function __construct()
    {
        $this->settings = MelhorEnvioSetting::getSettings();
        
        // Se não tem settings no banco, criar/usar do .env
        if (!$this->settings) {
            $sandbox = config('services.melhor_envio.sandbox', true);
            $this->settings = new MelhorEnvioSetting([
                'client_id' => $sandbox 
                    ? config('services.melhor_envio.client_id_sandbox') 
                    : config('services.melhor_envio.client_id_prod'),
                'client_secret' => $sandbox
                    ? config('services.melhor_envio.client_secret_sandbox')
                    : config('services.melhor_envio.client_secret_prod'),
                'origin_postal_code' => config('services.melhor_envio.origin_cep'),
                'sandbox_mode' => $sandbox,
            ]);
        }
        
        $this->baseUrl = $this->settings && $this->settings->sandbox_mode
            ? 'https://sandbox.melhorenvio.com.br/api/v2'
            : 'https://melhorenvio.com.br/api/v2';
    }

    /**
     * Get OAuth authorization URL
     */
    public function getAuthorizationUrl(string $redirectUri, string $state): string
    {
        if (!$this->settings) {
            throw new \Exception('Melhor Envio não configurado');
        }

        $params = http_build_query([
            'client_id' => $this->settings->client_id,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'state' => $state,
            'scope' => 'cart-read cart-write companies-read companies-write coupons-read coupons-write notifications-read orders-read products-read products-write purchases-read shipping-calculate shipping-cancel shipping-checkout shipping-companies shipping-generate shipping-preview shipping-print shipping-share shipping-tracking ecommerce-shipping transactions-read'
        ]);

        $authUrl = $this->settings->sandbox_mode
            ? 'https://sandbox.melhorenvio.com.br/oauth/authorize'
            : 'https://melhorenvio.com.br/oauth/authorize';

        return $authUrl . '?' . $params;
    }

    /**
     * Exchange authorization code for access token
     */
    public function authenticate(string $code, string $redirectUri): array
    {
        if (!$this->settings) {
            throw new \Exception('Melhor Envio não configurado');
        }

        $tokenUrl = $this->settings->sandbox_mode
            ? 'https://sandbox.melhorenvio.com.br/oauth/token'
            : 'https://melhorenvio.com.br/oauth/token';

        $response = Http::asForm()->post($tokenUrl, [
            'grant_type' => 'authorization_code',
            'client_id' => $this->settings->client_id,
            'client_secret' => $this->settings->client_secret,
            'redirect_uri' => $redirectUri,
            'code' => $code,
        ]);

        if ($response->failed()) {
            Log::error('Melhor Envio OAuth failed', ['response' => $response->json()]);
            throw new \Exception('Falha na autenticação: ' . $response->body());
        }

        $data = $response->json();

        // Save tokens
        $this->settings->update([
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'],
            'expires_at' => now()->addSeconds($data['expires_in']),
        ]);

        return $data;
    }

    /**
     * Refresh access token
     */
    public function refreshToken(): array
    {
        if (!$this->settings || !$this->settings->refresh_token) {
            throw new \Exception('Refresh token não disponível');
        }

        $tokenUrl = $this->settings->sandbox_mode
            ? 'https://sandbox.melhorenvio.com.br/oauth/token'
            : 'https://melhorenvio.com.br/oauth/token';

        $response = Http::asForm()->post($tokenUrl, [
            'grant_type' => 'refresh_token',
            'client_id' => $this->settings->client_id,
            'client_secret' => $this->settings->client_secret,
            'refresh_token' => $this->settings->refresh_token,
        ]);

        if ($response->failed()) {
            Log::error('Melhor Envio refresh token failed', ['response' => $response->json()]);
            throw new \Exception('Falha ao renovar token: ' . $response->body());
        }

        $data = $response->json();

        $this->settings->update([
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'],
            'expires_at' => now()->addSeconds($data['expires_in']),
        ]);

        return $data;
    }

    /**
     * Get valid access token (refresh if needed)
     */
    private function getAccessToken(): string
    {
        // Se tem bearer token (método direto), usar ele
        if ($this->settings && $this->settings->bearer_token) {
            return $this->settings->bearer_token;
        }

        // Senão, usar OAuth (access_token)
        if (!$this->settings || !$this->settings->access_token) {
            throw new \Exception('Melhor Envio não autenticado. Configure o Bearer Token ou OAuth primeiro.');
        }

        if ($this->settings->isTokenExpired()) {
            $this->refreshToken();
        }

        return $this->settings->access_token;
    }

    /**
     * Calculate shipping for given destination and products
     */
    public function calculateShipping(string $toPostalCode, array $products): array
    {
        if (!$this->settings || !$this->settings->origin_postal_code) {
            throw new \Exception('CEP de origem não configurado');
        }

        $token = $this->getAccessToken();

        // Prepare package dimensions
        $package = $this->calculatePackageDimensions($products);

        $response = Http::withToken($token)
            ->post($this->baseUrl . '/me/shipment/calculate', [
                'from' => [
                    'postal_code' => $this->sanitizePostalCode($this->settings->origin_postal_code),
                ],
                'to' => [
                    'postal_code' => $this->sanitizePostalCode($toPostalCode),
                ],
                'package' => $package,
            ]);

        if ($response->failed()) {
            Log::error('Melhor Envio calculate failed', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);
            throw new \Exception('Erro ao calcular frete: ' . $response->body());
        }

        return $this->formatShippingOptions($response->json());
    }

    /**
     * Calculate package dimensions from products
     */
    private function calculatePackageDimensions(array $products): array
    {
        $totalWeight = 0;
        $maxHeight = 2; // cm - mínimo
        $maxWidth = 11; // cm - mínimo
        $maxLength = 16; // cm - mínimo

        foreach ($products as $product) {
            $quantity = $product['quantity'] ?? 1;
            
            // Peso (converter para kg se necessário)
            $weight = ($product['weight'] ?? 0.3) * $quantity;
            $totalWeight += $weight;

            // Dimensões (você pode pegar do produto se tiver)
            $maxHeight = max($maxHeight, $product['height'] ?? 2);
            $maxWidth = max($maxWidth, $product['width'] ?? 11);
            $maxLength = max($maxLength, $product['length'] ?? 16);
        }

        return [
            'height' => (int) $maxHeight,
            'width' => (int) $maxWidth,
            'length' => (int) $maxLength,
            'weight' => max(0.3, $totalWeight), // Peso mínimo 0.3kg
        ];
    }

    /**
     * Format shipping options for response
     */
    private function formatShippingOptions(array $options): array
    {
        $formatted = [];

        foreach ($options as $option) {
            if (isset($option['error']) && $option['error']) {
                continue;
            }

            $formatted[] = [
                'id' => $option['id'],
                'name' => $option['name'],
                'company' => $option['company']['name'] ?? '',
                'company_logo' => $option['company']['picture'] ?? '',
                'price' => (float) $option['price'],
                'discount' => (float) ($option['discount'] ?? 0),
                'delivery_time' => (int) $option['delivery_time'],
                'delivery_range' => [
                    'min' => (int) ($option['delivery_range']['min'] ?? $option['delivery_time']),
                    'max' => (int) ($option['delivery_range']['max'] ?? $option['delivery_time']),
                ],
                'packages' => $option['packages'] ?? [],
            ];
        }

        // Sort by price (cheapest first)
        usort($formatted, fn($a, $b) => $a['price'] <=> $b['price']);

        return $formatted;
    }

    /**
     * Create shipment (generate label)
     */
    public function createShipment(array $orderData): array
    {
        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->post($this->baseUrl . '/me/cart', [
                'service' => $orderData['shipping_service_id'],
                'from' => [
                    'name' => $orderData['from_name'],
                    'phone' => $orderData['from_phone'],
                    'email' => $orderData['from_email'],
                    'document' => $orderData['from_document'],
                    'company_document' => $orderData['from_company_document'] ?? null,
                    'state_register' => $orderData['from_state_register'] ?? null,
                    'address' => $orderData['from_address'],
                    'complement' => $orderData['from_complement'] ?? null,
                    'number' => $orderData['from_number'],
                    'district' => $orderData['from_district'],
                    'city' => $orderData['from_city'],
                    'state_abbr' => $orderData['from_state'],
                    'postal_code' => $this->sanitizePostalCode($orderData['from_postal_code']),
                ],
                'to' => [
                    'name' => $orderData['to_name'],
                    'phone' => $orderData['to_phone'],
                    'email' => $orderData['to_email'],
                    'document' => $orderData['to_document'],
                    'address' => $orderData['to_address'],
                    'complement' => $orderData['to_complement'] ?? null,
                    'number' => $orderData['to_number'],
                    'district' => $orderData['to_district'],
                    'city' => $orderData['to_city'],
                    'state_abbr' => $orderData['to_state'],
                    'postal_code' => $this->sanitizePostalCode($orderData['to_postal_code']),
                ],
                'products' => $orderData['products'],
                'volumes' => $orderData['volumes'],
                'options' => [
                    'insurance_value' => $orderData['insurance_value'] ?? 0,
                    'receipt' => $orderData['receipt'] ?? false,
                    'own_hand' => $orderData['own_hand'] ?? false,
                    'reverse' => false,
                    'non_commercial' => $orderData['non_commercial'] ?? false,
                    'invoice' => $orderData['invoice'] ?? null,
                ],
            ]);

        if ($response->failed()) {
            Log::error('Melhor Envio create shipment failed', ['response' => $response->json()]);
            throw new \Exception('Erro ao criar envio: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Track shipment
     */
    public function trackShipment(string $trackingCode): array
    {
        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->get($this->baseUrl . '/me/shipment/tracking', [
                'orders' => $trackingCode,
            ]);

        if ($response->failed()) {
            Log::error('Melhor Envio tracking failed', ['response' => $response->json()]);
            throw new \Exception('Erro ao rastrear envio: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Sanitize postal code
     */
    private function sanitizePostalCode(string $postalCode): string
    {
        return preg_replace('/\D/', '', $postalCode);
    }
}
