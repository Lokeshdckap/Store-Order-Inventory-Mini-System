<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'               => $this->uuid,
            'order_number'       => $this->order_number,
            'status'             => $this->status,
            'subtotal'           => (float) $this->subtotal,
            'tax_total'          => (float) $this->tax_total,
            'grand_total'        => (float) $this->grand_total,
            'customer'           => [
                'uuid'  => $this->customer?->uuid,
                'name'  => $this->customer?->name,
                'email' => $this->customer?->email,
            ],
            'items'              => OrderItemResource::collection($this->whenLoaded('items')),
            'item_count'         => $this->relationLoaded('items') ? $this->items->sum('quantity') : null,
            'created_at'         => $this->created_at?->toIso8601String(),
            'formatted_created_at' => $this->created_at?->format('M d, Y h:i A'),
        ];
    }
}
