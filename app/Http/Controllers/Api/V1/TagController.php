<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TagController extends Controller
{
    public function index(): JsonResponse
    {
        $tags = Tag::withCount('products')->get();

        return response()->json([
            'data' => $tags,
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:tags,slug,NULL,id,tenant_id,' . TenantContext::getId(),
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

        $tag = Tag::create($data);

        return response()->json([
            'data' => $tag,
            'meta' => ['timestamp' => now()->toIso8601String()],
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $tag = Tag::withCount('products')->findOrFail($id);

        return response()->json([
            'data' => $tag,
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $tag = Tag::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:tags,slug,' . $id . ',id,tenant_id,' . TenantContext::getId(),
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

        $tag->update($validator->validated());

        return response()->json([
            'data' => $tag,
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $tag = Tag::findOrFail($id);
        $tag->delete();

        return response()->json(null, 204);
    }
}

