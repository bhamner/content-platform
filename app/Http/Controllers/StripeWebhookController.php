<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessStripeWebhook;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        if (! is_string($secret) || $secret === '') {
            abort(500, 'Webhook secret not configured.');
        }

        try {
            $event = Webhook::constructEvent($payload, $sigHeader ?? '', $secret);
        } catch (SignatureVerificationException|\UnexpectedValueException $e) {
            return response('Invalid payload', 400);
        }

        ProcessStripeWebhook::dispatch($event->toArray());

        return response('OK', 200);
    }
}
