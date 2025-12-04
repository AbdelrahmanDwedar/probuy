<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = $this->faker->numberBetween(1000, 50000);
        $tax = (int) ($subtotal * 0.08);
        $shipping = $this->faker->numberBetween(500, 2000);
        
        return [
            'tenant_id' => Tenant::factory(),
            'order_number' => 'ORD-' . strtoupper(Str::random(8)),
            'customer_id' => Customer::factory(),
            'currency' => 'USD',
            'subtotal_cents' => $subtotal,
            'tax_cents' => $tax,
            'shipping_cents' => $shipping,
            'discount_cents' => 0,
            'total_cents' => $subtotal + $tax + $shipping,
            'status' => 'pending',
            'billing_address' => [
                'line1' => $this->faker->streetAddress(),
                'city' => $this->faker->city(),
                'state' => $this->faker->state(),
                'zip' => $this->faker->postcode(),
                'country' => 'US',
            ],
            'shipping_address' => [
                'line1' => $this->faker->streetAddress(),
                'city' => $this->faker->city(),
                'state' => $this->faker->state(),
                'zip' => $this->faker->postcode(),
                'country' => 'US',
            ],
            'customer_note' => $this->faker->optional(0.2)->sentence(),
            'metadata' => [],
            'placed_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'placed_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'placed_at' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}

