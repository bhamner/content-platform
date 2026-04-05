<?php

namespace App\Services\Stripe;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Stripe\Checkout\Session;

class CheckoutService
{
    public function __construct(
        private StripeClientFactory $clientFactory,
    ) {}

    public function createCheckoutSession(Order $order, Product $product, User $buyer, string $successUrl, string $cancelUrl): Session
    {
        $stripe = $this->clientFactory->make();

        $profile = $product->creator?->creatorProfile;
        if ($profile === null || $profile->stripe_account_id === null) {
            throw new \InvalidArgumentException('Creator is not ready to receive payments.');
        }

        $feePercent = config('marketplace.application_fee_percent', 10);
        $feeAmount = (int) floor($product->price * $feePercent / 100);
        if ($product->price > 0 && $feeAmount >= $product->price) {
            $feeAmount = max(0, $product->price - 1);
        }

        $session = $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'customer_email' => $buyer->email,
            'client_reference_id' => (string) $order->id,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'order_id' => (string) $order->id,
            ],
            'payment_intent_data' => [
                'application_fee_amount' => $feeAmount,
                'transfer_data' => [
                    'destination' => $profile->stripe_account_id,
                ],
                'metadata' => [
                    'order_id' => (string) $order->id,
                ],
            ],
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => strtolower($product->currency),
                        'product_data' => [
                            'name' => $product->title,
                        ],
                        'unit_amount' => $product->price,
                    ],
                    'quantity' => 1,
                ],
            ],
        ]);

        $order->update([
            'stripe_checkout_session_id' => $session->id,
            'application_fee_amount' => $feeAmount,
        ]);

        return $session;
    }
}
