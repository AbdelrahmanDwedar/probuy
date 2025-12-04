<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductVariant extends Model
{
    use BelongsToTenant, HasFactory, HasUuids;

    protected $fillable = [
        'product_id',
        'tenant_id',
        'sku',
        'price_cents',
        'compare_at_price_cents',
        'currency',
        'cost_cents',
        'attributes',
        'weight_grams',
        'dimensions',
    ];

    protected $casts = [
        'price_cents' => 'integer',
        'compare_at_price_cents' => 'integer',
        'cost_cents' => 'integer',
        'weight_grams' => 'integer',
        'attributes' => 'array',
        'dimensions' => 'array',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryItem(): HasOne
    {
        return $this->hasOne(InventoryItem::class);
    }

    // Price helpers
    public function getPriceAttribute(): float
    {
        return $this->price_cents / 100;
    }

    public function getFormattedPriceAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->price, 2);
    }

    public function hasDiscount(): bool
    {
        return $this->compare_at_price_cents && $this->compare_at_price_cents > $this->price_cents;
    }

    public function getDiscountPercentageAttribute(): ?int
    {
        if (!$this->hasDiscount()) {
            return null;
        }
        return (int) round((($this->compare_at_price_cents - $this->price_cents) / $this->compare_at_price_cents) * 100);
    }

    // Stock helpers
    public function getAvailableStockAttribute(): int
    {
        return $this->inventoryItem?->available_stock ?? 0;
    }

    public function isInStock(): bool
    {
        return $this->available_stock > 0;
    }

    public function isLowStock(): bool
    {
        $item = $this->inventoryItem;
        return $item && $item->stock_on_hand <= $item->low_stock_threshold;
    }
}

