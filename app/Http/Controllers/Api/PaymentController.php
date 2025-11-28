<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\CreatePaymentRequest;
use App\Services\MercadoPagoService;
use App\Services\Order\OrderCreationService;
use App\Services\Payment\CustomerDataFormatter;
use App\Services\Bling\BlingOrderService;
use App\Models\Order;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Controller: Pagamentos
 * Responsabilidade: Coordenar criação de pedidos e processamento de pagamentos
 */
class PaymentController extends Controller
{
    public function __construct(
        private MercadoPagoService $mercadoPago,
        private OrderCreationService $orderCreation,
        private CustomerDataFormatter $customerFormatter,
        private BlingOrderService $blingOrder
    ) {}

    /**
     * Criar pedido e gerar pagamento PIX
     */
    public function createPixPayment(CreatePaymentRequest $request)
    {
        DB::beginTransaction();

        try {
            $customer = Customer::findOrFail($request->customer_id);
            
            // Criar pedido
            $order = $this->orderCreation->createFromRequest($request, $customer, 'pix');

            // Gerar pagamento PIX
            $customerData = $this->customerFormatter->formatForMercadoPago($customer);
            $pixResult = $this->mercadoPago->createPixPayment($order, $customerData);

            if (!$pixResult['success']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => $pixResult['error']
                ], 400);
            }

            // Atualizar pedido com ID do pagamento
            $order->update([
                'payment_id' => $pixResult['payment_id'],
                'payment_status' => $pixResult['status']
            ]);

            // Criar pedido no Bling
            $blingResult = $this->blingOrder->createOrder($order);
            if ($blingResult['success']) {
                $order->update([
                    'bling_order_number' => $blingResult['bling_order_number'],
                    'bling_synced_at' => now()
                ]);
                Log::info('Pedido sincronizado com Bling', [
                    'order_id' => $order->id,
                    'bling_order_number' => $blingResult['bling_order_number']
                ]);
            } else {
                Log::warning('Falha ao criar pedido no Bling (pedido Laravel criado)', [
                    'order_id' => $order->id,
                    'error' => $blingResult['error']
                ]);
            }

            DB::commit();

            Log::info('Pedido PIX criado', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'payment_id' => $pixResult['payment_id']
            ]);

            // Recarregar pedido com relacionamentos para retornar dados completos
            $order->load(['customer', 'items.product']);

            return response()->json([
                'success' => true,
                'message' => 'Pedido criado com sucesso',
                'data' => [
                    'order' => [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'status' => $order->status,
                        'payment_method' => $order->payment_method,
                        'payment_status' => $order->payment_status,
                        'subtotal' => (float) $order->subtotal,
                        'shipping' => (float) $order->shipping,
                        'total' => (float) $order->total,
                        'bling_order_number' => $order->bling_order_number,
                        'created_at' => $order->created_at->toIso8601String(),
                        'customer' => [
                            'name' => $order->customer->name,
                            'email' => $order->customer->email,
                        ],
                        'items' => $order->items->map(function ($item) {
                            return [
                                'product_name' => $item->product_name,
                                'product_sku' => $item->product_sku,
                                'quantity' => $item->quantity,
                                'unit_price' => (float) $item->unit_price,
                                'total_price' => (float) $item->total_price,
                            ];
                        }),
                    ],
                    'payment' => [
                        'payment_id' => $pixResult['payment_id'],
                        'qr_code' => $pixResult['qr_code'],
                        'qr_code_base64' => $pixResult['qr_code_base64'],
                        'ticket_url' => $pixResult['ticket_url']
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao criar pedido PIX', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Criar pedido e gerar boleto
     */
    public function createBoletoPayment(CreatePaymentRequest $request)
    {
        DB::beginTransaction();

        try {
            $customer = Customer::findOrFail($request->customer_id);
            $order = $this->orderCreation->createFromRequest($request, $customer, 'boleto');

            $customerData = $this->customerFormatter->formatForMercadoPago($customer);
            $boletoResult = $this->mercadoPago->createBoletoPayment($order, $customerData);

            if (!$boletoResult['success']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => $boletoResult['error']
                ], 400);
            }

            $order->update([
                'payment_id' => $boletoResult['payment_id'],
                'payment_status' => 'pending'
            ]);

            // Criar pedido no Bling
            $blingResult = $this->blingOrder->createOrder($order);
            if ($blingResult['success']) {
                $order->update([
                    'bling_order_number' => $blingResult['bling_order_number'],
                    'bling_synced_at' => now()
                ]);
                Log::info('Pedido boleto sincronizado com Bling', [
                    'order_id' => $order->id,
                    'bling_order_number' => $blingResult['bling_order_number']
                ]);
            }

            DB::commit();

            Log::info('Pedido Boleto criado', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'payment_id' => $boletoResult['payment_id']
            ]);

            $order->load(['customer', 'items.product']);

            return response()->json([
                'success' => true,
                'message' => 'Boleto gerado com sucesso',
                'data' => [
                    'order' => [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'status' => $order->status,
                        'payment_method' => $order->payment_method,
                        'payment_status' => $order->payment_status,
                        'subtotal' => (float) $order->subtotal,
                        'shipping' => (float) $order->shipping,
                        'total' => (float) $order->total,
                        'created_at' => $order->created_at->toIso8601String(),
                    ],
                    'boleto' => [
                        'payment_id' => $boletoResult['payment_id'],
                        'ticket_url' => $boletoResult['ticket_url'],
                        'barcode' => $boletoResult['barcode'],
                        'due_date' => $boletoResult['due_date']
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao criar boleto', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar boleto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Criar pedido e processar cartão de crédito
     */
    public function createCardPayment(CreatePaymentRequest $request)
    {
        DB::beginTransaction();

        try {
            // Validar dados do cartão
            $request->validate([
                'card_token' => 'required|string',
                'installments' => 'required|integer|min:1|max:12',
                'payment_method_id' => 'required|string',
                'issuer_id' => 'required|string'
            ]);

            $customer = Customer::findOrFail($request->customer_id);
            $order = $this->orderCreation->createFromRequest($request, $customer, 'credit_card');

            $customerData = $this->customerFormatter->formatForMercadoPago($customer);
            
            $cardData = [
                'token' => $request->card_token,
                'installments' => $request->installments,
                'payment_method_id' => $request->payment_method_id,
                'issuer_id' => $request->issuer_id
            ];

            $cardResult = $this->mercadoPago->createCardPayment($order, $customerData, $cardData);

            if (!$cardResult['success']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => $cardResult['error']
                ], 400);
            }

            $order->update([
                'payment_id' => $cardResult['payment_id'],
                'payment_status' => $cardResult['status']
            ]);

            // Se aprovado, criar pedido no Bling
            if ($cardResult['approved']) {
                $blingResult = $this->blingOrder->createOrder($order);
                if ($blingResult['success']) {
                    $order->update([
                        'bling_order_number' => $blingResult['bling_order_number'],
                        'bling_synced_at' => now(),
                        'status' => 'processing'
                    ]);
                    Log::info('Pedido cartão sincronizado com Bling', [
                        'order_id' => $order->id,
                        'bling_order_number' => $blingResult['bling_order_number']
                    ]);
                }
            }

            DB::commit();

            Log::info('Pedido Cartão criado', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'payment_id' => $cardResult['payment_id'],
                'status' => $cardResult['status']
            ]);

            $order->load(['customer', 'items.product']);

            return response()->json([
                'success' => true,
                'message' => $cardResult['approved'] ? 'Pagamento aprovado!' : 'Pagamento em análise',
                'data' => [
                    'order' => [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'status' => $order->status,
                        'payment_method' => $order->payment_method,
                        'payment_status' => $order->payment_status,
                        'subtotal' => (float) $order->subtotal,
                        'shipping' => (float) $order->shipping,
                        'total' => (float) $order->total,
                        'created_at' => $order->created_at->toIso8601String(),
                    ],
                    'payment' => [
                        'payment_id' => $cardResult['payment_id'],
                        'status' => $cardResult['status'],
                        'status_detail' => $cardResult['status_detail'],
                        'approved' => $cardResult['approved']
                    ]
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Dados do cartão inválidos',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao processar pagamento com cartão', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar pagamento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obter Public Key para o frontend
     */
    public function getPublicKey()
    {
        return response()->json([
            'success' => true,
            'public_key' => $this->mercadoPago->getPublicKey()
        ]);
    }
}
