<?php

namespace App\Mail;

use App\Models\Order;
use App\DTOs\ShippingData;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrackingCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;
    public ShippingData $shippingData;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order, ShippingData $shippingData)
    {
        $this->order = $order;
        $this->shippingData = $shippingData;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Código de Rastreio Disponível - Pedido #' . $this->order->order_number . ' - Rodust',
            from: 'noreply@rodust.com.br',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Gerar URL de rastreamento baseado na transportadora
        $trackingUrl = $this->getTrackingUrl();

        return new Content(
            view: 'emails.tracking-code',
            with: [
                'customerName' => $this->order->customer->name,
                'orderNumber' => $this->order->order_number,
                'trackingCode' => $this->shippingData->trackingCode,
                'carrier' => $this->shippingData->carrier,
                'serviceName' => $this->shippingData->serviceName,
                'trackingUrl' => $trackingUrl,
                'shippedAt' => $this->shippingData->shippedAt,
            ]
        );
    }

    /**
     * Gerar URL de rastreamento baseado na transportadora
     */
    protected function getTrackingUrl(): ?string
    {
        if (!$this->shippingData->trackingCode) {
            return null;
        }

        $carrier = strtolower($this->shippingData->carrier ?? '');

        return match(true) {
            str_contains($carrier, 'correios') => "https://www.correios.com.br/precisa-de-ajuda/como-rastrear-um-objeto/rastreamento-de-objetos?objeto={$this->shippingData->trackingCode}",
            str_contains($carrier, 'jadlog') => "https://www.jadlog.com.br/jadlog/tracking.jad?cte={$this->shippingData->trackingCode}",
            str_contains($carrier, 'melhor envio') => "https://melhorenvio.com.br/rastreamento/{$this->shippingData->trackingCode}",
            default => null,
        };
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}

