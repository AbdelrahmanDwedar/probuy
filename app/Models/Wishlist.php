<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wishlist extends Model
{
    use BelongsToTenant, HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'name',
        'is_default',
        'is_public',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_public' => 'boolean',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    // Helper methods
    public function addProduct(string $productId, ?string $variantId = null, ?string $note = null): WishlistItem
    {
        return $this->items()->firstOrCreate(
            [
                'product_id' => $productId,
                'product_variant_id' => $variantId,
            ],
            [
                'note' => $note,
            ]
        );
    }

    public function removeProduct(string $productId, ?string $variantId = null): bool
    {
        return $this->items()
            ->where('product_id', $productId)
            ->where('product_variant_id', $variantId)
            ->delete() > 0;
    }

    public function hasProduct(string $productId, ?string $variantId = null): bool
    {
        return $this->items()
            ->where('product_id', $productId)
            ->where('product_variant_id', $variantId)
            ->exists();
    }
}

