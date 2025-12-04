<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryItemFactory extends Factory
{
    protected $model = InventoryItem::class;

    public function definition(): array
    {
        $stockOnHand = $this->faker->numberBetween(0, 1000);
        
        return [
            'product_variant_id' => ProductVariant::factory(),
            'tenant_id' => fn (array $attributes) => ProductVariant::find($attributes['product_variant_id'])->tenant_id,
            'stock_on_hand' => $stockOnHand,
            'stock_reserved' => $stockOnHand > 0 ? $this->faker->numberBetween(0, min(10, $stockOnHand)) : 0,
            'low_stock_threshold' => 10,
            'warehouse_location' => $this->faker->optional(0.7)->bothify('??-##-##'),
            'meta' => [],
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_on_hand' => 0,
            'stock_reserved' => 0,
        ]);
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_on_hand' => 5,
            'stock_reserved' => 0,
            'low_stock_threshold' => 10,
        ]);
    }
}

