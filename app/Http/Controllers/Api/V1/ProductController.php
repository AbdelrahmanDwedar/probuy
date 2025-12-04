<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['variants', 'categories', 'tags']);

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('category')) {
            $query->whereHas('categories', fn($q) => $q->where('slug', $request->category));
        }

        if ($request->has('tag')) {
            $query->whereHas('tags', fn($q) => $q->where('slug', $request->tag));
        }

        if ($request->has('q')) {
            $query->where('title', 'ILIKE', '%' . $request->q . '%');
        }

        if ($request->has('price_min') && $request->has('price_max')) {
            $query->whereHas('variants', function($q) use ($request) {
                $q->whereBetween('price_cents', [
                    $request->price_min * 100,
                    $request->price_max * 100
                ]);
            });
        }

        $products = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
            ],
            'links' => [
                'first' => $products->url(1),
                'last' => $products->url($products->lastPage()),
                'prev' => $products->previousPageUrl(),
                'next' => $products->nextPageUrl(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sku' => 'required|string|max:255|unique:products,sku,NULL,id,tenant_id,' . TenantContext::getId(),
            'title' => 'required|string|max:500',
            'description' => 'nullable|string',
            'status' => 'nullable|in:draft,published,archived',
            'metadata' => 'nullable|array',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
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

        $product = Product::create($validator->validated());

        if ($request->has('categories')) {
            $product->categories()->sync($request->categories);
        }

        if ($request->has('tags')) {
            $product->tags()->sync($request->tags);
        }

        return response()->json([
            'data' => $product->load(['variants', 'categories', 'tags']),
            'meta' => ['timestamp' => now()->toIso8601String()],
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $product = Product::with(['variants.inventoryItem', 'categories', 'tags', 'media'])->findOrFail($id);

        return response()->json([
            'data' => $product,
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'sku' => 'sometimes|string|max:255|unique:products,sku,' . $id . ',id,tenant_id,' . TenantContext::getId(),
            'title' => 'sometimes|string|max:500',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:draft,published,archived',
            'metadata' => 'nullable|array',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
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

        $product->update($validator->validated());

        if ($request->has('categories')) {
            $product->categories()->sync($request->categories);
        }

        if ($request->has('tags')) {
            $product->tags()->sync($request->tags);
        }

        return response()->json([
            'data' => $product->load(['variants', 'categories', 'tags']),
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'meta' => [
                'message' => 'Product deleted successfully',
                'timestamp' => now()->toIso8601String(),
            ],
        ], 204);
    }

    public function publish(string $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        try {
            $product->publish();
            
            return response()->json([
                'data' => $product,
                'meta' => [
                    'message' => 'Product published successfully',
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'error' => [
                    'code' => 'BUSINESS_LOGIC_ERROR',
                    'message' => $e->getMessage(),
                ],
            ], 400);
        }
    }

    public function archive(string $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $product->archive();

        return response()->json([
            'data' => $product,
            'meta' => [
                'message' => 'Product archived successfully',
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }
}

