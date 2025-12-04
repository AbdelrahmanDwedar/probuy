<?php

namespace App\Listeners;

use App\Events\StockLevelLow;
use App\Jobs\SendEmailJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendLowStockAlert implements ShouldQueue
{
    public function handle(StockLevelLow $event): void
    {
        $inventoryItem = $event->inventoryItem;
        $variant = $inventoryItem->variant;

        // TODO: Get notification email from tenant settings
        $notificationEmail = 'inventory@example.com';

        SendEmailJob::dispatch(
            tenantId: $inventoryItem->tenant_id,
            to: $notificationEmail,
            subject: "Low Stock Alert: {$variant->sku}",
            template: 'emails.low-stock-alert',
            data: [
                'variant' => $variant,
                'inventory_item' => $inventoryItem,
                'stock_on_hand' => $inventoryItem->stock_on_hand,
                'threshold' => $inventoryItem->low_stock_threshold,
            ]
        );
    }
}

