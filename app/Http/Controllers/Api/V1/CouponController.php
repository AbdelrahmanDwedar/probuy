<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CouponController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Coupon::query();

        if ($request->boolean('active_only')) {
            $query->active();
        }

        $coupons = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $coupons->items(),
            'meta' => [
                'current_page' => $coupons->currentPage(),
                'per_page' => $coupons->perPage(),
                'total' => $coupons->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:255|unique:coupons,code,NULL,id,tenant_id,' . TenantContext::getId(),
            'type' => 'required|in:percentage,fixed,free_shipping',
            'value' => 'required|integer|min:0',
            'currency' => 'nullable|string|size:3',
            'usage_limit' => 'nullable|integer|min:1',
            'minimum_purchase_cents' => 'nullable|integer|min:0',
            'conditions' => 'nullable|array',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
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

        $coupon = Coupon::create($validator->validated());

        return response()->json([
            'data' => $coupon,
            'meta' => ['timestamp' => now()->toIso8601String()],
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);

        return response()->json([
            'data' => $coupon,
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    public function validate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|exists:coupons,code',
            'order_total_cents' => 'required|integer|min:0',
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

        $coupon = Coupon::where('code', $request->code)->first();

        if (!$coupon->canBeAppliedTo($request->order_total_cents)) {
            return response()->json([
                'data' => [
                    'valid' => false,
                    'reason' => $coupon->isActive() ? 'Minimum purchase not met' : 'Coupon is not active',
                ],
            ]);
        }

        $discount = $coupon->calculateDiscount($request->order_total_cents);

        return response()->json([
            'data' => [
                'valid' => true,
                'coupon' => $coupon,
                'discount_cents' => $discount,
                'final_total_cents' => $request->order_total_cents - $discount,
            ],
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'code' => 'sometimes|string|max:255|unique:coupons,code,' . $id . ',id,tenant_id,' . TenantContext::getId(),
            'type' => 'sometimes|in:percentage,fixed,free_shipping',
            'value' => 'sometimes|integer|min:0',
            'currency' => 'nullable|string|size:3',
            'usage_limit' => 'nullable|integer|min:1',
            'minimum_purchase_cents' => 'nullable|integer|min:0',
            'conditions' => 'nullable|array',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
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

        $coupon->update($validator->validated());

        return response()->json([
            'data' => $coupon,
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->delete();

        return response()->json(null, 204);
    }
}

