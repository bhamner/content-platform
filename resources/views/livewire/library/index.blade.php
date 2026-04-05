<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public function with(): array
    {
        $purchases = Auth::user()
            ->purchases()
            ->with(['product.files', 'product.creator.creatorProfile'])
            ->whereNull('revoked_at')
            ->latest()
            ->get();

        return ['purchases' => $purchases];
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">Your library</flux:heading>
        <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">Downloads for products you have purchased.</flux:text>
    </div>

    @if (request()->query('session_id'))
        <p class="rounded-lg border border-green-600/40 bg-green-600/10 px-4 py-3 text-sm text-green-700 dark:text-green-400">
            Payment received — your library is updating. Refresh if files are not visible yet.
        </p>
    @endif

    <div class="space-y-8">
        @forelse ($purchases as $purchase)
            @php($product = $purchase->product)
            @if ($product)
                <div class="rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
                    <flux:heading size="lg" class="mb-1">{{ $product->title }}</flux:heading>
                    @if ($product->creator?->creatorProfile)
                        <flux:text class="mb-4 text-sm text-zinc-500">
                            by {{ $product->creator->creatorProfile->display_name }}
                        </flux:text>
                    @endif
                    <div class="flex flex-col gap-2">
                        @forelse ($product->files as $file)
                            <a
                                href="{{ route('library.download', $file) }}"
                                class="inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium text-zinc-700 underline decoration-zinc-400 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white"
                            >
                                Download {{ $file->original_name }}
                            </a>
                        @empty
                            <flux:text class="text-zinc-500">No files attached to this product.</flux:text>
                        @endforelse
                    </div>
                </div>
            @endif
        @empty
            <flux:text class="text-zinc-500">You have not purchased anything yet.</flux:text>
            <flux:button :href="route('home')" class="mt-4" variant="primary" wire:navigate>Browse products</flux:button>
        @endforelse
    </div>
</div>
