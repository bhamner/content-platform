<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Services\Stripe\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function __invoke(Request $request, Product $product, CheckoutService $checkoutService): RedirectResponse
    {
        $this->authorize('purchase', $product);

        $profile = $product->creator?->creatorProfile;
        if ($profile === null || ! $profile->canReceivePayments()) {
            abort(422, 'This product is not available for purchase.');
        }

        $order = DB::transaction(function () use ($request, $product) {
            $order = Order::create([
                'user_id' => $request->user()->id,
                'amount_total' => $product->price,
                'currency' => $product->currency,
                'status' => OrderStatus::Pending,
            ]);

            $order->items()->create([
                'product_id' => $product->id,
                'product_title' => $product->title,
                'unit_price' => $product->price,
                'creator_user_id' => $product->user_id,
            ]);

            return $order;
        });

        $session = $checkoutService->createCheckoutSession(
            $order,
            $product,
            $request->user(),
            route('library.index', absolute: true).'?session_id={CHECKOUT_SESSION_ID}',
            route('storefront.product', [$profile, $product->slug], absolute: true),
        );

        return redirect()->away($session->url);
    }
}
