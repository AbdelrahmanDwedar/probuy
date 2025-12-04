<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'addresses' => [
                [
                    'type' => 'billing',
                    'is_default' => true,
                    'line1' => $this->faker->streetAddress(),
                    'line2' => $this->faker->optional()->secondaryAddress(),
                    'city' => $this->faker->city(),
                    'state' => $this->faker->state(),
                    'zip' => $this->faker->postcode(),
                    'country' => 'US',
                ],
            ],
            'meta' => [],
        ];
    }

    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'email' => null,
            'addresses' => [],
        ]);
    }

    public function withShippingAddress(): static
    {
        return $this->state(function (array $attributes) {
            $addresses = $attributes['addresses'] ?? [];
            $addresses[] = [
                'type' => 'shipping',
                'is_default' => false,
                'line1' => $this->faker->streetAddress(),
                'line2' => $this->faker->optional()->secondaryAddress(),
                'city' => $this->faker->city(),
                'state' => $this->faker->state(),
                'zip' => $this->faker->postcode(),
                'country' => 'US',
            ];
            
            return ['addresses' => $addresses];
        });
    }
}

