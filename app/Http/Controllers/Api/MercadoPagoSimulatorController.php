<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Enums\PaymentStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller para SIMULAR webhooks do Mercado Pago em ambiente de desenvolvimento
 * 
 * ⚠️ REMOVER EM PRODUÇÃO ou proteger com middleware de autenticação
 */
class MercadoPagoSimulatorController extends Controller
{
    /**
     * Simular aprovação de pagamento PIX
     * 
     * POST /api/dev/simulate-pix-payment
     * Body: { "order_id": 123 }
     * 
     * Simula o webhook que o Mercado Pago enviaria quando o PIX é pago
     */
    public function simulatePixPayment(Request $request)
    {
        if (config('app.env') === 'production') {
            return response()->json(['error' => 'Simulador disponível apenas em desenvolvimento'], 403);
        }

        $orderId = $request->input('order_id');
        
        if (!$orderId) {
            return response()->json(['error' => 'order_id é obrigatório'], 400);
        }

        $order = Order::find($orderId);
        
        if (!$order) {
            return response()->json(['error' => 'Pedido não encontrado'], 404);
        }

        // Simular estrutura do webhook do Mercado Pago
        // Se o pedido já tiver payment_id, usar ele. Caso contrário, criar um ID simulado
        $paymentId = $order->payment_id ?? 'sim_' . time();
        
        $webhookData = [
            'action' => 'payment.updated',
            'api_version' => 'v1',
            'data' => [
                'id' => $paymentId
            ],
            'date_created' => now()->toIso8601String(),
            'id' => rand(1000000, 9999999),
            'live_mode' => false,
            'type' => 'payment',
            'user_id' => config('services.mercadopago.user_id', '123456'),
        ];

        Log::info('🧪 SIMULADOR: Enviando webhook simulado para pedido', [
            'order_id' => $orderId,
            'webhook_data' => $webhookData
        ]);

        // Chamar o webhook controller real
        $webhookController = app(MercadoPagoWebhookController::class);
        $mockRequest = Request::create(
            '/api/webhooks/mercadopago',
            'POST',
            $webhookData
        );

        $response = $webhookController->handle($mockRequest);

        return response()->json([
            'success' => true,
            'message' => '🧪 Webhook simulado enviado',
            'order_id' => $orderId,
            'order_status' => $order->fresh()->status,
            'payment_status' => $order->fresh()->payment_status,
            'webhook_data' => $webhookData,
            'webhook_response' => $response->getData()
        ]);
    }

    /**
     * Simular diferentes status de pagamento
     * 
     * POST /api/dev/simulate-payment-status
     * Body: { 
     *   "order_id": 123,
     *   "status": "approved|rejected|pending|in_process" 
     * }
     */
    public function simulatePaymentStatus(Request $request)
    {
        if (config('app.env') === 'production') {
            return response()->json(['error' => 'Simulador disponível apenas em desenvolvimento'], 403);
        }

        $orderId = $request->input('order_id');
        $status = $request->input('status', 'approved');
        
        $order = Order::find($orderId);
        
        if (!$order) {
            return response()->json(['error' => 'Pedido não encontrado'], 404);
        }

        // Mapear status
        $paymentStatus = match($status) {
            'approved' => PaymentStatus::APPROVED,
            'rejected' => PaymentStatus::REJECTED,
            'pending' => PaymentStatus::PENDING,
            'in_process' => PaymentStatus::IN_PROCESS,
            'cancelled' => PaymentStatus::CANCELLED,
            default => PaymentStatus::PENDING
        };

        $order->update([
            'payment_status' => $paymentStatus->value
        ]);

        Log::info('🧪 SIMULADOR: Status de pagamento alterado manualmente', [
            'order_id' => $orderId,
            'old_status' => $order->getOriginal('payment_status'),
            'new_status' => $paymentStatus->value
        ]);

        // Se o status for "approved", disparar a lógica de aprovação (email + Bling sync)
        if ($paymentStatus === PaymentStatus::APPROVED && $order->status === 'pending') {
            $order->update([
                'status' => 'processing',
                'paid_at' => now()
            ]);

            // Enviar para o Bling
            \App\Jobs\SyncOrderToBling::dispatch($order);
            Log::info('🧪 Pedido enviado para sincronização com Bling');

            // Enviar email de confirmação
            try {
                \Illuminate\Support\Facades\Mail::to($order->customer->email)
                    ->send(new \App\Mail\PaymentConfirmedMail($order));
                Log::info('🧪 Email de confirmação enviado');
            } catch (\Exception $e) {
                Log::error('Erro ao enviar email', ['error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => '🧪 Status de pagamento simulado',
            'order_id' => $orderId,
            'payment_status' => $paymentStatus->value,
            'order' => $order->fresh()
        ]);
    }

    /**
     * Listar pedidos pendentes de pagamento PIX
     * 
     * GET /api/dev/pending-pix-orders
     */
    public function listPendingPixOrders()
    {
        if (config('app.env') === 'production') {
            return response()->json(['error' => 'Simulador disponível apenas em desenvolvimento'], 403);
        }

        $orders = Order::where('payment_method', 'pix')
            ->where('payment_status', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'total' => $orders->count(),
            'orders' => $orders->map(function($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'total' => $order->total,
                    'payment_status' => $order->payment_status,
                    'created_at' => $order->created_at->format('d/m/Y H:i'),
                    'pix_qr_code' => $order->pix_qr_code ? 'Disponível' : 'N/A',
                    'simulate_url' => url("/api/dev/simulate-pix-payment?order_id={$order->id}")
                ];
            })
        ]);
    }
}
