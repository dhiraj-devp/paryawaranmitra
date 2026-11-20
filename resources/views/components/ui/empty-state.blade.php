@props(['title'])

<div {{ $attributes->merge(['class' => 'px-6 py-12 text-center']) }}>
    <p class="text-sm font-medium text-ink">{{ $title }}</p>
    @if (trim($slot) !== '')
        <p class="mt-1 text-sm text-muted">{{ $slot }}</p>
    @endif
</div>
