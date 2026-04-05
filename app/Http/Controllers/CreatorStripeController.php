<?php

namespace App\Http\Controllers;

use App\Services\Stripe\ConnectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CreatorStripeController extends Controller
{
    public function connect(Request $request, ConnectService $connect): RedirectResponse
    {
        $user = $request->user();
        $profile = $user->creatorProfile;

        if ($profile === null) {
            return redirect()->route('creator.setup');
        }

        if ($profile->stripe_account_id === null) {
            $accountId = $connect->createExpressAccount($user);
            $profile->update([
                'stripe_account_id' => $accountId,
            ]);
        }

        $url = $connect->createAccountLink(
            $profile->stripe_account_id,
            route('creator.stripe.refresh', absolute: true),
            route('creator.stripe.return', absolute: true),
        );

        return redirect()->away($url);
    }

    public function refresh(Request $request, ConnectService $connect): RedirectResponse
    {
        $profile = $request->user()->creatorProfile;
        if ($profile?->stripe_account_id === null) {
            return redirect()->route('creator.setup');
        }

        $url = $connect->createAccountLink(
            $profile->stripe_account_id,
            route('creator.stripe.refresh', absolute: true),
            route('creator.stripe.return', absolute: true),
        );

        return redirect()->away($url);
    }

    public function return(Request $request, ConnectService $connect): RedirectResponse
    {
        $profile = $request->user()->creatorProfile;
        if ($profile !== null) {
            $connect->syncAccountStatus($profile);
        }

        return redirect()->route('creator.products.index')
            ->with('status', 'stripe-onboarding-updated');
    }
}
