<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Category::with(['parent', 'children']);

        // Only root categories if requested
        if ($request->boolean('root_only')) {
            $query->whereNull('parent_id');
        }

        $categories = $query->orderBy('sort_order')->get();

        return response()->json([
            'data' => $categories,
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'parent_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug,NULL,id,tenant_id,' . TenantContext::getId(),
            'description' => 'nullable|string',
            'meta' => 'nullable|array',
            'sort_order' => 'nullable|integer',
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

        $data = $validator->validated();
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        $category = Category::create($data);

        return response()->json([
            'data' => $category->load(['parent', 'children']),
            'meta' => ['timestamp' => now()->toIso8601String()],
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $category = Category::with(['parent', 'children', 'products'])->findOrFail($id);

        return response()->json([
            'data' => $category,
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'parent_id' => 'nullable|exists:categories,id',
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:categories,slug,' . $id . ',id,tenant_id,' . TenantContext::getId(),
            'description' => 'nullable|string',
            'meta' => 'nullable|array',
            'sort_order' => 'nullable|integer',
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

        $category->update($validator->validated());

        return response()->json([
            'data' => $category->load(['parent', 'children']),
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        
        if ($category->hasChildren()) {
            return response()->json([
                'error' => [
                    'code' => 'CATEGORY_HAS_CHILDREN',
                    'message' => 'Cannot delete category with subcategories',
                ],
            ], 400);
        }

        $category->delete();

        return response()->json(null, 204);
    }
}

