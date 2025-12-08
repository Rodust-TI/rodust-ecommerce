<?php

namespace App\Exceptions;

/**
 * Exception para erros específicos do Bling
 * 
 * Usada quando há problemas na comunicação com a API do Bling:
 * - Falha de autenticação
 * - Erros de API
 * - Timeout
 * - Dados inválidos
 */
class BlingException extends IntegrationException
{
    /**
     * Criar exception para erro de autenticação
     */
    public static function authenticationFailed(?string $details = null): self
    {
        return new self(
            'Falha na autenticação com Bling. ' . ($details ?? 'Verifique suas credenciais.'),
            401,
            null,
            ['type' => 'authentication_failed'],
            401
        );
    }

    /**
     * Criar exception para token expirado
     */
    public static function tokenExpired(): self
    {
        return new self(
            'Token do Bling expirado. Renovação automática falhou.',
            401,
            null,
            ['type' => 'token_expired'],
            401
        );
    }

    /**
     * Criar exception para erro de API
     */
    public static function apiError(string $message, ?int $statusCode = null, ?array $response = null): self
    {
        return new self(
            "Erro na API do Bling: {$message}",
            $statusCode ?? 500,
            null,
            [
                'type' => 'api_error',
                'status_code' => $statusCode,
                'response' => $response,
            ],
            $statusCode ?? 500
        );
    }

    /**
     * Criar exception para timeout
     */
    public static function timeout(int $seconds = 30): self
    {
        return new self(
            "Timeout na comunicação com Bling após {$seconds} segundos.",
            504,
            null,
            ['type' => 'timeout', 'seconds' => $seconds],
            504
        );
    }

    /**
     * Criar exception para dados inválidos
     */
    public static function invalidData(string $field, ?string $details = null): self
    {
        return new self(
            "Dados inválidos para Bling: {$field}. " . ($details ?? ''),
            422,
            null,
            ['type' => 'invalid_data', 'field' => $field],
            422
        );
    }
}
