<?php

namespace App\Events;

use App\Models\Product;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductPublished
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Product $product
    ) {}
}

