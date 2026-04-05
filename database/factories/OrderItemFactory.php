<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory()->published(),
            'product_title' => 'Digital product',
            'unit_price' => 1000,
            'creator_user_id' => User::factory(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (OrderItem $item) {
            $product = $item->product;
            if ($product) {
                $item->update([
                    'product_title' => $product->title,
                    'unit_price' => $product->price,
                    'creator_user_id' => $product->user_id,
                ]);
            }
        });
    }

    public function forProduct(Product $product): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product->id,
            'product_title' => $product->title,
            'unit_price' => $product->price,
            'creator_user_id' => $product->user_id,
        ]);
    }
}
