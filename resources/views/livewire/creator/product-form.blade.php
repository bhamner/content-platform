<?php

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.app')] class extends Component {
    use WithFileUploads;

    public ?Product $product = null;

    public string $title = '';

    public string $slug = '';

    public string $description = '';

    /** Price in major units (e.g. dollars) for the form */
    public string $price_display = '9.99';

    public string $status = 'draft';

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $newFiles = [];

    public function mount(?Product $product = null): void
    {
        if (Auth::user()->creatorProfile === null) {
            $this->redirect(route('creator.setup', absolute: false), navigate: true);
        }

        $this->product = $product;

        if ($product) {
            $this->authorize('update', $product);
            $this->title = $product->title;
            $this->slug = $product->slug;
            $this->description = (string) $product->description;
            $this->price_display = number_format($product->price / 100, 2, '.', '');
            $this->status = $product->status->value;
        }
    }

    public function save(): void
    {
        $user = Auth::user();

        if ($this->product === null) {
            $this->authorize('create', Product::class);
        }

        $maxKb = config('marketplace.max_upload_kb', 512000);

        $this->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => [
                'required',
                'string',
                'max:120',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('products', 'slug')
                    ->where('user_id', $user->id)
                    ->ignore($this->product?->id),
            ],
            'description' => ['nullable', 'string', 'max:20000'],
            'price_display' => ['required', 'numeric', 'min:0.5', 'max:99999.99'],
            'status' => ['required', Rule::in([ProductStatus::Draft->value, ProductStatus::Published->value])],
            'newFiles' => ['array', 'max:20'],
            'newFiles.*' => ['file', 'max:'.$maxKb],
        ]);

        $priceCents = (int) round(((float) $this->price_display) * 100);

        $wantsPublished = $this->status === ProductStatus::Published->value;

        $payload = [
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description ?: null,
            'price' => $priceCents,
            'currency' => 'usd',
        ];

        if ($this->product) {
            $this->authorize('update', $this->product);
            $this->product->update($payload);
            $model = $this->product->fresh();
        } else {
            $model = $user->products()->create(array_merge($payload, [
                'status' => ProductStatus::Draft,
            ]));
            $this->product = $model;
        }

        foreach ($this->newFiles as $file) {
            $path = $file->store('products/'.$model->id, 'local');
            $model->files()->create([
                'disk' => 'local',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'bytes' => $file->getSize(),
                'sort_order' => ((int) $model->files()->max('sort_order')) + 1,
            ]);
        }

        $this->newFiles = [];

        if ($wantsPublished) {
            $model->refresh();
            $this->authorize('publish', $model);
            $model->update(['status' => ProductStatus::Published]);
            $this->status = ProductStatus::Published->value;
        } else {
            $model->update(['status' => ProductStatus::Draft]);
            $this->status = ProductStatus::Draft->value;
        }

        session()->flash('status', 'product-saved');

        $this->redirect(route('creator.products.edit', $model, absolute: false), navigate: true);
    }

    public function deleteFile(int $fileId): void
    {
        $product = $this->product;
        if (! $product) {
            return;
        }

        $this->authorize('update', $product);

        $file = $product->files()->whereKey($fileId)->first();
        if (! $file) {
            return;
        }

        Storage::disk($file->disk)->delete($file->path);
        $file->delete();

        if ($product->status === ProductStatus::Published && $product->files()->count() === 0) {
            $product->update(['status' => ProductStatus::Draft]);
            $this->status = ProductStatus::Draft->value;
        }
    }

    public function with(): array
    {
        return [
            'files' => $this->product?->files ?? collect(),
            'heading' => $this->product ? 'Edit product' : 'New product',
        ];
    }
}; ?>

<div class="max-w-2xl space-y-8">
    <flux:heading size="xl">{{ $heading }}</flux:heading>

    @if (session('status') === 'product-saved')
        <flux:text class="text-sm text-green-600 dark:text-green-400">Saved.</flux:text>
    @endif

    <form wire:submit="save" class="space-y-6">
        <flux:input wire:model="title" label="Title" required />
        <flux:input wire:model="slug" label="URL slug" description="Unique for your store; used on your public product page." required />
        <flux:textarea wire:model="description" label="Description" rows="6" />
        <flux:input wire:model="price_display" type="text" label="Price (USD)" description="Major units, e.g. 9.99" required />

        <flux:select wire:model="status" label="Status">
            <flux:select.option value="draft">Draft</flux:select.option>
            <flux:select.option value="published">Published</flux:select.option>
        </flux:select>

        @error('status')
            <flux:text class="text-sm text-red-600">{{ $message }}</flux:text>
        @enderror

        <div>
            <flux:heading size="sm" class="mb-2">Files</flux:heading>
            <flux:text class="mb-2 text-sm text-zinc-500">Upload PDFs, zip archives, or other assets (private storage).</flux:text>
            <input
                type="file"
                wire:model="newFiles"
                multiple
                class="block w-full text-sm text-zinc-600 file:mr-4 file:rounded-lg file:border-0 file:bg-zinc-100 file:px-4 file:py-2 file:text-sm file:font-medium dark:text-zinc-400 dark:file:bg-zinc-800"
            />
            <div wire:loading wire:target="newFiles" class="mt-2 text-sm text-zinc-500">Uploading…</div>
            @error('newFiles.*')
                <flux:text class="mt-1 text-sm text-red-600">{{ $message }}</flux:text>
            @enderror
        </div>

        @if ($files->isNotEmpty())
            <ul class="space-y-2 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                @foreach ($files as $file)
                    <li class="flex items-center justify-between gap-4 text-sm">
                        <span class="truncate">{{ $file->original_name }}</span>
                        <flux:button size="sm" variant="danger" type="button" wire:click="deleteFile({{ $file->id }})" wire:confirm="Remove this file?">
                            Remove
                        </flux:button>
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">Save</flux:button>
            <flux:button :href="route('creator.products.index')" variant="ghost" type="button" wire:navigate>Back</flux:button>
        </div>
    </form>
</div>
