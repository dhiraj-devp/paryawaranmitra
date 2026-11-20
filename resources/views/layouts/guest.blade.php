<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@hasSection('title')@yield('title') — @endif{{ config('app.name') }}</title>
    @include('partials.assets')
    @livewireStyles
</head>
<body class="min-h-screen bg-canvas text-ink antialiased {{ app()->getLocale() === 'mr' ? 'font-devanagari' : 'font-sans' }}">
    <a href="#content" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-white focus:px-3 focus:py-2">{{ __('ui.a11y.skip') }}</a>
    @hasSection('fullscreen')
        <main id="content" class="min-h-screen">
            @yield('content')
        </main>
    @else
        <header class="border-b border-line bg-white">
            <div class="mx-auto flex h-16 max-w-5xl items-center justify-between gap-4 px-4">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-md bg-forest-700 text-sm font-semibold text-white">PM</span>
                    <span class="text-sm font-semibold">{{ config('app.name') }}</span>
                </a>
                <x-nav.language-switcher context="guest" />
            </div>
        </header>
        <main id="content" class="mx-auto w-full max-w-5xl px-4 py-8">
            @hasSection('content')
                @yield('content')
            @else
                {{ $slot ?? '' }}
            @endif
        </main>
    @endif
    @livewireScripts
</body>
</html>
