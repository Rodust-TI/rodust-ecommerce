<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'product_sku',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    /**
     * Relação com Order
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relação com Product (nullable)
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
