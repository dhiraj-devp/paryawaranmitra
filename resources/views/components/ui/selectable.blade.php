@props(['action' => null, 'returnTo' => null])

<div
    x-data="{
        picking: false,
        selected: [],
        toggle(event) {
            const id = event.target.value;
            if (event.target.checked) {
                if (! this.selected.includes(id)) this.selected.push(id);
            } else {
                this.selected = this.selected.filter((value) => value !== id);
            }
        }
    }"
    {{ $attributes->merge(['class' => 'w-full']) }}
>
    <div class="flex flex-wrap items-center gap-2 border-b border-line px-4 py-2">
        <button type="button" class="inline-flex h-8 items-center gap-1.5 rounded-md border border-line bg-white px-3 text-sm font-medium text-ink hover:bg-forest-50 focus:outline-none focus:ring-2 focus:ring-forest-700" x-on:click="picking = !picking; selected = []">
            <x-ui.icon name="check" class="h-4 w-4" x-show="!picking" />
            <x-ui.icon name="x" class="h-4 w-4" x-show="picking" x-cloak />
            <span x-show="!picking">{{ __('ui.table.select') }}</span>
            <span x-show="picking" x-cloak>{{ __('ui.table.selecting') }}</span>
        </button>
        @if ($action)
            <form x-show="picking" x-cloak method="post" action="{{ $action }}" onsubmit="return confirm(@js(__('ui.table.confirm_delete')))" class="inline-flex items-center">
                @csrf
                @method('DELETE')
                <input type="hidden" name="confirm_delete" value="1">
                @if ($returnTo)
                    <input type="hidden" name="return_to" value="{{ $returnTo }}">
                @endif
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="ids[]" :value="id">
                </template>
                <button type="submit" class="inline-flex h-8 items-center gap-1.5 rounded-md bg-red-700 px-3 text-sm font-medium text-white hover:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-700 disabled:cursor-not-allowed disabled:opacity-40" x-bind:disabled="selected.length === 0">
                    <x-ui.icon name="trash" class="h-4 w-4" />
                    {{ __('ui.table.delete_selected') }}
                </button>
            </form>
        @endif
    </div>
    {{ $slot }}
</div>
