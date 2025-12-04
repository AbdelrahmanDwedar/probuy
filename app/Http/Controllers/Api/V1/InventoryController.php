<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InventoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = InventoryItem::with(['variant.product']);

        if ($request->boolean('low_stock')) {
            $query->whereRaw('stock_on_hand <= low_stock_threshold');
        }

        if ($request->boolean('out_of_stock')) {
            $query->whereRaw('(stock_on_hand - stock_reserved) <= 0');
        }

        $items = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $items->items(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function show(string $variantId): JsonResponse
    {
        $variant = ProductVariant::findOrFail($variantId);
        $inventoryItem = $variant->inventoryItem()->with(['movements' => fn($q) => $q->latest()->limit(10)])->first();

        if (!$inventoryItem) {
            return response()->json([
                'error' => [
                    'code' => 'INVENTORY_NOT_FOUND',
                    'message' => 'No inventory record found for this variant',
                ],
            ], 404);
        }

        return response()->json([
            'data' => $inventoryItem,
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    public function adjust(Request $request, string $variantId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'delta' => 'required|integer',
            'reason' => 'required|string|in:purchase,sale,adjustment,return,damage,transfer',
            'notes' => 'nullable|string',
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

        $variant = ProductVariant::findOrFail($variantId);
        $inventoryItem = $variant->inventoryItem;

        if (!$inventoryItem) {
            $inventoryItem = InventoryItem::create([
                'product_variant_id' => $variant->id,
                'stock_on_hand' => 0,
                'stock_reserved' => 0,
            ]);
        }

        try {
            $movement = $inventoryItem->adjust(
                delta: $request->delta,
                reason: $request->reason,
                actorId: auth()->id(),
                meta: ['notes' => $request->notes]
            );

            return response()->json([
                'data' => [
                    'inventory_item' => $inventoryItem->fresh(),
                    'movement' => $movement,
                ],
                'meta' => [
                    'message' => 'Stock adjusted successfully',
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'ADJUSTMENT_FAILED',
                    'message' => $e->getMessage(),
                ],
            ], 400);
        }
    }

    public function reserve(Request $request, string $variantId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
            'order_id' => 'nullable|exists:orders,id',
            'expires_in_minutes' => 'nullable|integer|min:1|max:1440',
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

        $variant = ProductVariant::findOrFail($variantId);
        $inventoryItem = $variant->inventoryItem;

        if (!$inventoryItem) {
            return response()->json([
                'error' => [
                    'code' => 'INVENTORY_NOT_FOUND',
                    'message' => 'No inventory record found for this variant',
                ],
            ], 404);
        }

        try {
            $reservation = $inventoryItem->reserve(
                quantity: $request->quantity,
                orderId: $request->order_id,
                expiresInMinutes: $request->get('expires_in_minutes', 15)
            );

            return response()->json([
                'data' => [
                    'reservation' => $reservation,
                    'inventory_item' => $inventoryItem->fresh(),
                ],
                'meta' => [
                    'message' => 'Stock reserved successfully',
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 201);
        } catch (\DomainException $e) {
            return response()->json([
                'error' => [
                    'code' => 'INSUFFICIENT_STOCK',
                    'message' => $e->getMessage(),
                ],
            ], 400);
        }
    }
}

