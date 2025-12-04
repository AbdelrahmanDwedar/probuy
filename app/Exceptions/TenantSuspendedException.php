<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class TenantSuspendedException extends Exception
{
    protected $message = 'This tenant account is suspended';
    protected $code = 403;

    public function render(): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 'TENANT_SUSPENDED',
                'message' => $this->message,
                'details' => 'Please contact support for more information.',
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ], $this->code);
    }
}

