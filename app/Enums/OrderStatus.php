<?php

namespace App\Enums;

/**
 * Enum: Status de Pedido
 * 
 * Define os possíveis status internos de um pedido no sistema.
 * Estes status são mapeados para/de status do Bling.
 */
enum OrderStatus: string
{
    case PENDING = 'pending';           // Aguardando processamento / Em aberto
    case PROCESSING = 'processing';      // Em processamento / Em andamento
    case INVOICED = 'invoiced';         // Faturado / NF emitida
    case SHIPPED = 'shipped';           // Enviado / Em transporte
    case DELIVERED = 'delivered';       // Entregue
    case CANCELLED = 'cancelled';       // Cancelado

    /**
     * Obter label legível em português
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pendente',
            self::PROCESSING => 'Em Processamento',
            self::INVOICED => 'Faturado',
            self::SHIPPED => 'Enviado',
            self::DELIVERED => 'Concluído', // Atendido/Entregue = Pedido concluído
            self::CANCELLED => 'Cancelado',
        };
    }

    /**
     * Obter cor para exibição no frontend
     */
    public function color(): string
    {
        return match($this) {
            self::PENDING => 'yellow',
            self::PROCESSING => 'blue',
            self::INVOICED => 'purple',
            self::SHIPPED => 'indigo',
            self::DELIVERED => 'green',
            self::CANCELLED => 'red',
        };
    }

    /**
     * Obter ícone para exibição no frontend
     */
    public function icon(): string
    {
        return match($this) {
            self::PENDING => '⏳',
            self::PROCESSING => '⚙️',
            self::INVOICED => '📄',
            self::SHIPPED => '🚚',
            self::DELIVERED => '✅',
            self::CANCELLED => '❌',
        };
    }

    /**
     * Verificar se é um status final (não pode mais mudar)
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::DELIVERED, self::CANCELLED]);
    }

    /**
     * Obter todos os status como array para API
     */
    public static function toArray(): array
    {
        return array_map(fn(self $status) => [
            'value' => $status->value,
            'label' => $status->label(),
            'color' => $status->color(),
            'icon' => $status->icon(),
            'is_final' => $status->isFinal(),
        ], self::cases());
    }

    /**
     * Criar instância a partir de uma string (case-insensitive)
     */
    public static function fromString(string $value): ?self
    {
        foreach (self::cases() as $case) {
            if (strcasecmp($case->value, $value) === 0) {
                return $case;
            }
        }
        return null;
    }
}
