<?php

namespace Tests\Feature;

use App\Jobs\SendOrderConfirmationEmail;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = \App\Models\User::factory()->create();
        \Laravel\Sanctum\Sanctum::actingAs($admin);
    }

    public function test_can_successfully_create_order_with_multiple_products(): void
    {
        Queue::fake();

        $prodA = Product::create([
            'name'           => 'Mechanical Keyboard',
            'code'           => 'SKU-KB01',
            'price'          => 100.00,
            'tax_percentage' => 10.00,
            'stock'          => 10,
        ]);

        $prodB = Product::create([
            'name'           => 'USB-C Cable',
            'code'           => 'SKU-CB02',
            'price'          => 20.00,
            'tax_percentage' => 5.00,
            'stock'          => 15,
        ]);

        $payload = [
            'customer_name'  => 'Jane Doe',
            'customer_email' => 'jane@example.com',
            'items' => [
                ['product_uuid' => $prodA->uuid, 'quantity' => 2], // Subtotal: 200, Tax: 20, Total: 220
                ['product_uuid' => $prodB->uuid, 'quantity' => 3], // Subtotal: 60,  Tax: 3,  Total: 63
            ],
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.customer.email', 'jane@example.com')
            ->assertJsonCount(2, 'data.items');

        $this->assertEquals(260.00, (float) $response->json('data.subtotal'));
        $this->assertEquals(23.00,  (float) $response->json('data.tax_total'));
        $this->assertEquals(283.00, (float) $response->json('data.grand_total'));

        // Verify stock deducted
        $this->assertEquals(8,  $prodA->fresh()->stock);
        $this->assertEquals(12, $prodB->fresh()->stock);

        // Verify UUIDs present in response
        $this->assertNotNull($response->json('data.uuid'));
        $this->assertNotNull($response->json('data.customer.uuid'));

        // Verify customer created
        $this->assertDatabaseHas('customers', [
            'email' => 'jane@example.com',
            'name'  => 'Jane Doe',
        ]);

        // Verify order saved
        $this->assertDatabaseHas('orders', [
            'subtotal'    => 260.00,
            'tax_total'   => 23.00,
            'grand_total' => 283.00,
            'status'      => 'completed',
        ]);

        // Verify job dispatched
        Queue::assertPushed(SendOrderConfirmationEmail::class);
    }

    public function test_order_creation_fails_when_stock_is_insufficient(): void
    {
        Queue::fake();

        $product = Product::create([
            'name'           => 'Limited Edition Mouse',
            'code'           => 'SKU-LM01',
            'price'          => 50.00,
            'tax_percentage' => 10.00,
            'stock'          => 2,
        ]);

        $payload = [
            'customer_name'  => 'John Overbuyer',
            'customer_email' => 'john@example.com',
            'items' => [
                ['product_uuid' => $product->uuid, 'quantity' => 3], // 3 requested, only 2 available
            ],
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('product_code',         $product->code)
            ->assertJsonPath('available_stock',      2)
            ->assertJsonPath('requested_quantity',   3);

        // Verify stock untouched
        $this->assertEquals(2, $product->fresh()->stock);

        // Verify no order created
        $this->assertDatabaseCount('orders',      0);
        $this->assertDatabaseCount('order_items', 0);

        // Verify job not dispatched
        Queue::assertNotPushed(SendOrderConfirmationEmail::class);
    }

    public function test_reusing_existing_customer_by_email(): void
    {
        $existingCustomer = Customer::create([
            'name'  => 'Original Name',
            'email' => 'repeat@example.com',
        ]);

        $product = Product::create([
            'name'           => 'Desk Mat',
            'code'           => 'SKU-DM01',
            'price'          => 15.00,
            'tax_percentage' => 0.00,
            'stock'          => 10,
        ]);

        $response = $this->postJson('/api/orders', [
            'customer_name'  => 'Repeat Customer',
            'customer_email' => 'repeat@example.com',
            'items' => [
                ['product_uuid' => $product->uuid, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.customer.uuid', $existingCustomer->uuid);

        $this->assertDatabaseCount('customers', 1);
    }

    public function test_can_fetch_order_by_uuid(): void
    {
        $customer = Customer::create([
            'name'  => 'Show Customer',
            'email' => 'show@example.com',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-SHOW-001',
            'customer_id'  => $customer->id,
            'subtotal'     => 100.00,
            'tax_total'    => 10.00,
            'grand_total'  => 110.00,
            'status'       => 'completed',
        ]);

        $response = $this->getJson('/api/orders/' . $order->uuid);

        $response->assertStatus(200)
            ->assertJsonPath('data.uuid', $order->uuid)
            ->assertJsonPath('data.order_number', 'ORD-SHOW-001')
            ->assertJsonPath('data.customer.uuid', $customer->uuid);
    }

    public function test_can_update_product_stock_by_uuid(): void
    {
        $product = Product::create([
            'name'           => 'Stock Item',
            'code'           => 'SKU-STK-01',
            'price'          => 25.00,
            'tax_percentage' => 5.00,
            'stock'          => 10,
        ]);

        $response = $this->patchJson('/api/products/' . $product->uuid . '/stock', [
            'stock' => 45,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('product.uuid', $product->uuid)
            ->assertJsonPath('product.stock', 45);

        $this->assertEquals(45, $product->fresh()->stock);
    }

    public function test_validation_errors_for_invalid_order_payload(): void
    {
        $response = $this->postJson('/api/orders', [
            'customer_name'  => '',
            'customer_email' => 'invalid-not-an-email',
            'items'          => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_name', 'customer_email', 'items']);
    }

    public function test_validation_normalizes_whitespace_and_casing_in_email(): void
    {
        $product = Product::create([
            'name'           => 'Cable Adapter',
            'code'           => 'SKU-CA01',
            'price'          => 15.00,
            'tax_percentage' => 5.00,
            'stock'          => 10,
        ]);

        $response = $this->postJson('/api/orders', [
            'customer_name'  => '  Whitespace User  ',
            'customer_email' => '  Whitespace.User@Example.Com  ',
            'items'          => [
                ['product_uuid' => $product->uuid, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.customer.email', 'whitespace.user@example.com')
            ->assertJsonPath('data.customer.name', 'Whitespace User');
    }
}

