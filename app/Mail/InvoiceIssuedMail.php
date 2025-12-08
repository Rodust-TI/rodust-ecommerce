<?php

namespace App\Mail;

use App\Models\Order;
use App\DTOs\InvoiceData;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceIssuedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;
    public InvoiceData $invoiceData;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order, InvoiceData $invoiceData)
    {
        $this->order = $order;
        $this->invoiceData = $invoiceData;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nota Fiscal Emitida - Pedido #' . $this->order->order_number . ' - Rodust',
            from: 'noreply@rodust.com.br',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-issued',
            with: [
                'customerName' => $this->order->customer->name,
                'orderNumber' => $this->order->order_number,
                'invoiceNumber' => $this->invoiceData->invoiceNumber,
                'invoiceKey' => $this->invoiceData->invoiceKey,
                'invoicePdfUrl' => $this->invoiceData->pdfUrl ?? $this->order->invoice_pdf_url,
                'issuedAt' => $this->invoiceData->issuedAt ?? $this->order->invoice_issued_at,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}

