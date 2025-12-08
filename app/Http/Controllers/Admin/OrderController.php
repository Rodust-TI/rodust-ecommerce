<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Customer;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Listar pedidos com busca e filtros
     * GET /admin/orders
     */
    public function index(Request $request)
    {
        $query = Order::with('customer');

        // Busca por número do pedido, nome do cliente ou email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('bling_order_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($customerQuery) use ($search) {
                      $customerQuery->where('name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filtro por status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtro por status de pagamento
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filtro por cliente
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filtro por data (desde)
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        // Filtro por data (até)
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Ordenação
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $orders = $query->paginate(20)->withQueryString();

        // Status disponíveis para filtro
        $orderStatuses = OrderStatus::cases();
        $paymentStatuses = PaymentStatus::cases();

        return view('admin.orders.index', compact('orders', 'orderStatuses', 'paymentStatuses'));
    }

    /**
     * Visualizar detalhes do pedido
     * GET /admin/orders/{order}
     */
    public function show(Order $order)
    {
        $order->load(['customer', 'customer.addresses', 'items.product']);

        return view('admin.orders.show', compact('order'));
    }

    /**
     * Formulário de edição
     * GET /admin/orders/{order}/edit
     */
    public function edit(Order $order)
    {
        $order->load(['customer', 'items']);
        $orderStatuses = OrderStatus::cases();
        $paymentStatuses = PaymentStatus::cases();

        return view('admin.orders.edit', compact('order', 'orderStatuses', 'paymentStatuses'));
    }

    /**
     * Atualizar pedido
     * PUT /admin/orders/{order}
     */
    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:' . implode(',', array_column(OrderStatus::cases(), 'value')),
            'payment_status' => 'required|string|in:' . implode(',', array_column(PaymentStatus::cases(), 'value')),
            'notes' => 'nullable|string|max:1000',
        ]);

        $order->update($validated);

        Log::info('Pedido atualizado via painel admin', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
        ]);

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Pedido atualizado com sucesso!');
    }

    /**
     * Deletar pedido (soft delete)
     * DELETE /admin/orders/{order}
     */
    public function destroy(Order $order)
    {
        $orderNumber = $order->order_number;
        $order->delete();

        Log::info('Pedido removido via painel admin', [
            'order_id' => $order->id,
            'order_number' => $orderNumber,
        ]);

        return redirect()
            ->route('admin.orders.index')
            ->with('success', 'Pedido removido com sucesso!');
    }
}
