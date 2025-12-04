<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use BelongsToTenant, HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'email',
        'name',
        'phone',
        'addresses',
        'meta',
    ];

    protected $casts = [
        'addresses' => 'array',
        'meta' => 'array',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    // Helper methods
    public function getFullNameAttribute(): string
    {
        return $this->name ?? $this->email ?? 'Guest';
    }

    public function getDefaultAddress(): ?array
    {
        $addresses = $this->addresses ?? [];
        foreach ($addresses as $address) {
            if ($address['is_default'] ?? false) {
                return $address;
            }
        }
        return $addresses[0] ?? null;
    }

    public function addAddress(array $address): void
    {
        $addresses = $this->addresses ?? [];
        $addresses[] = $address;
        $this->update(['addresses' => $addresses]);
    }
}
