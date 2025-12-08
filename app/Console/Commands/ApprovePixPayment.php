<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Enums\PaymentStatus;
use App\Jobs\SyncOrderToBling;
use App\Services\Bling\BlingOrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\PaymentConfirmedMail;

class ApprovePixPayment extends Command
{
    protected $signature = 'payment:approve-pix 
                            {order_id? : ID do pedido a aprovar}
                            {--list : Listar pedidos PIX pendentes}
                            {--all : Aprovar todos os pedidos PIX pendentes}';

    protected $description = 'Aprovar pagamento PIX de um pedido (para testes)';

    public function __construct(
        private BlingOrderService $blingOrder
    ) {
        parent::__construct();
    }

    public function handle()
    {
        if ($this->option('list')) {
            return $this->listPendingPixOrders();
        }

        if ($this->option('all')) {
            return $this->approveAllPending();
        }

        $orderId = $this->argument('order_id');

        if (!$orderId) {
            $this->error('❌ ID do pedido é obrigatório');
            $this->info('');
            $this->info('Uso:');
            $this->line('  php artisan payment:approve-pix {order_id}');
            $this->line('  php artisan payment:approve-pix --list');
            $this->line('  php artisan payment:approve-pix --all');
            return 1;
        }

        return $this->approveOrder($orderId);
    }

    /**
     * Listar pedidos PIX pendentes
     */
    private function listPendingPixOrders()
    {
        $orders = Order::where('payment_method', 'pix')
            ->where('payment_status', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        if ($orders->isEmpty()) {
            $this->info('✅ Nenhum pedido PIX pendente encontrado.');
            return 0;
        }

        $this->info("📋 Pedidos PIX Pendentes ({$orders->count()}):");
        $this->newLine();

        $headers = ['ID', 'Número', 'Cliente', 'Total', 'Criado em', 'Payment ID'];
        $rows = [];

        foreach ($orders as $order) {
            $rows[] = [
                $order->id,
                $order->order_number,
                $order->customer->name ?? 'N/A',
                'R$ ' . number_format($order->total, 2, ',', '.'),
                $order->created_at->format('d/m/Y H:i'),
                $order->payment_id ?? 'N/A'
            ];
        }

        $this->table($headers, $rows);
        $this->newLine();
        $this->info('Para aprovar um pedido:');
        $this->line('  php artisan payment:approve-pix {id}');

        return 0;
    }

    /**
     * Aprovar um pedido específico
     */
    private function approveOrder($orderId)
    {
        $order = Order::find($orderId);

        if (!$order) {
            $this->error("❌ Pedido #{$orderId} não encontrado.");
            return 1;
        }

        if ($order->payment_method !== 'pix') {
            $this->error("❌ Pedido #{$orderId} não é um pagamento PIX.");
            return 1;
        }

        if ($order->payment_status === 'approved') {
            $this->warn("⚠️  Pedido #{$orderId} já está aprovado.");
            return 0;
        }

        $this->info("🔄 Aprovando pagamento PIX do pedido #{$orderId}...");
        $this->newLine();

        // Atualizar status do pagamento
        $order->update([
            'payment_status' => PaymentStatus::APPROVED->value
        ]);

        $this->info("✅ Status de pagamento atualizado para 'approved'");

        // Se o pedido ainda está pendente, atualizar status e disparar ações
        if ($order->status === 'pending') {
            $order->update([
                'status' => 'processing',
                'paid_at' => now()
            ]);

            $this->info("✅ Status do pedido atualizado para 'processing'");

            // Sincronizar com Bling
            // Se pedido já existe no Bling, atualizar status. Senão, criar novo.
            if ($order->bling_order_number) {
                $this->info("📦 Atualizando status do pedido no Bling...");
                try {
                    $result = $this->blingOrder->updateOrder($order);
                    if ($result['success']) {
                        $this->info("✅ Status do pedido atualizado no Bling para 'processing'");
                    } else {
                        $this->warn("⚠️  Erro ao atualizar pedido no Bling: {$result['error']}");
                    }
                } catch (\Exception $e) {
                    $this->warn("⚠️  Erro ao atualizar pedido no Bling: {$e->getMessage()}");
                }
            } else {
                $this->info("📦 Criando pedido no Bling...");
                SyncOrderToBling::dispatch($order);
                $this->info("✅ Job de criação no Bling enfileirado");
            }

            // Enviar email de confirmação
            try {
                $this->info("📧 Enviando email de confirmação...");
                Mail::to($order->customer->email)
                    ->send(new PaymentConfirmedMail($order));
                $this->info("✅ Email enviado para: {$order->customer->email}");
            } catch (\Exception $e) {
                $this->warn("⚠️  Erro ao enviar email: {$e->getMessage()}");
                Log::error('Erro ao enviar email de confirmação', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->newLine();
        $this->info("✅ Pagamento PIX aprovado com sucesso!");
        $this->newLine();
        $this->table(
            ['Campo', 'Valor'],
            [
                ['Pedido', $order->order_number],
                ['Status', $order->fresh()->status],
                ['Status Pagamento', $order->fresh()->payment_status],
                ['Total', 'R$ ' . number_format($order->total, 2, ',', '.')],
                ['Cliente', $order->customer->name],
                ['Email', $order->customer->email],
            ]
        );

        Log::info('🧪 Pagamento PIX aprovado manualmente via comando', [
            'order_id' => $order->id,
            'order_number' => $order->order_number
        ]);

        return 0;
    }

    /**
     * Aprovar todos os pedidos PIX pendentes
     */
    private function approveAllPending()
    {
        $orders = Order::where('payment_method', 'pix')
            ->where('payment_status', 'pending')
            ->get();

        if ($orders->isEmpty()) {
            $this->info('✅ Nenhum pedido PIX pendente encontrado.');
            return 0;
        }

        if (!$this->confirm("⚠️  Deseja aprovar {$orders->count()} pedido(s) PIX pendente(s)?")) {
            $this->info('Operação cancelada.');
            return 0;
        }

        $this->info("🔄 Aprovando {$orders->count()} pedido(s)...");
        $this->newLine();

        $bar = $this->output->createProgressBar($orders->count());
        $bar->start();

        foreach ($orders as $order) {
            $order->update([
                'payment_status' => PaymentStatus::APPROVED->value
            ]);

            if ($order->status === 'pending') {
                $order->update([
                    'status' => 'processing',
                    'paid_at' => now()
                ]);

                // Sincronizar com Bling
                if ($order->bling_order_number) {
                    try {
                        $this->blingOrder->updateOrder($order);
                    } catch (\Exception $e) {
                        Log::error('Erro ao atualizar pedido no Bling', [
                            'order_id' => $order->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                } else {
                    SyncOrderToBling::dispatch($order);
                }

                try {
                    Mail::to($order->customer->email)
                        ->send(new PaymentConfirmedMail($order));
                } catch (\Exception $e) {
                    Log::error('Erro ao enviar email', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->newLine();
        $this->info("✅ {$orders->count()} pedido(s) aprovado(s) com sucesso!");

        return 0;
    }
}
