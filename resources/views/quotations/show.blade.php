@php $portal = auth()->user()->portal->value; @endphp

@extends('layouts.app')

@section('title', $quotation->quotation_number)

@section('content')
    <p class="mb-4 text-sm"><a href="{{ route($portal.'.quotations.index') }}" class="text-forest-800 hover:underline">{{ __('ui.quotations.back') }}</a></p>

    <x-ui.page-header :title="$quotation->quotation_number" :description="$quotation->workOrder?->client?->legal_name">
        <x-slot:actions>
            <x-ui.badge :tone="$quotation->status->tone()">{{ $quotation->status->label() }}</x-ui.badge>
            @can('update', $quotation)
                @if ($quotation->status->canRevise())
                    <x-ui.button variant="secondary" :href="route($portal.'.quotations.revise', $quotation)">{{ __('ui.quotations.revise') }}</x-ui.button>
                @endif
            @endcan
            @if ($quotation->currentRevision)
                <x-ui.button variant="secondary" :href="route($portal.'.quotations.revisions.pdf', [$quotation, $quotation->currentRevision])">{{ __('ui.quotations.download') }}</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    @if ($taxPending)
        <x-ui.alert tone="warning" class="mb-4">{{ __('ui.quotations.tax_pending') }}</x-ui.alert>
    @endif

    @if ($quotation->acceptedRevision && $quotation->accepted_revision_id !== $quotation->current_revision_id)
        <x-ui.alert class="mb-4">{{ __('ui.quotations.accepted_kept', ['accepted' => $quotation->acceptedRevision->label(), 'current' => $quotation->currentRevision?->label()]) }}</x-ui.alert>
    @endif

    <x-ui.card>
        <dl class="grid gap-4 p-5 sm:grid-cols-2">
            <x-ui.definition :label="__('ui.quotations.client')">{{ $quotation->workOrder?->client?->legal_name }}</x-ui.definition>
            <x-ui.definition :label="__('ui.quotations.unit')">{{ $quotation->workOrder?->industryUnit?->name }}</x-ui.definition>
            <x-ui.definition :label="__('ui.quotations.service')">{{ $quotation->workOrder?->service ? $quotation->workOrder->service->code.' — '.$quotation->workOrder->service->name : '' }}</x-ui.definition>
            @can('view', $quotation->workOrder)
                <x-ui.definition :label="__('ui.quotations.work_order')">
                    <a href="{{ route($portal.'.work-orders.show', $quotation->workOrder) }}" class="text-forest-800 hover:underline">{{ $quotation->workOrder->number }}</a>
                </x-ui.definition>
            @else
                <x-ui.definition :label="__('ui.quotations.work_order')">{{ $quotation->workOrder?->number }}</x-ui.definition>
            @endcan
            <x-ui.definition :label="__('ui.quotations.revision')">{{ $quotation->currentRevision?->label() }}</x-ui.definition>
            <x-ui.definition :label="__('ui.quotations.accepted_revision')">{{ $quotation->acceptedRevision?->label() }}</x-ui.definition>
            <x-ui.definition :label="__('ui.quotations.issue_date')">{{ $quotation->currentRevision?->issue_date?->format('d-m-Y') }}</x-ui.definition>
            <x-ui.definition :label="__('ui.quotations.valid_until')">{{ $quotation->currentRevision?->valid_until?->format('d-m-Y') }}</x-ui.definition>
        </dl>
    </x-ui.card>

    @if ($quotation->currentRevision)
        <h2 class="mt-8 mb-3 text-base font-semibold">{{ __('ui.quotations.lines') }}</h2>
        <x-ui.card>
            @include('quotations._lines', ['revision' => $quotation->currentRevision])
        </x-ui.card>

        @if ($quotation->currentRevision->payment_terms || $quotation->currentRevision->customer_notes || $quotation->currentRevision->terms_and_conditions || $quotation->currentRevision->internal_notes)
            <x-ui.card class="mt-4">
                <dl class="grid gap-4 p-5">
                    <x-ui.definition :label="__('ui.quotations.payment_terms')">{{ $quotation->currentRevision->payment_terms }}</x-ui.definition>
                    <x-ui.definition :label="__('ui.quotations.customer_notes')">{{ $quotation->currentRevision->customer_notes }}</x-ui.definition>
                    <x-ui.definition :label="__('ui.quotations.terms')">{{ $quotation->currentRevision->terms_and_conditions }}</x-ui.definition>
                    <x-ui.definition :label="__('ui.quotations.internal_notes')">{{ $quotation->currentRevision->internal_notes }}</x-ui.definition>
                </dl>
            </x-ui.card>
        @endif
    @endif

    @can('update', $quotation)
        @if ($quotation->status->next() !== [])
            <x-ui.card class="mt-4 p-5">
                <h2 class="text-sm font-semibold">{{ __('ui.quotations.change_status') }}</h2>
                <form method="post" action="{{ route($portal.'.quotations.status', $quotation) }}" class="mt-4 grid gap-4">
                    @csrf
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.select id="quotation-status-change" name="status" :label="__('ui.status.label')" :error="$errors->first('status')" required>
                            @foreach ($quotation->status->next() as $option)
                                <option value="{{ $option->value }}" @selected(old('status') === $option->value)>{{ $option->label() }}</option>
                            @endforeach
                        </x-ui.select>
                        <x-ui.input id="quotation-status-reason" name="reason" :label="__('ui.quotations.reason')" :value="old('reason')" :error="$errors->first('reason')" :hint="__('ui.quotations.reason_hint')" />
                    </div>
                    <label class="flex items-start gap-2 text-sm text-ink">
                        <input type="checkbox" name="confirm_status" value="1" class="mt-1" @checked(old('confirm_status'))>
                        <span>{{ __('ui.quotations.confirm_status') }}</span>
                    </label>
                    <div>
                        <x-ui.button variant="secondary">{{ __('ui.quotations.change_status') }}</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        @endif
    @endcan

    <h2 class="mt-8 mb-3 text-base font-semibold">{{ __('ui.quotations.history') }}</h2>
    <x-ui.card>
        @if ($quotation->revisions->count() < 2)
            <x-ui.empty-state :title="__('ui.quotations.one_revision')" />
        @else
            <div class="border-b border-line p-4">
                <a href="{{ route($portal.'.quotations.compare', $quotation) }}" class="text-sm font-medium text-forest-800 hover:underline">{{ __('ui.quotations.compare') }}</a>
            </div>
        @endif
        <x-ui.table>
            <x-slot:head>
                <th class="px-4 py-3 font-medium">{{ __('ui.quotations.revision') }}</th>
                <th class="px-4 py-3 font-medium">{{ __('ui.quotations.issue_date') }}</th>
                <th class="px-4 py-3 font-medium">{{ __('ui.quotations.valid_until') }}</th>
                <th class="px-4 py-3 font-medium">{{ __('ui.quotations.total') }}</th>
                <th class="px-4 py-3 font-medium"></th>
            </x-slot:head>
            @foreach ($quotation->revisions->sortByDesc('revision_number') as $revision)
                <tr>
                    <td class="px-4 py-3">
                        <a href="{{ route($portal.'.quotations.revisions.show', [$quotation, $revision]) }}" class="font-medium text-forest-800 hover:underline">{{ $revision->label() }}</a>
                        @if ($quotation->accepted_revision_id === $revision->id)
                            <span class="text-xs text-muted">{{ __('ui.quotations.accepted_mark') }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">{{ $revision->issue_date?->format('d-m-Y') }}</td>
                    <td class="px-4 py-3">{{ $revision->valid_until?->format('d-m-Y') }}</td>
                    <td class="px-4 py-3">{{ \App\Support\Money::inr($revision->grand_total) }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route($portal.'.quotations.revisions.pdf', [$quotation, $revision]) }}" class="text-forest-800 hover:underline">{{ __('ui.quotations.download') }}</a>
                    </td>
                </tr>
            @endforeach
        </x-ui.table>
    </x-ui.card>

    <h2 class="mt-8 mb-3 text-base font-semibold">{{ __('ui.quotations.activity') }}</h2>
    <x-ui.card>
        @if ($history->isEmpty())
            <x-ui.empty-state :title="__('ui.quotations.no_activity')" />
        @else
            <x-ui.table>
                <x-slot:head>
                    <th class="px-4 py-3 font-medium">{{ __('ui.audit.when') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.audit.actor') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.audit.action') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.quotations.reason') }}</th>
                </x-slot:head>
                @foreach ($history as $log)
                    <tr>
                        <td class="whitespace-nowrap px-4 py-3 text-muted">{{ $log->created_at?->timezone(config('app.timezone'))->format('d-m-Y H:i') }}</td>
                        <td class="px-4 py-3">{{ $log->actor?->name ?? __('ui.audit.system') }}</td>
                        <td class="px-4 py-3">{{ $log->label() }}</td>
                        <td class="px-4 py-3">{{ $log->new_values['reason'] ?? '' }}</td>
                    </tr>
                @endforeach
            </x-ui.table>
        @endif
    </x-ui.card>
@endsection
