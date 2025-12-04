<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class BusinessLogicException extends Exception
{
    protected $code = 400;

    public function render(): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 'BUSINESS_LOGIC_ERROR',
                'message' => $this->message,
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ], $this->code);
    }
}

