<?php

namespace App\Services\Stripe;

use App\Models\CreatorProfile;
use App\Models\User;

class ConnectService
{
    public function __construct(
        private StripeClientFactory $clientFactory,
    ) {}

    public function createExpressAccount(User $user): string
    {
        $stripe = $this->clientFactory->make();

        $account = $stripe->accounts->create([
            'type' => 'express',
            'country' => config('marketplace.stripe_connect_country', 'US'),
            'email' => $user->email,
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers' => ['requested' => true],
            ],
        ]);

        return $account->id;
    }

    public function createAccountLink(string $accountId, string $refreshUrl, string $returnUrl): string
    {
        $stripe = $this->clientFactory->make();

        $link = $stripe->accountLinks->create([
            'account' => $accountId,
            'refresh_url' => $refreshUrl,
            'return_url' => $returnUrl,
            'type' => 'account_onboarding',
        ]);

        return $link->url;
    }

    public function syncAccountStatus(CreatorProfile $profile): void
    {
        if ($profile->stripe_account_id === null) {
            return;
        }

        $stripe = $this->clientFactory->make();
        $account = $stripe->accounts->retrieve($profile->stripe_account_id);

        $profile->update([
            'charges_enabled' => (bool) $account->charges_enabled,
            'payouts_enabled' => (bool) $account->payouts_enabled,
            'details_submitted' => (bool) $account->details_submitted,
        ]);
    }
}
