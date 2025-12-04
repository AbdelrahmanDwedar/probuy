<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

class SearchService
{
    public function searchProducts(string $query, array $filters = []): Builder
    {
        $search = Product::query()
            ->with(['variants', 'categories', 'tags'])
            ->where(function (Builder $q) use ($query) {
                $q->where('title', 'ILIKE', "%{$query}%")
                    ->orWhere('description', 'ILIKE', "%{$query}%")
                    ->orWhere('sku', 'ILIKE', "%{$query}%");
            });

        // Apply filters
        if (isset($filters['category'])) {
            $search->whereHas('categories', fn($q) => $q->where('slug', $filters['category']));
        }

        if (isset($filters['tags']) && is_array($filters['tags'])) {
            $search->whereHas('tags', fn($q) => $q->whereIn('slug', $filters['tags']));
        }

        if (isset($filters['min_price'])) {
            $search->whereHas('variants', fn($q) => $q->where('price_cents', '>=', $filters['min_price'] * 100));
        }

        if (isset($filters['max_price'])) {
            $search->whereHas('variants', fn($q) => $q->where('price_cents', '<=', $filters['max_price'] * 100));
        }

        if (isset($filters['min_rating'])) {
            $search->where('average_rating', '>=', $filters['min_rating']);
        }

        if (isset($filters['in_stock'])) {
            $search->whereHas('variants.inventoryItem', fn($q) => $q->whereRaw('(stock_on_hand - stock_reserved) > 0'));
        }

        return $search;
    }
}
