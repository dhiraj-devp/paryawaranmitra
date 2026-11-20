@props(['variant' => 'primary', 'href' => null, 'type' => 'submit'])

@php
    $variants = [
        'primary' => 'bg-forest-700 text-white hover:bg-forest-800',
        'secondary' => 'border border-line bg-white text-ink hover:bg-forest-50',
        'ghost' => 'text-ink hover:bg-forest-50',
    ];
    $class = 'inline-flex items-center justify-center gap-2 rounded-md px-3 py-2 text-sm font-medium disabled:cursor-not-allowed disabled:opacity-60 '.$variants[$variant];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</button>
@endif
