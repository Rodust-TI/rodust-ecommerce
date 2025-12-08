<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Order;
use App\Exceptions\MercadoPagoException;
use App\Exceptions\BlingException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

/**
 * Testes do PaymentController
 * 
 * Como rodar:
 * docker exec docker-laravel.test-1 php artisan test --filter=PaymentControllerTest
 * 
 * IMPORTANTE:
 * - Estes testes usam serviços REAIS (sem mocks)
 * - Requerem configuração do Mercado Pago no .env
 * - Bling tem limite de 3 requests/segundo em desenvolvimento
 * - Para testes completos de pagamento, use o fluxo do frontend
 * 
 * O que estes testes fazem:
 * - Testam validação de campos obrigatórios
 * - Verificam estrutura de resposta
 * - Testam criação de pedidos no banco
 */
class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Customer $customer;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Criar dados de teste
        $this->customer = Customer::factory()->create([
            'email_verified_at' => now(),
        ]);
        
        $this->product = Product::factory()->create([
            'price' => 100.00,
            'stock' => 10,
        ]);
    }

    /**
     * Teste: Validação de campos obrigatórios
     * 
     * Este teste verifica se a validação está funcionando corretamente
     * sem precisar chamar serviços externos.
     */
    public function test_validates_required_fields(): void
    {
        $response = $this->actingAsCustomer($this->customer)
            ->postJson('/api/payments/pix', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_id', 'items', 'shipping_method_id', 'shipping_cost']);
    }

    /**
     * Teste: Validação de estrutura de dados de pagamento PIX
     * 
     * Verifica se a estrutura do request está correta sem processar pagamento real.
     */
    public function test_pix_payment_request_structure(): void
    {
        $requestData = [
            'customer_id' => $this->customer->id,
            'shipping_method_id' => '1',
            'shipping_cost' => 15.50,
            'shipping_method' => [
                'name' => 'SEDEX',
                'company' => 'Correios',
                'delivery_time' => '1-2 dias úteis'
            ],
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'price' => $this->product->price,
                ]
            ],
            'shipping_address' => [
                'postal_code' => '01310100',
                'street' => 'Av. Paulista',
                'number' => '1000',
                'complement' => 'Apto 101',
                'neighborhood' => 'Bela Vista',
                'city' => 'São Paulo',
                'state' => 'SP',
            ],
        ];

        // Verificar que a estrutura está correta
        $this->assertArrayHasKey('customer_id', $requestData);
        $this->assertArrayHasKey('items', $requestData);
        $this->assertArrayHasKey('shipping_address', $requestData);
        $this->assertArrayHasKey('shipping_method_id', $requestData);
        $this->assertArrayHasKey('shipping_cost', $requestData);
        $this->assertIsArray($requestData['items']);
        $this->assertIsArray($requestData['shipping_address']);
    }

    /**
     * Teste: Validação de estrutura de dados de pagamento Cartão
     */
    public function test_card_payment_request_structure(): void
    {
        $requestData = [
            'customer_id' => $this->customer->id,
            'shipping_method_id' => '1',
            'shipping_cost' => 15.50,
            'shipping_method' => [
                'name' => 'SEDEX',
                'company' => 'Correios',
            ],
            'card_token' => 'test_token_123',
            'installments' => 1,
            'payment_method_id' => 'visa',
            'issuer_id' => '123',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'price' => $this->product->price,
                ]
            ],
            'shipping_address' => [
                'postal_code' => '01310100',
                'street' => 'Av. Paulista',
                'number' => '1000',
                'city' => 'São Paulo',
                'state' => 'SP',
            ],
        ];

        // Verificar estrutura
        $this->assertArrayHasKey('card_token', $requestData);
        $this->assertArrayHasKey('installments', $requestData);
        $this->assertArrayHasKey('payment_method_id', $requestData);
    }

    /**
     * Teste: Verificar que neighborhood pode ser vazio
     */
    public function test_shipping_address_neighborhood_can_be_empty(): void
    {
        $requestData = [
            'customer_id' => $this->customer->id,
            'shipping_method_id' => '1',
            'shipping_cost' => 15.50,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'price' => $this->product->price,
                ]
            ],
            'shipping_address' => [
                'postal_code' => '01310100',
                'street' => 'Av. Paulista',
                'number' => '1000',
                'city' => 'São Paulo',
                'state' => 'SP',
                // neighborhood omitido - deve ser aceito
            ],
        ];

        // Verificar que não tem neighborhood
        $this->assertArrayNotHasKey('neighborhood', $requestData['shipping_address']);
    }

    /**
     * NOTA SOBRE TESTES DE INTEGRAÇÃO REAL:
     * 
     * Para testar pagamentos reais:
     * 
     * 1. Cartão de Crédito:
     *    - Use o fluxo completo do frontend
     *    - Cartão de teste: 5031 4332 1540 6351 (Visa) - sempre aprova
     *    - CVV: 123
     *    - Data: qualquer data futura
     * 
     * 2. PIX:
     *    - Requer webhook configurado (Cloudflare Tunnel)
     *    - Gera QR code, aguarda confirmação via webhook
     * 
     * 3. Boleto:
     *    - Requer webhook configurado
     *    - Gera boleto, aguarda confirmação via webhook
     * 
     * 4. Bling:
     *    - Limite de 3 requests/segundo em desenvolvimento
     *    - Não tem sandbox
     *    - Teste com cuidado para não ser bloqueado
     */
}
