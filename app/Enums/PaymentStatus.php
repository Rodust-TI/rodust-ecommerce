<?php

namespace App\Enums;

/**
 * Enum: Status de Pagamento
 * 
 * Define os possíveis status de pagamento de um pedido.
 * Mapeado dos status do Mercado Pago.
 */
enum PaymentStatus: string
{
    case PENDING = 'pending';           // Aguardando pagamento
    case APPROVED = 'approved';         // Pagamento aprovado
    case AUTHORIZED = 'authorized';     // Pagamento autorizado (captura pendente)
    case IN_PROCESS = 'in_process';     // Em processamento
    case IN_MEDIATION = 'in_mediation'; // Em mediação
    case REJECTED = 'rejected';         // Rejeitado
    case CANCELLED = 'cancelled';       // Cancelado
    case REFUNDED = 'refunded';         // Reembolsado
    case CHARGED_BACK = 'charged_back'; // Chargeback

    /**
     * Obter label legível em português
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Aguardando Pagamento',
            self::APPROVED => 'Pago',
            self::AUTHORIZED => 'Autorizado',
            self::IN_PROCESS => 'Em Processamento',
            self::IN_MEDIATION => 'Em Mediação',
            self::REJECTED => 'Rejeitado',
            self::CANCELLED => 'Cancelado',
            self::REFUNDED => 'Reembolsado',
            self::CHARGED_BACK => 'Chargeback',
        };
    }

    /**
     * Obter cor para exibição no frontend
     */
    public function color(): string
    {
        return match($this) {
            self::PENDING => 'yellow',
            self::APPROVED => 'green',
            self::AUTHORIZED => 'blue',
            self::IN_PROCESS => 'blue',
            self::IN_MEDIATION => 'orange',
            self::REJECTED => 'red',
            self::CANCELLED => 'gray',
            self::REFUNDED => 'purple',
            self::CHARGED_BACK => 'red',
        };
    }

    /**
     * Obter ícone para exibição no frontend
     */
    public function icon(): string
    {
        return match($this) {
            self::PENDING => '⏳',
            self::APPROVED => '✅',
            self::AUTHORIZED => '🔒',
            self::IN_PROCESS => '⚙️',
            self::IN_MEDIATION => '⚖️',
            self::REJECTED => '❌',
            self::CANCELLED => '🚫',
            self::REFUNDED => '💰',
            self::CHARGED_BACK => '⚠️',
        };
    }

    /**
     * Verificar se é um status final (não pode mais mudar)
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::APPROVED,
            self::REJECTED,
            self::CANCELLED,
            self::REFUNDED,
            self::CHARGED_BACK
        ]);
    }

    /**
     * Verificar se o pagamento foi bem-sucedido
     */
    public function isSuccessful(): bool
    {
        return in_array($this, [self::APPROVED, self::AUTHORIZED]);
    }

    /**
     * Verificar se o pagamento falhou
     */
    public function isFailed(): bool
    {
        return in_array($this, [self::REJECTED, self::CANCELLED]);
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
            'is_successful' => $status->isSuccessful(),
            'is_failed' => $status->isFailed(),
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

    /**
     * Mapear status do Mercado Pago para enum
     */
    public static function fromMercadoPago(string $mpStatus): self
    {
        return match($mpStatus) {
            'pending' => self::PENDING,
            'approved' => self::APPROVED,
            'authorized' => self::AUTHORIZED,
            'in_process' => self::IN_PROCESS,
            'in_mediation' => self::IN_MEDIATION,
            'rejected' => self::REJECTED,
            'cancelled' => self::CANCELLED,
            'refunded' => self::REFUNDED,
            'charged_back' => self::CHARGED_BACK,
            default => self::PENDING,
        };
    }
}
