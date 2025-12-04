<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VariantController extends Controller
{
    public function index(string $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);
        $variants = $product->variants()->with('inventoryItem')->get();

        return response()->json([
            'data' => $variants,
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    public function store(Request $request, string $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);

        $validator = Validator::make($request->all(), [
            'sku' => 'required|string|max:255|unique:product_variants,sku,NULL,id,tenant_id,' . TenantContext::getId(),
            'price_cents' => 'required|integer|min:0',
            'compare_at_price_cents' => 'nullable|integer|min:0',
            'currency' => 'nullable|string|size:3',
            'cost_cents' => 'nullable|integer|min:0',
            'attributes' => 'nullable|array',
            'weight_grams' => 'nullable|integer|min:0',
            'dimensions' => 'nullable|array',
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

        $variant = $product->variants()->create(array_merge(
            $validator->validated(),
            ['tenant_id' => TenantContext::getId()]
        ));

        return response()->json([
            'data' => $variant,
            'meta' => ['timestamp' => now()->toIso8601String()],
        ], 201);
    }

    public function show(string $productId, string $id): JsonResponse
    {
        $variant = ProductVariant::where('product_id', $productId)
            ->with(['product', 'inventoryItem'])
            ->findOrFail($id);

        return response()->json([
            'data' => $variant,
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    public function update(Request $request, string $productId, string $id): JsonResponse
    {
        $variant = ProductVariant::where('product_id', $productId)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'sku' => 'sometimes|string|max:255|unique:product_variants,sku,' . $id . ',id,tenant_id,' . TenantContext::getId(),
            'price_cents' => 'sometimes|integer|min:0',
            'compare_at_price_cents' => 'nullable|integer|min:0',
            'currency' => 'sometimes|string|size:3',
            'cost_cents' => 'nullable|integer|min:0',
            'attributes' => 'sometimes|array',
            'weight_grams' => 'nullable|integer|min:0',
            'dimensions' => 'nullable|array',
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

        $variant->update($validator->validated());

        return response()->json([
            'data' => $variant,
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    public function destroy(string $productId, string $id): JsonResponse
    {
        $variant = ProductVariant::where('product_id', $productId)->findOrFail($id);
        $variant->delete();

        return response()->json(null, 204);
    }
}

