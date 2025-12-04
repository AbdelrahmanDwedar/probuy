<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use BelongsToTenant, HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'customer_id',
        'order_id',
        'rating',
        'title',
        'comment',
        'is_verified_purchase',
        'is_approved',
        'approved_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_verified_purchase' => 'boolean',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (Review $review) {
            $review->product->updateAverageRating();
        });

        static::updated(function (Review $review) {
            $review->product->updateAverageRating();
        });

        static::deleted(function (Review $review) {
            $review->product->updateAverageRating();
        });
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // Helper methods
    public function approve(): void
    {
        $this->update([
            'is_approved' => true,
            'approved_at' => now(),
        ]);
    }

    public function reject(): void
    {
        $this->update([
            'is_approved' => false,
            'approved_at' => null,
        ]);
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified_purchase', true);
    }
}

