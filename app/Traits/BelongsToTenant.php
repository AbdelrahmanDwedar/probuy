<?php

namespace App\Traits;

use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        // Automatically scope all queries to current tenant
        static::addGlobalScope('tenant', function (Builder $builder) {
            if ($tenant = TenantContext::get()) {
                $builder->where($builder->getQuery()->from . '.tenant_id', $tenant->id);
            }
        });

        // Automatically set tenant_id when creating
        static::creating(function (Model $model) {
            if (!$model->tenant_id && $tenant = TenantContext::get()) {
                $model->tenant_id = $tenant->id;
            }
        });

        // Validate tenant_id matches current tenant on update
        static::updating(function (Model $model) {
            if ($tenant = TenantContext::get()) {
                if ($model->tenant_id !== $tenant->id) {
                    throw new \Exception('Cannot update model from different tenant');
                }
            }
        });
    }

    /**
     * Get the tenant this model belongs to
     */
    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope query to specific tenant
     */
    public function scopeForTenant(Builder $query, string|Tenant $tenant): Builder
    {
        $tenantId = $tenant instanceof Tenant ? $tenant->id : $tenant;
        return $query->where('tenant_id', $tenantId);
    }
}

