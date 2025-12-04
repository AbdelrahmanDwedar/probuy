<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Coupon extends Model
{
    use BelongsToTenant, HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'code',
        'type',
        'value',
        'currency',
        'usage_limit',
        'usage_count',
        'minimum_purchase_cents',
        'conditions',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'value' => 'integer',
        'usage_limit' => 'integer',
        'usage_count' => 'integer',
        'minimum_purchase_cents' => 'integer',
        'conditions' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // Validation methods
    public function isActive(): bool
    {
        $now = now();
        
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }
        
        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }
        
        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) {
            return false;
        }
        
        return true;
    }

    public function canBeAppliedTo(int $orderTotalCents): bool
    {
        if (!$this->isActive()) {
            return false;
        }
        
        if ($this->minimum_purchase_cents && $orderTotalCents < $this->minimum_purchase_cents) {
            return false;
        }
        
        return true;
    }

    // Calculate discount
    public function calculateDiscount(int $orderTotalCents): int
    {
        return match ($this->type) {
            'percentage' => (int) ($orderTotalCents * ($this->value / 10000)),
            'fixed' => min($this->value, $orderTotalCents),
            'free_shipping' => 0, // Handled separately
            default => 0,
        };
    }

    // Increment usage
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    // Scopes
    public function scopeActive($query)
    {
        $now = now();
        return $query->where(function ($q) use ($now) {
            $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
        })->where(function ($q) use ($now) {
            $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
        })->where(function ($q) {
            $q->whereNull('usage_limit')
                ->orWhereRaw('usage_count < usage_limit');
        });
    }
}

