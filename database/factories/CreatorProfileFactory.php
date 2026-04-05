<?php

namespace Database\Factories;

use App\Models\CreatorProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CreatorProfile>
 */
class CreatorProfileFactory extends Factory
{
    public function definition(): array
    {
        $display = fake()->unique()->userName();

        return [
            'user_id' => User::factory(),
            'slug' => Str::slug($display).'-'.Str::random(6),
            'display_name' => $display,
            'bio' => fake()->optional()->paragraph(),
            'stripe_account_id' => null,
            'charges_enabled' => false,
            'payouts_enabled' => false,
            'details_submitted' => false,
        ];
    }

    public function stripeReady(): static
    {
        return $this->state(fn (array $attributes) => [
            'stripe_account_id' => 'acct_'.Str::random(16),
            'charges_enabled' => true,
            'payouts_enabled' => true,
            'details_submitted' => true,
        ]);
    }
}
