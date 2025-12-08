<?php

namespace App\Services\Webhook;

use App\Models\Order;
use App\Models\WebhookLog;
use App\Services\MercadoPagoService;
use App\Services\Bling\BlingOrderService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\PaymentConfirmedMail;

class MercadoPagoWebhookHandler
{
    public function __construct(
        private MercadoPagoService $mercadoPago,
        private BlingOrderService $blingOrder
    ) {}

    /**
     * Processar webhook do Mercado Pago
     */
    public function handle(array $data, WebhookLog $webhookLog): void
    {
        $paymentId = $data['data']['id'] ?? null;
        $isSimulator = str_starts_with($paymentId ?? '', 'sim_');

        // Validar tipo de notificação
        if (!isset($data['type']) || $data['type'] !== 'payment') {
            Log::info('Webhook ignorado - tipo não é payment', [
                'type' => $data['type'] ?? 'null',
                'webhook_log_id' => $webhookLog->id
            ]);
            return;
        }

        if (!$paymentId) {
            Log::warning('Webhook sem payment_id', [
                'data' => $data,
                'webhook_log_id' => $webhookLog->id
            ]);
            return;
        }

        // Buscar status do pagamento na API do Mercado Pago
        $paymentStatus = $this->mercadoPago->getPaymentStatus($paymentId);

        if (!$paymentStatus['success']) {
            Log::error('Erro ao consultar status do pagamento', [
                'payment_id' => $paymentId,
                'error' => $paymentStatus['error'],
                'webhook_log_id' => $webhookLog->id
            ]);
            return;
        }

        // Buscar pedido pelo payment_id
        $order = Order::where('payment_id', $paymentId)->first();

        if (!$order) {
            Log::warning('Pedido não encontrado para payment_id', [
                'payment_id' => $paymentId,
                'webhook_log_id' => $webhookLog->id
            ]);
            return;
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
            'webhook_log_id' => $webhookLog->id
        ]);

        // Atualizar metadata do log
        app(WebhookLogService::class)->addMetadata($webhookLog, [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'payment_id' => $paymentId,
            'payment_status' => $newStatus,
        ]);

        // Se pagamento aprovado, atualizar status do pedido
        if ($paymentStatus['approved'] && $order->status === 'pending') {
            $this->handleApprovedPayment($order, $paymentStatus, $webhookLog);
        }

        // Se pagamento cancelado/rejeitado
        if (in_array($newStatus, ['cancelled', 'rejected'])) {
            $order->update(['status' => 'cancelled']);

            Log::info('Pedido cancelado devido a pagamento rejeitado', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'webhook_log_id' => $webhookLog->id
            ]);
        }
    }

    /**
     * Processar pagamento aprovado
     */
    protected function handleApprovedPayment(Order $order, array $paymentStatus, WebhookLog $webhookLog): void
    {
        // Calcular taxa do Mercado Pago
        $transactionAmount = $paymentStatus['transaction_amount'] ?? $order->total;
        $netAmount = $paymentStatus['transaction_details']['net_received_amount'] ?? $transactionAmount;
        $paymentFee = $transactionAmount - $netAmount;

        $order->update([
            'status' => 'processing',
            'paid_at' => now(),
            'payment_fee' => $paymentFee,
            'net_amount' => $netAmount,
            'installments' => $paymentStatus['installments'] ?? 1,
            'payment_details' => [
                'payment_id' => $order->payment_id,
                'payment_method_id' => $paymentStatus['payment_method_id'] ?? null,
                'payment_type_id' => $paymentStatus['payment_type_id'] ?? null,
                'fee_details' => $paymentStatus['fee_details'] ?? [],
                'transaction_amount' => $transactionAmount,
                'net_received_amount' => $netAmount,
                'installment_amount' => $paymentStatus['transaction_details']['installment_amount'] ?? null,
            ]
        ]);

        Log::info('Pedido marcado como pago e em processamento', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'payment_fee' => $paymentFee,
            'net_amount' => $netAmount,
            'webhook_log_id' => $webhookLog->id
        ]);

        // Sincronizar com Bling
        if ($order->bling_order_number) {
            try {
                $this->blingOrder->updateOrder($order);
                Log::info('Status do pedido atualizado no Bling', [
                    'order_id' => $order->id,
                    'bling_order_number' => $order->bling_order_number,
                    'webhook_log_id' => $webhookLog->id
                ]);
            } catch (\Exception $e) {
                Log::error('Erro ao atualizar pedido no Bling', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                    'webhook_log_id' => $webhookLog->id
                ]);
            }
        } else {
            try {
                $blingResult = $this->blingOrder->createOrder($order);
                if ($blingResult['success']) {
                    $order->update([
                        'bling_order_number' => $blingResult['bling_order_number'],
                        'bling_synced_at' => now(),
                    ]);
                    Log::info('Pedido criado no Bling via webhook', [
                        'order_id' => $order->id,
                        'bling_order_number' => $blingResult['bling_order_number'],
                        'webhook_log_id' => $webhookLog->id
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Erro ao criar pedido no Bling via webhook', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                    'webhook_log_id' => $webhookLog->id
                ]);
            }
        }

        // Enviar email de confirmação
        try {
            Mail::to($order->customer->email)->send(new PaymentConfirmedMail($order));
            Log::info('Email de confirmação de pagamento enviado', [
                'order_id' => $order->id,
                'webhook_log_id' => $webhookLog->id
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao enviar email de confirmação', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'webhook_log_id' => $webhookLog->id
            ]);
        }
    }
}

