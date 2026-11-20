@php
    $portal = auth()->user()->portal->value;
    $locked = $enquiry->exists && $enquiry->status === \App\Enums\EnquiryStatus::Converted;
    $date = old('enquiry_date', $enquiry->enquiry_date?->format('Y-m-d') ?? today()->format('Y-m-d'));
@endphp

<form method="post" action="{{ $enquiry->exists ? route($portal.'.enquiries.update', $enquiry) : route($portal.'.enquiries.store') }}" class="grid gap-4">
    @csrf
    @if ($enquiry->exists)
        @method('PUT')
    @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.input id="enquiry-date" type="date" name="enquiry_date" :label="__('ui.enquiries.date')" :value="$date" required :error="$errors->first('enquiry_date')" />
        <x-ui.select id="enquiry-source" name="source" :label="__('ui.enquiries.source')" required :error="$errors->first('source')">
            @foreach (\App\Enums\EnquirySource::cases() as $option)
                <option value="{{ $option->value }}" @selected(old('source', $enquiry->source?->value) === $option->value)>{{ $option->label() }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.input id="enquiry-contact" name="contact_name" :label="__('ui.enquiries.contact')" :value="old('contact_name', $enquiry->contact_name)" required :error="$errors->first('contact_name')" />
        <x-ui.input id="enquiry-mobile" name="mobile" :label="__('ui.enquiries.mobile')" :value="old('mobile', $enquiry->mobile)" required :error="$errors->first('mobile')" />
        <x-ui.input id="enquiry-email" type="email" name="email" :label="__('ui.enquiries.email')" :value="old('email', $enquiry->email)" :error="$errors->first('email')" />
    </div>

    <x-ui.select id="enquiry-service" name="service_id" :label="__('ui.enquiries.service')" :error="$errors->first('service_id')" :disabled="$locked">
        <option value="">{{ __('ui.enquiries.no_service') }}</option>
        @foreach ($services as $service)
            <option value="{{ $service->id }}" @selected((string) old('service_id', $enquiry->service_id) === (string) $service->id)>{{ $service->code }} — {{ $service->name }}</option>
        @endforeach
    </x-ui.select>
    @if ($locked)
        <input type="hidden" name="service_id" value="{{ $enquiry->service_id }}">
    @endif

    <x-ui.textarea id="enquiry-requirement" name="requirement" :label="__('ui.enquiries.requirement')" required :error="$errors->first('requirement')">{{ old('requirement', $enquiry->requirement) }}</x-ui.textarea>
    <x-ui.textarea id="enquiry-notes" name="internal_notes" :label="__('ui.enquiries.notes')" :error="$errors->first('internal_notes')">{{ old('internal_notes', $enquiry->internal_notes) }}</x-ui.textarea>

    <div class="border-t border-line pt-3">
        <p class="text-sm font-medium text-ink">{{ __('ui.enquiries.additional') }}</p>
        <p class="mt-1 text-sm text-muted">{{ __('ui.enquiries.link_hint') }}</p>
    </div>

    <x-ui.select id="enquiry-client" name="client_id" :label="__('ui.enquiries.client')" :error="$errors->first('client_id')" :disabled="$locked">
        <option value="">{{ __('ui.enquiries.no_client') }}</option>
        @foreach ($clients as $client)
            <option value="{{ $client->id }}" @selected((string) old('client_id', $enquiry->client_id) === (string) $client->id)>{{ $client->legal_name }}</option>
        @endforeach
    </x-ui.select>
    @if ($locked)
        <input type="hidden" name="client_id" value="{{ $enquiry->client_id }}">
    @endif

    <x-ui.select id="enquiry-unit" name="industry_unit_id" :label="__('ui.enquiries.unit')" :error="$errors->first('industry_unit_id')" :disabled="$locked">
        <option value="">{{ __('ui.enquiries.no_unit') }}</option>
        @foreach ($units as $unit)
            <option value="{{ $unit->id }}" @selected((string) old('industry_unit_id', $enquiry->industry_unit_id) === (string) $unit->id)>{{ $unit->client?->legal_name }} — {{ $unit->name }}</option>
        @endforeach
    </x-ui.select>
    @if ($locked)
        <input type="hidden" name="industry_unit_id" value="{{ $enquiry->industry_unit_id }}">
    @endif

    <x-ui.select id="enquiry-assignee" name="assigned_to" :label="__('ui.enquiries.assignee')" :error="$errors->first('assigned_to')">
        <option value="">{{ __('ui.enquiries.unassigned') }}</option>
        @foreach ($staff as $person)
            <option value="{{ $person->id }}" @selected((string) old('assigned_to', $enquiry->assigned_to) === (string) $person->id)>{{ $person->name }}</option>
        @endforeach
    </x-ui.select>

    @if ($locked)
        <p class="text-sm text-muted">{{ __('ui.enquiries.locked_hint') }}</p>
    @endif

    <div class="flex items-center gap-3">
        <x-ui.button>{{ $enquiry->exists ? __('ui.enquiries.save') : __('ui.enquiries.create') }}</x-ui.button>
        <a href="{{ $enquiry->exists ? route($portal.'.enquiries.show', $enquiry) : route($portal.'.enquiries.index') }}" class="text-sm text-muted hover:underline">{{ __('ui.enquiries.cancel') }}</a>
    </div>
</form>
