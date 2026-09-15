<?php

namespace App\Exceptions;

use App\Models\Product;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InsufficientStockException extends Exception
{
    public function __construct(
        public readonly Product $product,
        public readonly int $requestedQuantity,
        public readonly int $availableStock
    ) {
        parent::__construct(
            "Insufficient stock for product '{$product->name}' (Code: {$product->code}). Requested: {$requestedQuantity}, Available: {$availableStock}."
        );
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'errors' => [
                'stock' => [$this->getMessage()],
            ],
            'product_uuid'       => $this->product->uuid,
            'product_code'       => $this->product->code,
            'requested_quantity' => $this->requestedQuantity,
            'available_stock'    => $this->availableStock,
        ], 422);
    }
}
