<?php

namespace App\Services\Customer;

use App\Models\Customer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Customer Authentication Service
 * 
 * Responsável por: Login, Logout, Verificação de credenciais
 * 
 * ⚠️ ATENÇÃO: Este módulo é crítico para autenticação de clientes.
 * Qualquer alteração pode afetar login/logout de TODOS os clientes.
 * 
 * @package App\Services\Customer
 */
class CustomerAuthService
{
    /**
     * Autentica um cliente e retorna token
     * 
     * @param string $email
     * @param string $password
     * @return array ['customer' => Customer, 'token' => string]
     * @throws ValidationException
     */
    public function login(string $email, string $password): array
    {
        // Buscar cliente
        $customer = Customer::where('email', $email)->first();

        // Verificar se o cliente existe
        if (!$customer) {
            throw ValidationException::withMessages([
                'email' => ['Email ou senha incorretos.']
            ]);
        }

        // Verificar senha
        if (!Hash::check($password, $customer->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email ou senha incorretos.']
            ]);
        }

        // Verificar se a conta precisa redefinir senha (recuperação de desastre)
        if ($customer->must_reset_password) {
            throw ValidationException::withMessages([
                'email' => ['Sua conta foi recuperada. Por favor, clique em "Esqueci minha senha" para criar uma nova senha.']
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

        return [
            'customer' => $customer,
            'token' => $token
        ];
    }

    /**
     * Revoga o token atual do cliente (logout)
     * 
     * @param Customer $customer
     * @return void
     */
    public function logout(Customer $customer): void
    {
        $customer->currentAccessToken()->delete();
    }

    /**
     * Verifica se um cliente tem permissão para fazer login
     * 
     * @param Customer $customer
     * @return bool
     */
    public function canLogin(Customer $customer): bool
    {
        return !$customer->must_reset_password 
               && $customer->email_verified_at !== null;
    }

    /**
     * Verifica se as credenciais são válidas (sem criar token)
     * 
     * @param string $email
     * @param string $password
     * @return bool
     */
    public function verifyCredentials(string $email, string $password): bool
    {
        $customer = Customer::where('email', $email)->first();
        
        if (!$customer) {
            return false;
        }

        return Hash::check($password, $customer->password);
    }
}
