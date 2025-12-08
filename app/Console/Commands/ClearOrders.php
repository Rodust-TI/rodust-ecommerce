<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearOrders extends Command
{
    protected $signature = 'orders:clear {--confirm : Confirmar sem perguntar}';
    protected $description = 'Limpar todos os pedidos do Laravel (útil para testes)';

    public function handle()
    {
        if (!$this->option('confirm')) {
            if (!$this->confirm('⚠️  ATENÇÃO: Isso irá DELETAR TODOS os pedidos do Laravel. Deseja continuar?')) {
                $this->info('Operação cancelada.');
                return 0;
            }
        }

        $this->info('🗑️  Limpando pedidos...');

        try {
            // Contar pedidos antes
            $count = Order::count();
            $this->info("   Pedidos encontrados: {$count}");

            // Limpar order_items primeiro (devido a foreign keys)
            DB::table('order_items')->truncate();
            
            // Deletar todos os pedidos (hard delete)
            $deleted = Order::query()->delete();

            $this->info("✅ {$deleted} pedido(s) deletado(s) com sucesso!");
            $this->info("   Tabela order_items também foi limpa.");
            $this->newLine();
            $this->info("💡 Agora você pode limpar os pedidos no Bling também e começar testes novos.");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Erro ao limpar pedidos: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }
}
