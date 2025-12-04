<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    public function index(Request $request, string $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);
        
        $query = $product->reviews()->with(['customer']);
        
        if (!$request->user() || !$request->user()->can('view:all-reviews')) {
            $query->approved();
        }
        
        $reviews = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $reviews->items(),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
                'average_rating' => $product->average_rating,
                'review_count' => $product->review_count,
            ],
        ]);
    }

    public function store(Request $request, string $productId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'comment' => 'nullable|string|max:1000',
            'order_id' => 'nullable|exists:orders,id',
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

        $product = Product::findOrFail($productId);
        
        // Check if user already reviewed this product
        $existingReview = Review::where('product_id', $productId)
            ->where('customer_id', $request->user()->id)
            ->first();
            
        if ($existingReview) {
            return response()->json([
                'error' => [
                    'code' => 'REVIEW_EXISTS',
                    'message' => 'You have already reviewed this product',
                ],
            ], 400);
        }

        // Check if verified purchase
        $isVerifiedPurchase = false;
        if ($request->order_id) {
            $order = Order::where('id', $request->order_id)
                ->where('customer_id', $request->user()->id)
                ->whereHas('lines', fn($q) => $q->whereHas('variant', fn($q) => $q->where('product_id', $productId)))
                ->exists();
            $isVerifiedPurchase = $order;
        }

        $review = Review::create([
            'tenant_id' => TenantContext::getId(),
            'product_id' => $productId,
            'customer_id' => $request->user()->id,
            'order_id' => $request->order_id,
            'rating' => $request->rating,
            'title' => $request->title,
            'comment' => $request->comment,
            'is_verified_purchase' => $isVerifiedPurchase,
            'is_approved' => false, // Requires approval
        ]);

        return response()->json([
            'data' => $review,
            'meta' => [
                'message' => 'Review submitted successfully. It will be visible after approval.',
                'timestamp' => now()->toIso8601String(),
            ],
        ], 201);
    }

    public function approve(string $productId, string $reviewId): JsonResponse
    {
        $review = Review::where('product_id', $productId)->findOrFail($reviewId);
        $review->approve();

        return response()->json([
            'data' => $review,
            'meta' => [
                'message' => 'Review approved successfully',
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function reject(string $productId, string $reviewId): JsonResponse
    {
        $review = Review::where('product_id', $productId)->findOrFail($reviewId);
        $review->reject();

        return response()->json([
            'data' => $review,
            'meta' => [
                'message' => 'Review rejected successfully',
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function destroy(string $productId, string $reviewId): JsonResponse
    {
        $review = Review::where('product_id', $productId)->findOrFail($reviewId);
        
        // Only allow customer to delete their own review or admin
        if ($review->customer_id !== auth()->id() && !auth()->user()->can('delete:reviews')) {
            return response()->json([
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have permission to delete this review',
                ],
            ], 403);
        }

        $review->delete();

        return response()->json(null, 204);
    }
}

