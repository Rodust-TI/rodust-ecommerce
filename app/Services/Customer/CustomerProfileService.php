<?php

namespace App\Services\Customer;

use App\Models\Customer;
use App\Jobs\SyncCustomerToBling;
use Illuminate\Support\Facades\Log;

/**
 * Customer Profile Service
 * 
 * Responsável por: Atualização de dados pessoais, sincronização com Bling
 * 
 * ⚠️ ATENÇÃO: Este módulo controla atualização de perfil.
 * Alterações podem afetar sincronização com Bling e dados do cliente.
 * 
 * @package App\Services\Customer
 */
class CustomerProfileService
{
    /**
     * Atualiza dados do perfil do cliente
     * 
     * @param Customer $customer
     * @param array $data Dados validados do perfil
     * @return Customer
     */
    public function updateProfile(Customer $customer, array $data): Customer
    {
        // LOG TEMPORÁRIO PARA DEBUG
        Log::info('UpdateProfile - Dados recebidos', [
            'customer_id' => $customer->id,
            'validated_data' => $data
        ]);

        // Atualizar dados
        $customer->update($data);

        // Se alterou dados relevantes para Bling, sincronizar
        $blingFields = ['name', 'cpf', 'cnpj', 'phone', 'person_type', 'birth_date', 
                       'fantasy_name', 'state_registration', 'state_uf', 'nfe_email', 
                       'phone_commercial', 'taxpayer_type'];
        
        $hasChangedBlingData = collect($data)
            ->keys()
            ->intersect($blingFields)
            ->isNotEmpty();

        if ($hasChangedBlingData) {
            // Recarregar customer com addresses para sync completo
            $customer->refresh();
            $customer->load('addresses');
            
            SyncCustomerToBling::dispatch($customer);
            
            Log::info('Profile updated - Bling sync dispatched', [
                'customer_id' => $customer->id,
                'changed_fields' => array_keys($data)
            ]);
        }

        return $customer->fresh(); // Retorna customer atualizado
    }

    /**
     * Retorna dados do perfil formatados para resposta
     * 
     * @param Customer $customer
     * @return array
     */
    public function getProfileData(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'cpf' => $customer->cpf,
            'cnpj' => $customer->cnpj,
            'phone' => $customer->phone,
            'person_type' => $customer->person_type,
            'birth_date' => $customer->birth_date?->format('Y-m-d'),
            'fantasy_name' => $customer->fantasy_name,
            'state_registration' => $customer->state_registration,
            'state_uf' => $customer->state_uf,
            'nfe_email' => $customer->nfe_email,
            'phone_commercial' => $customer->phone_commercial,
            'taxpayer_type' => $customer->taxpayer_type,
            'email_verified' => !is_null($customer->email_verified_at),
            'bling_id' => $customer->bling_id,
            'bling_synced_at' => $customer->bling_synced_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Verifica se algum campo crítico foi alterado
     * 
     * @param Customer $customer
     * @param array $data
     * @return bool
     */
    public function hasChangedCriticalData(Customer $customer, array $data): bool
    {
        $criticalFields = ['email', 'cpf', 'cnpj'];
        
        foreach ($criticalFields as $field) {
            if (isset($data[$field]) && $customer->{$field} !== $data[$field]) {
                return true;
            }
        }
        
        return false;
    }
}
