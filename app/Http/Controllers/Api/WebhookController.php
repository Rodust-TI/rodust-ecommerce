<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Webhook\WebhookLogService;
use App\Services\Webhook\BlingWebhookHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private WebhookLogService $logService,
        private BlingWebhookHandler $blingHandler
    ) {}

    /**
     * Handle incoming webhooks from Bling ERP
     * 
     * Webhook types:
     * - produtos (products): Create/update/delete product
     * - estoques (stock): Update stock levels
     * - pedidos (orders): Order status changes
     * - notasfiscais (invoices): Fiscal note issued
     * - nfce (consumer invoices): Consumer note issued
     */
    public function handle(Request $request)
    {
        $startTime = microtime(true);
        $payload = $request->all();
        
        // Criar log do webhook ANTES de processar
        $webhookLog = $this->logService->createLog('bling', $request, $payload);
        
        // Salvar no cache para o comando de escuta
        \Illuminate\Support\Facades\Cache::put("webhook_recent_bling", [
            'id' => $webhookLog->id,
            'type' => $payload['event'] ?? null,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ], 60); // 1 minuto

        Log::info('Bling Webhook Received', [
            'webhook_log_id' => $webhookLog->id,
            'event_id' => $webhookLog->event_id,
            'event' => $webhookLog->event_type,
        ]);

        // Validate webhook signature
        if (!$this->validateWebhook($request)) {
            $this->logService->markAsError($webhookLog, 'Invalid signature', 401);
            
            Log::warning('Invalid Bling webhook signature', [
                'webhook_log_id' => $webhookLog->id
            ]);
            
            return response()->json(['error' => 'Invalid signature'], 401);
        }
        
        // Atualizar status para processing
        $this->logService->markAsProcessing($webhookLog);

        // Extrair evento no formato "resource.action" (ex: "order.updated", "stock.updated")
        $event = $payload['event'] ?? null;
        
        if (!$event) {
            $this->logService->markAsError($webhookLog, 'Missing event field', 400);
            Log::warning('Bling webhook sem campo "event"', ['webhook_log_id' => $webhookLog->id]);
            return response()->json(['error' => 'Missing event field'], 400);
        }
        
        // Separar resource e action
        $parts = explode('.', $event);
        if (count($parts) !== 2) {
            $this->logService->markAsError($webhookLog, 'Invalid event format', 400);
            Log::warning('Bling webhook event com formato inválido', [
                'event' => $event,
                'webhook_log_id' => $webhookLog->id
            ]);
            return response()->json(['error' => 'Invalid event format'], 400);
        }
        
        [$resource, $action] = $parts;

        try {
            // Log completo do payload para debug (apenas em desenvolvimento)
            if (config('app.env') === 'local') {
                Log::debug('Bling Webhook - Payload completo', [
                    'webhook_log_id' => $webhookLog->id,
                    'event' => $event,
                    'resource' => $resource,
                    'action' => $action,
                    'full_payload' => $payload,
                ]);
            }
            
            // Processar webhook usando handler dedicado
            $this->blingHandler->handle($payload, $action, $webhookLog);

            // IMPORTANTE: Bling requer resposta 2xx em até 5 segundos
            $response = response()->json(['success' => true], 200);
            
            // Atualizar log com sucesso
            $this->logService->markAsSuccess($webhookLog, '{"success": true}', 200, [
                'processing_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ]);
            
            return $response;

        } catch (\Exception $e) {
            Log::error('Webhook processing error', [
                'webhook_log_id' => $webhookLog->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Atualizar log com erro
            $this->logService->markAsError($webhookLog, $e->getMessage(), 500);

            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Validate webhook signature from Bling
     * 
     * Conforme documentação: https://developer.bling.com.br/webhooks
     * O Bling envia um hash HMAC-SHA256 no header X-Bling-Signature-256
     * Formato: sha256={hash}
     * 
     * O hash é calculado usando o payload JSON e o client_secret
     */
    protected function validateWebhook(Request $request): bool
    {
        $signatureHeader = $request->header('X-Bling-Signature-256');
        
        // Em ambiente local, aceitar sem assinatura para facilitar testes
        if (!$signatureHeader && config('app.env') === 'local') {
            Log::info('Bling webhook sem assinatura - aceito em ambiente local');
            return true;
        }
        
        if (!$signatureHeader) {
            Log::warning('Bling webhook sem header X-Bling-Signature-256');
            return false;
        }
        
        // Extrair hash do formato "sha256={hash}"
        if (!str_starts_with($signatureHeader, 'sha256=')) {
            Log::warning('Bling webhook signature com formato inválido', [
                'header' => $signatureHeader
            ]);
            return false;
        }
        
        $receivedHash = substr($signatureHeader, 7); // Remove "sha256="
        
        // Obter client_secret do config
        $clientSecret = config('services.bling.client_secret');
        
        if (!$clientSecret) {
            Log::error('Bling client_secret não configurado para validação de webhook');
            return false;
        }
        
        // Obter payload bruto (JSON)
        $payload = $request->getContent();
        
        // Calcular hash HMAC-SHA256
        $calculatedHash = hash_hmac('sha256', $payload, $clientSecret);
        
        // Comparar hashes usando hash_equals para evitar timing attacks
        $isValid = hash_equals($receivedHash, $calculatedHash);
        
        if (!$isValid) {
            Log::warning('Bling webhook signature inválida', [
                'received' => substr($receivedHash, 0, 16) . '...',
                'calculated' => substr($calculatedHash, 0, 16) . '...'
            ]);
        } else {
            Log::debug('Bling webhook signature válida');
        }
        
        return $isValid;
    }
}
