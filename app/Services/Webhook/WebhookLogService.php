<?php

namespace App\Services\Webhook;

use App\Models\WebhookLog;
use Illuminate\Http\Request;

class WebhookLogService
{
    /**
     * Criar log de webhook recebido
     */
    public function createLog(string $source, Request $request, array $payload): WebhookLog
    {
        $eventId = $payload['eventId'] ?? $payload['id'] ?? null;
        $event = $payload['event'] ?? $payload['type'] ?? null;
        
        return WebhookLog::create([
            'source' => $source,
            'event_id' => $eventId,
            'event_type' => $event,
            'resource' => $this->extractResource($event),
            'action' => $this->extractAction($event),
            'status' => 'received',
            'payload' => json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'metadata' => [
                'headers' => $request->headers->all(),
                'ip' => $request->ip(),
            ],
        ]);
    }

    /**
     * Atualizar status do log
     */
    public function updateStatus(WebhookLog $log, string $status, ?string $errorMessage = null, ?int $responseCode = null): void
    {
        $log->update([
            'status' => $status,
            'error_message' => $errorMessage,
            'response_code' => $responseCode,
            'processed_at' => $status === 'success' || $status === 'error' ? now() : null,
        ]);
    }

    /**
     * Marcar como processando
     */
    public function markAsProcessing(WebhookLog $log): void
    {
        $this->updateStatus($log, 'processing');
    }

    /**
     * Marcar como sucesso
     */
    public function markAsSuccess(WebhookLog $log, string $response = '{"success": true}', int $responseCode = 200, array $metadata = []): void
    {
        $currentMetadata = $log->metadata ?? [];
        $log->update([
            'status' => 'success',
            'response' => $response,
            'response_code' => $responseCode,
            'processed_at' => now(),
            'metadata' => array_merge($currentMetadata, $metadata),
        ]);
    }

    /**
     * Marcar como erro
     */
    public function markAsError(WebhookLog $log, string $errorMessage, int $responseCode = 500): void
    {
        $this->updateStatus($log, 'error', $errorMessage, $responseCode);
    }

    /**
     * Adicionar metadata ao log
     */
    public function addMetadata(WebhookLog $log, array $metadata): void
    {
        $currentMetadata = $log->metadata ?? [];
        $log->update([
            'metadata' => array_merge($currentMetadata, $metadata),
        ]);
    }

    /**
     * Extrair resource do event (ex: "order.updated" -> "order")
     */
    protected function extractResource(?string $event): ?string
    {
        if (!$event) return null;
        $parts = explode('.', $event);
        return $parts[0] ?? null;
    }

    /**
     * Extrair action do event (ex: "order.updated" -> "updated")
     */
    protected function extractAction(?string $event): ?string
    {
        if (!$event) return null;
        $parts = explode('.', $event);
        return $parts[1] ?? null;
    }
}

