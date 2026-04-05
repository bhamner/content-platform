<?php

use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Jobs\ProcessStripeWebhook;
use App\Models\CreatorProfile;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductFile;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

test('storefront home lists published products', function () {
    $creator = User::factory()->create();
    CreatorProfile::factory()->for($creator)->create();
    Product::factory()->for($creator)->published()->create(['title' => 'Visible RPG']);

    $this->get(route('home'))->assertOk()->assertSee('Visible RPG');
});

test('buyer can download owned product files', function () {
    Storage::fake('local');

    $buyer = User::factory()->create();
    $creator = User::factory()->create();
    CreatorProfile::factory()->for($creator)->create();

    $product = Product::factory()->for($creator)->published()->create();
    $file = ProductFile::factory()->for($product)->create([
        'disk' => 'local',
        'path' => 'products/'.$product->id.'/doc.pdf',
        'original_name' => 'doc.pdf',
    ]);
    Storage::disk('local')->put($file->path, 'pdf-bytes');

    $order = Order::factory()->for($buyer)->paid()->create();
    Purchase::query()->create([
        'user_id' => $buyer->id,
        'product_id' => $product->id,
        'order_id' => $order->id,
    ]);

    $this->actingAs($buyer)
        ->get(route('library.download', $file))
        ->assertOk();
});

test('guest cannot download product files', function () {
    $creator = User::factory()->create();
    CreatorProfile::factory()->for($creator)->create();
    $product = Product::factory()->for($creator)->published()->create();
    $file = ProductFile::factory()->for($product)->create(['disk' => 'local', 'path' => 'x']);

    $this->get(route('library.download', $file))->assertRedirect(route('login'));
});

test('checkout session completed webhook creates purchases idempotently', function () {
    $buyer = User::factory()->create();
    $creator = User::factory()->create();
    CreatorProfile::factory()->for($creator)->stripeReady()->create();

    $product = Product::factory()->for($creator)->published()->create();

    $order = Order::factory()->for($buyer)->create([
        'status' => OrderStatus::Pending,
        'amount_total' => $product->price,
        'currency' => 'usd',
    ]);

    $order->items()->create([
        'product_id' => $product->id,
        'product_title' => $product->title,
        'unit_price' => $product->price,
        'creator_user_id' => $creator->id,
    ]);

    $payload = [
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'payment_status' => 'paid',
                'metadata' => ['order_id' => (string) $order->id],
                'amount_total' => $product->price,
                'currency' => 'usd',
                'payment_intent' => 'pi_test_abc',
            ],
        ],
    ];

    ProcessStripeWebhook::dispatchSync($payload);
    ProcessStripeWebhook::dispatchSync($payload);

    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
    expect(Purchase::query()->count())->toBe(1);
});

test('creator cannot publish without stripe charges enabled', function () {
    $creator = User::factory()->create();
    CreatorProfile::factory()->for($creator)->create([
        'charges_enabled' => false,
        'stripe_account_id' => null,
    ]);

    $product = Product::factory()->for($creator)->create(['status' => ProductStatus::Draft]);
    ProductFile::factory()->for($product)->create();

    expect(Gate::forUser($creator)->denies('publish', $product))->toBeTrue();
});

test('creator can publish when stripe is ready and product has files', function () {
    $creator = User::factory()->create();
    CreatorProfile::factory()->for($creator)->stripeReady()->create();

    $product = Product::factory()->for($creator)->create(['status' => ProductStatus::Draft]);
    ProductFile::factory()->for($product)->create();

    expect(Gate::forUser($creator)->allows('publish', $product))->toBeTrue();
});
