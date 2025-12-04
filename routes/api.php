<?php

use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CouponController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\ShipmentController;
use App\Http\Controllers\Api\V1\TagController;
use App\Http\Controllers\Api\V1\TenantController;
use App\Http\Controllers\Api\V1\VariantController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes (no auth required)
Route::prefix('v1')->group(function () {
    // Payment webhooks (no auth)
    Route::post('webhooks/payment', [PaymentController::class, 'webhook']);
});

// Protected API routes (require authentication)
Route::prefix('v1')->middleware(['auth:api', 'tenant'])->group(function () {
    
    // Products
    Route::apiResource('products', ProductController::class);
    Route::post('products/{id}/publish', [ProductController::class, 'publish']);
    Route::post('products/{id}/archive', [ProductController::class, 'archive']);
    
    // Product Variants
    Route::get('products/{productId}/variants', [VariantController::class, 'index']);
    Route::post('products/{productId}/variants', [VariantController::class, 'store']);
    Route::get('products/{productId}/variants/{id}', [VariantController::class, 'show']);
    Route::put('products/{productId}/variants/{id}', [VariantController::class, 'update']);
    Route::delete('products/{productId}/variants/{id}', [VariantController::class, 'destroy']);
    
    // Categories
    Route::apiResource('categories', CategoryController::class);
    
    // Tags
    Route::apiResource('tags', TagController::class);
    
    // Inventory
    Route::get('inventory', [InventoryController::class, 'index']);
    Route::get('inventory/{variantId}', [InventoryController::class, 'show']);
    Route::post('inventory/{variantId}/adjust', [InventoryController::class, 'adjust']);
    Route::post('inventory/{variantId}/reserve', [InventoryController::class, 'reserve']);
    
    // Orders
    Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show']);
    Route::post('orders/{id}/cancel', [OrderController::class, 'cancel']);
    
    // Payments
    Route::get('payments', [PaymentController::class, 'index']);
    Route::get('payments/{id}', [PaymentController::class, 'show']);
    Route::post('orders/{orderId}/payment', [PaymentController::class, 'processPayment']);
    Route::post('payments/{id}/refund', [PaymentController::class, 'refund']);
    
    // Shipments
    Route::get('shipments', [ShipmentController::class, 'index']);
    Route::get('shipments/{id}', [ShipmentController::class, 'show']);
    Route::get('shipments/track/{trackingNumber}', [ShipmentController::class, 'track']);
    Route::post('shipments/{id}/delivered', [ShipmentController::class, 'markAsDelivered']);
    
    // Customers
    Route::apiResource('customers', CustomerController::class);
    
    // Employees
    Route::apiResource('employees', EmployeeController::class);
    Route::post('employees/{id}/roles', [EmployeeController::class, 'assignRoles']);
    
    // Coupons
    Route::apiResource('coupons', CouponController::class);
    Route::post('coupons/validate', [CouponController::class, 'validate']);
    
    // Reports
    Route::get('reports/sales', [ReportController::class, 'sales']);
    Route::get('reports/inventory', [ReportController::class, 'inventory']);
    Route::get('reports/customers', [ReportController::class, 'customers']);
    Route::get('reports/products', [ReportController::class, 'products']);
    
    // Tenants (system admin only)
    Route::apiResource('tenants', TenantController::class)->middleware('admin');
    Route::post('tenants/{id}/suspend', [TenantController::class, 'suspend'])->middleware('admin');
    Route::post('tenants/{id}/activate', [TenantController::class, 'activate'])->middleware('admin');
    
    // Search
    Route::get('search', [App\Http\Controllers\Api\V1\SearchController::class, 'search']);
    
    // Reviews
    Route::get('products/{productId}/reviews', [App\Http\Controllers\Api\V1\ReviewController::class, 'index']);
    Route::post('products/{productId}/reviews', [App\Http\Controllers\Api\V1\ReviewController::class, 'store']);
    Route::post('products/{productId}/reviews/{reviewId}/approve', [App\Http\Controllers\Api\V1\ReviewController::class, 'approve']);
    Route::post('products/{productId}/reviews/{reviewId}/reject', [App\Http\Controllers\Api\V1\ReviewController::class, 'reject']);
    Route::delete('products/{productId}/reviews/{reviewId}', [App\Http\Controllers\Api\V1\ReviewController::class, 'destroy']);
    
    // Wishlists
    Route::get('wishlists', [App\Http\Controllers\Api\V1\WishlistController::class, 'index']);
    Route::post('wishlists', [App\Http\Controllers\Api\V1\WishlistController::class, 'store']);
    Route::get('wishlists/{id}', [App\Http\Controllers\Api\V1\WishlistController::class, 'show']);
    Route::post('wishlists/{id}/products', [App\Http\Controllers\Api\V1\WishlistController::class, 'addProduct']);
    Route::delete('wishlists/{id}/products/{productId}', [App\Http\Controllers\Api\V1\WishlistController::class, 'removeProduct']);
    Route::delete('wishlists/{id}', [App\Http\Controllers\Api\V1\WishlistController::class, 'destroy']);
});
