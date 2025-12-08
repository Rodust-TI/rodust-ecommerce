<?php

namespace App\Contracts;

use App\DTOs\ShippingData;

/**
 * Interface para serviços de Envio/Rastreio
 * 
 * Abstrai operações de envio independente do fornecedor (Melhor Envio, Bling, etc)
 */
interface ShippingServiceInterface
{
    /**
     * Buscar código de rastreio de um envio
     * 
     * @param string $erpShipmentId ID do envio no ERP
     * @return ShippingData|null Dados do envio ou null se não encontrado
     */
    public function getTrackingData(string $erpShipmentId): ?ShippingData;

    /**
     * Processar dados de envio recebidos via webhook
     * 
     * @param ShippingData $shippingData Dados do envio
     * @return void
     */
    public function processShipping(ShippingData $shippingData): void;
}

