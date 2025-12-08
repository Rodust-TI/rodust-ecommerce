<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\OrderItem;

echo "⚠️  ATENÇÃO: Este script vai DELETAR TODOS os pedidos!\n";
echo "Pressione CTRL+C para cancelar ou ENTER para continuar...\n";

if (PHP_SAPI === 'cli') {
    // Aguardar 3 segundos em modo CLI
    sleep(3);
}

echo "\n🗑️  Deletando todos os pedidos...\n";

try {
    // Contar antes
    $totalOrders = Order::count();
    $totalItems = OrderItem::count();
    
    echo "Pedidos a deletar: {$totalOrders}\n";
    echo "Items a deletar: {$totalItems}\n\n";
    
    // Deletar items primeiro (foreign key)
    OrderItem::query()->delete();
    echo "✅ Items deletados\n";
    
    // Deletar pedidos
    Order::query()->delete();
    echo "✅ Pedidos deletados\n";
    
    // Resetar auto_increment
    DB::statement('ALTER TABLE orders AUTO_INCREMENT = 1');
    DB::statement('ALTER TABLE order_items AUTO_INCREMENT = 1');
    echo "✅ Auto increment resetado para 1\n";
    
    echo "\n🎉 Sucesso! Banco limpo e pronto para novos pedidos.\n";
    echo "Próximo pedido será ID = 1\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
