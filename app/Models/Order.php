<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use BelongsToTenant, HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'order_number',
        'customer_id',
        'currency',
        'subtotal_cents',
        'tax_cents',
        'shipping_cents',
        'discount_cents',
        'total_cents',
        'status',
        'billing_address',
        'shipping_address',
        'customer_note',
        'metadata',
        'placed_at',
    ];

    protected $casts = [
        'subtotal_cents' => 'integer',
        'tax_cents' => 'integer',
        'shipping_cents' => 'integer',
        'discount_cents' => 'integer',
        'total_cents' => 'integer',
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'metadata' => 'array',
        'placed_at' => 'datetime',
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

    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(StockReservation::class);
    }

    // Price helpers
    public function getTotalAttribute(): float
    {
        return $this->total_cents / 100;
    }

    public function getFormattedTotalAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->total, 2);
    }

    // Status methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPaid(): bool
    {
        return in_array($this->status, ['paid', 'processing', 'fulfilled', 'completed']);
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'paid']);
    }

    public function canBeRefunded(): bool
    {
        return in_array($this->status, ['paid', 'processing', 'fulfilled', 'completed']);
    }

    // Business logic
    public function calculateTotals(): void
    {
        $subtotal = $this->lines->sum(fn($line) => $line->price_cents * $line->quantity);
        $tax = $this->lines->sum('tax_cents');
        $discount = $this->lines->sum('discount_cents');
        
        $this->subtotal_cents = $subtotal;
        $this->tax_cents = $tax;
        $this->discount_cents = $discount;
        $this->total_cents = $subtotal + $tax + $this->shipping_cents - $discount;
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => 'paid',
            'placed_at' => $this->placed_at ?? now(),
        ]);

        event(new \App\Events\OrderPaid($this));
    }

    public function cancel(?string $reason = null): void
    {
        if (!$this->canBeCancelled()) {
            throw new \DomainException("Order cannot be cancelled in status: {$this->status}");
        }

        $this->update(['status' => 'cancelled']);

        // Release stock reservations
        foreach ($this->reservations()->active()->get() as $reservation) {
            $reservation->inventoryItem->release($reservation);
        }

        event(new \App\Events\OrderCancelled($this, $reason));
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->whereIn('status', ['paid', 'processing', 'fulfilled', 'completed']);
    }

    public function scopePlacedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('placed_at', [$startDate, $endDate]);
    }
}

