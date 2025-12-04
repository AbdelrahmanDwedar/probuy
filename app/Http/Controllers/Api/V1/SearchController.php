<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        private SearchService $searchService
    ) {}

    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([
                'error' => [
                    'code' => 'QUERY_TOO_SHORT',
                    'message' => 'Search query must be at least 2 characters',
                ],
            ], 400);
        }

        $filters = [
            'category' => $request->input('category'),
            'tags' => $request->input('tags'),
            'min_price' => $request->input('min_price'),
            'max_price' => $request->input('max_price'),
            'min_rating' => $request->input('min_rating'),
            'in_stock' => $request->boolean('in_stock'),
        ];

        $results = $this->searchService
            ->searchProducts($query, $filters)
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $results->items(),
            'meta' => [
                'query' => $query,
                'current_page' => $results->currentPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'filters_applied' => array_filter($filters),
            ],
        ]);
    }
}

