<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Integration extends Model
{
    protected $fillable = [
        'service',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'credentials',
        'config',
        'is_active',
        'last_sync_at',
    ];

    protected $casts = [
        'credentials' => 'array',
        'config' => 'array',
        'is_active' => 'boolean',
        'token_expires_at' => 'datetime',
        'last_sync_at' => 'datetime',
    ];

    /**
     * Verificar se token está expirado
     */
    public function isTokenExpired(): bool
    {
        if (!$this->token_expires_at) {
            return true;
        }

        return $this->token_expires_at->isPast();
    }

    /**
     * Marcar como sincronizado agora
     */
    public function markSynced(): void
    {
        $this->update(['last_sync_at' => now()]);
    }
}
