<?php

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Purchase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessStripeWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $event
     */
    public function __construct(
        public array $event,
    ) {}

    public function handle(): void
    {
        match ($this->event['type'] ?? null) {
            'checkout.session.completed' => $this->handleCheckoutSessionCompleted(),
            'charge.refunded' => $this->handleChargeRefunded(),
            default => null,
        };
    }

    private function handleCheckoutSessionCompleted(): void
    {
        $session = $this->event['data']['object'] ?? [];
        if (! is_array($session)) {
            return;
        }

        if (($session['payment_status'] ?? '') !== 'paid') {
            return;
        }

        $orderId = $session['metadata']['order_id'] ?? $session['client_reference_id'] ?? null;
        if ($orderId === null || $orderId === '') {
            return;
        }

        $order = Order::query()->find((int) $orderId);
        if ($order === null) {
            return;
        }

        if ($order->status === OrderStatus::Paid) {
            return;
        }

        $paymentIntent = $session['payment_intent'] ?? null;
        $paymentIntentId = is_string($paymentIntent)
            ? $paymentIntent
            : (is_array($paymentIntent) ? ($paymentIntent['id'] ?? null) : null);

        DB::transaction(function () use ($order, $session, $paymentIntentId) {
            $order->update([
                'status' => OrderStatus::Paid,
                'stripe_payment_intent_id' => $paymentIntentId,
                'amount_total' => (int) ($session['amount_total'] ?? $order->amount_total),
                'currency' => strtolower((string) ($session['currency'] ?? $order->currency)),
            ]);

            foreach ($order->items as $item) {
                if ($item->product_id === null) {
                    continue;
                }

                Purchase::query()->firstOrCreate(
                    [
                        'user_id' => $order->user_id,
                        'order_id' => $order->id,
                        'product_id' => $item->product_id,
                    ],
                    ['revoked_at' => null],
                );
            }
        });
    }

    private function handleChargeRefunded(): void
    {
        $charge = $this->event['data']['object'] ?? [];
        if (! is_array($charge)) {
            return;
        }

        $paymentIntentId = $charge['payment_intent'] ?? null;
        if (! is_string($paymentIntentId)) {
            return;
        }

        $order = Order::query()
            ->where('stripe_payment_intent_id', $paymentIntentId)
            ->first();

        if ($order === null) {
            return;
        }

        DB::transaction(function () use ($order) {
            $order->update(['status' => OrderStatus::Refunded]);

            Purchase::query()
                ->where('order_id', $order->id)
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);
        });
    }
}
