<?php

namespace Database\Factories;

use App\Models\Tag;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        $name = $this->faker->word();
        
        return [
            'tenant_id' => Tenant::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
        ];
    }
}

