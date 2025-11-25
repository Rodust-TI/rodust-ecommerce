<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CustomerAddressController extends Controller
{
    /**
     * Listar endereços do cliente autenticado
     * GET /api/customers/addresses
     */
    public function index(Request $request)
    {
        $customer = $request->user();
        $addresses = $customer->addresses()->orderBy('is_default', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'addresses' => $addresses,
            ]
        ]);
    }

    /**
     * Criar novo endereço
     * POST /api/customers/addresses
     */
    public function store(Request $request)
    {
        $customer = $request->user();

        $validated = $request->validate([
            'type' => 'required|in:shipping,billing,invoice',
            'label' => 'nullable|string|max:255',
            'recipient_name' => 'nullable|string|max:255',
            'zipcode' => 'required|string|size:8', // Apenas números
            'address' => 'required|string|max:255',
            'number' => 'required|string|max:10',
            'complement' => 'nullable|string|max:255',
            'neighborhood' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|size:2',
            'country' => 'nullable|string|size:2',
            'invoice_cpf_cnpj' => 'nullable|string|max:18',
            'invoice_name' => 'nullable|string|max:255',
            'invoice_ie' => 'nullable|string|max:20',
            'invoice_im' => 'nullable|string|max:20',
            'is_default' => 'boolean',
        ]);

        // Se for endereço de faturamento (invoice), validar CPF/CNPJ
        if ($validated['type'] === 'invoice') {
            if (empty($validated['invoice_cpf_cnpj'])) {
                throw ValidationException::withMessages([
                    'invoice_cpf_cnpj' => ['CPF ou CNPJ é obrigatório para endereço de faturamento.']
                ]);
            }
        }

        // Criar endereço
        $address = $customer->addresses()->create([
            'type' => $validated['type'],
            'label' => $validated['label'] ?? null,
            'recipient_name' => $validated['recipient_name'] ?? null,
            'zipcode' => $validated['zipcode'],
            'address' => $validated['address'],
            'number' => $validated['number'],
            'complement' => $validated['complement'] ?? null,
            'neighborhood' => $validated['neighborhood'],
            'city' => $validated['city'],
            'state' => strtoupper($validated['state']),
            'country' => $validated['country'] ?? 'BR',
            'invoice_cpf_cnpj' => $validated['invoice_cpf_cnpj'] ?? null,
            'invoice_name' => $validated['invoice_name'] ?? null,
            'invoice_ie' => $validated['invoice_ie'] ?? null,
            'invoice_im' => $validated['invoice_im'] ?? null,
            'is_default' => $validated['is_default'] ?? false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Endereço cadastrado com sucesso!',
            'data' => [
                'address' => $address,
            ]
        ], 201);
    }

    /**
     * Mostrar um endereço específico
     * GET /api/customers/addresses/{id}
     */
    public function show(Request $request, $id)
    {
        $customer = $request->user();
        $address = $customer->addresses()->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'address' => $address,
            ]
        ]);
    }

    /**
     * Atualizar endereço
     * PUT /api/customers/addresses/{id}
     */
    public function update(Request $request, $id)
    {
        $customer = $request->user();
        $address = $customer->addresses()->findOrFail($id);

        $validated = $request->validate([
            'type' => 'sometimes|in:shipping,billing,invoice',
            'label' => 'nullable|string|max:255',
            'recipient_name' => 'nullable|string|max:255',
            'zipcode' => 'sometimes|string|size:8',
            'address' => 'sometimes|string|max:255',
            'number' => 'sometimes|string|max:10',
            'complement' => 'nullable|string|max:255',
            'neighborhood' => 'sometimes|string|max:255',
            'city' => 'sometimes|string|max:255',
            'state' => 'sometimes|string|size:2',
            'country' => 'nullable|string|size:2',
            'invoice_cpf_cnpj' => 'nullable|string|max:18',
            'invoice_name' => 'nullable|string|max:255',
            'invoice_ie' => 'nullable|string|max:20',
            'invoice_im' => 'nullable|string|max:20',
            'is_default' => 'boolean',
        ]);

        // Converter estado para maiúsculas se enviado
        if (isset($validated['state'])) {
            $validated['state'] = strtoupper($validated['state']);
        }

        $address->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Endereço atualizado com sucesso!',
            'data' => [
                'address' => $address->fresh(),
            ]
        ]);
    }

    /**
     * Deletar endereço
     * DELETE /api/customers/addresses/{id}
     */
    public function destroy(Request $request, $id)
    {
        $customer = $request->user();
        $address = $customer->addresses()->findOrFail($id);

        $address->delete();

        return response()->json([
            'success' => true,
            'message' => 'Endereço removido com sucesso!'
        ]);
    }

    /**
     * Definir endereço como padrão
     * POST /api/customers/addresses/{id}/set-default
     */
    public function setDefault(Request $request, $id)
    {
        $customer = $request->user();
        $address = $customer->addresses()->findOrFail($id);

        // Remover padrão de outros endereços do mesmo tipo
        $customer->addresses()
            ->where('type', $address->type)
            ->update(['is_default' => false]);

        // Definir este como padrão
        $address->update(['is_default' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Endereço definido como padrão!',
            'data' => [
                'address' => $address->fresh(),
            ]
        ]);
    }

    /**
     * Buscar endereço por CEP (integração futura com ViaCEP)
     * GET /api/customers/addresses/search-zipcode/{zipcode}
     */
    public function searchZipcode($zipcode)
    {
        // Limpar CEP
        $zipcode = preg_replace('/\D/', '', $zipcode);

        if (strlen($zipcode) !== 8) {
            return response()->json([
                'success' => false,
                'message' => 'CEP inválido.'
            ], 400);
        }

        // Consultar ViaCEP
        try {
            $response = file_get_contents("https://viacep.com.br/ws/{$zipcode}/json/");
            $data = json_decode($response, true);

            if (isset($data['erro'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'CEP não encontrado.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'zipcode' => $data['cep'],
                    'address' => $data['logradouro'],
                    'complement' => $data['complemento'],
                    'neighborhood' => $data['bairro'],
                    'city' => $data['localidade'],
                    'state' => $data['uf'],
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao consultar CEP.'
            ], 500);
        }
    }
}

