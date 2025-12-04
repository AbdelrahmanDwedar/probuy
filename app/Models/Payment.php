<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use BelongsToTenant, HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'order_id',
        'provider',
        'provider_transaction_id',
        'status',
        'amount_cents',
        'currency',
        'provider_payload',
        'error_message',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'provider_payload' => 'array',
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

    public function isAuthorized(): bool
    {
        return $this->status === 'authorized';
    }

    public function isCaptured(): bool
    {
        return $this->status === 'captured';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    // Helpers
    public function getAmountAttribute(): float
    {
        return $this->amount_cents / 100;
    }

    public function getFormattedAmountAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->amount, 2);
    }
}

