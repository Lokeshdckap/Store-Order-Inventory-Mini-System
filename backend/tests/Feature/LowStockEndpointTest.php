<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LowStockEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = \App\Models\User::factory()->create();
        \Laravel\Sanctum\Sanctum::actingAs($admin);
    }

    public function test_returns_products_below_default_low_stock_threshold(): void
    {
        // Default threshold is 5 (from config/inventory.php)
        Product::create(['name' => 'Zero Stock', 'code' => 'P-0', 'price' => 10, 'tax_percentage' => 0, 'stock' => 0]);
        Product::create(['name' => 'Low Stock 3', 'code' => 'P-3', 'price' => 10, 'tax_percentage' => 0, 'stock' => 3]);
        Product::create(['name' => 'Low Stock 5', 'code' => 'P-5', 'price' => 10, 'tax_percentage' => 0, 'stock' => 5]);
        Product::create(['name' => 'Ample Stock 6', 'code' => 'P-6', 'price' => 10, 'tax_percentage' => 0, 'stock' => 6]);
        Product::create(['name' => 'High Stock 20', 'code' => 'P-20', 'price' => 10, 'tax_percentage' => 0, 'stock' => 20]);

        $response = $this->getJson('/api/products/low-stock');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.threshold', 5)
            ->assertJsonPath('meta.total_low_stock_items', 3);

        $codes = collect($response->json('data'))->pluck('code')->all();
        $this->assertContains('P-0', $codes);
        $this->assertContains('P-3', $codes);
        $this->assertContains('P-5', $codes);
        $this->assertNotContains('P-6', $codes);
        $this->assertNotContains('P-20', $codes);
    }

    public function test_supports_custom_threshold_query_parameter(): void
    {
        Product::create(['name' => 'Stock 2', 'code' => 'P-2', 'price' => 10, 'tax_percentage' => 0, 'stock' => 2]);
        Product::create(['name' => 'Stock 4', 'code' => 'P-4', 'price' => 10, 'tax_percentage' => 0, 'stock' => 4]);

        $response = $this->getJson('/api/products/low-stock?threshold=2');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.threshold', 2)
            ->assertJsonPath('data.0.code', 'P-2');
    }
}
