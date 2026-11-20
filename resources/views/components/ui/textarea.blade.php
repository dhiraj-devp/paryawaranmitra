@props(['label', 'id', 'error' => null, 'required' => false])

<div>
    <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-ink">
        {{ $label }}
        @if ($required)<span class="text-red-700">*</span>@endif
    </label>
    <textarea id="{{ $id }}" {{ $attributes->merge(['class' => 'block min-h-28 w-full rounded-md border bg-white px-3 py-2 text-sm text-ink placeholder:text-muted focus:border-forest-700 focus:outline-none focus:ring-2 focus:ring-forest-700/20 '.($error ? 'border-red-700' : 'border-line')]) }}>{!! $slot !!}</textarea>
    @if ($error)
        <p class="mt-1.5 text-sm text-red-700">{{ $error }}</p>
    @endif
</div>
