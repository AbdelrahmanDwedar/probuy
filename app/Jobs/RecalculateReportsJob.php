<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Product;
use App\Services\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RecalculateReportsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public string $tenantId,
        public ?string $date = null
    ) {
        $this->onQueue('maintenance');
        $this->date = $this->date ?? now()->toDateString();
    }

    public function handle(): void
    {
        TenantContext::setById($this->tenantId);

        // Calculate sales for the day
        $salesData = Order::whereTenant($this->tenantId)
            ->whereDate('placed_at', $this->date)
            ->selectRaw('
                COUNT(*) as order_count,
                SUM(total_cents) as total_revenue,
                AVG(total_cents) as average_order_value
            ')
            ->first();

        // Cache the results
        $cacheKey = TenantContext::cacheKey("sales:report:{$this->date}");
        Cache::tags(TenantContext::cacheTags(['reports']))->put($cacheKey, [
            'date' => $this->date,
            'order_count' => $salesData->order_count ?? 0,
            'total_revenue' => $salesData->total_revenue ?? 0,
            'average_order_value' => $salesData->average_order_value ?? 0,
            'calculated_at' => now()->toIso8601String(),
        ], now()->addDay());

        // Calculate product stats
        $productCount = Product::whereTenant($this->tenantId)->count();
        $publishedCount = Product::whereTenant($this->tenantId)->published()->count();

        $productStatsKey = TenantContext::cacheKey("products:stats");
        Cache::tags(TenantContext::cacheTags(['reports']))->put($productStatsKey, [
            'total_products' => $productCount,
            'published_products' => $publishedCount,
            'draft_products' => $productCount - $publishedCount,
            'calculated_at' => now()->toIso8601String(),
        ], now()->addHour());

        Log::info("Reports recalculated", [
            'tenant_id' => $this->tenantId,
            'date' => $this->date,
        ]);
    }
}

