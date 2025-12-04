<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessPaymentJob;
use App\Models\Order;
use App\Models\Payment;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Payment::with(['order']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('provider')) {
            $query->where('provider', $request->provider);
        }

        if ($request->has('order_id')) {
            $query->where('order_id', $request->order_id);
        }

        $payments = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $payments->items(),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $payment = Payment::with(['order'])->findOrFail($id);

        return response()->json([
            'data' => $payment,
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    public function processPayment(Request $request, string $orderId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'provider' => 'required|in:stripe,paypal,manual',
            'payment_data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'The given data was invalid',
                    'details' => $validator->errors(),
                ],
            ], 422);
        }

        $order = Order::findOrFail($orderId);

        if (!$order->isPending()) {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_ORDER_STATUS',
                    'message' => 'Order is not in pending status',
                ],
            ], 400);
        }

        // Dispatch payment processing job
        ProcessPaymentJob::dispatch(
            TenantContext::getId(),
            $order->id,
            $request->provider,
            $request->get('payment_data', [])
        );

        return response()->json([
            'data' => [
                'order_id' => $order->id,
                'status' => 'processing',
                'message' => 'Payment is being processed',
            ],
            'meta' => ['timestamp' => now()->toIso8601String()],
        ], 202);
    }

    public function webhook(Request $request): JsonResponse
    {
        // TODO: Implement webhook verification (Stripe/PayPal signature)
        $provider = $request->header('X-Payment-Provider', 'stripe');

        // TODO: Parse webhook payload based on provider
        // For now, just log the webhook
        \Log::info('Payment webhook received', [
            'provider' => $provider,
            'payload' => $request->all(),
        ]);

        return response()->json(['status' => 'received'], 200);
    }

    public function refund(Request $request, string $id): JsonResponse
    {
        $payment = Payment::findOrFail($id);

        if (!$payment->isCaptured()) {
            return response()->json([
                'error' => [
                    'code' => 'CANNOT_REFUND',
                    'message' => 'Payment must be captured before refunding',
                ],
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'amount_cents' => 'nullable|integer|min:1|max:' . $payment->amount_cents,
            'reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'The given data was invalid',
                    'details' => $validator->errors(),
                ],
            ], 422);
        }

        // TODO: Implement actual refund with payment gateway
        $payment->update([
            'status' => 'refunded',
            'provider_payload' => array_merge($payment->provider_payload ?? [], [
                'refund' => [
                    'amount_cents' => $request->get('amount_cents', $payment->amount_cents),
                    'reason' => $request->get('reason'),
                    'refunded_at' => now()->toIso8601String(),
                ],
            ]),
        ]);

        return response()->json([
            'data' => $payment,
            'meta' => [
                'message' => 'Refund processed successfully',
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }
}

