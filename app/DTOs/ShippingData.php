<?php

namespace App\DTOs;

/**
 * DTO: Dados de Envio/Rastreio
 * 
 * Padroniza dados de envio independente da origem (Melhor Envio, Bling, etc)
 */
class ShippingData
{
    public function __construct(
        public readonly string $orderNumber,      // Número do pedido no sistema
        public readonly ?string $trackingCode,    // Código de rastreamento
        public readonly ?string $carrier,         // Transportadora (Jadlog, Correios, etc)
        public readonly ?string $serviceName,     // Nome do serviço (PAC, SEDEX, etc)
        public readonly ?string $status,          // Status do envio (posted, in_transit, delivered)
        public readonly ?\DateTime $shippedAt,    // Data de postagem
        public readonly ?\DateTime $deliveredAt, // Data de entrega
        public readonly ?string $erpOrderId,     // ID do pedido no ERP (ex: Melhor Envio)
        public readonly ?string $erpShipmentId,  // ID do envio no ERP
    ) {}

    /**
     * Criar a partir de dados do Melhor Envio
     */
    public static function fromMelhorEnvio(array $data, string $orderNumber): self
    {
        $status = match($data['status'] ?? null) {
            'posted' => 'shipped',
            'in_transit' => 'shipped',
            'delivered' => 'delivered',
            default => null,
        };

        return new self(
            orderNumber: $orderNumber,
            trackingCode: $data['tracking_code'] ?? $data['protocol'] ?? null,
            carrier: $data['carrier'] ?? $data['transportadora'] ?? null,
            serviceName: $data['service_name'] ?? $data['servico'] ?? null,
            status: $status,
            shippedAt: isset($data['posted_at']) ? new \DateTime($data['posted_at']) : null,
            deliveredAt: isset($data['delivered_at']) ? new \DateTime($data['delivered_at']) : null,
            erpOrderId: $data['order_id'] ?? null,
            erpShipmentId: $data['id'] ?? $data['shipment_id'] ?? null,
        );
    }

    /**
     * Criar a partir de dados do Bling (se tiver transporte)
     */
    public static function fromBling(array $data, string $orderNumber): self
    {
        $transporte = $data['transporte'] ?? [];
        
        return new self(
            orderNumber: $orderNumber,
            trackingCode: $transporte['codigoRastreamento'] ?? null,
            carrier: $transporte['transportadora'] ?? null,
            serviceName: $transporte['tipoFrete'] ?? null,
            status: null, // Bling não fornece status de envio
            shippedAt: null,
            deliveredAt: null,
            erpOrderId: $data['id'] ?? null,
            erpShipmentId: null,
        );
    }

    /**
     * Converter para array (para salvar no banco)
     */
    public function toArray(): array
    {
        return [
            'tracking_code' => $this->trackingCode,
            'shipping_carrier' => $this->carrier,
            'shipping_method_name' => $this->serviceName,
            'status' => $this->status,
        ];
    }

    /**
     * Verificar se tem código de rastreio
     */
    public function hasTrackingCode(): bool
    {
        return !empty($this->trackingCode);
    }
}

