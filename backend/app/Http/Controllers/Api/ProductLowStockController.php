<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductLowStockController extends Controller
{
    /**
     * List products below the configurable low-stock threshold.
     */
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $threshold = (int) $request->query(
            'threshold',
            config('inventory.low_stock_threshold', 5)
        );

        $products = Product::lowStock($threshold)
            ->orderBy('stock', 'asc')
            ->get();

        return ProductResource::collection($products)->additional([
            'meta' => [
                'threshold' => $threshold,
                'total_low_stock_items' => $products->count(),
            ],
        ]);
    }
}
