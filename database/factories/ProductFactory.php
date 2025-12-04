<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'sku' => 'PROD-' . strtoupper($this->faker->unique()->bothify('???-###')),
            'title' => $this->faker->words(3, true),
            'description' => $this->faker->paragraphs(3, true),
            'status' => 'draft',
            'metadata' => [
                'brand' => $this->faker->company(),
                'material' => $this->faker->optional()->word(),
            ],
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'archived',
        ]);
    }
}

