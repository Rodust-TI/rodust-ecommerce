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
        'promotional_price',
        'cost',
        'stock',
        'image',
        'images',
        'active',
        'bling_id',
        'wordpress_post_id',
        'bling_category_id',
        'bling_synced_at',
        // Dimensões físicas
        'width',
        'height',
        'length',
        'weight',
        // Informações comerciais
        'brand',
        'free_shipping',
        // Controle de sincronização
        'last_sync_at',
        'sync_status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'promotional_price' => 'decimal:2',
        'cost' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'length' => 'decimal:2',
        'weight' => 'decimal:3',
        'stock' => 'integer',
        'active' => 'boolean',
        'free_shipping' => 'boolean',
        'images' => 'array', // JSON
        'bling_synced_at' => 'datetime',
        'last_sync_at' => 'datetime',
    ];

    /**
     * Relação com OrderItem
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Obter preço efetivo (promocional se existir, senão normal)
     */
    public function getEffectivePriceAttribute(): float
    {
        return $this->promotional_price ?? $this->price;
    }

    /**
     * Verificar se produto está em promoção
     */
    public function isOnSale(): bool
    {
        return $this->promotional_price && $this->promotional_price < $this->price;
    }

    /**
     * Obter desconto percentual
     */
    public function getDiscountPercentageAttribute(): ?int
    {
        if (!$this->isOnSale()) {
            return null;
        }

        return (int) round((($this->price - $this->promotional_price) / $this->price) * 100);
    }

    /**
     * Verificar se tem dimensões completas para frete
     */
    public function hasShippingDimensions(): bool
    {
        return $this->width && $this->height && $this->length && $this->weight;
    }
}
