<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductDeleteController extends Controller
{
    /**
     * Delete specified product if not referenced by existing orders.
     */
    public function __invoke(Product $product): JsonResponse
    {
        // Safety check: Prevent deletion if product has historical order items
        $orderCount = $product->orderItems()->count();

        if ($orderCount > 0) {
            return response()->json([
                'message' => "Cannot delete product '{$product->name}' (Code: {$product->code}) because it has {$orderCount} associated order record(s). Consider setting stock to 0 instead.",
                'errors'  => [
                    'product' => ["Product is referenced in existing customer orders."],
                ],
            ], 422);
        }

        $productName = $product->name;
        $productCode = $product->code;
        $product->delete();

        return response()->json([
            'message' => "Product '{$productName}' (Code: {$productCode}) was deleted successfully.",
        ], 200);
    }
}
