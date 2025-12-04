<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $this->resolveTenantId($request);

        if (!$tenantId) {
            return response()->json([
                'error' => [
                    'code' => 'TENANT_REQUIRED',
                    'message' => 'Tenant identification is required',
                ],
            ], 400);
        }

        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            return response()->json([
                'error' => [
                    'code' => 'TENANT_NOT_FOUND',
                    'message' => 'Tenant not found',
                ],
            ], 404);
        }

        if (!$tenant->isActive()) {
            return response()->json([
                'error' => [
                    'code' => 'TENANT_SUSPENDED',
                    'message' => 'This tenant account is suspended',
                ],
            ], 403);
        }

        TenantContext::set($tenant);

        $response = $next($request);

        // Clear tenant context after request
        TenantContext::clear();

        return $response;
    }

    private function resolveTenantId(Request $request): ?string
    {
        // 1. Try from header
        if ($tenantId = $request->header('X-Tenant-Id')) {
            return $tenantId;
        }

        // 2. Try from authenticated user (employee)
        if ($user = $request->user()) {
            if (method_exists($user, 'tenant') && $user->tenant) {
                return $user->tenant->id;
            }
            if (isset($user->tenant_id)) {
                return $user->tenant_id;
            }
        }

        // 3. Try from route parameter
        if ($tenantId = $request->route('tenant_id')) {
            return $tenantId;
        }

        return null;
    }
}
