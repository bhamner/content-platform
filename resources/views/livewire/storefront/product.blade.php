<?php

use App\Models\CreatorProfile;
use App\Models\Product;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.marketplace')] class extends Component {
    public CreatorProfile $creatorProfile;

    public string $product_slug;

    public ?Product $product = null;

    public function mount(CreatorProfile $creatorProfile, string $product_slug): void
    {
        $this->creatorProfile = $creatorProfile;
        $this->product_slug = $product_slug;

        $this->product = Product::query()
            ->where('user_id', $creatorProfile->user_id)
            ->where('slug', $product_slug)
            ->with(['creator.creatorProfile', 'files'])
            ->firstOrFail();

        abort_unless($this->product->user_id === $this->creatorProfile->user_id, 404);
    }

    public function with(): array
    {
        $canPurchase = auth()->check()
            && auth()->user()->hasVerifiedEmail()
            && Gate::forUser(auth()->user())->allows('purchase', $this->product);

        $owns = auth()->check()
            && auth()->user()->purchases()
                ->where('product_id', $this->product->id)
                ->whereNull('revoked_at')
                ->exists();

        return [
            'canPurchase' => $canPurchase,
            'owns' => $owns,
        ];
    }
}; ?>

<div>
    <flux:heading size="xl" class="mb-2">{{ $product->title }}</flux:heading>
    <flux:text class="mb-2 text-zinc-500">by {{ $creatorProfile->display_name }}</flux:text>
    <flux:text class="mb-6 text-2xl font-semibold">${{ number_format($product->price / 100, 2) }}</flux:text>

    @if ($product->description)
        <flux:text class="prose prose-invert mb-8 max-w-2xl whitespace-pre-wrap dark:prose-invert">{{ $product->description }}</flux:text>
    @endif

    @auth
        @if (! auth()->user()->hasVerifiedEmail())
            <flux:callout variant="warning" class="mb-6">
                Verify your email address before you can purchase.
                <flux:link :href="route('verification.notice')" class="ms-1" wire:navigate>Resend link</flux:link>
            </flux:callout>
        @endif

        @if ($owns)
            <flux:callout variant="success" class="mb-6">You own this product. Find it in your library.</flux:callout>
            <flux:button :href="route('library.index')" variant="primary" wire:navigate>Go to library</flux:button>
        @elseif ($canPurchase)
            <form action="{{ route('checkout.store', $product) }}" method="POST" class="inline">
                @csrf
                <flux:button type="submit" variant="primary">Buy now</flux:button>
            </form>
        @elseif (auth()->id() === $product->user_id)
            <flux:text class="text-zinc-500">This is your product.</flux:text>
        @else
            <flux:text class="text-zinc-500">This product is not available for purchase.</flux:text>
        @endif
    @else
        <flux:text class="mb-4">Log in to purchase.</flux:text>
        <flux:button :href="route('login')" variant="primary" wire:navigate>Log in</flux:button>
    @endauth
</div>
