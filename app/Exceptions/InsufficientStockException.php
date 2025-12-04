<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InsufficientStockException extends Exception
{
    protected $code = 400;

    public function __construct(
        public int $available,
        public int $requested
    ) {
        parent::__construct("Insufficient stock. Available: {$available}, Requested: {$requested}");
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 'INSUFFICIENT_STOCK',
                'message' => $this->message,
                'details' => [
                    'available' => $this->available,
                    'requested' => $this->requested,
                ],
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ], $this->code);
    }
}

