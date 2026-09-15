<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'           => $this->uuid,
            'product_uuid'   => $this->product?->uuid,
            'product_name'   => $this->product?->name ?? 'Deleted Product',
            'product_code'   => $this->product?->code ?? 'N/A',
            'quantity'       => (int) $this->quantity,
            'unit_price'     => (float) $this->unit_price,
            'tax_percentage' => (float) $this->tax_percentage,
            'subtotal'       => (float) $this->subtotal,
            'tax_amount'     => (float) $this->tax_amount,
            'total'          => (float) $this->total,
        ];
    }
}
