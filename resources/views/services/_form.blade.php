@php
    $selected = old('industries', $service->industries->pluck('industry_category')->all());
    $knownCategories = collect($categories)->push(old('category', $service->category))->filter()->unique()->sort()->values();
@endphp

<form method="post" action="{{ $action }}" class="space-y-4">
    @csrf
    @if ($method !== 'post')
        @method($method)
    @endif

    @if ($errors->any())
        <x-ui.alert tone="danger">{{ $errors->first() }}</x-ui.alert>
    @endif

    <x-ui.card class="p-5">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.input id="code" name="code" :label="__('ui.services.code')" :value="old('code', $service->code)" required :error="$errors->first('code')" />
            <x-ui.input id="name" name="name" :label="__('ui.services.name')" :value="old('name', $service->name)" required :error="$errors->first('name')" />
            <x-ui.input id="category" name="category" :label="__('ui.services.category')" :value="old('category', $service->category)" :hint="__('ui.services.category_hint')" list="known-service-categories" required :error="$errors->first('category')" />
            <datalist id="known-service-categories">
                @foreach ($knownCategories as $option)
                    <option value="{{ $option }}"></option>
                @endforeach
            </datalist>
            <x-ui.input id="expected_processing_days" name="expected_processing_days" type="number" min="1" max="3650" :label="__('ui.services.days')" :value="old('expected_processing_days', $service->expected_processing_days)" :hint="__('ui.services.days_hint')" :error="$errors->first('expected_processing_days')" />
            <div class="sm:col-span-2">
                <x-ui.textarea id="description" name="description" :label="__('ui.services.description')" required :error="$errors->first('description')">{{ old('description', $service->description) }}</x-ui.textarea>
            </div>
            <div class="sm:col-span-2">
                <x-ui.textarea id="detailed_description" name="detailed_description" :label="__('ui.services.detailed_description')" :error="$errors->first('detailed_description')">{{ old('detailed_description', $service->detailed_description) }}</x-ui.textarea>
            </div>
            <div class="sm:col-span-2">
                <x-ui.textarea id="internal_notes" name="internal_notes" :label="__('ui.services.notes')" :error="$errors->first('internal_notes')">{{ old('internal_notes', $service->internal_notes) }}</x-ui.textarea>
            </div>
        </div>
    </x-ui.card>

    <x-ui.card class="p-5">
        <h2 class="text-sm font-semibold">{{ __('ui.services.eligibility') }}</h2>
        <p class="mt-1 text-sm text-muted">{{ __('ui.services.eligibility_hint') }}</p>
        @error('industries')
            <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
        @enderror
        @error('industries.*')
            <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
        @enderror
        <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
            @foreach (config('industries.categories', []) as $category)
                <label class="flex items-start gap-2 text-sm text-ink">
                    <input type="checkbox" name="industries[]" value="{{ $category }}" class="mt-1" @checked(in_array($category, $selected, true))>
                    <span>{{ __('ui.units.categories.'.$category) }}</span>
                </label>
            @endforeach
        </div>
    </x-ui.card>

    <div class="flex items-center gap-2">
        <x-ui.button>{{ __('ui.services.save') }}</x-ui.button>
        <x-ui.button variant="secondary" :href="$cancel">{{ __('ui.services.cancel') }}</x-ui.button>
    </div>
</form>
