@php
    $categories = config('industries.categories', []);
    $selectedCategory = old('industry_category', $unit->industry_category);
@endphp

<form method="post" action="{{ $action }}" class="space-y-4">
    @csrf
    @if ($method !== 'post')
        @method($method)
    @endif

    @if ($errors->any())
        <x-ui.alert tone="danger">{{ $errors->first('client') ?: __('ui.clients.fix_errors') }}</x-ui.alert>
    @endif

    <x-ui.card class="p-5">
        <h2 class="text-sm font-semibold">{{ __('ui.units.business') }}</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <x-ui.input id="name" name="name" :label="__('ui.units.name')" :value="old('name', $unit->name)" required :error="$errors->first('name')" />
            <x-ui.select id="industry_category" name="industry_category" :label="__('ui.units.category')" required :error="$errors->first('industry_category')">
                @foreach ($categories as $category)
                    <option value="{{ $category }}" @selected($selectedCategory === $category)>{{ __('ui.units.categories.'.$category) }}</option>
                @endforeach
            </x-ui.select>
            <div class="sm:col-span-2">
                <x-ui.textarea id="activity" name="activity" :label="__('ui.units.activity')" :error="$errors->first('activity')">{{ old('activity', $unit->activity) }}</x-ui.textarea>
            </div>
        </div>
    </x-ui.card>

    <x-ui.card class="p-5">
        <h2 class="text-sm font-semibold">{{ __('ui.units.site') }}</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-ui.input id="site_address_line" name="site_address_line" :label="__('ui.units.site_address')" :value="old('site_address_line', $unit->site_address_line)" required :error="$errors->first('site_address_line')" />
            </div>
            <x-ui.input id="plot_number" name="plot_number" :label="__('ui.units.plot')" :value="old('plot_number', $unit->plot_number)" :error="$errors->first('plot_number')" />
            <x-ui.input id="site_landmark" name="site_landmark" :label="__('ui.units.landmark')" :value="old('site_landmark', $unit->site_landmark)" />
            <x-ui.input id="site_village" name="site_village" :label="__('ui.units.village')" :value="old('site_village', $unit->site_village)" />
            <x-ui.input id="site_taluka" name="site_taluka" :label="__('ui.units.taluka')" :value="old('site_taluka', $unit->site_taluka)" />
            <x-ui.input id="site_district" name="site_district" :label="__('ui.units.district')" :value="old('site_district', $unit->site_district)" required :error="$errors->first('site_district')" />
            <x-ui.input id="site_state" name="site_state" :label="__('ui.units.state')" :value="old('site_state', $unit->site_state ?: 'Maharashtra')" required :error="$errors->first('site_state')" />
            <x-ui.input id="site_pincode" name="site_pincode" :label="__('ui.units.pincode')" :value="old('site_pincode', $unit->site_pincode)" maxlength="6" required :error="$errors->first('site_pincode')" />
        </div>
    </x-ui.card>

    <x-ui.card class="p-5">
        <h2 class="text-sm font-semibold">{{ __('ui.units.site_contact') }}</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <x-ui.input id="site_contact_name" name="site_contact_name" :label="__('ui.units.contact_name')" :value="old('site_contact_name', $unit->site_contact_name)" />
            <x-ui.input id="site_contact_phone" name="site_contact_phone" :label="__('ui.units.contact_phone')" :value="old('site_contact_phone', $unit->site_contact_phone)" :error="$errors->first('site_contact_phone')" />
            <div class="sm:col-span-2">
                <x-ui.textarea id="unit_notes" name="notes" :label="__('ui.clients.notes')" :error="$errors->first('notes')">{{ old('notes', $unit->notes) }}</x-ui.textarea>
            </div>
        </div>
    </x-ui.card>

    <div class="flex items-center gap-2">
        <x-ui.button>{{ __('ui.units.save') }}</x-ui.button>
        <x-ui.button variant="secondary" :href="$cancel">{{ __('ui.clients.cancel') }}</x-ui.button>
    </div>
</form>
