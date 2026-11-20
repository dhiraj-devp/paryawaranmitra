@php $portal = auth()->user()->portal->value; @endphp

@extends('layouts.app')

@section('title', __('ui.quotations.title'))

@section('content')
    <x-ui.page-header :title="__('ui.quotations.title')" :description="__('ui.quotations.intro')">
        <x-slot:actions>
            @can('create', \App\Models\Quotation::class)
                <x-ui.button :href="route($portal.'.quotations.create')">{{ __('ui.quotations.create') }}</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <form method="get" class="mb-4 grid gap-4 sm:grid-cols-[minmax(0,20rem)_14rem_auto] sm:items-end">
        <x-ui.input id="quotation-search" :label="__('ui.header.search')" type="search" name="q" value="{{ $search }}" placeholder="{{ __('ui.quotations.search_placeholder') }}" />
        <x-ui.select id="quotation-status" name="status" :label="__('ui.status.label')">
            <option value="">{{ __('ui.status.all') }}</option>
            @foreach (\App\Enums\QuotationStatus::cases() as $option)
                <option value="{{ $option->value }}" @selected($status === $option->value)>{{ $option->label() }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.button variant="secondary">{{ __('ui.quotations.filter') }}</x-ui.button>
    </form>

    <x-ui.card class="w-full">
        @if ($quotations->isEmpty())
            <x-ui.empty-state :title="__('ui.quotations.empty')" />
        @else
            <x-ui.selectable>
            <x-ui.table>
                <x-slot:head>
                    <th class="px-3 py-3" x-show="picking" x-cloak></th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.table.serial') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.quotations.number') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.quotations.client') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.quotations.work_order') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.quotations.service') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.quotations.revision') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.quotations.total') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.status.label') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.quotations.issue_date') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.quotations.valid_until') }}</th>
                    <th class="px-4 py-3 text-right font-medium">{{ __('ui.table.actions') }}</th>
                </x-slot:head>
                @foreach ($quotations as $quotation)
                    <tr class="hover:bg-forest-50">
                        <td class="px-3 py-3" x-show="picking" x-cloak>
                            <input type="checkbox" value="{{ $quotation->id }}" x-on:change="toggle($event)" class="rounded border-line text-forest-700 focus:ring-forest-700/30" aria-label="{{ $quotation->quotation_number }}">
                        </td>
                        <td class="px-4 py-3 text-muted">{{ $quotations->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route($portal.'.quotations.show', $quotation) }}" class="font-medium text-forest-800 hover:underline">{{ $quotation->quotation_number }}</a>
                        </td>
                        <td class="px-4 py-3">{{ $quotation->workOrder?->client?->legal_name }}</td>
                        <td class="px-4 py-3">{{ $quotation->workOrder?->number }}</td>
                        <td class="px-4 py-3">{{ $quotation->workOrder?->service?->code }}</td>
                        <td class="px-4 py-3">{{ $quotation->currentRevision?->label() }}</td>
                        <td class="px-4 py-3">{{ \App\Support\Money::inr($quotation->currentRevision?->grand_total) }}</td>
                        <td class="px-4 py-3"><x-ui.badge :tone="$quotation->status->tone()">{{ $quotation->status->label() }}</x-ui.badge></td>
                        <td class="px-4 py-3">{{ $quotation->currentRevision?->issue_date?->format('d-m-Y') }}</td>
                        <td class="px-4 py-3">{{ $quotation->currentRevision?->valid_until?->format('d-m-Y') }}</td>
                        <td class="px-4 py-3">
                            <x-ui.row-actions
                                :view="route($portal.'.quotations.show', $quotation)"
                                :edit="auth()->user()->can('update', $quotation) ? route($portal.'.quotations.revise', $quotation) : null"
                            />
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
            </x-ui.selectable>
        @endif
    </x-ui.card>

    @if ($quotations->hasPages())
        <div class="mt-4">{{ $quotations->links() }}</div>
    @endif
@endsection
