<?php

namespace App\Services;

class TenantMediaDiskResolver
{
    public static function getDisk(): string
    {
        // For local development, use local disk with tenant-specific path
        // For production, use S3 with tenant-specific prefix
        return config('app.env') === 'production' ? 's3' : 'tenant_media';
    }

    public static function getPathPrefix(): string
    {
        if (!TenantContext::has()) {
            return 'default';
        }

        $tenantId = TenantContext::getId();
        return "tenants/$tenantId";
    }

    public static function getFullPath(string $path = ''): string
    {
        $prefix = self::getPathPrefix();
        return $path ? "$prefix/$path" : $prefix;
    }
}
