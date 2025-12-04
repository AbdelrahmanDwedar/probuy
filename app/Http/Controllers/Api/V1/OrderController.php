<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\OrderPlaced;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessPaymentJob;
use App\Jobs\ReserveStockJob;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['customer', 'lines']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('from') && $request->has('to')) {
            $query->whereBetween('placed_at', [$request->from, $request->to]);
        }

        $orders = $query->latest('created_at')->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $orders->items(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'nullable|exists:customers,id',
            'customer_email' => 'required_without:customer_id|email',
            'customer_name' => 'required_without:customer_id|string',
            'lines' => 'required|array|min:1',
            'lines.*.variant_id' => 'required|exists:product_variants,id',
            'lines.*.quantity' => 'required|integer|min:1',
            'billing_address' => 'required|array',
            'shipping_address' => 'required|array',
            'customer_note' => 'nullable|string',
            'coupon_code' => 'nullable|string|exists:coupons,code',
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

        try {
            $order = DB::transaction(function () use ($request) {
                // Get or create customer
                $customer = $this->resolveCustomer($request);

                // Create order
                $order = Order::create([
                    'tenant_id' => TenantContext::getId(),
                    'order_number' => $this->generateOrderNumber(),
                    'customer_id' => $customer?->id,
                    'currency' => 'USD',
                    'billing_address' => $request->billing_address,
                    'shipping_address' => $request->shipping_address,
                    'customer_note' => $request->customer_note,
                    'status' => 'pending',
                ]);

                // Create order lines
                $subtotal = 0;
                foreach ($request->lines as $lineData) {
                    $variant = ProductVariant::findOrFail($lineData['variant_id']);
                    
                    $order->lines()->create([
                        'product_variant_id' => $variant->id,
                        'sku' => $variant->sku,
                        'title' => $variant->product->title,
                        'quantity' => $lineData['quantity'],
                        'price_cents' => $variant->price_cents,
                        'tax_cents' => 0, // TODO: Calculate tax
                    ]);

                    $subtotal += $variant->price_cents * $lineData['quantity'];
                }

                // Calculate totals
                $order->subtotal_cents = $subtotal;
                $order->tax_cents = 0; // TODO: Calculate tax
                $order->shipping_cents = 0; // TODO: Calculate shipping
                $order->discount_cents = 0; // TODO: Apply coupon
                $order->total_cents = $subtotal + $order->tax_cents + $order->shipping_cents - $order->discount_cents;
                $order->save();

                return $order;
            });

            // Dispatch background jobs
            ReserveStockJob::dispatch(TenantContext::getId(), $order->id);
            ProcessPaymentJob::dispatch(TenantContext::getId(), $order->id);

            // Emit event
            event(new OrderPlaced($order));

            return response()->json([
                'data' => $order->load(['lines', 'customer']),
                'meta' => [
                    'message' => 'Order created successfully',
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'ORDER_CREATION_FAILED',
                    'message' => $e->getMessage(),
                ],
            ], 400);
        }
    }

    public function show(string $id): JsonResponse
    {
        $order = Order::with(['customer', 'lines.variant', 'payments', 'shipments'])->findOrFail($id);

        return response()->json([
            'data' => $order,
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    public function cancel(string $id): JsonResponse
    {
        $order = Order::findOrFail($id);

        try {
            $order->cancel('Customer requested cancellation');

            return response()->json([
                'data' => $order,
                'meta' => [
                    'message' => 'Order cancelled successfully',
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'error' => [
                    'code' => 'CANNOT_CANCEL_ORDER',
                    'message' => $e->getMessage(),
                ],
            ], 400);
        }
    }

    private function resolveCustomer(Request $request): ?Customer
    {
        if ($request->has('customer_id')) {
            return Customer::find($request->customer_id);
        }

        // Guest checkout - create customer record
        return Customer::firstOrCreate(
            [
                'tenant_id' => TenantContext::getId(),
                'email' => $request->customer_email,
            ],
            [
                'name' => $request->customer_name,
            ]
        );
    }

    private function generateOrderNumber(): string
    {
        return 'ORD-' . strtoupper(Str::random(8));
    }
}

