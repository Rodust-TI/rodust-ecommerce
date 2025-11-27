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
        'payment_id',
        'notes',
        'bling_id',
        'bling_order_id',
        'bling_order_number',
        'bling_synced_at',
        'invoice_number',
        'invoice_key',
        'invoice_pdf_url',
        'invoice_issued_at',
        'last_bling_sync',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'shipping' => 'decimal:2',
        'total' => 'decimal:2',
        'bling_synced_at' => 'datetime',
        'invoice_issued_at' => 'datetime',
        'last_bling_sync' => 'datetime',
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
