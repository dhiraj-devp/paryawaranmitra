@props(['title', 'description' => null])

<div {{ $attributes->merge(['class' => 'mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between']) }}>
    <div>
        <h1 class="text-xl font-semibold tracking-tight text-ink">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1 max-w-3xl text-sm text-muted">{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
