<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    use BelongsToTenant, HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'inventory_item_id',
        'delta',
        'reason',
        'reference_type',
        'reference_id',
        'actor_id',
        'meta',
        'created_at',
    ];

    protected $casts = [
        'delta' => 'integer',
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            $model->created_at = now();
        });
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    // Helper methods
    public function isAddition(): bool
    {
        return $this->delta > 0;
    }

    public function isReduction(): bool
    {
        return $this->delta < 0;
    }
}

