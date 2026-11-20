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
<body class="h-screen overflow-hidden bg-canvas text-ink antialiased {{ app()->getLocale() === 'mr' ? 'font-devanagari' : 'font-sans' }}">
    <a href="#content" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-white focus:px-3 focus:py-2">{{ __('ui.a11y.skip') }}</a>

    <div
        class="flex h-full min-h-0"
        x-data="{
            collapsed: false,
            init() {
                try { this.collapsed = localStorage.getItem('pm.sidebar') === '1'; } catch (e) { this.collapsed = false; }
            },
            toggle() {
                this.collapsed = ! this.collapsed;
                try { localStorage.setItem('pm.sidebar', this.collapsed ? '1' : '0'); } catch (e) {}
            }
        }"
    >
        <input id="nav-open" type="checkbox" class="peer sr-only">
        <x-nav.sidebar />
        <label id="nav-backdrop" for="nav-open" aria-label="{{ __('ui.a11y.close_menu') }}" class="fixed inset-0 z-30 bg-ink/40"></label>

        <div class="flex min-h-0 min-w-0 flex-1 flex-col">
            <x-nav.header />
            <main id="content" class="w-full min-h-0 flex-1 overflow-y-auto px-4 py-6 sm:px-6 lg:px-8">
                @if (session('status'))
                    <div x-data="{ show: true }" x-show="show" x-cloak x-transition x-init="setTimeout(() => show = false, 4200)" class="fixed right-4 top-20 z-[60] flex max-w-sm items-start gap-3 rounded-lg bg-forest-800 px-4 py-3 text-sm text-white shadow-lg" role="status">
                        <x-ui.icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-forest-100" />
                        <p class="min-w-0 flex-1">{{ session('status') }}</p>
                        <button type="button" class="text-white/80 hover:text-white" x-on:click="show = false" aria-label="{{ __('ui.a11y.close') }}">
                            <x-ui.icon name="x" class="h-4 w-4" />
                        </button>
                    </div>
                @endif
                @hasSection('content')
                    @yield('content')
                @else
                    {{ $slot ?? '' }}
                @endif
            </main>
        </div>
    </div>
    @livewireScripts
</body>
</html>
