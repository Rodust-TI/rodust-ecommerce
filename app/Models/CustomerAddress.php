<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerAddress extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'type',
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
        'invoice_cpf_cnpj',
        'invoice_name',
        'invoice_ie',
        'invoice_im',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Relação com Customer
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Ao salvar um endereço como padrão, remove o padrão dos outros do mesmo tipo
     */
    protected static function booted()
    {
        static::saving(function ($address) {
            if ($address->is_default) {
                static::where('customer_id', $address->customer_id)
                    ->where('type', $address->type)
                    ->where('id', '!=', $address->id ?? 0)
                    ->update(['is_default' => false]);
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

