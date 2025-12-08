<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MercadoPagoService;
use App\Services\Webhook\WebhookLogService;
use App\Services\Webhook\MercadoPagoWebhookHandler;
use App\Models\Order;
use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MercadoPagoWebhookController extends Controller
{
    public function __construct(
        private MercadoPagoService $mercadoPago,
        private WebhookLogService $logService,
        private MercadoPagoWebhookHandler $handler
    ) {}

    /**
     * Handle payment notifications from Mercado Pago
     */
    public function handle(Request $request)
    {
        $startTime = microtime(true);
        $data = $request->all();
        
        // Criar log do webhook ANTES de processar
        $webhookLog = $this->logService->createLog('mercadopago', $request, $data);
        
        // Salvar no cache para o comando de escuta
        Cache::put("webhook_recent_mercadopago", [
            'id' => $webhookLog->id,
            'type' => $data['type'] ?? null,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ], 60); // 1 minuto
        
        Log::info('Webhook MercadoPago recebido', [
            'webhook_log_id' => $webhookLog->id,
            'type' => $data['type'] ?? null,
            'payment_id' => $data['data']['id'] ?? null,
        ]);

        try {
            $paymentId = $data['data']['id'] ?? null;
            $isSimulator = str_starts_with($paymentId ?? '', 'sim_');
            $isDevelopment = config('app.env') !== 'production';

            // Validar assinatura do webhook
            if (!$isDevelopment && !$this->validateWebhookSignature($request)) {
                $this->logService->markAsError($webhookLog, 'Invalid signature', 401);
                Log::warning('Webhook MercadoPago com assinatura inválida', [
                    'webhook_log_id' => $webhookLog->id
                ]);
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            // Validar tipo de notificação
            if (!isset($data['type']) || $data['type'] !== 'payment') {
                $this->logService->markAsSuccess($webhookLog, '{"status": "ok"}', 200, [
                    'reason' => 'Tipo não é payment',
                    'type' => $data['type'] ?? 'null',
                ]);
                Log::info('Webhook ignorado - tipo não é payment', [
                    'type' => $data['type'] ?? 'null',
                    'webhook_log_id' => $webhookLog->id
                ]);
                return response()->json(['status' => 'ok'], 200);
            }
            
            if (!$paymentId) {
                $this->logService->markAsError($webhookLog, 'payment_id missing', 400);
                Log::warning('Webhook sem payment_id', [
                    'data' => $data,
                    'webhook_log_id' => $webhookLog->id
                ]);
                return response()->json(['error' => 'payment_id missing'], 400);
            }

            // Atualizar status para processing
            $this->logService->markAsProcessing($webhookLog);

            // Processar webhook usando handler dedicado
            $this->handler->handle($data, $webhookLog);

            // Retornar sucesso
            $response = response()->json(['status' => 'ok'], 200);
            
            // Atualizar log com sucesso
            $this->logService->markAsSuccess($webhookLog, '{"status": "ok"}', 200, [
                'processing_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ]);
            
            return $response;

        } catch (\Exception $e) {
            Log::error('Erro ao processar webhook MercadoPago', [
                'webhook_log_id' => $webhookLog->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Atualizar log com erro
            if (isset($webhookLog)) {
                $this->logService->markAsError($webhookLog, $e->getMessage(), 500);
            }

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Validate Mercado Pago webhook signature
     * 
     * @see https://www.mercadopago.com.br/developers/pt/docs/your-integrations/notifications/webhooks#editor_3
     */
    private function validateWebhookSignature(Request $request): bool
    {
        $secret = config('services.mercadopago.webhook_secret');
        
        // Se não tiver secret configurado, pular validação em dev
        if (empty($secret)) {
            Log::warning('MERCADOPAGO_WEBHOOK_SECRET não configurado - validação ignorada');
            return true;
        }

        // Pegar headers do Mercado Pago
        $xSignature = $request->header('x-signature');
        $xRequestId = $request->header('x-request-id');

        if (!$xSignature || !$xRequestId) {
            Log::warning('Headers x-signature ou x-request-id ausentes');
            return false;
        }

        // Extrair ts e v1 do header x-signature
        // Formato: "ts=1234567890,v1=hash"
        $parts = [];
        foreach (explode(',', $xSignature) as $part) {
            [$key, $value] = explode('=', $part, 2);
            $parts[trim($key)] = trim($value);
        }

        $ts = $parts['ts'] ?? null;
        $hash = $parts['v1'] ?? null;

        if (!$ts || !$hash) {
            Log::warning('Formato de x-signature inválido', ['x-signature' => $xSignature]);
            return false;
        }

        // Construir string para validação
        $dataId = $request->input('data.id');
        $manifest = "id:{$dataId};request-id:{$xRequestId};ts:{$ts};";

        // Calcular HMAC
        $expectedHash = hash_hmac('sha256', $manifest, $secret);

        // Comparar hashes
        if (!hash_equals($expectedHash, $hash)) {
            Log::warning('Hash do webhook não corresponde', [
                'expected' => $expectedHash,
                'received' => $hash,
                'manifest' => $manifest
            ]);
            return false;
        }

        Log::info('Assinatura do webhook validada com sucesso');
        return true;
    }
}
