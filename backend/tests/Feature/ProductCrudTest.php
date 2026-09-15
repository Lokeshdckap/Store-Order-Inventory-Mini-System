<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = User::factory()->create();
        Sanctum::actingAs($admin);
    }

    public function test_admin_can_create_new_product(): void
    {
        $payload = [
            'name'           => 'Wireless Mouse Pro',
            'code'           => 'sku-wmp-01',
            'price'          => 49.99,
            'tax_percentage' => 18.00,
            'stock'          => 20,
        ];

        $response = $this->postJson('/api/products', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Wireless Mouse Pro')
            ->assertJsonPath('data.code', 'SKU-WMP-01') // code auto-uppercased
            ->assertJsonPath('data.stock', 20);

        $this->assertNotNull($response->json('data.uuid'));

        $this->assertDatabaseHas('products', [
            'code'  => 'SKU-WMP-01',
            'name'  => 'Wireless Mouse Pro',
            'stock' => 20,
        ]);
    }

    public function test_product_creation_validation_errors(): void
    {
        Product::create([
            'name'           => 'Existing Item',
            'code'           => 'SKU-EXIST-01',
            'price'          => 10.00,
            'tax_percentage' => 5.00,
            'stock'          => 5,
        ]);

        $response = $this->postJson('/api/products', [
            'name'           => '',
            'code'           => 'SKU-EXIST-01', // duplicate
            'price'          => -5,              // negative price
            'tax_percentage' => 150,            // exceeds 100
            'stock'          => -1,              // negative stock
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'code', 'price', 'tax_percentage', 'stock']);
    }

    public function test_admin_can_update_product_by_uuid(): void
    {
        $product = Product::create([
            'name'           => 'Original Keyboard',
            'code'           => 'SKU-KB-ORIG',
            'price'          => 50.00,
            'tax_percentage' => 10.00,
            'stock'          => 15,
        ]);

        $response = $this->putJson('/api/products/' . $product->uuid, [
            'name'           => 'Updated Keyboard V2',
            'price'          => 65.50,
            'tax_percentage' => 12.00,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.uuid', $product->uuid)
            ->assertJsonPath('data.name', 'Updated Keyboard V2')
            ->assertJsonPath('data.price', 65.50);

        $this->assertEquals('Updated Keyboard V2', $product->fresh()->name);
        $this->assertEquals(65.50, (float) $product->fresh()->price);
    }

    public function test_admin_can_delete_unreferenced_product_by_uuid(): void
    {
        $product = Product::create([
            'name'           => 'Temporary Item',
            'code'           => 'SKU-TEMP-01',
            'price'          => 19.99,
            'tax_percentage' => 5.00,
            'stock'          => 10,
        ]);

        $response = $this->deleteJson('/api/products/' . $product->uuid);

        $response->assertStatus(200)
            ->assertJsonPath('message', "Product 'Temporary Item' (Code: SKU-TEMP-01) was deleted successfully.");

        $this->assertDatabaseMissing('products', [
            'code' => 'SKU-TEMP-01',
        ]);
    }

    public function test_cannot_delete_product_referenced_by_orders(): void
    {
        $customer = Customer::create([
            'name'  => 'Test Customer',
            'email' => 'customer@example.com',
        ]);

        $product = Product::create([
            'name'           => 'Ordered Product',
            'code'           => 'SKU-ORD-01',
            'price'          => 30.00,
            'tax_percentage' => 5.00,
            'stock'          => 10,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-DEL-TEST-01',
            'customer_id'  => $customer->id,
            'subtotal'     => 30.00,
            'tax_total'    => 1.50,
            'grand_total'  => 31.50,
            'status'       => 'completed',
        ]);

        OrderItem::create([
            'order_id'       => $order->id,
            'product_id'     => $product->id,
            'quantity'       => 1,
            'unit_price'     => 30.00,
            'tax_percentage' => 5.00,
            'subtotal'       => 30.00,
            'tax_amount'     => 1.50,
            'total'          => 31.50,
        ]);

        // Attempt deletion
        $response = $this->deleteJson('/api/products/' . $product->uuid);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product']);

        // Assert product is preserved
        $this->assertDatabaseHas('products', [
            'code' => 'SKU-ORD-01',
        ]);
    }
}
