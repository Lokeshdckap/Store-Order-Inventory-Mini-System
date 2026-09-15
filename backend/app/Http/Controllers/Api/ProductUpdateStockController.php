<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductUpdateStockController extends Controller
{
    /**
     * Update product stock level.
     */
    public function __invoke(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'stock' => ['required', 'integer', 'min:0'],
        ]);

        $product->update(['stock' => $validated['stock']]);

        return response()->json([
            'message' => 'Stock updated successfully',
            'product' => new ProductResource($product->fresh()),
        ], 200);
    }
}
