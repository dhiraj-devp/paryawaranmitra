@props(['view' => null, 'edit' => null])

<div class="flex items-center justify-end gap-1">
    @if ($view)
        <a href="{{ $view }}" title="{{ __('ui.table.view') }}" aria-label="{{ __('ui.table.view') }}" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-forest-800 hover:bg-forest-50 focus:outline-none focus:ring-2 focus:ring-forest-700">
            <x-ui.icon name="eye" class="h-4 w-4" />
        </a>
    @endif
    @if ($edit)
        <a href="{{ $edit }}" title="{{ __('ui.table.edit') }}" aria-label="{{ __('ui.table.edit') }}" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-forest-800 hover:bg-forest-50 focus:outline-none focus:ring-2 focus:ring-forest-700">
            <x-ui.icon name="pencil" class="h-4 w-4" />
        </a>
    @endif
    {{ $slot }}
</div>
