<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request: Validação de dados de pagamento
 * Responsabilidade: Validar dados recebidos para criar pagamento
 */
class CreatePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Já validado pelo middleware auth:sanctum
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'shipping_method_id' => 'required',
            'shipping_cost' => 'required|numeric|min:0',
            'shipping_address' => 'required|array',
            'shipping_address.postal_code' => 'required|string',
            'shipping_address.street' => 'required|string',
            'shipping_address.number' => 'required|string',
            'shipping_address.neighborhood' => 'required|string',
            'shipping_address.city' => 'required|string',
            'shipping_address.state' => 'required|string|size:2',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'Cliente não identificado',
            'customer_id.exists' => 'Cliente inválido',
            'shipping_cost.required' => 'Custo de frete é obrigatório',
            'shipping_address.required' => 'Endereço de entrega é obrigatório',
            'items.required' => 'Carrinho vazio',
            'items.min' => 'Adicione pelo menos um item ao carrinho',
        ];
    }
}
