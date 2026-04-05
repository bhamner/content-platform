<?php

use App\Enums\ProductStatus;
use App\Models\CreatorProfile;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.marketplace')] class extends Component {
    public CreatorProfile $creatorProfile;

    public function mount(CreatorProfile $creatorProfile): void
    {
        $this->creatorProfile = $creatorProfile;
    }

    public function with(): array
    {
        $products = $this->creatorProfile->user
            ->products()
            ->where('status', ProductStatus::Published)
            ->latest()
            ->get();

        return ['products' => $products];
    }
}; ?>

<div>
    <flux:heading size="xl" class="mb-2">{{ $creatorProfile->display_name }}</flux:heading>
    @if ($creatorProfile->bio)
        <flux:text class="mb-8 max-w-2xl text-zinc-600 dark:text-zinc-400">{{ $creatorProfile->bio }}</flux:text>
    @endif

    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($products as $product)
            <a
                href="{{ route('storefront.product', [$creatorProfile, $product->slug]) }}"
                wire:navigate
                class="block rounded-xl border border-zinc-200 bg-zinc-50 p-5 transition hover:border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-zinc-600"
            >
                <flux:heading size="lg" class="mb-1">{{ $product->title }}</flux:heading>
                <flux:text class="mt-2 font-medium">${{ number_format($product->price / 100, 2) }}</flux:text>
            </a>
        @empty
            <flux:text class="col-span-full text-zinc-500">This creator has no published products yet.</flux:text>
        @endforelse
    </div>
</div>
