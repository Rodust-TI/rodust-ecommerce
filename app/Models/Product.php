<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sku',
        'name',
        'description',
        'price',
        'cost',
        'stock',
        'image',
        'active',
        'bling_id',
        'bling_synced_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'stock' => 'integer',
        'active' => 'boolean',
        'bling_synced_at' => 'datetime',
    ];

    /**
     * Relação com OrderItem
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
