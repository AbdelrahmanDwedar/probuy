<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReserveStockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 10;

    public function __construct(
        public string $tenantId,
        public string $orderId
    ) {
        $this->onQueue('high');
    }

    public function handle(): void
    {
        TenantContext::setById($this->tenantId);

        $order = Order::findOrFail($this->orderId);

        DB::transaction(function () use ($order) {
            foreach ($order->lines as $line) {
                if (!$line->variant) {
                    continue;
                }

                $inventoryItem = $line->variant->inventoryItem;
                
                if (!$inventoryItem) {
                    throw new \Exception("No inventory item for variant: {$line->variant->sku}");
                }

                $inventoryItem->reserve(
                    quantity: $line->quantity,
                    orderId: $order->id,
                    expiresInMinutes: 15
                );
            }
        });

        Log::info("Stock reserved for order", [
            'tenant_id' => $this->tenantId,
            'order_id' => $this->orderId,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Failed to reserve stock", [
            'tenant_id' => $this->tenantId,
            'order_id' => $this->orderId,
            'error' => $exception->getMessage(),
        ]);

        // Cancel the order if stock reservation fails
        $order = Order::find($this->orderId);
        if ($order && $order->status === 'pending') {
            $order->cancel('Stock reservation failed');
        }
    }
}

