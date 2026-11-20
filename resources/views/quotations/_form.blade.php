@php
    $portal = auth()->user()->portal->value;
    $serviceLine = isset($quotation)
        ? $quotation->currentRevision?->lines->first(fn ($line) => $line->line_type === \App\Enums\QuotationLineType::Service)
        : null;
    $issueDate = old('issue_date', $serviceLine ? $quotation->currentRevision->issue_date?->toDateString() : now()->toDateString());
    $validUntil = old('valid_until', $serviceLine ? $quotation->currentRevision->valid_until?->toDateString() : '');
@endphp

@if ($errors->any() && $errors->first('revision'))
    <x-ui.alert tone="danger" class="mb-4">{{ $errors->first('revision') }}</x-ui.alert>
@endif

@if ($taxPending)
    <x-ui.alert tone="warning" class="mb-4">{{ __('ui.quotations.tax_pending') }}</x-ui.alert>
@endif

<form method="post" action="{{ $action }}" class="space-y-4">
    @csrf

    <x-ui.card class="p-5">
        @isset($quotation)
            <dl class="grid gap-4 sm:grid-cols-2">
                <x-ui.definition :label="__('ui.quotations.work_order')">{{ $quotation->workOrder?->number }}</x-ui.definition>
                <x-ui.definition :label="__('ui.quotations.client')">{{ $quotation->workOrder?->client?->legal_name }}</x-ui.definition>
                <x-ui.definition :label="__('ui.quotations.unit')">{{ $quotation->workOrder?->industryUnit?->name }}</x-ui.definition>
                <x-ui.definition :label="__('ui.quotations.service')">{{ $quotation->workOrder?->service ? $quotation->workOrder->service->code.' — '.$quotation->workOrder->service->name : '' }}</x-ui.definition>
            </dl>
            <p class="mt-4 text-sm text-muted">{{ __('ui.quotations.revise_note', ['revision' => $quotation->currentRevision?->label()]) }}</p>
        @else
            <x-ui.select id="quotation-work-order" name="work_order_id" :label="__('ui.quotations.work_order')" :error="$errors->first('work_order_id')" required>
                <option value="">{{ __('ui.quotations.choose_work_order') }}</option>
                @foreach ($workOrders as $workOrder)
                    <option value="{{ $workOrder->id }}" @selected((string) old('work_order_id', $selected?->id) === (string) $workOrder->id)>
                        {{ $workOrder->number }} — {{ $workOrder->client?->legal_name }} — {{ $workOrder->service?->code }}
                    </option>
                @endforeach
            </x-ui.select>
            <p class="mt-3 text-sm text-muted">{{ __('ui.quotations.work_order_hint') }}</p>
        @endisset
    </x-ui.card>

    <x-ui.card class="grid gap-4 p-5 sm:grid-cols-2">
        <x-ui.input id="quotation-issue-date" name="issue_date" type="date" :label="__('ui.quotations.issue_date')" :value="$issueDate" :error="$errors->first('issue_date')" required />
        <x-ui.input id="quotation-valid-until" name="valid_until" type="date" :label="__('ui.quotations.valid_until')" :value="$validUntil" :error="$errors->first('valid_until')" required />
        @if ($schedule)
            <div class="sm:col-span-2">
                <x-ui.alert>{{ __('ui.quotations.schedule_copied', ['government' => \App\Support\Money::inr($schedule->government_fee), 'consultancy' => \App\Support\Money::inr($schedule->consultancy_fee)]) }}</x-ui.alert>
            </div>
        @elseif(isset($quotation) || $selected)
            <div class="sm:col-span-2">
                <x-ui.alert tone="warning">{{ __('ui.quotations.no_fee') }}</x-ui.alert>
            </div>
        @endif
        <x-ui.input id="quotation-government-fee" name="government_fee" inputmode="decimal" :label="__('ui.quotations.government_fee')" :value="old('government_fee', $serviceLine->government_fee ?? $schedule?->government_fee)" :error="$errors->first('government_fee')" required />
        <x-ui.input id="quotation-consultancy-fee" name="consultancy_fee" inputmode="decimal" :label="__('ui.quotations.consultancy_fee')" :value="old('consultancy_fee', $serviceLine->consultancy_fee ?? $schedule?->consultancy_fee)" :error="$errors->first('consultancy_fee')" required />
        <x-ui.input id="quotation-quantity" name="service_quantity" inputmode="decimal" :label="__('ui.quotations.quantity')" :value="old('service_quantity', $serviceLine->quantity ?? '1')" :error="$errors->first('service_quantity')" required />
        <x-ui.input id="quotation-discount" name="service_discount" inputmode="decimal" :label="__('ui.quotations.discount')" :value="old('service_discount', $serviceLine->discount_amount ?? '0.00')" :error="$errors->first('service_discount')" />
        <div class="sm:col-span-2">
            <x-ui.input id="quotation-service-description" name="service_description" :label="__('ui.quotations.service_description')" :value="old('service_description', $serviceLine->description ?? '')" :error="$errors->first('service_description')" :hint="__('ui.quotations.service_description_hint')" />
        </div>
    </x-ui.card>

    <x-ui.card class="p-5">
        <h2 class="text-sm font-semibold">{{ __('ui.quotations.custom_lines') }}</h2>
        <p class="mt-1 text-sm text-muted">{{ __('ui.quotations.custom_hint') }}</p>
        <div class="mt-4 space-y-4">
            @foreach ($customRows as $index => $row)
                <div class="grid gap-3 border-t border-line pt-4 sm:grid-cols-5">
                    <div class="sm:col-span-2">
                        <x-ui.input :id="'custom-description-'.$index" :name="'custom_lines['.$index.'][description]'" :label="__('ui.quotations.description')" :value="old('custom_lines.'.$index.'.description', $row['description'] ?? '')" :error="$errors->first('custom_lines.'.$index.'.description')" />
                    </div>
                    <x-ui.select :id="'custom-side-'.$index" :name="'custom_lines['.$index.'][charge_side]'" :label="__('ui.quotations.charge_side')" :error="$errors->first('custom_lines.'.$index.'.charge_side')">
                        <option value="">{{ __('ui.quotations.choose_side') }}</option>
                        @foreach (\App\Enums\ChargeSide::cases() as $side)
                            <option value="{{ $side->value }}" @selected(old('custom_lines.'.$index.'.charge_side', $row['charge_side'] ?? '') === $side->value)>{{ $side->label() }}</option>
                        @endforeach
                    </x-ui.select>
                    <x-ui.input :id="'custom-quantity-'.$index" :name="'custom_lines['.$index.'][quantity]'" inputmode="decimal" :label="__('ui.quotations.quantity')" :value="old('custom_lines.'.$index.'.quantity', $row['quantity'] ?? '1')" :error="$errors->first('custom_lines.'.$index.'.quantity')" />
                    <x-ui.input :id="'custom-amount-'.$index" :name="'custom_lines['.$index.'][amount]'" inputmode="decimal" :label="__('ui.quotations.amount')" :value="old('custom_lines.'.$index.'.amount', $row['amount'] ?? '')" :error="$errors->first('custom_lines.'.$index.'.amount')" />
                    <div class="sm:col-span-2">
                        <x-ui.input :id="'custom-discount-'.$index" :name="'custom_lines['.$index.'][discount_amount]'" inputmode="decimal" :label="__('ui.quotations.discount')" :value="old('custom_lines.'.$index.'.discount_amount', $row['discount_amount'] ?? '0.00')" :error="$errors->first('custom_lines.'.$index.'.discount_amount')" />
                    </div>
                </div>
            @endforeach
        </div>
    </x-ui.card>

    <x-ui.card class="grid gap-4 p-5">
        <x-ui.textarea id="quotation-payment-terms" name="payment_terms" :label="__('ui.quotations.payment_terms')" :error="$errors->first('payment_terms')">{{ old('payment_terms', isset($quotation) ? $quotation->currentRevision?->payment_terms : '') }}</x-ui.textarea>
        <x-ui.textarea id="quotation-terms" name="terms_and_conditions" :label="__('ui.quotations.terms')" :error="$errors->first('terms_and_conditions')">{{ old('terms_and_conditions', isset($quotation) ? $quotation->currentRevision?->terms_and_conditions : '') }}</x-ui.textarea>
        <x-ui.textarea id="quotation-customer-notes" name="customer_notes" :label="__('ui.quotations.customer_notes')" :error="$errors->first('customer_notes')">{{ old('customer_notes', isset($quotation) ? $quotation->currentRevision?->customer_notes : '') }}</x-ui.textarea>
        <x-ui.textarea id="quotation-internal-notes" name="internal_notes" :label="__('ui.quotations.internal_notes')" :error="$errors->first('internal_notes')">{{ old('internal_notes', isset($quotation) ? $quotation->currentRevision?->internal_notes : '') }}</x-ui.textarea>
        @isset($quotation)
            @if ($quotation->status === \App\Enums\QuotationStatus::Accepted)
                <x-ui.alert tone="warning">{{ __('ui.quotations.accepted_locked', ['revision' => $quotation->acceptedRevision?->label()]) }}</x-ui.alert>
                <x-ui.input id="quotation-revision-reason" name="revision_reason" :label="__('ui.quotations.reason')" :value="old('revision_reason')" :error="$errors->first('revision_reason')" required />
                <label class="flex items-start gap-2 text-sm text-ink">
                    <input type="checkbox" name="confirm_revision" value="1" class="mt-1" @checked(old('confirm_revision'))>
                    <span>{{ __('ui.quotations.confirm_revision') }}</span>
                </label>
                @if ($errors->first('confirm_revision'))
                    <p class="text-sm text-red-700">{{ $errors->first('confirm_revision') }}</p>
                @endif
            @endif
        @endisset
        <div class="flex flex-wrap gap-2">
            <x-ui.button>{{ $submitLabel }}</x-ui.button>
            <x-ui.button variant="secondary" :href="$cancel">{{ __('ui.quotations.cancel') }}</x-ui.button>
        </div>
    </x-ui.card>
</form>
