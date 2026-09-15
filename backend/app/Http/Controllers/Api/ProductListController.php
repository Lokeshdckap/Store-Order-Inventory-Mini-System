<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductListController extends Controller
{
    /**
     * List all products in catalog with optional search filtering.
     */
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $query = Product::query();

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('name', 'asc')->get();

        return ProductResource::collection($products);
    }
}
