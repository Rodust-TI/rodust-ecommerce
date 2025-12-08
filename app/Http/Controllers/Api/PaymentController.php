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
use App\Exceptions\MercadoPagoException;
use App\Exceptions\BlingException;
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
            
            // IMPORTANTE: Criar pedido no Bling ANTES de processar pagamento
            // para evitar que o cliente pague um pedido que não pode ser criado no Bling
            // Primeiro, criar o pedido temporariamente no Laravel para obter o número
            $order = $this->orderCreation->createFromRequest($request, $customer, 'pix');
            
            // Tentar criar no Bling ANTES de processar pagamento
            try {
                $blingResult = $this->blingOrder->createOrder($order);
                
                if ($blingResult['success']) {
                    // Pedido criado no Bling com sucesso - associar ID
                    $order->update([
                        'bling_order_number' => $blingResult['bling_order_number'],
                        'bling_synced_at' => now(),
                    ]);
                    Log::info('Pedido criado no Bling antes do pagamento PIX', [
                        'order_id' => $order->id,
                        'bling_order_number' => $blingResult['bling_order_number']
                    ]);
                } else {
                    // Erro ao criar no Bling - verificar se é duplicação
                    throw new \Exception('Erro ao criar pedido no Bling: ' . ($blingResult['error'] ?? 'Unknown error'));
                }
            } catch (\App\Exceptions\BlingDuplicateOrderException $e) {
                // Pedido duplicado - deletar pedido do Laravel e retornar erro
                DB::rollBack();
                $order->delete(); // Deletar pedido criado
                
                Log::warning('Pedido duplicado no Bling - cancelando criação', [
                    'order_number' => $e->getOrderNumber()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Este pedido já foi processado anteriormente. Por favor, verifique seus pedidos ou tente novamente em alguns instantes.',
                    'title' => 'Pedido Duplicado',
                    'message_type' => 'error',
                ], 409); // 409 Conflict
            }

            // Gerar pagamento PIX
            $customerData = $this->customerFormatter->formatForMercadoPago($customer);
            $pixResult = $this->mercadoPago->createPixPayment($order, $customerData);

            // Atualizar pedido com ID do pagamento
            $order->update([
                'payment_id' => $pixResult['payment_id'],
                'payment_status' => $pixResult['status']
            ]);

            // Pedido já está no Bling (criado acima), então não precisa criar novamente
            Log::info('Pedido PIX criado - aguardando aprovação do pagamento', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'payment_id' => $pixResult['payment_id'],
                'bling_order_number' => $order->bling_order_number
            ]);

            DB::commit();
            
        } catch (MercadoPagoException $e) {
            DB::rollBack();
            
            $context = $e->getContext();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'title' => $context['title'] ?? 'Erro no pagamento',
                'message_type' => $context['message_type'] ?? 'error',
                'field' => $context['field'] ?? null,
                'can_retry' => $context['can_retry'] ?? false,
                'should_change_payment' => $context['should_change_payment'] ?? false,
            ], $e->getStatusCode());
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro inesperado ao criar pagamento PIX', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor. Tente novamente em alguns instantes.'
            ], 500);
        }

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

    }

    /**
     * Criar pedido e gerar boleto
     */
    public function createBoletoPayment(CreatePaymentRequest $request)
    {
        DB::beginTransaction();

        try {
            $customer = Customer::findOrFail($request->customer_id);
            
            // IMPORTANTE: Criar pedido no Bling ANTES de processar pagamento
            // para evitar que o cliente pague um pedido que não pode ser criado no Bling
            // Primeiro, criar o pedido temporariamente no Laravel para obter o número
            $order = $this->orderCreation->createFromRequest($request, $customer, 'boleto');
            
            // Tentar criar no Bling ANTES de processar pagamento
            try {
                $blingResult = $this->blingOrder->createOrder($order);
                
                if ($blingResult['success']) {
                    // Pedido criado no Bling com sucesso - associar ID
                    $order->update([
                        'bling_order_number' => $blingResult['bling_order_number'],
                        'bling_synced_at' => now(),
                    ]);
                    Log::info('Pedido criado no Bling antes do pagamento Boleto', [
                        'order_id' => $order->id,
                        'bling_order_number' => $blingResult['bling_order_number']
                    ]);
                } else {
                    // Erro ao criar no Bling - verificar se é duplicação
                    throw new \Exception('Erro ao criar pedido no Bling: ' . ($blingResult['error'] ?? 'Unknown error'));
                }
            } catch (\App\Exceptions\BlingDuplicateOrderException $e) {
                // Pedido duplicado - deletar pedido do Laravel e retornar erro
                DB::rollBack();
                $order->delete(); // Deletar pedido criado
                
                Log::warning('Pedido duplicado no Bling - cancelando criação', [
                    'order_number' => $e->getOrderNumber()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Este pedido já foi processado anteriormente. Por favor, verifique seus pedidos ou tente novamente em alguns instantes.',
                    'title' => 'Pedido Duplicado',
                    'message_type' => 'error',
                ], 409); // 409 Conflict
            }

            $customerData = $this->customerFormatter->formatForMercadoPago($customer);
            $boletoResult = $this->mercadoPago->createBoletoPayment($order, $customerData);

            $order->update([
                'payment_id' => $boletoResult['payment_id'],
                'payment_status' => 'pending'
            ]);

            // Pedido já está no Bling (criado acima), então não precisa criar novamente
            Log::info('Pedido Boleto criado - aguardando aprovação do pagamento', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'payment_id' => $boletoResult['payment_id'],
                'bling_order_number' => $order->bling_order_number
            ]);

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

        } catch (MercadoPagoException $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro inesperado ao criar boleto', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor. Tente novamente em alguns instantes.'
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

            $order->update([
                'payment_id' => $cardResult['payment_id'],
                'payment_status' => $cardResult['status']
            ]);

            // Se aprovado, atualizar status e paid_at ANTES de enviar ao Bling
            if ($cardResult['approved']) {
                $order->update([
                    'status' => 'processing',
                    'paid_at' => now()
                ]);
                
                $blingResult = $this->blingOrder->createOrder($order);
                if ($blingResult['success']) {
                    $order->update([
                        'bling_order_number' => $blingResult['bling_order_number'],
                        'bling_synced_at' => now()
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
                'status' => $cardResult['status'],
                'status_detail' => $cardResult['status_detail'] ?? null
            ]);

            $order->load(['customer', 'items.product']);

            return response()->json([
                'success' => true,
                'message' => $cardResult['message'] ?? ($cardResult['approved'] ? 'Pagamento aprovado!' : 'Pagamento em análise'),
                'title' => $cardResult['title'] ?? ($cardResult['approved'] ? 'Sucesso!' : 'Pagamento pendente'),
                'message_type' => $cardResult['message_type'] ?? ($cardResult['approved'] ? 'success' : 'warning'),
                'can_retry' => $cardResult['can_retry'] ?? false,
                'should_change_payment' => $cardResult['should_change_payment'] ?? false,
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

        } catch (MercadoPagoException $e) {
            DB::rollBack();
            
            $context = $e->getContext();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'title' => $context['title'] ?? 'Erro no pagamento',
                'message_type' => $context['message_type'] ?? 'error',
                'field' => $context['field'] ?? null,
                'can_retry' => $context['can_retry'] ?? false,
                'should_change_payment' => $context['should_change_payment'] ?? false,
            ], $e->getStatusCode());
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Dados do cartão inválidos',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro inesperado ao processar pagamento com cartão', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor. Tente novamente em alguns instantes.'
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
