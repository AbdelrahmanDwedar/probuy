<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Tag;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $tags = Tag::factory(10)->create();

        User::factory(50)->create()->each(fn ($user) => (
            Product::factory(rand(1, 6))->create([
                'user_id' => $user->id,
            ])->each(fn($product) => (
                $product->tags()->attach($tags->random(2))
            ))
        )
        );
    }
}
