<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Exception base para erros de integração
 * 
 * Usada como classe base para todas as exceptions de integrações externas.
 * Permite tratamento centralizado de erros de APIs externas.
 */
class IntegrationException extends Exception
{
    protected ?array $context = null;
    protected ?int $statusCode = null;

    public function __construct(
        string $message = "",
        int $code = 0,
        ?\Throwable $previous = null,
        ?array $context = null,
        ?int $statusCode = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context ?? [];
        $this->statusCode = $statusCode ?? 500;
    }

    /**
     * Obter contexto adicional do erro
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Obter código de status HTTP sugerido
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Renderizar exception como resposta HTTP
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'message' => $this->getMessage(),
                'code' => $this->getCode(),
                'type' => class_basename($this),
            ],
            'context' => $this->context,
        ], $this->statusCode);
    }
}
