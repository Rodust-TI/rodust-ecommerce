<?php

namespace App\Services\Invoice;

use App\Contracts\InvoiceServiceInterface;
use App\Contracts\ERPInterface;
use App\DTOs\InvoiceData;
use App\Models\Order;
use App\Mail\InvoiceIssuedMail;
use App\Services\Bling\BlingOrderService;
use App\Services\Bling\BlingStatusService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Services\Storage\InvoiceStorageService;

/**
 * Service: Processamento de Notas Fiscais
 * 
 * Processa NFs independente do ERP (Bling, Tiny, etc)
 */
class InvoiceService implements InvoiceServiceInterface
{
    public function __construct(
        private ERPInterface $erp,
        private InvoiceStorageService $storage,
        private BlingOrderService $blingOrder,
        private BlingStatusService $blingStatus
    ) {}

    /**
     * Buscar PDF da nota fiscal do ERP
     */
    public function downloadInvoicePdf(string $erpOrderId, string $invoiceKey): ?string
    {
        try {
            // Por enquanto, apenas log - implementação específica virá depois
            // Cada ERP tem sua própria forma de buscar PDF
            Log::info('Downloading invoice PDF', [
                'erp_order_id' => $erpOrderId,
                'invoice_key' => $invoiceKey,
            ]);

            // TODO: Implementar busca de PDF específica por ERP
            // Por enquanto retorna null (será implementado quando tivermos a API do Bling para PDF)
            return null;
        } catch (\Exception $e) {
            Log::error('Error downloading invoice PDF', [
                'erp_order_id' => $erpOrderId,
                'invoice_key' => $invoiceKey,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Processar dados de NF recebidos via webhook
     */
    public function processInvoice(InvoiceData $invoiceData): void
    {
        try {
            // Buscar pedido pelo número do ERP (bling_order_number) ou order_number
            $order = null;
            
            if ($invoiceData->erpOrderId) {
                $order = Order::where('bling_order_number', (string) $invoiceData->erpOrderId)->first();
            }
            
            if (!$order) {
                $order = Order::where('order_number', $invoiceData->orderNumber)->first();
            }

            if (!$order) {
                Log::warning('Order not found for invoice', [
                    'order_number' => $invoiceData->orderNumber,
                    'erp_order_id' => $invoiceData->erpOrderId,
                ]);
                return;
            }

            // Baixar PDF se disponível
            $pdfUrl = null;
            if ($invoiceData->invoiceKey && $invoiceData->erpOrderId) {
                $pdfContent = $this->downloadInvoicePdf($invoiceData->erpOrderId, $invoiceData->invoiceKey);
                
                if ($pdfContent) {
                    // Salvar PDF usando storage service
                    $pdfUrl = $this->storage->store(
                        $invoiceData->invoiceKey,
                        $pdfContent,
                        $invoiceData->invoiceType ?? 'nfe'
                    );
                }
            }

            // Atualizar pedido com dados da NF
            $updateData = $invoiceData->toArray();
            if ($pdfUrl) {
                $updateData['invoice_pdf_url'] = $pdfUrl;
            }

            $order->update($updateData);

            // Atualizar status se necessário
            if ($invoiceData->isComplete() && $order->status !== 'invoiced') {
                $order->update(['status' => 'invoiced']);
                
                // Atualizar status no Bling para "Faturado" (se existir)
                if ($order->bling_order_number) {
                    try {
                        // Buscar ID do status "Faturado" no Bling
                        $faturadoStatusId = $this->blingStatus->findStatusIdByNames(['Faturado', 'Faturamento', 'Faturado']);
                        
                        if ($faturadoStatusId) {
                            $this->blingOrder->updateOrderStatus($order, $faturadoStatusId);
                            Log::info('Status atualizado no Bling para "Faturado" após emissão de NF', [
                                'order_id' => $order->id,
                                'bling_order_number' => $order->bling_order_number,
                                'status_id' => $faturadoStatusId,
                            ]);
                        } else {
                            Log::info('Status "Faturado" não encontrado no Bling - pulando atualização', [
                                'order_id' => $order->id,
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::error('Erro ao atualizar status no Bling após emissão de NF', [
                            'order_id' => $order->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            Log::info('Invoice processed successfully', [
                'order_id' => $order->id,
                'invoice_number' => $invoiceData->invoiceNumber,
                'pdf_url' => $pdfUrl,
            ]);

            // Enviar email para o cliente
            try {
                Mail::to($order->customer->email)->send(new InvoiceIssuedMail($order, $invoiceData));
                Log::info('Invoice email sent', [
                    'order_id' => $order->id,
                    'customer_email' => $order->customer->email,
                ]);
            } catch (\Exception $e) {
                Log::error('Error sending invoice email', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error processing invoice', [
                'invoice_data' => $invoiceData->toArray(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

