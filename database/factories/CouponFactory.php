<?php

namespace Database\Factories;

use App\Models\Coupon;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['percentage', 'fixed', 'free_shipping']);
        
        return [
            'tenant_id' => Tenant::factory(),
            'code' => strtoupper($this->faker->unique()->bothify('????##')),
            'type' => $type,
            'value' => $type === 'percentage' ? $this->faker->numberBetween(500, 5000) : $this->faker->numberBetween(100, 5000),
            'currency' => 'USD',
            'usage_limit' => $this->faker->optional(0.5)->numberBetween(10, 100),
            'usage_count' => 0,
            'minimum_purchase_cents' => $this->faker->optional(0.7)->numberBetween(1000, 10000),
            'conditions' => [],
            'starts_at' => now()->subDays(7),
            'ends_at' => now()->addDays(30),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'ends_at' => now()->subDays(1),
        ]);
    }

    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'usage_limit' => null,
        ]);
    }
}

