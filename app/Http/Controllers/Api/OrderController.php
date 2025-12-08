<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Product;
use App\Jobs\SyncOrderToBling;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Order::with(['customer', 'items.product']);

        // Se não for admin, mostrar apenas pedidos do cliente autenticado
        $user = $request->user();
        if ($user && !$request->has('admin')) {
            $query->where('customer_id', $user->id);
        }

        // Filtro por status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filtro por cliente (apenas para admin)
        if ($request->has('customer_id') && $request->has('admin')) {
            $query->where('customer_id', $request->customer_id);
        }

        $perPage = $request->get('per_page', 15);
        $orders = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($orders);
    }

    /**
     * Store a newly created resource in storage (Checkout).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer' => 'required|array',
            'customer.name' => 'required|string|max:255',
            'customer.email' => 'required|email',
            'customer.phone' => 'nullable|string',
            'customer.cpf_cnpj' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'shipping' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Criar ou buscar cliente
            $customer = Customer::firstOrCreate(
                ['email' => $request->customer['email']],
                $request->customer
            );

            // Calcular totais
            $subtotal = 0;
            $items = [];

            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                
                // Verificar estoque
                if ($product->stock < $item['quantity']) {
                    return response()->json([
                        'error' => "Estoque insuficiente para o produto {$product->name}"
                    ], 400);
                }

                $unitPrice = $product->price;
                $totalPrice = $unitPrice * $item['quantity'];
                $subtotal += $totalPrice;

                $items[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                ];

                // Atualizar estoque
                $product->decrement('stock', $item['quantity']);
            }

            $shipping = $request->get('shipping', 0);
            $discount = $request->get('discount', 0);
            $total = $subtotal + $shipping - $discount;

            // Criar pedido
            $order = Order::create([
                'customer_id' => $customer->id,
                'order_number' => 'ORD-' . time(),
                'status' => 'pending',
                'subtotal' => $subtotal,
                'discount' => $discount,
                'shipping' => $shipping,
                'total' => $total,
                'payment_method' => $request->get('payment_method'),
                'payment_status' => 'pending',
            ]);

            // Criar itens do pedido
            foreach ($items as $item) {
                $order->items()->create($item);
            }

            DB::commit();

            // Nota: Pedidos com PIX/Boleto serão enviados ao Bling apenas após confirmação de pagamento
            // Pedidos com cartão de crédito são enviados imediatamente (pagamento já processado)
            if ($request->get('payment_method') === 'credit_card') {
                SyncOrderToBling::dispatch($order);
            }

            return response()->json($order->load(['customer', 'items']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $order = Order::with(['customer', 'items.product'])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    /**
     * Verificar status do pedido (público - usado para polling PIX)
     * GET /api/orders/{id}/status
     */
    public function checkStatus(string $id)
    {
        $order = Order::find($id);
        
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido não encontrado'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payment_method,
                'paid_at' => $order->paid_at?->toIso8601String(),
                'is_paid' => $order->payment_status === 'approved' || $order->status === 'processing',
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $order = Order::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:pending,paid,processing,shipped,delivered,cancelled',
            'payment_status' => 'sometimes|in:pending,paid,failed,refunded',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $order->update($request->all());

        return response()->json($order);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $order = Order::findOrFail($id);
        $order->delete();

        return response()->json(['message' => 'Order deleted successfully'], 200);
    }
}
