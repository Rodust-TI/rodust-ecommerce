<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MelhorEnvioSetting extends Model
{
    protected $fillable = [
        'client_id',
        'client_secret',
        'bearer_token',
        'access_token',
        'refresh_token',
        'expires_at',
        'sandbox_mode',
        'origin_postal_code',
    ];

    protected $casts = [
        'sandbox_mode' => 'boolean',
        'expires_at' => 'datetime',
    ];

    /**
     * Check if token is expired
     */
    public function isTokenExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Get the singleton settings instance
     */
    public static function getSettings(): ?self
    {
        return self::first();
    }
}
