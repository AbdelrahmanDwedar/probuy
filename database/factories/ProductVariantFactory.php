<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'tenant_id' => fn (array $attributes) => Product::find($attributes['product_id'])->tenant_id,
            'sku' => 'VAR-' . strtoupper($this->faker->unique()->bothify('???-###')),
            'price_cents' => $this->faker->numberBetween(1000, 50000),
            'compare_at_price_cents' => $this->faker->optional(0.3)->numberBetween(5000, 75000),
            'currency' => 'USD',
            'cost_cents' => $this->faker->numberBetween(500, 25000),
            'attributes' => [
                'size' => $this->faker->randomElement(['S', 'M', 'L', 'XL']),
                'color' => $this->faker->safeColorName(),
            ],
            'weight_grams' => $this->faker->numberBetween(100, 5000),
            'dimensions' => [
                'length' => $this->faker->numberBetween(10, 100),
                'width' => $this->faker->numberBetween(10, 100),
                'height' => $this->faker->numberBetween(5, 50),
            ],
        ];
    }

    public function onSale(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'compare_at_price_cents' => $attributes['price_cents'] * 1.5,
            ];
        });
    }
}

