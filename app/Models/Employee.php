<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class Employee extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, HasUuids, Notifiable;

    protected $guard_name = 'api';

    protected $fillable = [
        'tenant_id',
        'email',
        'name',
        'password',
        'is_active',
        'meta',
        'email_verified_at',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'meta' => 'array',
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // Helper methods
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(['owner', 'admin']);
    }

    public function canManageEmployees(): bool
    {
        return $this->hasPermissionTo('manage:employees');
    }
}
