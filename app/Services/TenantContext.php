<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;

class TenantContext
{
    private static ?Tenant $tenant = null;

    /**
     * Set the current tenant context
     */
    public static function set(?Tenant $tenant): void
    {
        self::$tenant = $tenant;
    }

    /**
     * Get the current tenant
     */
    public static function get(): ?Tenant
    {
        return self::$tenant;
    }

    /**
     * Set tenant by ID (with caching)
     */
    public static function setById(string $tenantId): void
    {
        $tenant = Cache::tags(['tenant'])->remember(
            "tenant:$tenantId",
            now()->addHour(),
            fn() => Tenant::find($tenantId)
        );

        if (!$tenant) {
            throw new \Exception("Tenant not found: $tenantId");
        }

        self::set($tenant);
    }

    /**
     * Clear the current tenant context
     */
    public static function clear(): void
    {
        self::$tenant = null;
    }

    /**
     * Get tenant ID or throw exception
     */
    public static function getId(): string
    {
        if (!self::$tenant) {
            throw new \Exception('No tenant context set');
        }
        return self::$tenant->id;
    }

    /**
     * Check if tenant context is set
     */
    public static function has(): bool
    {
        return self::$tenant !== null;
    }

    /**
     * Get cache key prefix for current tenant
     */
    public static function cacheKey(string $key): string
    {
        $tenantId = self::getId();
        return "tenant:$tenantId:$key";
    }

    /**
     * Get cache tags for current tenant
     */
    public static function cacheTags(array $additionalTags = []): array
    {
        $tenantId = self::getId();
        return array_merge(["tenant:$tenantId"], $additionalTags);
    }
}

