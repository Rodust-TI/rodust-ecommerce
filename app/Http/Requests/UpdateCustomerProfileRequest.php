<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Autorização verificada no controller via auth
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $customerId = auth('sanctum')->id();
        
        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                Rule::unique('customers', 'email')->ignore($customerId)
            ],
            'phone' => 'nullable|string|max:20',
            'person_type' => 'sometimes|required|in:F,J',
            'cpf' => [
                'nullable',
                'string',
                'size:11',
                Rule::unique('customers', 'cpf')->ignore($customerId)
            ],
            'cnpj' => [
                'nullable',
                'string',
                'size:14',
                Rule::unique('customers', 'cnpj')->ignore($customerId)
            ],
            'birth_date' => 'nullable|date|before:today',
            'fantasy_name' => 'nullable|string|max:255',
            'state_registration' => 'nullable|string|size:12',
            'state_uf' => 'nullable|string|size:2',
            'nfe_email' => 'nullable|email',
            'phone_commercial' => 'nullable|string|max:20',
            'taxpayer_type' => 'sometimes|integer|in:1,2,9',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'email.required' => 'O email é obrigatório.',
            'email.email' => 'Digite um email válido.',
            'email.unique' => 'Este email já está cadastrado.',
            'person_type.required' => 'Selecione o tipo de pessoa (Física ou Jurídica).',
            'person_type.in' => 'Tipo de pessoa inválido.',
            'cpf.unique' => 'Este CPF já está cadastrado.',
            'cnpj.unique' => 'Este CNPJ já está cadastrado.',
            'state_registration.size' => 'A Inscrição Estadual deve ter exatamente 12 dígitos (ex: 535371914110).',
            'birth_date.date' => 'Data de nascimento inválida.',
            'birth_date.before' => 'Data de nascimento deve ser anterior a hoje.',
            'nfe_email.email' => 'Email para NF-e inválido.',
            'taxpayer_type.in' => 'Tipo de contribuinte inválido.',
        ];
    }

    /**
     * Validações adicionais após validação básica.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Se taxpayer_type = 1 (contribuinte ICMS), state_registration e CNPJ são obrigatórios
            if ($this->input('taxpayer_type') == 1) {
                if (empty($this->input('state_registration'))) {
                    $validator->errors()->add('state_registration', 'Inscrição Estadual é obrigatória para contribuintes de ICMS.');
                }
                if (empty($this->input('cnpj'))) {
                    $validator->errors()->add('cnpj', 'CNPJ é obrigatório para contribuintes de ICMS.');
                }
            }
        });
    }
}
