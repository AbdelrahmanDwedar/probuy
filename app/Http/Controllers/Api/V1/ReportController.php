<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function sales(Request $request): JsonResponse
    {
        $from = $request->get('from', now()->subDays(30)->toDateString());
        $to = $request->get('to', now()->toDateString());

        $cacheKey = TenantContext::cacheKey("report:sales:$from:$to");

        $data = Cache::tags(TenantContext::cacheTags(['reports']))->remember(
            $cacheKey,
            now()->addHour(),
            function () use ($from, $to) {
                return Order::whereBetween('placed_at', [$from, $to])
                    ->selectRaw('
                        DATE(placed_at) as date,
                        COUNT(*) as order_count,
                        SUM(total_cents) as revenue,
                        AVG(total_cents) as average_order_value
                    ')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();
            }
        );

        $summary = [
            'total_orders' => $data->sum('order_count'),
            'total_revenue' => $data->sum('revenue'),
            'average_order_value' => $data->avg('average_order_value'),
        ];

        return response()->json([
            'data' => [
                'daily' => $data,
                'summary' => $summary,
            ],
            'meta' => [
                'period' => ['from' => $from, 'to' => $to],
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function inventory(Request $request): JsonResponse
    {
        $data = DB::table('inventory_items as i')
            ->join('product_variants as v', 'i.product_variant_id', '=', 'v.id')
            ->join('products as p', 'v.product_id', '=', 'p.id')
            ->where('i.tenant_id', TenantContext::getId())
            ->select([
                'p.id as product_id',
                'p.title as product_title',
                'v.sku',
                'i.stock_on_hand',
                'i.stock_reserved',
                DB::raw('(i.stock_on_hand - i.stock_reserved) as available_stock'),
                'i.low_stock_threshold',
                DB::raw('CASE WHEN (i.stock_on_hand - i.stock_reserved) <= 0 THEN true ELSE false END as out_of_stock'),
                DB::raw('CASE WHEN i.stock_on_hand <= i.low_stock_threshold THEN true ELSE false END as low_stock'),
            ])
            ->get();

        $summary = [
            'total_items' => $data->count(),
            'out_of_stock' => $data->where('out_of_stock', true)->count(),
            'low_stock' => $data->where('low_stock', true)->count(),
            'total_stock_value' => $data->sum('available_stock'),
        ];

        return response()->json([
            'data' => [
                'items' => $data,
                'summary' => $summary,
            ],
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    public function customers(Request $request): JsonResponse
    {
        $from = $request->get('from', now()->subDays(30)->toDateString());
        $to = $request->get('to', now()->toDateString());

        $data = DB::table('customers as c')
            ->leftJoin('orders as o', 'c.id', '=', 'o.customer_id')
            ->where('c.tenant_id', TenantContext::getId())
            ->whereBetween('o.placed_at', [$from, $to])
            ->select([
                'c.id',
                'c.name',
                'c.email',
                DB::raw('COUNT(o.id) as order_count'),
                DB::raw('SUM(o.total_cents) as total_spent'),
                DB::raw('AVG(o.total_cents) as average_order_value'),
                DB::raw('MAX(o.placed_at) as last_order_at'),
            ])
            ->groupBy('c.id', 'c.name', 'c.email')
            ->having('order_count', '>', 0)
            ->orderByDesc('total_spent')
            ->limit(100)
            ->get();

        return response()->json([
            'data' => $data,
            'meta' => [
                'period' => ['from' => $from, 'to' => $to],
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        $from = $request->get('from', now()->subDays(30)->toDateString());
        $to = $request->get('to', now()->toDateString());

        $data = DB::table('order_lines as ol')
            ->join('orders as o', 'ol.order_id', '=', 'o.id')
            ->join('product_variants as v', 'ol.product_variant_id', '=', 'v.id')
            ->join('products as p', 'v.product_id', '=', 'p.id')
            ->where('o.tenant_id', TenantContext::getId())
            ->whereBetween('o.placed_at', [$from, $to])
            ->select([
                'p.id',
                'p.title',
                'p.sku',
                DB::raw('SUM(ol.quantity) as units_sold'),
                DB::raw('SUM(ol.price_cents * ol.quantity) as revenue'),
                DB::raw('COUNT(DISTINCT o.id) as order_count'),
            ])
            ->groupBy('p.id', 'p.title', 'p.sku')
            ->orderByDesc('revenue')
            ->limit(50)
            ->get();

        return response()->json([
            'data' => $data,
            'meta' => [
                'period' => ['from' => $from, 'to' => $to],
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }
}

