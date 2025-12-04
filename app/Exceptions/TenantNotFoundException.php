<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class TenantNotFoundException extends Exception
{
    protected $message = 'Tenant not found';
    protected $code = 404;

    public function render(): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 'TENANT_NOT_FOUND',
                'message' => $this->message,
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ], $this->code);
    }
}
