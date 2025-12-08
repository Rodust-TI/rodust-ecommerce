<?php

namespace App\Services\Customer;

use App\Models\Customer;
use App\Mail\PasswordResetMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Customer Recovery Service
 * 
 * Responsável por: Recuperação de senha (forgot/reset password)
 * 
 * ⚠️ ATENÇÃO: Este módulo é crítico para recuperação de conta.
 * Alterações podem afetar acesso de clientes que esqueceram senha.
 * 
 * @package App\Services\Customer
 */
class CustomerRecoveryService
{
    /**
     * Inicia processo de recuperação de senha
     * 
     * @param string $email
     * @return bool True se email foi enviado, False se não encontrou cliente
     */
    public function initPasswordReset(string $email): bool
    {
        $customer = Customer::where('email', $email)->first();

        if (!$customer) {
            // Não revelar se o email existe ou não (segurança)
            return true; // Retorna true para não revelar
        }

        // Gerar token de reset
        $resetToken = Str::random(64);
        
        $customer->update([
            'password_reset_token' => $resetToken,
            'password_reset_token_expires_at' => now()->addHours(1),
        ]);

        // URL de reset (WordPress)
        $resetUrl = config('urls.wordpress.reset_password') . '?token=' . $resetToken;

        // Enviar email
        try {
            Mail::to($customer->email)->send(new PasswordResetMail($customer, $resetUrl));
        } catch (\Exception $e) {
            Log::error('Erro ao enviar email de recuperação de senha', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage()
            ]);
        }

        return true;
    }

    /**
     * Redefine a senha usando token
     * 
     * @param string $token
     * @param string $newPassword
     * @return Customer|null
     */
    public function resetPassword(string $token, string $newPassword): ?Customer
    {
        $customer = Customer::where('password_reset_token', $token)
            ->where('password_reset_token_expires_at', '>', now())
            ->first();

        if (!$customer) {
            return null;
        }

        // Atualizar senha e limpar flags de reset
        $customer->update([
            'password' => $newPassword, // Auto-hash via cast
            'password_reset_token' => null,
            'password_reset_token_expires_at' => null,
            'must_reset_password' => false,
            'email_verified_at' => $customer->email_verified_at ?? now(), // Verificar email automaticamente
        ]);

        return $customer;
    }

    /**
     * Verifica se um token de reset é válido
     * 
     * @param string $token
     * @return bool
     */
    public function isValidResetToken(string $token): bool
    {
        return Customer::where('password_reset_token', $token)
            ->where('password_reset_token_expires_at', '>', now())
            ->exists();
    }
}
