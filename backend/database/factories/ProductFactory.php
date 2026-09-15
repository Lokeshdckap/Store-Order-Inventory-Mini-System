<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'code' => 'SKU-' . strtoupper(fake()->unique()->bothify('??###')),
            'price' => fake()->randomFloat(2, 5, 200),
            'tax_percentage' => fake()->randomElement([5.00, 12.00, 18.00]),
            'stock' => fake()->numberBetween(10, 100),
        ];
    }

    public function lowStock(int $stock = 3): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => $stock,
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 0,
        ]);
    }
}
