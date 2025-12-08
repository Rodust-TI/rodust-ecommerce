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

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sku' => 'SKU-' . fake()->unique()->numerify('######'),
            'name' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 10, 1000),
            'promotional_price' => null,
            'stock' => fake()->numberBetween(0, 100),
            'active' => true,
            'width' => fake()->randomFloat(2, 10, 50),
            'height' => fake()->randomFloat(2, 10, 50),
            'length' => fake()->randomFloat(2, 10, 50),
            'weight' => fake()->randomFloat(3, 0.1, 10),
            'brand' => fake()->company(),
            'free_shipping' => false,
        ];
    }

    /**
     * Indicate that the product is on sale.
     */
    public function onSale(): static
    {
        return $this->state(function (array $attributes) {
            $price = $attributes['price'] ?? 100;
            return [
                'promotional_price' => $price * 0.8, // 20% off
            ];
        });
    }

    /**
     * Indicate that the product has free shipping.
     */
    public function freeShipping(): static
    {
        return $this->state(fn (array $attributes) => [
            'free_shipping' => true,
        ]);
    }

    /**
     * Indicate that the product is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }
}
