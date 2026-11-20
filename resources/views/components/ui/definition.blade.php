@props(['label'])

<div>
    <dt class="text-xs font-medium uppercase tracking-wide text-muted">{{ $label }}</dt>
    <dd class="mt-1 text-sm text-ink">{!! $slot->isEmpty() ? '—' : $slot !!}</dd>
</div>
