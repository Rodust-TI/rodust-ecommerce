<?php

namespace App\Exceptions;

/**
 * Exception para erros específicos do Mercado Pago
 * 
 * Usada quando há problemas na comunicação com a API do Mercado Pago:
 * - Erros de pagamento
 * - Dados de cartão inválidos
 * - Falha na criação de pagamento
 */
class MercadoPagoException extends IntegrationException
{
    /**
     * Criar exception para erro de pagamento
     */
    public static function paymentFailed(string $message, ?array $details = null): self
    {
        return new self(
            "Falha no pagamento: {$message}",
            402,
            null,
            array_merge(['type' => 'payment_failed'], $details ?? []),
            402
        );
    }

    /**
     * Criar exception para erro de pagamento com dados do ErrorMapper
     * 
     * @param array $errorMapperData Dados retornados pelo MercadoPagoErrorMapper
     * @param int|null $statusCode Status HTTP da resposta da API
     * @param array|null $apiResponse Resposta completa da API
     */
    public static function paymentFailedWithErrorMapper(
        array $errorMapperData,
        ?int $statusCode = null,
        ?array $apiResponse = null
    ): self {
        return new self(
            $errorMapperData['message'] ?? 'Falha no pagamento',
            $statusCode ?? 402,
            null,
            array_merge(
                [
                    'type' => 'payment_failed',
                    'title' => $errorMapperData['title'] ?? 'Erro no pagamento',
                    'message_type' => $errorMapperData['type'] ?? 'error',
                    'field' => $errorMapperData['field'] ?? null,
                    'can_retry' => $errorMapperData['can_retry'] ?? false,
                    'should_change_payment' => $errorMapperData['should_change_payment'] ?? false,
                ],
                $apiResponse ? ['api_response' => $apiResponse] : []
            ),
            $statusCode ?? 402
        );
    }

    /**
     * Criar exception para dados de cartão inválidos
     */
    public static function invalidCardData(string $field, ?string $details = null): self
    {
        return new self(
            "Dados de cartão inválidos: {$field}. " . ($details ?? ''),
            422,
            null,
            ['type' => 'invalid_card_data', 'field' => $field],
            422
        );
    }

    /**
     * Criar exception para erro de API
     */
    public static function apiError(string $message, ?int $statusCode = null, ?array $response = null): self
    {
        return new self(
            "Erro na API do Mercado Pago: {$message}",
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
