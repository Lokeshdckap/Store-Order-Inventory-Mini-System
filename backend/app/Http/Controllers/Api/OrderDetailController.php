<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;

class OrderDetailController extends Controller
{
    /**
     * Display the specified order with customer and line items.
     */
    public function __invoke(Order $order): OrderResource
    {
        return new OrderResource($order->load(['customer', 'items.product']));
    }
}
