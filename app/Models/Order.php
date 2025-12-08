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
        'paid_at',
        'payment_fee',
        'net_amount',
        'payment_details',
        'installments',
        'shipping_address',
        'shipping_method_name',
        'shipping_carrier',
        'tracking_code',
        'notes',
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
        'payment_fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'shipping_address' => 'array',
        'payment_details' => 'array',
        'paid_at' => 'datetime',
        'bling_synced_at' => 'datetime',
        'invoice_issued_at' => 'datetime',
        'last_bling_sync' => 'datetime',
    ];
    
    /**
     * Acessores para compatibilidade com frontend
     */
    protected $appends = ['subtotal_amount', 'shipping_cost', 'total_amount'];
    
    public function getSubtotalAmountAttribute()
    {
        return $this->subtotal;
    }
    
    public function getShippingCostAttribute()
    {
        return $this->shipping;
    }
    
    public function getTotalAmountAttribute()
    {
        return $this->total;
    }

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
