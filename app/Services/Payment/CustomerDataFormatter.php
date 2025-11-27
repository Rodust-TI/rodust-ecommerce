<?php

namespace App\Services\Payment;

use App\Models\Customer;

/**
 * Service: Preparação de dados do cliente para pagamento
 * Responsabilidade: Formatar dados do cliente para APIs de pagamento
 */
class CustomerDataFormatter
{
    /**
     * Formatar dados do cliente para Mercado Pago
     */
    public function formatForMercadoPago(Customer $customer): array
    {
        return [
            'email' => $customer->email,
            'name' => $customer->name,
            'document' => $this->getDocument($customer)
        ];
    }

    /**
     * Obter documento (CPF ou CNPJ)
     */
    private function getDocument(Customer $customer): string
    {
        return $customer->cpf ?: $customer->cnpj;
    }
}
