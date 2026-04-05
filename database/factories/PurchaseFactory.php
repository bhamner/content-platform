<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Purchase>
 */
class PurchaseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'product_id' => Product::factory()->published(),
            'order_id' => Order::factory(),
            'revoked_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Purchase $purchase) {
            $purchase->order->update(['user_id' => $purchase->user_id]);
        });
    }
}
