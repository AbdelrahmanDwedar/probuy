<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use BelongsToTenant, HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'actor_type',
        'actor_id',
        'action',
        'target_type',
        'target_id',
        'changes',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'changes' => 'array',
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

    // Polymorphic relationships
    public function actor()
    {
        return $this->morphTo();
    }

    public function target()
    {
        return $this->morphTo();
    }

    // Helper method to log activity
    public static function logActivity(
        string $action,
        ?Model $target = null,
        ?Model $actor = null,
        ?array $changes = null
    ): self {
        return self::create([
            'tenant_id' => \App\Services\TenantContext::getId(),
            'actor_type' => $actor ? get_class($actor) : null,
            'actor_id' => $actor?->id,
            'action' => $action,
            'target_type' => $target ? get_class($target) : null,
            'target_id' => $target?->id,
            'changes' => $changes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}

