<?php

namespace App\Contracts;

use App\DTOs\InvoiceData;

/**
 * Interface para serviços de Nota Fiscal
 * 
 * Abstrai operações de NF independente do ERP (Bling, Tiny, SAP, etc)
 */
interface InvoiceServiceInterface
{
    /**
     * Buscar PDF da nota fiscal
     * 
     * @param string $erpOrderId ID do pedido no ERP
     * @param string $invoiceKey Chave de acesso da NF
     * @return string|null Conteúdo do PDF (binary) ou null se não disponível
     */
    public function downloadInvoicePdf(string $erpOrderId, string $invoiceKey): ?string;

    /**
     * Processar dados de NF recebidos via webhook
     * 
     * @param InvoiceData $invoiceData Dados da NF
     * @return void
     */
    public function processInvoice(InvoiceData $invoiceData): void;
}

