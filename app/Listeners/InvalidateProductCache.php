<?php

namespace App\Listeners;

use App\Events\ProductPublished;
use Illuminate\Support\Facades\Cache;

class InvalidateProductCache
{
    public function handle(ProductPublished $event): void
    {
        $product = $event->product;
        $tenantId = $product->tenant_id;

        // Invalidate single product cache
        Cache::tags(["tenant:$tenantId", "product:{$product->id}"])->flush();

        // Invalidate product lists
        Cache::tags(["tenant:$tenantId", "products"])->flush();

        // Invalidate category caches
        Cache::tags(["tenant:$tenantId", "categories"])->flush();
    }
}

