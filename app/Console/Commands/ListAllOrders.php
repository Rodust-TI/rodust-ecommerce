<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class ListAllOrders extends Command
{
    protected $signature = 'orders:list {--limit=10 : Número de pedidos a exibir}';
    protected $description = 'Listar todos os pedidos';

    public function handle()
    {
        $limit = (int) $this->option('limit');
        
        $orders = Order::orderBy('id', 'desc')
            ->limit($limit)
            ->get(['id', 'order_number', 'bling_order_number', 'status', 'payment_status', 'payment_method', 'paid_at', 'created_at']);

        if ($orders->isEmpty()) {
            $this->info('Nenhum pedido encontrado.');
            return 0;
        }

        $this->info("Últimos {$orders->count()} pedidos:");
        $this->newLine();

        $headers = ['ID', 'Número', 'Bling ID', 'Status', 'Payment', 'Método', 'Paid At', 'Criado em'];
        $data = $orders->map(function ($order) {
            return [
                $order->id,
                $order->order_number,
                $order->bling_order_number ?? 'N/A',
                $order->status,
                $order->payment_status ?? 'N/A',
                $order->payment_method ?? 'N/A',
                $order->paid_at ? $order->paid_at->format('d/m/Y H:i') : 'null',
                $order->created_at->format('d/m/Y H:i'),
            ];
        });

        $this->table($headers, $data);

        return 0;
    }
}
