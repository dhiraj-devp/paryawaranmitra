@php $portal = auth()->user()->portal->value; @endphp

@extends('layouts.app')

@section('title', $workOrder->number)

@section('content')
    <p class="mb-3 text-sm"><a href="{{ route($portal.'.work-orders.index') }}" class="text-forest-800 hover:underline">{{ __('ui.work_orders.back') }}</a></p>

    <x-ui.page-header :title="$workOrder->number" :description="$workOrder->client?->legal_name">
        <x-slot:actions>
            <x-ui.badge :tone="$workOrder->status->tone()">{{ $workOrder->status->label() }}</x-ui.badge>
            @can('update', $workOrder)
                <x-ui.button variant="secondary" :href="route($portal.'.work-orders.edit', $workOrder)">{{ __('ui.work_orders.edit') }}</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-3 lg:grid-cols-2">
        <x-ui.card>
            <h2 class="border-b border-line px-4 py-2.5 text-sm font-semibold">{{ __('ui.work_orders.section_links') }}</h2>
            <dl class="grid gap-3 p-4 sm:grid-cols-2">
                <x-ui.definition :label="__('ui.work_orders.client')">{{ $workOrder->client?->legal_name }}</x-ui.definition>
                <x-ui.definition :label="__('ui.work_orders.unit')">{{ $workOrder->industryUnit?->name }}</x-ui.definition>
                <x-ui.definition :label="__('ui.work_orders.service')">{{ $workOrder->service ? $workOrder->service->code.' — '.$workOrder->service->name : '' }}</x-ui.definition>
                <x-ui.definition :label="__('ui.work_orders.assignee')">{{ $workOrder->assignee?->name }}</x-ui.definition>
                @can('view', $workOrder->enquiry)
                    <x-ui.definition :label="__('ui.work_orders.enquiry')">
                        <a href="{{ route($portal.'.enquiries.show', $workOrder->enquiry) }}" class="text-forest-800 hover:underline">{{ $workOrder->enquiry->number }}</a>
                    </x-ui.definition>
                @endcan
            </dl>
        </x-ui.card>

        <x-ui.card>
            <h2 class="border-b border-line px-4 py-2.5 text-sm font-semibold">{{ __('ui.work_orders.section_record') }}</h2>
            <dl class="grid gap-3 p-4 sm:grid-cols-2">
                <x-ui.definition :label="__('ui.work_orders.start_date')">{{ $workOrder->start_date?->format('d-m-Y') }}</x-ui.definition>
                <x-ui.definition :label="__('ui.work_orders.target_date')">{{ $workOrder->target_completion_date?->format('d-m-Y') }}</x-ui.definition>
                <div class="sm:col-span-2">
                    <x-ui.definition :label="__('ui.work_orders.description')">
                        @if (filled($workOrder->work_description))
                            <span class="whitespace-pre-line">{{ $workOrder->work_description }}</span>
                        @endif
                    </x-ui.definition>
                </div>
                <div class="sm:col-span-2">
                    <x-ui.definition :label="__('ui.work_orders.notes')">
                        @if (filled($workOrder->internal_notes))
                            <span class="whitespace-pre-line">{{ $workOrder->internal_notes }}</span>
                        @endif
                    </x-ui.definition>
                </div>
            </dl>
        </x-ui.card>
    </div>

    @can('update', $workOrder)
        <div class="mt-3 grid gap-3 lg:grid-cols-2">
            <x-ui.card class="p-4">
                <h2 class="text-sm font-semibold">{{ __('ui.status.change') }}</h2>
                <form method="post" action="{{ route($portal.'.work-orders.status', $workOrder) }}" class="mt-3 grid gap-3">
                    @csrf
                    <x-ui.select id="work-order-status-change" name="status" :label="__('ui.status.label')" :error="$errors->first('status')">
                        @foreach (\App\Enums\WorkOrderStatus::cases() as $option)
                            <option value="{{ $option->value }}" @selected(old('status', $workOrder->status->value) === $option->value)>{{ $option->label() }}</option>
                        @endforeach
                    </x-ui.select>
                    <label class="flex items-start gap-2 text-sm text-ink">
                        <input type="checkbox" name="confirm_status" value="1" class="mt-0.5" @checked(old('confirm_status'))>
                        <span>{{ __('ui.status.confirm') }}</span>
                    </label>
                    <div>
                        <x-ui.button variant="secondary">{{ __('ui.status.change') }}</x-ui.button>
                    </div>
                </form>
            </x-ui.card>

            <x-ui.card class="p-4">
                <h2 class="text-sm font-semibold">{{ __('ui.work_orders.assign') }}</h2>
                <form method="post" action="{{ route($portal.'.work-orders.assign', $workOrder) }}" class="mt-3 grid gap-3">
                    @csrf
                    <x-ui.select id="work-order-assign" name="assigned_to" :label="__('ui.work_orders.assignee')" :error="$errors->first('assigned_to')">
                        <option value="">{{ __('ui.enquiries.unassigned') }}</option>
                        @foreach ($staff as $person)
                            <option value="{{ $person->id }}" @selected((string) old('assigned_to', $workOrder->assigned_to) === (string) $person->id)>{{ $person->name }}</option>
                        @endforeach
                    </x-ui.select>
                    <label class="flex items-start gap-2 text-sm text-ink">
                        <input type="checkbox" name="confirm_assignment" value="1" class="mt-0.5" @checked(old('confirm_assignment'))>
                        <span>{{ __('ui.enquiries.confirm_assignment') }}</span>
                    </label>
                    <div>
                        <x-ui.button variant="secondary">{{ __('ui.work_orders.assign') }}</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    @endcan

    @if (auth()->user()->hasPermission('quotations.view'))
        <x-ui.card class="mt-3">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-4 py-2.5">
                <h2 class="text-sm font-semibold">{{ __('ui.quotations.for_work_order') }}</h2>
                @can('create', \App\Models\Quotation::class)
                    <x-ui.button variant="secondary" :href="route($portal.'.quotations.create', ['work_order' => $workOrder->id])">{{ __('ui.quotations.create') }}</x-ui.button>
                @endcan
            </div>
            @if ($quotations->isEmpty())
                <x-ui.empty-state :title="__('ui.quotations.empty')" />
            @else
                <x-ui.table>
                    <x-slot:head>
                        <th class="px-4 py-2.5 font-medium">{{ __('ui.quotations.number') }}</th>
                        <th class="px-4 py-2.5 font-medium">{{ __('ui.quotations.revision') }}</th>
                        <th class="px-4 py-2.5 font-medium">{{ __('ui.quotations.total') }}</th>
                        <th class="px-4 py-2.5 font-medium">{{ __('ui.status.label') }}</th>
                    </x-slot:head>
                    @foreach ($quotations as $quotation)
                        <tr>
                            <td class="px-4 py-2.5">
                                <a href="{{ route($portal.'.quotations.show', $quotation) }}" class="font-medium text-forest-800 hover:underline">{{ $quotation->quotation_number }}</a>
                            </td>
                            <td class="px-4 py-2.5">{{ $quotation->currentRevision?->label() }}</td>
                            <td class="px-4 py-2.5">{{ \App\Support\Money::inr($quotation->currentRevision?->grand_total) }}</td>
                            <td class="px-4 py-2.5"><x-ui.badge :tone="$quotation->status->tone()">{{ $quotation->status->label() }}</x-ui.badge></td>
                        </tr>
                    @endforeach
                </x-ui.table>
            @endif
        </x-ui.card>
    @endif

    <x-ui.disclosure class="mt-3" :title="__('ui.work_orders.timeline')" :count="$history->count()" :open="$history->count() <= 6">
        @if ($history->isEmpty())
            <x-ui.empty-state :title="__('ui.work_orders.no_timeline')" />
        @else
            <x-ui.table>
                <x-slot:head>
                    <th class="px-4 py-2.5 font-medium">{{ __('ui.audit.when') }}</th>
                    <th class="px-4 py-2.5 font-medium">{{ __('ui.audit.actor') }}</th>
                    <th class="px-4 py-2.5 font-medium">{{ __('ui.audit.action') }}</th>
                </x-slot:head>
                @foreach ($history as $log)
                    <tr>
                        <td class="whitespace-nowrap px-4 py-2.5 text-muted">{{ $log->created_at?->timezone(config('app.timezone'))->format('d-m-Y H:i') }}</td>
                        <td class="px-4 py-2.5">{{ $log->actor?->name ?? __('ui.audit.system') }}</td>
                        <td class="px-4 py-2.5">{{ $log->label() }}</td>
                    </tr>
                @endforeach
            </x-ui.table>
        @endif
    </x-ui.disclosure>
@endsection
