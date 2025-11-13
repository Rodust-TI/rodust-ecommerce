<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle incoming webhooks from Bling ERP
     * 
     * Webhook types:
     * - produtos (products): Create/update/delete product
     * - estoques (stock): Update stock levels
     * - pedidos (orders): Order status changes
     * - notasfiscais (invoices): Fiscal note issued
     * - nfce (consumer invoices): Consumer note issued
     */
    public function handle(Request $request)
    {
        // Log webhook payload for debugging
        Log::info('Bling Webhook Received', [
            'headers' => $request->headers->all(),
            'body' => $request->all(),
        ]);

        // Validate webhook signature (Bling sends a hash)
        if (!$this->validateWebhook($request)) {
            Log::warning('Invalid Bling webhook signature');
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $data = $request->all();
        $topic = $data['topic'] ?? null; // Ex: 'produtos', 'estoques', 'pedidos'

        try {
            switch ($topic) {
                case 'produtos':
                    $this->handleProductWebhook($data);
                    break;

                case 'estoques':
                    $this->handleStockWebhook($data);
                    break;

                case 'pedidos':
                    $this->handleOrderWebhook($data);
                    break;

                case 'notasfiscais':
                case 'nfce':
                    $this->handleInvoiceWebhook($data);
                    break;

                default:
                    Log::info('Unknown webhook topic: ' . $topic);
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Webhook processing error', [
                'topic' => $topic,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Validate webhook signature from Bling
     */
    protected function validateWebhook(Request $request): bool
    {
        // Bling pode enviar um hash no header para validação
        // Verificar documentação oficial: https://developer.bling.com.br/webhooks
        
        $signature = $request->header('X-Bling-Signature');
        
        // Se não tiver assinatura, aceitar temporariamente (remover em produção)
        if (!$signature && config('app.env') === 'local') {
            return true;
        }

        // TODO: Implementar validação de assinatura conforme documentação Bling
        // Geralmente é um HMAC-SHA256 do payload com o client_secret
        
        return true; // Temporário
    }

    /**
     * Handle product creation/update/deletion
     */
    protected function handleProductWebhook(array $data): void
    {
        $event = $data['event'] ?? null; // 'created', 'updated', 'deleted'
        $productData = $data['data'] ?? [];
        
        $blingId = $productData['id'] ?? null;
        
        if (!$blingId) {
            Log::warning('Product webhook without Bling ID');
            return;
        }

        switch ($event) {
            case 'created':
            case 'updated':
                // Atualizar ou criar produto no banco local
                Product::updateOrCreate(
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
                
                Log::info("Product {$event} from Bling", ['bling_id' => $blingId]);
                break;

            case 'deleted':
                // Desativar produto ao invés de deletar (soft delete)
                Product::where('bling_id', $blingId)->update([
                    'active' => false,
                    'last_bling_sync' => now(),
                ]);
                
                Log::info("Product deleted from Bling", ['bling_id' => $blingId]);
                break;
        }
    }

    /**
     * Handle stock level updates
     */
    protected function handleStockWebhook(array $data): void
    {
        $productData = $data['data'] ?? [];
        $blingId = $productData['idProduto'] ?? null;
        $newStock = $productData['saldo'] ?? null;

        if (!$blingId || $newStock === null) {
            Log::warning('Stock webhook without product ID or stock value');
            return;
        }

        // Atualizar estoque do produto
        $updated = Product::where('bling_id', $blingId)->update([
            'stock' => $newStock,
            'last_bling_sync' => now(),
        ]);

        if ($updated) {
            Log::info("Stock updated from Bling", [
                'bling_id' => $blingId,
                'new_stock' => $newStock,
            ]);
        }
    }

    /**
     * Handle order status changes
     */
    protected function handleOrderWebhook(array $data): void
    {
        $event = $data['event'] ?? null;
        $orderData = $data['data'] ?? [];
        $blingId = $orderData['id'] ?? null;

        if (!$blingId) {
            Log::warning('Order webhook without Bling ID');
            return;
        }

        // Atualizar status do pedido local
        $order = Order::where('bling_id', $blingId)->first();
        
        if ($order) {
            $order->update([
                'status' => $this->mapBlingOrderStatus($orderData['situacao'] ?? null),
                'last_bling_sync' => now(),
            ]);

            Log::info("Order {$event} from Bling", [
                'bling_id' => $blingId,
                'status' => $order->status,
            ]);
        }
    }

    /**
     * Handle invoice/nfce issuance
     */
    protected function handleInvoiceWebhook(array $data): void
    {
        $invoiceData = $data['data'] ?? [];
        $orderBlingId = $invoiceData['idPedido'] ?? null;
        $invoiceNumber = $invoiceData['numero'] ?? null;
        $invoiceKey = $invoiceData['chaveAcesso'] ?? null;

        if (!$orderBlingId) {
            Log::warning('Invoice webhook without order ID');
            return;
        }

        // Atualizar pedido com dados da nota fiscal
        $order = Order::where('bling_id', $orderBlingId)->first();
        
        if ($order) {
            $order->update([
                'invoice_number' => $invoiceNumber,
                'invoice_key' => $invoiceKey,
                'invoice_issued_at' => now(),
                'status' => 'invoiced',
                'last_bling_sync' => now(),
            ]);

            Log::info("Invoice issued for order", [
                'order_id' => $order->id,
                'invoice_number' => $invoiceNumber,
            ]);
        }
    }

    /**
     * Map Bling order status to internal status
     */
    protected function mapBlingOrderStatus(?string $blingStatus): string
    {
        return match($blingStatus) {
            'Em aberto' => 'pending',
            'Em andamento' => 'processing',
            'Faturado' => 'invoiced',
            'Enviado' => 'shipped',
            'Entregue' => 'delivered',
            'Cancelado' => 'cancelled',
            default => 'pending',
        };
    }
}
