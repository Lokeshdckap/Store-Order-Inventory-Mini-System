<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductCreateController extends Controller
{
    /**
     * Create a new product in the catalog.
     */
    public function __invoke(StoreProductRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $product = Product::create($validated);

        return response()->json([
            'message' => 'Product created successfully',
            'data'    => new ProductResource($product),
        ], 201);
    }
}
