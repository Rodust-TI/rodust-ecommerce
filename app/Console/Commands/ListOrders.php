<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class ListOrders extends Command
{
    protected $signature = 'orders:list';
    protected $description = 'Listar todos os pedidos com informações do Bling';

    public function handle()
    {
        $orders = Order::whereNotNull('bling_order_number')->get();
        
        if ($orders->isEmpty()) {
            $this->warn('Nenhum pedido com bling_order_number encontrado');
            return Command::SUCCESS;
        }
        
        $this->info("Pedidos encontrados: {$orders->count()}");
        $this->newLine();
        
        $rows = [];
        foreach ($orders as $order) {
            $rows[] = [
                $order->id,
                $order->order_number,
                $order->bling_order_number,
                $order->status,
            ];
        }
        
        $this->table(
            ['ID Laravel', 'Order Number', 'Bling Order Number', 'Status'],
            $rows
        );
        
        return Command::SUCCESS;
    }
}

