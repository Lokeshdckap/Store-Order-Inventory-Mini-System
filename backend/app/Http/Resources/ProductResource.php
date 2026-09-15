<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $threshold = (int) ($request->query('threshold', config('inventory.low_stock_threshold', 5)));

        return [
            'uuid'            => $this->uuid,
            'name'            => $this->name,
            'code'            => $this->code,
            'price'           => (float) $this->price,
            'formatted_price' => '$' . number_format((float) $this->price, 2),
            'tax_percentage'  => (float) $this->tax_percentage,
            'stock'           => (int) $this->stock,
            'is_low_stock'    => (int) $this->stock <= $threshold,
            'created_at'      => $this->created_at?->toIso8601String(),
            'updated_at'      => $this->updated_at?->toIso8601String(),
        ];
    }
}
