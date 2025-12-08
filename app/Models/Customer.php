<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'cpf',
        'cnpj',
        'person_type',
        'birth_date',
        'fantasy_name',
        'state_registration',
        'state_uf',
        'nfe_email',
        'phone_commercial',
        'taxpayer_type',
        'email_verified_at',
        'verification_token',
        'verification_token_expires_at',
        'password_reset_token',
        'password_reset_token_expires_at',
        'must_reset_password',
        // Campos de endereço antigos mantidos temporariamente
        'zipcode',
        'address',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'bling_id',
        'bling_synced_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'verification_token_expires_at' => 'datetime',
        'password_reset_token_expires_at' => 'datetime',
        'bling_synced_at' => 'datetime',
        'birth_date' => 'date',
        'must_reset_password' => 'boolean',
    ];

    /**
     * Relação com Orders
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Relação com Endereços
     */
    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    /**
     * Pegar endereço padrão de um tipo específico
     */
    public function defaultAddress($type = 'shipping')
    {
        return $this->addresses()
            ->where('type', $type)
            ->where('is_default', true)
            ->first();
    }

    /**
     * Validar CPF
     */
    public static function isValidCPF($cpf)
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        
        if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }
        
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        
        return true;
    }
}
