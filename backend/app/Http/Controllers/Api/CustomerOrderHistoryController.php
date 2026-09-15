<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;

class CustomerOrderHistoryController extends Controller
{
    /**
     * Fetch customer order history by email.
     * All identifiers returned are UUIDs.
     */
    public function __invoke(string $email): JsonResponse
    {
        $decodedEmail = urldecode($email);
        $customer = Customer::where('email', strtolower(trim($decodedEmail)))->first();

        if (! $customer) {
            return response()->json([
                'data'     => [],
                'customer' => null,
                'meta'     => [
                    'email'        => $decodedEmail,
                    'total_orders' => 0,
                ],
            ]);
        }

        $orders = $customer->orders()
            ->with(['items.product'])
            ->get();

        return response()->json([
            'data' => OrderResource::collection($orders),
            'customer' => [
                'uuid'  => $customer->uuid,
                'name'  => $customer->name,
                'email' => $customer->email,
            ],
            'meta' => [
                'email'        => $customer->email,
                'total_orders' => $orders->count(),
            ],
        ]);
    }
}
