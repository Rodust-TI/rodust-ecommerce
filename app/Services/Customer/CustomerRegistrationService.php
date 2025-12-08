<?php

namespace App\Services\Customer;

use App\Models\Customer;
use App\Mail\CustomerVerificationMail;
use App\Jobs\SyncCustomerToBling;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Customer Registration Service
 * 
 * Responsável por: Cadastro de novos clientes, envio de email de verificação
 * 
 * ⚠️ ATENÇÃO: Este módulo é crítico para cadastro de novos clientes.
 * Alterações podem afetar todo o fluxo de registro.
 * 
 * @package App\Services\Customer
 */
class CustomerRegistrationService
{
    /**
     * Registra um novo cliente
     * 
     * @param array $data Dados validados do cliente
     * @return Customer
     * @throws ValidationException
     */
    public function register(array $data): Customer
    {
        // Validar CPF
        if (!Customer::isValidCPF($data['cpf'])) {
            throw ValidationException::withMessages([
                'cpf' => ['CPF inválido. Verifique os números digitados.']
            ]);
        }

        // Gerar token de verificação
        $verificationToken = Str::random(64);

        // Criar cliente (sem email verificado)
        $customer = Customer::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'cpf' => $data['cpf'],
            'password' => $data['password'], // Auto-hash via cast
            'verification_token' => $verificationToken,
            'verification_token_expires_at' => now()->addHours(24),
        ]);

        // Enviar email de verificação
        $this->sendVerificationEmail($customer, $verificationToken);

        // Sincronizar cliente com Bling
        SyncCustomerToBling::dispatch($customer);

        return $customer;
    }

    /**
     * Envia email de verificação para o cliente
     * 
     * @param Customer $customer
     * @param string $token
     * @return void
     */
    public function sendVerificationEmail(Customer $customer, string $token): void
    {
        // URL de verificação (WordPress)
        $verificationUrl = config('urls.wordpress.verify_email') . '?token=' . $token;

        try {
            Mail::to($customer->email)->send(new CustomerVerificationMail($customer, $verificationUrl));
        } catch (\Exception $e) {
            // Silenciar erro de email - não bloqueia cadastro
            // Em produção, usar serviço de monitoramento como Sentry
            \Log::warning('Failed to send verification email', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Reenvia email de verificação
     * 
     * @param string $email
     * @return bool
     */
    public function resendVerification(string $email): bool
    {
        $customer = Customer::where('email', $email)->first();

        if (!$customer) {
            return false;
        }

        // Já verificado
        if ($customer->email_verified_at) {
            return false;
        }

        // Gerar novo token
        $verificationToken = Str::random(64);
        $customer->update([
            'verification_token' => $verificationToken,
            'verification_token_expires_at' => now()->addHours(24),
        ]);

        // Enviar email
        $this->sendVerificationEmail($customer, $verificationToken);

        return true;
    }

    /**
     * Verifica email do cliente usando token
     * 
     * @param string $token
     * @return Customer|null
     */
    public function verifyEmail(string $token): ?Customer
    {
        $customer = Customer::where('verification_token', $token)
            ->where('verification_token_expires_at', '>', now())
            ->first();

        if (!$customer) {
            return null;
        }

        // Marcar email como verificado
        $customer->update([
            'email_verified_at' => now(),
            'verification_token' => null,
            'verification_token_expires_at' => null,
        ]);

        // Disparar sincronização com Bling (assíncrono)
        SyncCustomerToBling::dispatch($customer);

        return $customer;
    }
}
