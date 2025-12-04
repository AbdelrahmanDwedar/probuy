<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class EmployeeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Employee::with('roles');

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $employees = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $employees->items(),
            'meta' => [
                'current_page' => $employees->currentPage(),
                'per_page' => $employees->perPage(),
                'total' => $employees->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:employees,email,NULL,id,tenant_id,' . TenantContext::getId(),
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
            'is_active' => 'nullable|boolean',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,name',
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

        $employee = Employee::create([
            'tenant_id' => TenantContext::getId(),
            'email' => $request->email,
            'name' => $request->name,
            'password' => Hash::make($request->password),
            'is_active' => $request->get('is_active', true),
        ]);

        if ($request->has('roles')) {
            $employee->assignRole($request->roles);
        }

        return response()->json([
            'data' => $employee->load('roles'),
            'meta' => ['timestamp' => now()->toIso8601String()],
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $employee = Employee::with(['roles', 'permissions'])->findOrFail($id);

        return response()->json([
            'data' => $employee,
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $employee = Employee::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'email' => 'sometimes|email|unique:employees,email,' . $id . ',id,tenant_id,' . TenantContext::getId(),
            'name' => 'sometimes|string|max:255',
            'password' => 'sometimes|string|min:8|confirmed',
            'is_active' => 'nullable|boolean',
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
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $employee->update($data);

        return response()->json([
            'data' => $employee,
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $employee = Employee::findOrFail($id);
        
        if ($employee->id === auth()->id()) {
            return response()->json([
                'error' => [
                    'code' => 'CANNOT_DELETE_SELF',
                    'message' => 'You cannot delete your own account',
                ],
            ], 400);
        }

        $employee->delete();

        return response()->json(null, 204);
    }

    public function assignRoles(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,name',
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

        $employee = Employee::findOrFail($id);
        $employee->syncRoles($request->roles);

        return response()->json([
            'data' => $employee->load('roles'),
            'meta' => [
                'message' => 'Roles assigned successfully',
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }
}

