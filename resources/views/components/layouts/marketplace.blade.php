@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>{{ $title ?? config('app.name') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
    </head>
    <body class="min-h-screen bg-white text-zinc-900 dark:bg-zinc-900 dark:text-zinc-100">
        <flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-950">
            <a href="{{ route('home') }}" class="mr-4 text-lg font-semibold tracking-tight" wire:navigate>
                {{ config('app.name') }}
            </a>
            <flux:navbar class="-mb-px max-lg:hidden">
                <flux:navbar.item :href="route('home')" :current="request()->routeIs('home')" wire:navigate>
                    Browse
                </flux:navbar.item>
            </flux:navbar>
            <flux:spacer />
            @auth
                <flux:navbar class="mr-2">
                    <flux:navbar.item :href="route('library.index')" :current="request()->routeIs('library.*')" wire:navigate>
                        Library
                    </flux:navbar.item>
                    <flux:navbar.item :href="route('creator.products.index')" :current="request()->routeIs('creator.*')" wire:navigate>
                        Creator
                    </flux:navbar.item>
                    <flux:navbar.item :href="route('dashboard')" wire:navigate>
                        Dashboard
                    </flux:navbar.item>
                </flux:navbar>
            @else
                <flux:navbar class="mr-2">
                    <flux:navbar.item :href="route('login')" wire:navigate>Log in</flux:navbar.item>
                    <flux:navbar.item :href="route('register')" wire:navigate>Register</flux:navbar.item>
                </flux:navbar>
            @endauth
        </flux:header>

        <main class="mx-auto max-w-6xl px-4 py-8">
            {{ $slot }}
        </main>

        @fluxScripts
    </body>
</html>
