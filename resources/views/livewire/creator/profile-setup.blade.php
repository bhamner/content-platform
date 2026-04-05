<?php

use App\Models\CreatorProfile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public string $display_name = '';

    public string $slug = '';

    public string $bio = '';

    public function mount(): void
    {
        $profile = Auth::user()->creatorProfile;
        if ($profile) {
            $this->display_name = $profile->display_name;
            $this->slug = $profile->slug;
            $this->bio = (string) $profile->bio;
        } else {
            $this->display_name = Auth::user()->name;
            $this->slug = Str::slug(Auth::user()->name).'-'.Str::lower(Str::random(4));
        }
    }

    public function save(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'display_name' => ['required', 'string', 'max:120'],
            'slug' => [
                'required',
                'string',
                'max:80',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique(CreatorProfile::class)->ignore($user->creatorProfile?->id),
            ],
            'bio' => ['nullable', 'string', 'max:2000'],
        ]);

        CreatorProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            $validated,
        );

        $this->redirect(route('creator.products.index', absolute: false), navigate: true);
    }
}; ?>

<div class="max-w-xl space-y-6">
    <div>
        <flux:heading size="xl">Creator profile</flux:heading>
        <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
            Choose your public store URL and display name. You can change these later.
        </flux:text>
    </div>

    <form wire:submit="save" class="space-y-6">
        <flux:input wire:model="display_name" label="Display name" required />
        <flux:input wire:model="slug" label="Store URL slug" description="Used as /c/your-slug" required />
        <flux:textarea wire:model="bio" label="Bio" rows="4" />

        <flux:button type="submit" variant="primary">Save and continue</flux:button>
    </form>
</div>
