<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->sentence(rand(4, 7));

        $description = '';
        for ($i = 0; $i < rand(2, 4); $i++) {
            $description .= $this->faker->sentences($i, asText: true);
        }

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.rand(1111, 9999),
            'price' => $this->faker->numberBetween(5, 122),
            'image' => basename($this->faker->image(storage_path('app/public'))),
            'in_stock' => (rand(1, 9) > 4),
            // 'description' => $description,
        ];
    }
}
