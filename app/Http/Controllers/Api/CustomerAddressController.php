<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use App\Services\BlingCustomerService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class CustomerAddressController extends Controller
{
    /**
     * Listar endereços do cliente autenticado
     * GET /api/customers/addresses
     */
    public function index(Request $request)
    {
        $customer = $request->user();
        $addresses = $customer->addresses()->orderBy('created_at', 'desc')->get();

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

        // Verificar limite de 5 endereços
        $addressCount = $customer->addresses()->count();
        if ($addressCount >= 5) {
            return response()->json([
                'success' => false,
                'message' => 'Limite atingido: 5 endereços. Para cadastrar um novo endereço é necessário apagar um endereço anterior.',
            ], 422);
        }

        $validated = $request->validate([
            'is_shipping' => 'nullable|boolean',
            'is_billing' => 'nullable|boolean',
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
        ]);

        // Criar endereço
        $address = $customer->addresses()->create([
            'is_shipping' => $validated['is_shipping'] ?? false,
            'is_billing' => $validated['is_billing'] ?? false,
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
        ]);

        // Sincronizar endereços com Bling (se for shipping ou billing)
        if ($address->is_shipping || $address->is_billing) {
            try {
                $blingService = app(BlingCustomerService::class);
                $synced = $blingService->syncAddresses($customer);
                
                if ($synced) {
                    Log::info('Endereços sincronizados com Bling após criação', [
                        'customer_id' => $customer->id,
                        'address_id' => $address->id,
                        'is_shipping' => $address->is_shipping,
                        'is_billing' => $address->is_billing
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Erro ao sincronizar endereços com Bling após criação', [
                    'customer_id' => $customer->id,
                    'address_id' => $address->id,
                    'error' => $e->getMessage()
                ]);
                // Não falhar a criação do endereço por erro na sincronização
            }
        }

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
            'is_shipping' => 'nullable|boolean',
            'is_billing' => 'nullable|boolean',
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
        ]);

        // Converter estado para maiúsculas se enviado
        if (isset($validated['state'])) {
            $validated['state'] = strtoupper($validated['state']);
        }

        $address->update($validated);

        // Sincronizar endereços com Bling (se for shipping ou billing)
        if ($address->is_shipping || $address->is_billing) {
            try {
                $blingService = app(BlingCustomerService::class);
                $synced = $blingService->syncAddresses($customer);
                
                if ($synced) {
                    Log::info('Endereços sincronizados com Bling após atualização', [
                        'customer_id' => $customer->id,
                        'address_id' => $address->id,
                        'is_shipping' => $address->is_shipping,
                        'is_billing' => $address->is_billing
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Erro ao sincronizar endereços com Bling após atualização', [
                    'customer_id' => $customer->id,
                    'address_id' => $address->id,
                    'error' => $e->getMessage()
                ]);
                // Não falhar a atualização do endereço por erro na sincronização
            }
        }

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
     * Toggle tipo do endereço (shipping/billing)
     * POST /api/customers/addresses/{id}/toggle-type
     */
    public function toggleType(Request $request, $id)
    {
        $customer = $request->user();
        $address = $customer->addresses()->findOrFail($id);

        $validated = $request->validate([
            'type' => 'required|in:shipping,billing',
        ]);

        $type = $validated['type'];
        
        // Toggle: se já está marcado, desmarca. Se não, marca.
        if ($type === 'shipping') {
            $newValue = !$address->is_shipping;
            $address->update(['is_shipping' => $newValue]);
            $message = $newValue ? 'Endereço definido como entrega!' : 'Endereço removido de entrega!';
        } else {
            $newValue = !$address->is_billing;
            $address->update(['is_billing' => $newValue]);
            $message = $newValue ? 'Endereço definido como cobrança!' : 'Endereço removido de cobrança!';
        }

        // Sincronizar com Bling se tiver algum tipo marcado
        $address = $address->fresh();
        if ($address->is_shipping || $address->is_billing) {
            try {
                $blingService = app(BlingCustomerService::class);
                $blingService->syncAddresses($customer);
            } catch (\Exception $e) {
                Log::error('Erro ao sincronizar após toggle de tipo', [
                    'customer_id' => $customer->id,
                    'address_id' => $address->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'address' => $address,
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

