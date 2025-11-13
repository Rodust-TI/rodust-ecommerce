<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'cpf_cnpj',
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

    protected $casts = [
        'bling_synced_at' => 'datetime',
    ];

    /**
     * Relação com Orders
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
