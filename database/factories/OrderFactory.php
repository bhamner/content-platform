<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'stripe_checkout_session_id' => null,
            'stripe_payment_intent_id' => null,
            'amount_total' => 999,
            'currency' => 'usd',
            'application_fee_amount' => null,
            'status' => OrderStatus::Pending,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Paid,
            'stripe_checkout_session_id' => 'cs_test_'.bin2hex(random_bytes(12)),
        ]);
    }
}
