<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConcurrencyStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_exactly_one_order_succeeds_when_one_unit_of_stock_is_left(): void
    {
        $service = app(OrderService::class);

        $product = Product::create([
            'name' => 'Single Unit GPU',
            'code' => 'SKU-GPU-01',
            'price' => 599.99,
            'tax_percentage' => 18.00,
            'stock' => 1,
        ]);

        $payload1 = [
            'customer_name' => 'Buyer One',
            'customer_email' => 'buyer1@example.com',
            'items' => [
                ['product_uuid' => $product->uuid, 'quantity' => 1],
            ],
        ];

        $payload2 = [
            'customer_name' => 'Buyer Two',
            'customer_email' => 'buyer2@example.com',
            'items' => [
                ['product_uuid' => $product->uuid, 'quantity' => 1],
            ],
        ];

        // First order should succeed
        $order1 = $service->createOrder($payload1);
        $this->assertNotNull($order1->uuid);
        $this->assertEquals(0, $product->fresh()->stock);

        // Second order for the same product must fail cleanly with InsufficientStockException
        $secondFailed = false;
        try {
            $service->createOrder($payload2);
        } catch (InsufficientStockException $e) {
            $secondFailed = true;
            $this->assertEquals(1, $e->requestedQuantity);
            $this->assertEquals(0, $e->availableStock);
        }

        $this->assertTrue($secondFailed, 'Second order should have failed due to zero stock.');
        $this->assertEquals(0, $product->fresh()->stock, 'Stock must never drop below 0.');
    }
}
