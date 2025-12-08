<?php

namespace App\Services\Webhook;

use App\Models\Product;
use App\Models\Order;
use App\Models\WebhookLog;
use App\Services\Bling\BlingStatusService;
use App\Services\Invoice\InvoiceService;
use App\Services\Shipping\ShippingService;
use App\Services\Webhook\WebhookLogService;
use App\DTOs\InvoiceData;
use App\DTOs\ShippingData;
use App\Jobs\SyncProductToWordPress;
use Illuminate\Support\Facades\Log;

class BlingWebhookHandler
{
    public function __construct(
        private BlingStatusService $statusService,
        private InvoiceService $invoiceService,
        private ShippingService $shippingService
    ) {}

    /**
     * Processar webhook do Bling
     */
    public function handle(array $data, string $action, WebhookLog $webhookLog): void
    {
        $resource = $this->extractResource($data['event'] ?? null);
        
        switch ($resource) {
            case 'product':
                $this->handleProduct($data, $action, $webhookLog);
                break;
            case 'stock':
            case 'virtual_stock':
                $this->handleStock($data, $action, $webhookLog);
                break;
            case 'order':
                $this->handleOrder($data, $action, $webhookLog);
                break;
            case 'invoice':
            case 'consumer_invoice':
                $this->handleInvoice($data, $action, $webhookLog);
                break;
            default:
                Log::info('Unknown Bling webhook resource', [
                    'resource' => $resource,
                    'action' => $action,
                    'webhook_log_id' => $webhookLog->id
                ]);
        }
    }

    /**
     * Handle product creation/update/deletion
     */
    protected function handleProduct(array $data, string $action, WebhookLog $webhookLog): void
    {
        $productData = $data['data'] ?? [];
        $blingId = $productData['id'] ?? null;

        if (!$blingId) {
            Log::warning('Product webhook without Bling ID', ['webhook_log_id' => $webhookLog->id]);
            return;
        }

        switch ($action) {
            case 'created':
            case 'updated':
                $product = Product::updateOrCreate(
                    ['bling_id' => $blingId],
                    [
                        'sku' => $productData['codigo'] ?? null,
                        'name' => $productData['nome'] ?? null,
                        'description' => $productData['descricao'] ?? null,
                        'price' => $productData['preco'] ?? 0,
                        'cost' => $productData['precoCusto'] ?? null,
                        'stock' => $productData['estoque'] ?? 0,
                        'image' => $productData['imagemURL'] ?? null,
                        'active' => true,
                        'last_bling_sync' => now(),
                    ]
                );

                Log::info("Product {$action} from Bling", [
                    'bling_id' => $blingId,
                    'product_id' => $product->id,
                    'webhook_log_id' => $webhookLog->id
                ]);

                // Sincronizar com WordPress
                SyncProductToWordPress::dispatch($product->id);
                
                // Atualizar metadata do log
                app(WebhookLogService::class)->addMetadata($webhookLog, [
                    'product_id' => $product->id,
                    'product_sku' => $product->sku,
                ]);
                break;

            case 'deleted':
                Product::where('bling_id', $blingId)->update([
                    'active' => false,
                    'last_bling_sync' => now(),
                ]);

                Log::info("Product deleted from Bling", [
                    'bling_id' => $blingId,
                    'webhook_log_id' => $webhookLog->id
                ]);
                break;
        }
    }

    /**
     * Handle stock level updates
     */
    protected function handleStock(array $data, string $action, WebhookLog $webhookLog): void
    {
        // Log completo do payload para debug
        Log::info('Bling Stock Webhook - Dados recebidos', [
            'action' => $action,
            'webhook_log_id' => $webhookLog->id,
            'full_payload' => $data, // Log completo para debug
            'data_section' => $data['data'] ?? null,
        ]);

        $stockData = $data['data'] ?? [];

        if (empty($stockData)) {
            Log::warning('Stock webhook sem dados', [
                'webhook_log_id' => $webhookLog->id,
                'full_data' => $data, // Log completo para debug
            ]);
            app(WebhookLogService::class)->addMetadata($webhookLog, [
                'error' => 'Missing stock data',
                'received_data' => $data,
            ]);
            return;
        }

        // Estrutura do Bling: data.produto.id (não data.id)
        $produto = $stockData['produto'] ?? [];
        $blingId = $produto['id'] ?? null;
        $codigo = $stockData['codigo'] ?? $produto['codigo'] ?? null; // Pode estar em produto ou no nível superior
        
        // Estoque: saldoVirtualTotal ou saldoFisicoTotal (preferir virtual)
        $estoqueAtual = $stockData['saldoVirtualTotal'] ?? $stockData['saldoFisicoTotal'] ?? null;
        
        // Se não tiver no nível superior, tentar pegar do depósito
        if ($estoqueAtual === null) {
            $deposito = $stockData['deposito'] ?? null;
            if ($deposito) {
                $estoqueAtual = $deposito['saldoVirtual'] ?? $deposito['saldoFisico'] ?? null;
            }
            
            // Se ainda não tiver, tentar array de depósitos (formato alternativo)
            if ($estoqueAtual === null && isset($stockData['depositos']) && is_array($stockData['depositos']) && !empty($stockData['depositos'])) {
                $deposito = $stockData['depositos'][0];
                $estoqueAtual = $deposito['saldoVirtual'] ?? $deposito['saldoFisico'] ?? $deposito['saldo'] ?? null;
            }
        }

        if (!$blingId && !$codigo) {
            Log::warning('Stock webhook sem ID ou código do produto', [
                'webhook_log_id' => $webhookLog->id,
                'stock_data' => $stockData,
            ]);
            app(WebhookLogService::class)->addMetadata($webhookLog, [
                'error' => 'Missing product ID or SKU',
                'stock_data' => $stockData,
            ]);
            return;
        }

        if ($estoqueAtual === null) {
            Log::warning('Stock webhook sem valor de estoque', [
                'bling_id' => $blingId,
                'codigo' => $codigo,
                'webhook_log_id' => $webhookLog->id,
                'stock_data' => $stockData,
            ]);
            app(WebhookLogService::class)->addMetadata($webhookLog, [
                'error' => 'Missing stock value',
                'stock_data' => $stockData,
            ]);
            return;
        }

        // Buscar produto por bling_id ou código
        $product = null;
        if ($blingId) {
            $product = Product::where('bling_id', $blingId)->first();
        }
        if (!$product && $codigo) {
            $product = Product::where('sku', $codigo)->first();
        }

        if (!$product) {
            Log::warning('Produto não encontrado para atualizar estoque', [
                'bling_id' => $blingId,
                'codigo' => $codigo,
                'webhook_log_id' => $webhookLog->id
            ]);
            return;
        }

        // Atualizar estoque no Laravel
        $oldStock = $product->stock;
        $product->update([
            'stock' => (int) $estoqueAtual,
            'last_bling_sync' => now(),
        ]);

        Log::info("Stock updated from Bling webhook", [
            'product_id' => $product->id,
            'product_sku' => $product->sku,
            'bling_id' => $blingId,
            'codigo' => $codigo,
            'old_stock' => $oldStock,
            'new_stock' => $estoqueAtual,
            'difference' => (int) $estoqueAtual - $oldStock,
            'webhook_log_id' => $webhookLog->id
        ]);

        // Sincronizar com WordPress (disparar job)
        try {
            SyncProductToWordPress::dispatch($product->id);
            Log::info("Job SyncProductToWordPress dispatched", [
                'product_id' => $product->id,
                'webhook_log_id' => $webhookLog->id
            ]);
        } catch (\Exception $e) {
            Log::error("Error dispatching SyncProductToWordPress job", [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
                'webhook_log_id' => $webhookLog->id
            ]);
        }

        // Atualizar metadata do log
        app(WebhookLogService::class)->addMetadata($webhookLog, [
            'product_id' => $product->id,
            'product_sku' => $codigo,
            'stock_updated' => [
                'old' => $oldStock,
                'new' => $estoqueAtual,
            ],
        ]);
    }

    /**
     * Handle order status changes
     * 
     * Também processa dados de transporte (rastreio) se disponíveis
     */
    protected function handleOrder(array $data, string $action, WebhookLog $webhookLog): void
    {
        $orderData = $data['data'] ?? [];
        $blingOrderId = $orderData['id'] ?? null; // ID do pedido no Bling (ex: 24525012441)
        $blingOrderNumber = $orderData['numero'] ?? null; // Número do pedido no Bling (ex: 4)

        if (!$blingOrderId && !$blingOrderNumber) {
            Log::warning('Order webhook without Bling order ID or number', ['webhook_log_id' => $webhookLog->id]);
            return;
        }

        // Buscar pedido local - tentar primeiro pelo ID do Bling, depois pelo número
        $order = null;
        if ($blingOrderId) {
            $order = Order::where('bling_order_number', (string) $blingOrderId)->first();
        }
        if (!$order && $blingOrderNumber) {
            $order = Order::where('bling_order_number', (string) $blingOrderNumber)->first();
        }

        if (!$order) {
            Log::warning('Order not found in local database', [
                'bling_order_id' => $blingOrderId,
                'bling_order_number' => $blingOrderNumber,
                'webhook_log_id' => $webhookLog->id
            ]);
            return;
        }

        // Mapear status do Bling
        $blingStatus = $orderData['situacao'] ?? null;

        // Se o webhook não contém o status, buscar o pedido completo do Bling
        // Usar o ID do Bling (preferencial) ou o número do pedido
        $blingOrderIdentifier = $blingOrderId ?? $blingOrderNumber;
        
        if (!$blingStatus) {
            Log::info('Webhook não contém status, buscando pedido completo do Bling', [
                'bling_order_id' => $blingOrderId,
                'bling_order_number' => $blingOrderNumber,
                'using_identifier' => $blingOrderIdentifier,
                'webhook_log_id' => $webhookLog->id
            ]);
            
            try {
                $blingOrderService = app(\App\Services\Bling\BlingOrderService::class);
                $blingOrderFull = $blingOrderService->getOrder((string) $blingOrderIdentifier);
                
                if ($blingOrderFull && isset($blingOrderFull['situacao'])) {
                    $blingStatus = $blingOrderFull['situacao'];
                    Log::info('Status obtido do pedido completo do Bling', [
                        'bling_order_id' => $blingOrderId,
                        'bling_order_number' => $blingOrderNumber,
                        'using_identifier' => $blingOrderIdentifier,
                        'status_id' => $blingStatus['id'] ?? null,
                        'webhook_log_id' => $webhookLog->id
                    ]);
                } else {
                    Log::warning('Pedido completo do Bling também não contém status', [
                        'bling_order_id' => $blingOrderId,
                        'bling_order_number' => $blingOrderNumber,
                        'using_identifier' => $blingOrderIdentifier,
                        'webhook_log_id' => $webhookLog->id
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Erro ao buscar pedido completo do Bling', [
                    'bling_order_id' => $blingOrderId,
                    'bling_order_number' => $blingOrderNumber,
                    'using_identifier' => $blingOrderIdentifier,
                    'error' => $e->getMessage(),
                    'webhook_log_id' => $webhookLog->id
                ]);
            }
        }

        if ($blingStatus) {
            $blingStatusId = $blingStatus['id'] ?? null;
            $blingStatusName = $this->statusService->getStatusName($blingStatusId);
            $internalStatus = $this->statusService->mapBlingStatusToInternal($blingStatus);
            
            $oldStatus = $order->status;

            $order->update([
                'status' => $internalStatus,
                'last_bling_sync' => now(),
            ]);

            Log::info("Order status updated from Bling webhook", [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'bling_order_id' => $blingOrderId,
                'bling_order_number' => $blingOrderNumber,
                'bling_status_id' => $blingStatusId,
                'bling_status_name' => $blingStatusName,
                'old_internal_status' => $oldStatus,
                'new_internal_status' => $internalStatus,
                'webhook_log_id' => $webhookLog->id,
                'status_source' => isset($orderData['situacao']) ? 'webhook' : 'api_fetch'
            ]);

            // Atualizar metadata do log
            app(WebhookLogService::class)->addMetadata($webhookLog, [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'bling_status_id' => $blingStatusId,
                'bling_status_name' => $blingStatusName,
                'old_internal_status' => $oldStatus,
                'new_internal_status' => $internalStatus,
                'status_source' => isset($orderData['situacao']) ? 'webhook' : 'api_fetch'
            ]);
        } else {
            Log::warning('Order webhook without status information (even after API fetch)', [
                'bling_order_number' => $blingOrderNumber,
                'order_data' => $orderData,
                'webhook_log_id' => $webhookLog->id
            ]);
            app(WebhookLogService::class)->addMetadata($webhookLog, [
                'error' => 'Missing status information in webhook and API fetch',
                'order_data_keys' => array_keys($orderData),
            ]);
        }

        // Processar dados de transporte (rastreio) se disponíveis
        if (isset($orderData['transporte']) && !empty($orderData['transporte'])) {
            try {
                $shippingDTO = ShippingData::fromBling($orderData, $order->order_number);
                
                if ($shippingDTO->hasTrackingCode()) {
                    $this->shippingService->processShipping($shippingDTO);
                    
                    Log::info("Shipping data processed from Bling order webhook", [
                        'order_id' => $order->id,
                        'tracking_code' => $shippingDTO->trackingCode,
                        'webhook_log_id' => $webhookLog->id
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error processing shipping data from Bling order webhook', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                    'webhook_log_id' => $webhookLog->id
                ]);
            }
        }
    }

    /**
     * Handle invoice/nfce issuance
     * 
     * Usa DTO e Service para desacoplar do Bling
     */
    protected function handleInvoice(array $data, string $action, WebhookLog $webhookLog): void
    {
        $invoiceData = $data['data'] ?? [];
        $orderBlingId = $invoiceData['idPedido'] ?? null;

        if (!$orderBlingId) {
            Log::warning('Invoice webhook without order ID', ['webhook_log_id' => $webhookLog->id]);
            app(WebhookLogService::class)->addMetadata($webhookLog, ['error' => 'Missing order ID']);
            return;
        }

        // Buscar pedido pelo número do Bling
        $order = Order::where('bling_order_number', (string) $orderBlingId)->first();

        if (!$order) {
            Log::warning('Order not found for invoice webhook', [
                'bling_order_id' => $orderBlingId,
                'webhook_log_id' => $webhookLog->id
            ]);
            app(WebhookLogService::class)->addMetadata($webhookLog, ['error' => 'Order not found']);
            return;
        }

        // Criar DTO a partir dos dados do Bling
        $invoiceDTO = InvoiceData::fromBling($invoiceData, $order->order_number);

        // Processar NF usando service (desacoplado do Bling)
        try {
            $this->invoiceService->processInvoice($invoiceDTO);

            // Atualizar metadata do log
            app(WebhookLogService::class)->addMetadata($webhookLog, [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'invoice_number' => $invoiceDTO->invoiceNumber,
                'invoice_key' => $invoiceDTO->invoiceKey,
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing invoice via service', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'webhook_log_id' => $webhookLog->id
            ]);
            app(WebhookLogService::class)->addMetadata($webhookLog, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Extrair resource do event
     */
    protected function extractResource(?string $event): ?string
    {
        if (!$event) return null;
        $parts = explode('.', $event);
        return $parts[0] ?? null;
    }
}

