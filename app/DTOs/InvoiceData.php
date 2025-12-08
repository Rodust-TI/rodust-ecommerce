<?php

namespace App\DTOs;

/**
 * DTO: Dados de Nota Fiscal
 * 
 * Padroniza dados de NF independente da origem (Bling, Tiny, SAP, etc)
 */
class InvoiceData
{
    public function __construct(
        public readonly string $orderNumber,      // Número do pedido no sistema
        public readonly ?string $invoiceNumber,   // Número da NF
        public readonly ?string $invoiceKey,      // Chave de acesso NF-e
        public readonly ?string $invoiceType,    // 'nfe' ou 'nfce'
        public readonly ?string $pdfUrl,          // URL do PDF (se disponível)
        public readonly ?\DateTime $issuedAt,    // Data de emissão
        public readonly ?string $erpOrderId,     // ID do pedido no ERP (ex: Bling)
    ) {}

    /**
     * Criar a partir de dados do Bling
     */
    public static function fromBling(array $data, string $orderNumber): self
    {
        return new self(
            orderNumber: $orderNumber,
            invoiceNumber: $data['numero'] ?? null,
            invoiceKey: $data['chaveAcesso'] ?? null,
            invoiceType: $data['tipo'] ?? 'nfe',
            pdfUrl: $data['pdfUrl'] ?? null,
            issuedAt: isset($data['dataEmissao']) ? new \DateTime($data['dataEmissao']) : null,
            erpOrderId: $data['idPedido'] ?? null,
        );
    }

    /**
     * Converter para array (para salvar no banco)
     */
    public function toArray(): array
    {
        return [
            'invoice_number' => $this->invoiceNumber,
            'invoice_key' => $this->invoiceKey,
            'invoice_type' => $this->invoiceType,
            'invoice_pdf_url' => $this->pdfUrl,
            'invoice_issued_at' => $this->issuedAt?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Verificar se tem dados completos
     */
    public function isComplete(): bool
    {
        return !empty($this->invoiceNumber) && !empty($this->invoiceKey);
    }
}

