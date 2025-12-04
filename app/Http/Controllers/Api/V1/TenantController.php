<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TenantController extends Controller
{
    public function __construct()
    {
        // Only system admins should access this controller
        // TODO: Add proper authorization middleware
    }

    public function index(Request $request): JsonResponse
    {
        $query = Tenant::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $tenants = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $tenants->items(),
            'meta' => [
                'current_page' => $tenants->currentPage(),
                'per_page' => $tenants->perPage(),
                'total' => $tenants->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:tenants,slug',
            'settings' => 'nullable|array',
            'trial_ends_at' => 'nullable|date',
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

        $tenant = Tenant::create($data);

        return response()->json([
            'data' => $tenant,
            'meta' => ['timestamp' => now()->toIso8601String()],
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $tenant = Tenant::withCount(['employees', 'customers', 'products', 'orders'])->findOrFail($id);

        return response()->json([
            'data' => $tenant,
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:tenants,slug,' . $id,
            'status' => 'sometimes|in:active,suspended,cancelled',
            'settings' => 'sometimes|array',
            'trial_ends_at' => 'nullable|date',
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

        $tenant->update($validator->validated());

        return response()->json([
            'data' => $tenant,
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    public function suspend(string $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->update(['status' => 'suspended']);

        return response()->json([
            'data' => $tenant,
            'meta' => [
                'message' => 'Tenant suspended successfully',
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function activate(string $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->update(['status' => 'active']);

        return response()->json([
            'data' => $tenant,
            'meta' => [
                'message' => 'Tenant activated successfully',
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }
}

