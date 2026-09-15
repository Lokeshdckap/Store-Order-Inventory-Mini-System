<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerOrderHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = \App\Models\User::factory()->create();
        \Laravel\Sanctum\Sanctum::actingAs($admin);
    }

    public function test_fetch_customer_order_history_by_email(): void
    {
        $customer = Customer::create([
            'name' => 'Alice Johnson',
            'email' => 'alice@example.com',
        ]);

        $product = Product::create([
            'name' => 'Product 1',
            'code' => 'SKU-001',
            'price' => 50.00,
            'tax_percentage' => 10.00,
            'stock' => 10,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-TEST-001',
            'customer_id' => $customer->id,
            'subtotal' => 50.00,
            'tax_total' => 5.00,
            'grand_total' => 55.00,
            'status' => 'completed',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 50.00,
            'tax_percentage' => 10.00,
            'subtotal' => 50.00,
            'tax_amount' => 5.00,
            'total' => 55.00,
        ]);

        $response = $this->getJson('/api/customers/' . urlencode('alice@example.com') . '/orders');

        $response->assertStatus(200)
            ->assertJsonPath('customer.email', 'alice@example.com')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.order_number', 'ORD-TEST-001')
            ->assertJsonCount(1, 'data.0.items');

        $this->assertEquals(55.00, (float) $response->json('data.0.grand_total'));
    }

    public function test_fetch_order_history_for_unknown_customer_returns_empty_list(): void
    {
        $response = $this->getJson('/api/customers/unknown@example.com/orders');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total_orders', 0)
            ->assertJsonCount(0, 'data');
    }
}
