@php $portal = auth()->user()->portal->value; @endphp

@extends('layouts.app')

@section('title', $quotation->quotation_number.' '.$revision->label())

@section('content')
    <p class="mb-4 text-sm"><a href="{{ route($portal.'.quotations.show', $quotation) }}" class="text-forest-800 hover:underline">{{ $quotation->quotation_number }}</a></p>

    <x-ui.page-header :title="$quotation->quotation_number.' · '.$revision->label()" :description="__('ui.quotations.preview')">
        <x-slot:actions>
            @if ($quotation->accepted_revision_id === $revision->id)
                <x-ui.badge tone="success">{{ __('ui.quotations.accepted_mark') }}</x-ui.badge>
            @endif
            <x-ui.button variant="secondary" :href="route($portal.'.quotations.revisions.pdf', [$quotation, $revision])">{{ __('ui.quotations.download') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($taxPending)
        <x-ui.alert tone="warning" class="mb-4">{{ __('ui.quotations.tax_pending') }}</x-ui.alert>
    @endif

    <x-ui.card class="mb-4">
        <dl class="grid gap-4 p-5 sm:grid-cols-2">
            <x-ui.definition :label="__('ui.quotations.client')">{{ $quotation->workOrder?->client?->legal_name }}</x-ui.definition>
            <x-ui.definition :label="__('ui.quotations.unit')">{{ $quotation->workOrder?->industryUnit?->name }}</x-ui.definition>
            <x-ui.definition :label="__('ui.quotations.service')">{{ $quotation->workOrder?->service ? $quotation->workOrder->service->code.' — '.$quotation->workOrder->service->name : '' }}</x-ui.definition>
            <x-ui.definition :label="__('ui.quotations.work_order')">{{ $quotation->workOrder?->number }}</x-ui.definition>
            <x-ui.definition :label="__('ui.quotations.issue_date')">{{ $revision->issue_date?->format('d-m-Y') }}</x-ui.definition>
            <x-ui.definition :label="__('ui.quotations.valid_until')">{{ $revision->valid_until?->format('d-m-Y') }}</x-ui.definition>
        </dl>
    </x-ui.card>

    <x-ui.card>
        @include('quotations._lines', ['revision' => $revision])
    </x-ui.card>
@endsection
