@php $portal = auth()->user()->portal->value; @endphp

@extends('layouts.app')

@section('title', __('ui.work_orders.title'))

@section('content')
    <x-ui.page-header :title="__('ui.work_orders.title')" :description="__('ui.work_orders.intro')" />

    <form method="get" class="mb-4 grid gap-4 sm:grid-cols-[minmax(0,20rem)_14rem_auto] sm:items-end">
        <x-ui.input id="work-order-search" :label="__('ui.header.search')" type="search" name="q" value="{{ $search }}" placeholder="{{ __('ui.work_orders.search_placeholder') }}" />
        <x-ui.select id="work-order-status" name="status" :label="__('ui.status.label')">
            <option value="">{{ __('ui.status.all') }}</option>
            @foreach (\App\Enums\WorkOrderStatus::cases() as $option)
                <option value="{{ $option->value }}" @selected($status === $option->value)>{{ $option->label() }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.button variant="secondary">{{ __('ui.work_orders.filter') }}</x-ui.button>
    </form>

    <x-ui.card class="w-full">
        @if ($workOrders->isEmpty())
            <x-ui.empty-state :title="__('ui.work_orders.empty')" />
        @else
            <x-ui.selectable>
            <x-ui.table>
                <x-slot:head>
                    <th class="px-3 py-3" x-show="picking" x-cloak></th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.table.serial') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.work_orders.number') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.work_orders.client') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.work_orders.unit') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.work_orders.service') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.work_orders.assignee') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.status.label') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.work_orders.start_date') }}</th>
                    <th class="px-4 py-3 text-right font-medium">{{ __('ui.table.actions') }}</th>
                </x-slot:head>
                @foreach ($workOrders as $workOrder)
                    <tr class="hover:bg-forest-50">
                        <td class="px-3 py-3" x-show="picking" x-cloak>
                            <input type="checkbox" value="{{ $workOrder->id }}" x-on:change="toggle($event)" class="rounded border-line text-forest-700 focus:ring-forest-700/30" aria-label="{{ $workOrder->number }}">
                        </td>
                        <td class="px-4 py-3 text-muted">{{ $workOrders->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route($portal.'.work-orders.show', $workOrder) }}" class="font-medium text-forest-800 hover:underline">{{ $workOrder->number }}</a>
                        </td>
                        <td class="px-4 py-3">{{ $workOrder->client?->legal_name }}</td>
                        <td class="px-4 py-3">{{ $workOrder->industryUnit?->name }}</td>
                        <td class="px-4 py-3">{{ $workOrder->service?->name }}</td>
                        <td class="px-4 py-3">{{ $workOrder->assignee?->name ?: '—' }}</td>
                        <td class="px-4 py-3"><x-ui.badge :tone="$workOrder->status->tone()">{{ $workOrder->status->label() }}</x-ui.badge></td>
                        <td class="px-4 py-3">{{ $workOrder->start_date?->format('d-m-Y') }}</td>
                        <td class="px-4 py-3">
                            <x-ui.row-actions :view="route($portal.'.work-orders.show', $workOrder)" :edit="auth()->user()->can('update', $workOrder) ? route($portal.'.work-orders.edit', $workOrder) : null" />
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
            </x-ui.selectable>
            @if ($workOrders->hasPages())
                <div class="border-t border-line px-4 py-3">{{ $workOrders->links() }}</div>
            @endif
        @endif
    </x-ui.card>
@endsection
