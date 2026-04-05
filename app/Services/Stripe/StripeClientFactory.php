<?php

namespace App\Services\Stripe;

use Stripe\StripeClient;

class StripeClientFactory
{
    public function make(): StripeClient
    {
        $secret = config('services.stripe.secret');

        if (! is_string($secret) || $secret === '') {
            throw new \RuntimeException('Stripe secret is not configured.');
        }

        return new StripeClient($secret);
    }
}
