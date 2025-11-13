<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'order_number',
        'status',
        'subtotal',
        'discount',
        'shipping',
        'total',
        'payment_method',
        'payment_status',
        'notes',
        'bling_id',
        'bling_synced_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'shipping' => 'decimal:2',
        'total' => 'decimal:2',
        'bling_synced_at' => 'datetime',
    ];

    /**
     * Relação com Customer
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relação com OrderItems
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
