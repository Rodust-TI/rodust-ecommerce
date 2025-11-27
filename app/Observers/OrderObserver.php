<?php

namespace App\Observers;

use App\Models\Order;
use Illuminate\Support\Str;

/**
 * Observer para Order
 * Responsabilidade: Gerar order_number automaticamente ao criar pedido
 */
class OrderObserver
{
    /**
     * Handle the Order "creating" event.
     * Gera order_number único antes de salvar
     */
    public function creating(Order $order): void
    {
        if (empty($order->order_number)) {
            $order->order_number = $this->generateOrderNumber();
        }
    }

    /**
     * Gerar número único do pedido
     * Formato: ROD-YYYYMMDD-XXXX
     * Exemplo: ROD-20251127-4582
     * 
     * Usa número aleatório para evitar duplicação em requisições concorrentes
     * Tenta até 10 vezes encontrar um número único
     */
    private function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $prefix = "ROD-{$date}-";
        
        $attempts = 0;
        $maxAttempts = 10;
        
        do {
            // Gerar número aleatório de 4 dígitos
            $randomNumber = rand(1000, 9999);
            $orderNumber = $prefix . $randomNumber;
            
            // Verificar se já existe
            $exists = Order::where('order_number', $orderNumber)->exists();
            
            if (!$exists) {
                return $orderNumber;
            }
            
            $attempts++;
        } while ($attempts < $maxAttempts);
        
        // Fallback: usar timestamp + microsegundos (garante unicidade)
        return $prefix . substr(microtime(true) * 10000, -4);
    }
}
