<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->words(3, true);

        return [
            'user_id' => User::factory(),
            'title' => ucfirst($title),
            'slug' => Str::slug($title).'-'.Str::random(4),
            'description' => fake()->paragraph(),
            'status' => ProductStatus::Draft,
            'price' => fake()->numberBetween(299, 4999),
            'currency' => 'usd',
            'stripe_price_id' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::Published,
        ]);
    }
}
