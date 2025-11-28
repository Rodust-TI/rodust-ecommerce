<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MercadoPagoService;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MercadoPagoWebhookController extends Controller
{
    private MercadoPagoService $mercadoPago;

    public function __construct(MercadoPagoService $mercadoPago)
    {
        $this->mercadoPago = $mercadoPago;
    }

    /**
     * Handle payment notifications from Mercado Pago
     */
    public function handle(Request $request)
    {
        try {
            $data = $request->all();
            
            Log::info('Webhook MercadoPago recebido', [
                'data' => $data,
                'headers' => $request->headers->all()
            ]);

            // Validar assinatura do webhook (x-signature)
            if (!$this->validateWebhookSignature($request)) {
                Log::warning('Webhook MercadoPago com assinatura inválida');
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            // Validar tipo de notificação
            if (!isset($data['type']) || $data['type'] !== 'payment') {
                Log::info('Webhook ignorado - tipo não é payment', ['type' => $data['type'] ?? 'null']);
                return response()->json(['status' => 'ok'], 200);
            }

            // Extrair payment_id
            $paymentId = $data['data']['id'] ?? null;
            
            if (!$paymentId) {
                Log::warning('Webhook sem payment_id', ['data' => $data]);
                return response()->json(['error' => 'payment_id missing'], 400);
            }

            // Buscar status do pagamento na API do Mercado Pago
            $paymentStatus = $this->mercadoPago->getPaymentStatus($paymentId);

            if (!$paymentStatus['success']) {
                Log::error('Erro ao consultar status do pagamento', [
                    'payment_id' => $paymentId,
                    'error' => $paymentStatus['error']
                ]);
                return response()->json(['error' => 'Failed to get payment status'], 500);
            }

            // Buscar pedido pelo payment_id
            $order = Order::where('payment_id', $paymentId)->first();

            if (!$order) {
                Log::warning('Pedido não encontrado para payment_id', ['payment_id' => $paymentId]);
                return response()->json(['error' => 'Order not found'], 404);
            }

            // Atualizar status do pagamento
            $previousStatus = $order->payment_status;
            $newStatus = $paymentStatus['status'];

            $order->update([
                'payment_status' => $newStatus
            ]);

            Log::info('Status de pagamento atualizado', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'payment_id' => $paymentId,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'status_detail' => $paymentStatus['status_detail']
            ]);

            // Se pagamento aprovado, atualizar status do pedido
            if ($paymentStatus['approved'] && $order->status === 'pending') {
                $order->update([
                    'status' => 'processing',
                    'paid_at' => now()
                ]);

                Log::info('Pedido marcado como pago e em processamento', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number
                ]);
            }

            // Se pagamento cancelado/rejeitado
            if (in_array($newStatus, ['cancelled', 'rejected'])) {
                $order->update(['status' => 'cancelled']);
                
                Log::info('Pedido cancelado devido a pagamento rejeitado', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'status_detail' => $paymentStatus['status_detail']
                ]);
            }

            return response()->json(['status' => 'ok'], 200);

        } catch (\Exception $e) {
            Log::error('Erro ao processar webhook MercadoPago', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

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
