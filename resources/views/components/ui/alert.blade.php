@props(['tone' => 'info'])

@php
    $tones = [
        'info' => 'border-forest-100 bg-forest-50 text-forest-900',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-900',
        'danger' => 'border-red-200 bg-red-50 text-red-900',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-md border px-4 py-3 text-sm '.$tones[$tone]]) }}>
    {{ $slot }}
</div>
