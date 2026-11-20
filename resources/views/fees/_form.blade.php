@php
    $selectedService = old('service_id', $selectedService ?? $schedule->service_id);
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
        <p class="mb-4 text-sm text-muted">{{ __('ui.fees.separate') }}</p>
        <div class="grid gap-4 sm:grid-cols-2">
            @if ($schedule->exists)
                <div class="sm:col-span-2">
                    <x-ui.definition :label="__('ui.fees.service')">{{ $schedule->service->code }} — {{ $schedule->service->name }}</x-ui.definition>
                </div>
            @else
                <div class="sm:col-span-2">
                    <x-ui.select id="service_id" name="service_id" :label="__('ui.fees.service')" required :error="$errors->first('service_id')">
                        <option value="">{{ __('ui.fees.service') }}</option>
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}" @selected((string) $selectedService === (string) $service->id)>{{ $service->code }} — {{ $service->name }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
            @endif
            <x-ui.input id="effective_from" name="effective_from" type="date" :label="__('ui.fees.effective_from')" :value="old('effective_from', $schedule->effective_from?->format('Y-m-d'))" required :error="$errors->first('effective_from')" />
            <x-ui.input id="effective_until" name="effective_until" type="date" :label="__('ui.fees.effective_until')" :value="old('effective_until', $schedule->effective_until?->format('Y-m-d'))" :hint="__('ui.fees.open_ended')" :error="$errors->first('effective_until')" />
            <x-ui.input id="government_fee" name="government_fee" inputmode="decimal" :label="__('ui.fees.government')" :value="old('government_fee', $schedule->government_fee)" :hint="__('ui.fees.amount_hint')" required :error="$errors->first('government_fee')" />
            <x-ui.input id="consultancy_fee" name="consultancy_fee" inputmode="decimal" :label="__('ui.fees.consultancy')" :value="old('consultancy_fee', $schedule->consultancy_fee)" required :error="$errors->first('consultancy_fee')" />
            <div class="sm:col-span-2">
                <x-ui.textarea id="internal_notes" name="internal_notes" :label="__('ui.fees.notes')" :error="$errors->first('internal_notes')">{{ old('internal_notes', $schedule->internal_notes) }}</x-ui.textarea>
            </div>
        </div>
        @if ($schedule->exists)
            <p class="mt-4 text-sm text-muted">{{ __('ui.fees.correction') }}</p>
        @endif
    </x-ui.card>

    <div class="flex items-center gap-2">
        <x-ui.button>{{ __('ui.fees.save') }}</x-ui.button>
        <x-ui.button variant="secondary" :href="$cancel">{{ __('ui.fees.cancel') }}</x-ui.button>
    </div>
</form>
