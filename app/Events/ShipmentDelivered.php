<?php

namespace App\Events;

use App\Models\Shipment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShipmentDelivered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Shipment $shipment
    ) {}
}

