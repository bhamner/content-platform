<?php

use App\Enums\ProductStatus;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public function mount(): void
    {
        if (Auth::user()->creatorProfile === null) {
            $this->redirect(route('creator.setup', absolute: false), navigate: true);
        }
    }

    public function with(): array
    {
        $products = Auth::user()
            ->products()
            ->withCount('files')
            ->latest()
            ->get();

        $profile = Auth::user()->creatorProfile;

        return [
            'products' => $products,
            'profile' => $profile,
        ];
    }
}; ?>

<div class="space-y-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Your products</flux:heading>
            <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">Create digital goods and publish when Stripe is connected.</flux:text>
        </div>
        <div class="flex flex-wrap gap-2">
            <flux:button :href="route('creator.stripe.connect')" variant="outline">Stripe setup</flux:button>
            <flux:button :href="route('creator.products.create')" variant="primary" wire:navigate>New product</flux:button>
        </div>
    </div>

    @if (session('status') === 'stripe-onboarding-updated')
        <flux:text class="text-sm text-green-600 dark:text-green-400">Stripe account updated.</flux:text>
    @endif

    @if ($profile && ! $profile->canReceivePayments())
        <flux:text class="text-sm text-amber-600 dark:text-amber-400">
            Complete Stripe onboarding to publish products and receive payouts.
        </flux:text>
    @endif

    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase">Title</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase">Files</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-zinc-500 uppercase"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($products as $product)
                    <tr>
                        <td class="px-4 py-3 text-sm font-medium">{{ $product->title }}</td>
                        <td class="px-4 py-3 text-sm capitalize">{{ $product->status->value }}</td>
                        <td class="px-4 py-3 text-sm">{{ $product->files_count }}</td>
                        <td class="px-4 py-3 text-right">
                            <flux:button size="sm" variant="ghost" :href="route('creator.products.edit', $product)" wire:navigate>
                                Edit
                            </flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-sm text-zinc-500">No products yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
