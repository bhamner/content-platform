<?php

use App\Enums\ProductStatus;
use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.marketplace')] class extends Component {
    public function with(): array
    {
        $products = Product::query()
            ->with(['creator.creatorProfile'])
            ->where('status', ProductStatus::Published)
            ->latest()
            ->paginate(12);

        return ['products' => $products];
    }
}; ?>

<div>
    <flux:heading size="xl" class="mb-2">Digital games and assets</flux:heading>
    <flux:text class="mb-8 text-zinc-600 dark:text-zinc-400">From indie creators. PDFs, asset packs, and more.</flux:text>

    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($products as $product)
            @php($profile = $product->creator?->creatorProfile)
            @if ($profile)
                <a
                    href="{{ route('storefront.product', [$profile, $product->slug]) }}"
                    wire:navigate
                    class="block rounded-xl border border-zinc-200 bg-zinc-50 p-5 transition hover:border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-zinc-600"
                >
                    <flux:heading size="lg" class="mb-1">{{ $product->title }}</flux:heading>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ $profile->display_name }}</flux:text>
                    <flux:text class="mt-3 font-medium">${{ number_format($product->price / 100, 2) }}</flux:text>
                </a>
            @endif
        @empty
            <flux:text class="col-span-full text-zinc-500">No products yet. Check back soon.</flux:text>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $products->links() }}
    </div>
</div>
