<?php

namespace App\Events;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockAdjusted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public InventoryItem $inventoryItem,
        public InventoryMovement $movement
    ) {}
}

