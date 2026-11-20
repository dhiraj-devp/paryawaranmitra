@props(['name', 'title', 'variant' => 'dialog', 'open' => false, 'wide' => false])

@php
    $drawer = $variant === 'drawer';
@endphp

<div
    x-data="{ open: @js((bool) $open) }"
    x-on:open-panel.window="if ($event.detail === @js($name)) open = true"
    x-on:keydown.escape.window="open = false"
    x-effect="document.documentElement.classList.toggle('overflow-hidden', open)"
>
    <div x-show="open" x-cloak x-transition.opacity.duration.200 class="fixed inset-0 z-50 bg-ink/40" x-on:click="open = false"></div>
    <div
        x-show="open"
        x-cloak
        @class([
            'fixed z-50 flex flex-col bg-white shadow-lg',
            'inset-y-0 right-0 w-full max-w-xl' => $drawer && $wide,
            'inset-y-0 right-0 w-full max-w-md' => $drawer && ! $wide,
            'inset-x-4 top-[6vh] mx-auto max-h-[88vh] w-auto max-w-3xl overflow-hidden rounded-lg border border-line sm:inset-x-auto sm:left-1/2 sm:w-full sm:-translate-x-1/2' => ! $drawer,
        ])
        role="dialog"
        aria-modal="true"
        aria-labelledby="panel-{{ $name }}"
        @if ($drawer)
            x-transition:enter="transform transition duration-200 ease-out"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition duration-150 ease-in"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
        @else
            x-transition.opacity.duration.150
        @endif
    >
        <div class="flex items-center justify-between gap-3 border-b border-line px-4 py-3">
            <h2 id="panel-{{ $name }}" class="text-sm font-semibold text-ink">{{ $title }}</h2>
            <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-ink hover:bg-forest-50" x-on:click="open = false" aria-label="{{ __('ui.a11y.close') }}">
                <x-ui.icon name="x" class="h-4 w-4" />
            </button>
        </div>
        <div class="min-h-0 flex-1 overflow-y-auto p-4">
            {{ $slot }}
        </div>
    </div>
</div>
