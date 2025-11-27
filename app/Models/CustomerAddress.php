<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerAddress extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'type', // Mantido para compatibilidade (deprecated)
        'is_shipping', // Endereço de entrega
        'is_billing', // Endereço de cobrança
        'label',
        'recipient_name',
        'zipcode',
        'address',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'country',
    ];

    protected $casts = [
        'is_shipping' => 'boolean',
        'is_billing' => 'boolean',
    ];

    /**
     * Relação com Customer
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Ao definir um endereço como shipping ou billing, remove dos outros
     * Apenas um endereço pode ser shipping e apenas um pode ser billing
     */
    protected static function booted()
    {
        static::saving(function ($address) {
            // Se está definindo como shipping, remove shipping dos outros
            if ($address->is_shipping && $address->isDirty('is_shipping')) {
                static::where('customer_id', $address->customer_id)
                    ->where('is_shipping', true)
                    ->where('id', '!=', $address->id ?? 0)
                    ->update(['is_shipping' => false]);
            }
            
            // Se está definindo como billing, remove billing dos outros
            if ($address->is_billing && $address->isDirty('is_billing')) {
                static::where('customer_id', $address->customer_id)
                    ->where('is_billing', true)
                    ->where('id', '!=', $address->id ?? 0)
                    ->update(['is_billing' => false]);
            }
        });
    }

    /**
     * Formatar CEP
     */
    public function getFormattedZipcodeAttribute()
    {
        return preg_replace('/(\d{5})(\d{3})/', '$1-$2', $this->zipcode);
    }

    /**
     * Endereço completo formatado
     */
    public function getFullAddressAttribute()
    {
        $parts = [
            $this->address,
            $this->number,
            $this->complement,
            $this->neighborhood,
            $this->city . '/' . $this->state,
            'CEP ' . $this->formatted_zipcode,
        ];

        return implode(', ', array_filter($parts));
    }
}

