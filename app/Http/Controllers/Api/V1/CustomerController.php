<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Customer::query();

        if ($request->has('q')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'ILIKE', '%' . $request->q . '%')
                  ->orWhere('email', 'ILIKE', '%' . $request->q . '%');
            });
        }

        $customers = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $customers->items(),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'nullable|email|unique:customers,email,NULL,id,tenant_id,' . TenantContext::getId(),
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'addresses' => 'nullable|array',
            'meta' => 'nullable|array',
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

        $customer = Customer::create($validator->validated());

        return response()->json([
            'data' => $customer,
            'meta' => ['timestamp' => now()->toIso8601String()],
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $customer = Customer::with(['orders' => fn($q) => $q->latest()->limit(10)])->findOrFail($id);

        return response()->json([
            'data' => $customer,
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'email' => 'sometimes|email|unique:customers,email,' . $id . ',id,tenant_id,' . TenantContext::getId(),
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:50',
            'addresses' => 'sometimes|array',
            'meta' => 'sometimes|array',
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

        $customer->update($validator->validated());

        return response()->json([
            'data' => $customer,
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();

        return response()->json(null, 204);
    }
}

