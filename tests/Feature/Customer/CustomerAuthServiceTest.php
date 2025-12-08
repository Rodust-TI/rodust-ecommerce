<?php

namespace Tests\Feature\Customer;

use Tests\TestCase;
use App\Models\Customer;
use App\Services\Customer\CustomerAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

/**
 * Testes do serviço de autenticação de clientes
 * 
 * Como rodar:
 * php artisan test --filter=CustomerAuthServiceTest
 * 
 * O que este teste faz:
 * - Cria um cliente fake no banco de dados de teste
 * - Testa login com credenciais corretas
 * - Testa login com senha errada
 * - Testa login com email não verificado
 * - Limpa tudo automaticamente depois
 */
class CustomerAuthServiceTest extends TestCase
{
    use RefreshDatabase; // Limpa o banco após cada teste

    private CustomerAuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = new CustomerAuthService();
    }

    /**
     * Teste: Login com credenciais válidas deve retornar customer e token
     */
    public function test_can_login_with_valid_credentials()
    {
        // ARRANGE (Preparar): Criar um cliente de teste com email verificado
        $customer = Customer::create([
            'name' => 'Teste Cliente',
            'email' => 'teste@exemplo.com',
            'cpf' => '12345678901',
            'password' => 'senha123', // Será hasheado automaticamente
            'email_verified_at' => now(),
        ]);

        // ACT (Agir): Tentar fazer login
        $result = $this->authService->login('teste@exemplo.com', 'senha123');

        // ASSERT (Verificar): Confirmar que funcionou
        $this->assertArrayHasKey('customer', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertEquals($customer->id, $result['customer']->id);
        $this->assertNotEmpty($result['token']);
    }

    /**
     * Teste: Login com senha incorreta deve lançar exceção
     */
    public function test_cannot_login_with_wrong_password()
    {
        // ARRANGE
        Customer::create([
            'name' => 'Teste Cliente',
            'email' => 'teste@exemplo.com',
            'cpf' => '12345678902',
            'password' => 'senha123',
            'email_verified_at' => now(),
        ]);

        // ACT & ASSERT: Espera que lance ValidationException
        $this->expectException(ValidationException::class);
        $this->authService->login('teste@exemplo.com', 'senha_errada');
    }

    /**
     * Teste: Login com email não verificado deve lançar exceção
     */
    public function test_cannot_login_with_unverified_email()
    {
        // ARRANGE
        Customer::create([
            'name' => 'Teste Cliente',
            'email' => 'teste@exemplo.com',
            'cpf' => '12345678903',
            'password' => 'senha123',
            'email_verified_at' => null, // Email NÃO verificado
        ]);

        // ACT & ASSERT
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('confirme seu email');
        
        $this->authService->login('teste@exemplo.com', 'senha123');
    }

    /**
     * Teste: Login com email inexistente deve lançar exceção
     */
    public function test_cannot_login_with_nonexistent_email()
    {
        // ACT & ASSERT
        $this->expectException(ValidationException::class);
        $this->authService->login('naoexiste@exemplo.com', 'senha123');
    }

    /**
     * Teste: Verificar credenciais retorna true para senha correta
     */
    public function test_can_verify_valid_credentials()
    {
        // ARRANGE
        Customer::create([
            'name' => 'Teste Cliente',
            'email' => 'teste@exemplo.com',
            'cpf' => '12345678904',
            'password' => 'senha123',
        ]);

        // ACT
        $result = $this->authService->verifyCredentials('teste@exemplo.com', 'senha123');

        // ASSERT
        $this->assertTrue($result);
    }

    /**
     * Teste: Verificar credenciais retorna false para senha incorreta
     */
    public function test_verify_credentials_returns_false_for_wrong_password()
    {
        // ARRANGE
        Customer::create([
            'name' => 'Teste Cliente',
            'email' => 'teste@exemplo.com',
            'cpf' => '12345678905',
            'password' => 'senha123',
        ]);

        // ACT
        $result = $this->authService->verifyCredentials('teste@exemplo.com', 'senha_errada');

        // ASSERT
        $this->assertFalse($result);
    }
}
