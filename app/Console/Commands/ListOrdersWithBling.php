<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class ListOrdersWithBling extends Command
{
    protected $signature = 'orders:list-bling';
    protected $description = 'Listar pedidos que têm número do Bling';

    public function handle()
    {
        $orders = Order::whereNotNull('bling_order_number')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get(['id', 'order_number', 'bling_order_number', 'status', 'payment_status', 'paid_at']);

        if ($orders->isEmpty()) {
            $this->info('Nenhum pedido encontrado com número do Bling.');
            return 0;
        }

        $this->info('Pedidos com número do Bling:');
        $this->newLine();

        $headers = ['ID', 'Número', 'Bling ID', 'Status', 'Payment', 'Paid At'];
        $data = $orders->map(function ($order) {
            return [
                $order->id,
                $order->order_number,
                $order->bling_order_number,
                $order->status,
                $order->payment_status ?? 'N/A',
                $order->paid_at ? $order->paid_at->format('d/m/Y H:i') : 'null',
            ];
        });

        $this->table($headers, $data);

        $this->newLine();
        $this->info('Para testar atualização, use:');
        $this->line('  <info>php artisan bling:test-update-order {id}</info>');

        return 0;
    }
}
