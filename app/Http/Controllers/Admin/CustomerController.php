<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    /**
     * Listar clientes com busca e filtros
     * GET /admin/customers
     */
    public function index(Request $request)
    {
        $query = Customer::withCount('orders');

        // Busca por nome, email, CPF ou CNPJ
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('cpf', 'like', "%{$search}%")
                  ->orWhere('cnpj', 'like', "%{$search}%");
            });
        }

        // Filtro por tipo de pessoa
        if ($request->filled('person_type')) {
            $query->where('person_type', $request->person_type);
        }

        // Filtro por email verificado
        if ($request->filled('email_verified')) {
            if ($request->email_verified === 'yes') {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        // Ordenação
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $customers = $query->paginate(20)->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    /**
     * Visualizar detalhes do cliente
     * GET /admin/customers/{customer}
     */
    public function show(Customer $customer)
    {
        $customer->load(['addresses', 'orders' => function ($query) {
            $query->orderBy('created_at', 'desc')->limit(10);
        }]);

        // Estatísticas do cliente
        $stats = [
            'total_orders' => $customer->orders()->count(),
            'total_spent' => $customer->orders()->where('payment_status', 'approved')->sum('total'),
            'last_order' => $customer->orders()->latest()->first(),
            'average_order_value' => $customer->orders()->where('payment_status', 'approved')->avg('total'),
        ];

        return view('admin.customers.show', compact('customer', 'stats'));
    }

    /**
     * Formulário de edição
     * GET /admin/customers/{customer}/edit
     */
    public function edit(Customer $customer)
    {
        $customer->load('addresses');
        return view('admin.customers.edit', compact('customer'));
    }

    /**
     * Atualizar cliente
     * PUT /admin/customers/{customer}
     */
    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email,' . $customer->id,
            'phone' => 'nullable|string|max:20',
            'phone_commercial' => 'nullable|string|max:20',
            'cpf' => 'nullable|string|max:14',
            'cnpj' => 'nullable|string|max:18',
            'person_type' => 'required|in:F,J',
            'birth_date' => 'nullable|date',
            'fantasy_name' => 'nullable|string|max:255',
            'state_registration' => 'nullable|string|max:50',
            'state_uf' => 'nullable|string|max:2',
            'nfe_email' => 'nullable|email|max:255',
            'taxpayer_type' => 'nullable|string|max:50',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        // Se senha foi fornecida, hash ela
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $customer->update($validated);

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('success', 'Cliente atualizado com sucesso!');
    }

    /**
     * Deletar cliente (soft delete)
     * DELETE /admin/customers/{customer}
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()
            ->route('admin.customers.index')
            ->with('success', 'Cliente removido com sucesso!');
    }
}
