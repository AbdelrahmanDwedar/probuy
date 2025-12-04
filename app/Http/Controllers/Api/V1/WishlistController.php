<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WishlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $wishlists = Wishlist::where('customer_id', $request->user()->id)
            ->withCount('items')
            ->get();

        return response()->json([
            'data' => $wishlists,
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $wishlist = Wishlist::with(['items.product', 'items.variant'])
            ->where('customer_id', auth()->id())
            ->findOrFail($id);

        return response()->json([
            'data' => $wishlist,
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'is_public' => 'nullable|boolean',
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

        $wishlist = Wishlist::create([
            'tenant_id' => TenantContext::getId(),
            'customer_id' => $request->user()->id,
            'name' => $request->name,
            'is_public' => $request->get('is_public', false),
            'is_default' => false,
        ]);

        return response()->json([
            'data' => $wishlist,
            'meta' => ['timestamp' => now()->toIso8601String()],
        ], 201);
    }

    public function addProduct(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
            'note' => 'nullable|string|max:500',
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

        $wishlist = Wishlist::where('customer_id', $request->user()->id)->findOrFail($id);
        
        $item = $wishlist->addProduct(
            $request->product_id,
            $request->variant_id,
            $request->note
        );

        return response()->json([
            'data' => $item->load(['product', 'variant']),
            'meta' => [
                'message' => 'Product added to wishlist',
                'timestamp' => now()->toIso8601String(),
            ],
        ], 201);
    }

    public function removeProduct(Request $request, string $id, string $productId): JsonResponse
    {
        $wishlist = Wishlist::where('customer_id', $request->user()->id)->findOrFail($id);
        
        $removed = $wishlist->removeProduct($productId, $request->variant_id);

        if (!$removed) {
            return response()->json([
                'error' => [
                    'code' => 'ITEM_NOT_FOUND',
                    'message' => 'Product not found in wishlist',
                ],
            ], 404);
        }

        return response()->json([
            'meta' => [
                'message' => 'Product removed from wishlist',
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $wishlist = Wishlist::where('customer_id', auth()->id())->findOrFail($id);
        
        if ($wishlist->is_default) {
            return response()->json([
                'error' => [
                    'code' => 'CANNOT_DELETE_DEFAULT',
                    'message' => 'Cannot delete default wishlist',
                ],
            ], 400);
        }

        $wishlist->delete();

        return response()->json(null, 204);
    }
}

