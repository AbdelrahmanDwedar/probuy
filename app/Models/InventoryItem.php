<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use BelongsToTenant, HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'product_variant_id',
        'stock_on_hand',
        'stock_reserved',
        'low_stock_threshold',
        'warehouse_location',
        'meta',
    ];

    protected $casts = [
        'stock_on_hand' => 'integer',
        'stock_reserved' => 'integer',
        'low_stock_threshold' => 'integer',
        'meta' => 'array',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(StockReservation::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    // Computed attributes
    public function getAvailableStockAttribute(): int
    {
        return max(0, $this->stock_on_hand - $this->stock_reserved);
    }

    public function isLowStock(): bool
    {
        return $this->stock_on_hand <= $this->low_stock_threshold;
    }

    public function isOutOfStock(): bool
    {
        return $this->available_stock <= 0;
    }

    // Business logic
    public function reserve(int $quantity, ?string $orderId = null, ?int $expiresInMinutes = 15): StockReservation
    {
        if ($quantity > $this->available_stock) {
            throw new \DomainException("Insufficient stock. Available: {$this->available_stock}, Requested: {$quantity}");
        }

        $reservation = $this->reservations()->create([
            'tenant_id' => $this->tenant_id,
            'order_id' => $orderId,
            'quantity' => $quantity,
            'reason' => 'order_placement',
            'expires_at' => $expiresInMinutes ? now()->addMinutes($expiresInMinutes) : null,
        ]);

        $this->increment('stock_reserved', $quantity);

        event(new \App\Events\StockReserved($this, $reservation));

        return $reservation;
    }

    public function release(StockReservation $reservation): void
    {
        if ($reservation->released_at) {
            throw new \DomainException('Reservation already released');
        }

        $reservation->update(['released_at' => now()]);
        $this->decrement('stock_reserved', $reservation->quantity);

        event(new \App\Events\StockReleased($this, $reservation));
    }

    public function adjust(int $delta, string $reason, ?string $actorId = null, array $meta = []): InventoryMovement
    {
        $movement = $this->movements()->create([
            'tenant_id' => $this->tenant_id,
            'delta' => $delta,
            'reason' => $reason,
            'actor_id' => $actorId,
            'meta' => $meta,
        ]);

        $this->increment('stock_on_hand', $delta);

        event(new \App\Events\StockAdjusted($this, $movement));

        if ($this->isLowStock()) {
            event(new \App\Events\StockLevelLow($this));
        }

        return $movement;
    }

    public function commit(StockReservation $reservation): void
    {
        // Convert reservation to actual stock reduction
        $this->decrement('stock_reserved', $reservation->quantity);
        $this->decrement('stock_on_hand', $reservation->quantity);

        $this->movements()->create([
            'tenant_id' => $this->tenant_id,
            'delta' => -$reservation->quantity,
            'reason' => 'order_fulfilled',
            'reference_type' => 'Order',
            'reference_id' => $reservation->order_id,
            'meta' => ['reservation_id' => $reservation->id],
        ]);

        $reservation->update(['released_at' => now()]);
    }
}

