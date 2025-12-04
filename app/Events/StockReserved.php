<?php

namespace App\Events;

use App\Models\InventoryItem;
use App\Models\StockReservation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockReserved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public InventoryItem $inventoryItem,
        public StockReservation $reservation
    ) {}
}

