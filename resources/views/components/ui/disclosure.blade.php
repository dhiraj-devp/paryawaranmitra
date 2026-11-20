@props(['title', 'count' => null, 'open' => true])

@php
    $hasActions = isset($actions) && ! $actions->isEmpty();
@endphp

<div {{ $attributes->merge(['class' => 'relative rounded-lg border border-line bg-white']) }}>
    <details @if ($open) open @endif>
        <summary @class([
            'flex cursor-pointer list-none items-center gap-2 px-4 py-2.5 text-sm font-semibold text-ink [&::-webkit-details-marker]:hidden',
            'pr-44' => $hasActions,
        ])>
            <span class="min-w-0">{{ $title }}</span>
            @if ($count !== null)
                <span class="rounded-md bg-canvas px-2 py-0.5 text-xs font-medium text-muted">{{ $count }}</span>
            @endif
        </summary>
        <div class="border-t border-line">
            {{ $slot }}
        </div>
    </details>
    @if ($hasActions)
        <div class="absolute right-3 top-1.5 z-10">{{ $actions }}</div>
    @endif
</div>
