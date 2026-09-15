<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

class OrderCreateController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    /**
     * Create a new order with atomic stock validation and deduction.
     */
    public function __invoke(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->createOrder($request->validated());

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(201);
    }
}
