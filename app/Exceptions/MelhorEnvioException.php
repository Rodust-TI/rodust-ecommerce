<?php

namespace App\Exceptions;

/**
 * Exception para erros específicos do Melhor Envio
 * 
 * Usada quando há problemas na comunicação com a API do Melhor Envio:
 * - Falha de autenticação OAuth
 * - Erros no cálculo de frete
 * - Problemas na criação de envio
 */
class MelhorEnvioException extends IntegrationException
{
    /**
     * Criar exception para erro de autenticação
     */
    public static function authenticationFailed(?string $details = null): self
    {
        return new self(
            'Falha na autenticação com Melhor Envio. ' . ($details ?? 'Configure OAuth primeiro.'),
            401,
            null,
            ['type' => 'authentication_failed'],
            401
        );
    }

    /**
     * Criar exception para token não disponível
     */
    public static function tokenNotAvailable(): self
    {
        return new self(
            'Token do Melhor Envio não disponível. Configure a autenticação primeiro.',
            401,
            null,
            ['type' => 'token_not_available'],
            401
        );
    }

    /**
     * Criar exception para erro no cálculo de frete
     */
    public static function shippingCalculationFailed(string $message, ?array $details = null): self
    {
        return new self(
            "Erro ao calcular frete: {$message}",
            422,
            null,
            array_merge(['type' => 'shipping_calculation_failed'], $details ?? []),
            422
        );
    }

    /**
     * Criar exception para erro de API
     */
    public static function apiError(string $message, ?int $statusCode = null, ?array $response = null): self
    {
        return new self(
            "Erro na API do Melhor Envio: {$message}",
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
}
