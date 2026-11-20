@php
    $constitutions = \App\Enums\ClientConstitution::cases();
    $selectedConstitution = old('constitution', $client->constitution?->value);
@endphp

<form method="post" action="{{ $action }}" class="space-y-4">
    @csrf
    @if ($method !== 'post')
        @method($method)
    @endif

    @if ($errors->any())
        <x-ui.alert tone="danger">{{ __('ui.clients.fix_errors') }}</x-ui.alert>
    @endif

    @error('duplicate_name')
        <x-ui.alert tone="warning">
            <p>{{ $message }}</p>
            <label class="mt-3 flex items-start gap-2">
                <input type="checkbox" name="acknowledge_duplicate" value="1" class="mt-1" @checked(old('acknowledge_duplicate'))>
                <span>{{ __('ui.clients.acknowledge_duplicate') }}</span>
            </label>
        </x-ui.alert>
    @enderror

    <x-ui.card class="p-5">
        <h2 class="text-sm font-semibold">{{ __('ui.clients.identity') }}</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <x-ui.input id="legal_name" name="legal_name" :label="__('ui.clients.legal_name')" :value="old('legal_name', $client->legal_name)" required :error="$errors->first('legal_name')" />
            <x-ui.input id="trade_name" name="trade_name" :label="__('ui.clients.trade_name')" :value="old('trade_name', $client->trade_name)" :error="$errors->first('trade_name')" />
            <x-ui.select id="constitution" name="constitution" :label="__('ui.clients.constitution')" required :error="$errors->first('constitution')">
                @foreach ($constitutions as $constitution)
                    <option value="{{ $constitution->value }}" @selected($selectedConstitution === $constitution->value)>{{ $constitution->label() }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.input id="pan" name="pan" :label="__('ui.clients.pan')" :value="old('pan', $client->pan)" maxlength="10" :error="$errors->first('pan')" />
            <x-ui.input id="gstin" name="gstin" :label="__('ui.clients.gstin')" :value="old('gstin', $client->gstin)" maxlength="15" :hint="__('ui.clients.gstin_hint')" :error="$errors->first('gstin')" />
        </div>
    </x-ui.card>

    <x-ui.card class="p-5">
        <h2 class="text-sm font-semibold">{{ __('ui.clients.contact') }}</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <x-ui.input id="contact_name" name="contact_name" :label="__('ui.clients.contact_name')" :value="old('contact_name', $client->contact_name)" :error="$errors->first('contact_name')" />
            <x-ui.input id="phone" name="phone" :label="__('ui.clients.phone')" :value="old('phone', $client->phone)" :error="$errors->first('phone')" />
            <x-ui.input id="email" name="email" type="email" :label="__('ui.clients.email')" :value="old('email', $client->email)" :error="$errors->first('email')" />
        </div>
    </x-ui.card>

    <x-ui.card class="p-5">
        <h2 class="text-sm font-semibold">{{ __('ui.clients.office') }}</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-ui.input id="address_line" name="address_line" :label="__('ui.clients.address')" :value="old('address_line', $client->address_line)" :error="$errors->first('address_line')" />
            </div>
            <x-ui.input id="city" name="city" :label="__('ui.clients.city')" :value="old('city', $client->city)" :error="$errors->first('city')" />
            <x-ui.input id="district" name="district" :label="__('ui.clients.district')" :value="old('district', $client->district)" :error="$errors->first('district')" />
            <x-ui.input id="state" name="state" :label="__('ui.clients.state')" :value="old('state', $client->state ?: 'Maharashtra')" required :error="$errors->first('state')" />
            <x-ui.input id="pincode" name="pincode" :label="__('ui.clients.pincode')" :value="old('pincode', $client->pincode)" maxlength="6" :error="$errors->first('pincode')" />
        </div>
    </x-ui.card>

    <x-ui.card class="p-5">
        <x-ui.textarea id="notes" name="notes" :label="__('ui.clients.notes')" :error="$errors->first('notes')">{{ old('notes', $client->notes) }}</x-ui.textarea>
    </x-ui.card>

    <div class="flex items-center gap-2">
        <x-ui.button>{{ __('ui.clients.save') }}</x-ui.button>
        <x-ui.button variant="secondary" :href="$cancel">{{ __('ui.clients.cancel') }}</x-ui.button>
    </div>
</form>
