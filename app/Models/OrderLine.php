<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderLine extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'order_id',
        'product_variant_id',
        'sku',
        'title',
        'quantity',
        'price_cents',
        'tax_cents',
        'discount_cents',
        'meta',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price_cents' => 'integer',
        'tax_cents' => 'integer',
        'discount_cents' => 'integer',
        'meta' => 'array',
    ];

    // Relationships
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    // Computed attributes
    public function getLineTotalAttribute(): int
    {
        return ($this->price_cents * $this->quantity) + $this->tax_cents - $this->discount_cents;
    }

    public function getFormattedLineTotalAttribute(): string
    {
        $currency = $this->order->currency ?? 'USD';
        return $currency . ' ' . number_format($this->line_total / 100, 2);
    }
}

