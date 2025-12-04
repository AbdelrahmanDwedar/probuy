<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    use BelongsToTenant, HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'order_id',
        'carrier',
        'service_level',
        'tracking_number',
        'status',
        'label_url',
        'estimated_delivery_at',
        'delivered_at',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'estimated_delivery_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // Status methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function hasLabel(): bool
    {
        return $this->status === 'label_generated' || $this->isInTransit() || $this->isDelivered();
    }

    public function isInTransit(): bool
    {
        return in_array($this->status, ['picked_up', 'in_transit']);
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    // Business logic
    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        event(new \App\Events\ShipmentDelivered($this));
    }
}

