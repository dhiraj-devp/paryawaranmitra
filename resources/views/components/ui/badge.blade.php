@props(['tone' => 'neutral'])

@php
    $tones = [
        'neutral' => 'bg-stone-100 text-stone-700',
        'success' => 'bg-green-100 text-green-800',
        'warning' => 'bg-amber-100 text-amber-900',
        'danger' => 'bg-red-100 text-red-800',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium '.$tones[$tone]]) }}>
    {{ $slot }}
</span>
