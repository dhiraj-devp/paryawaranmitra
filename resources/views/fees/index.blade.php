@php $portal = auth()->user()->portal->value; @endphp

@extends('layouts.app')

@section('title', __('ui.fees.title'))

@section('content')
    <x-ui.page-header :title="__('ui.fees.title')" :description="__('ui.fees.intro')">
        @can('create', App\Models\FeeSchedule::class)
            <x-slot:actions>
                <x-ui.button :href="route($portal.'.fees.create')">{{ __('ui.fees.create') }}</x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <x-ui.card class="mb-4 p-5">
        <h2 class="text-sm font-semibold">{{ __('ui.fees.lookup') }}</h2>
        <p class="mt-1 text-sm text-muted">{{ __('ui.fees.lookup_hint') }}</p>
        <form method="get" class="mt-4 grid gap-4 sm:grid-cols-[minmax(0,1fr)_12rem_auto] sm:items-end">
            <x-ui.select id="lookup-service" name="lookup_service" :label="__('ui.fees.service')">
                <option value="">{{ __('ui.fees.all_services') }}</option>
                @foreach ($services as $service)
                    <option value="{{ $service->id }}" @selected((string) request('lookup_service') === (string) $service->id)>{{ $service->code }} — {{ $service->name }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.input id="lookup-on" name="lookup_on" type="date" :label="__('ui.fees.lookup_date')" value="{{ request('lookup_on') }}" />
            <x-ui.button variant="secondary">{{ __('ui.fees.lookup_action') }}</x-ui.button>
        </form>
        @if ($lookupSubmitted)
            <div class="mt-4 border-t border-line pt-4">
                @if ($lookupService->status !== \App\Enums\RecordStatus::Active)
                    <x-ui.alert tone="warning">{{ __('ui.fees.inactive_lookup') }}</x-ui.alert>
                @elseif ($lookupSchedule === null)
                    <x-ui.alert>{{ __('ui.fees.none_configured') }}</x-ui.alert>
                @else
                    <dl class="grid gap-4 sm:grid-cols-2">
                        <x-ui.definition :label="__('ui.fees.government')">{{ \App\Support\Money::inr($lookupSchedule->government_fee) }}</x-ui.definition>
                        <x-ui.definition :label="__('ui.fees.consultancy')">{{ \App\Support\Money::inr($lookupSchedule->consultancy_fee) }}</x-ui.definition>
                        <x-ui.definition :label="__('ui.fees.effective_from')">{{ $lookupSchedule->effective_from->format('d-m-Y') }}</x-ui.definition>
                        <x-ui.definition :label="__('ui.fees.effective_until')">{{ $lookupSchedule->effective_until?->format('d-m-Y') ?: __('ui.fees.open_ended') }}</x-ui.definition>
                    </dl>
                    <p class="mt-3 text-sm"><a href="{{ route($portal.'.fees.show', $lookupSchedule) }}" class="text-forest-800 hover:underline">{{ $lookupService->code }} — {{ $lookupService->name }}</a></p>
                @endif
            </div>
        @endif
    </x-ui.card>

    <form method="get" class="mb-4 grid gap-4 sm:grid-cols-[minmax(0,16rem)_14rem_12rem_12rem_auto] sm:items-end">
        @if (request('lookup_service'))
            <input type="hidden" name="lookup_service" value="{{ request('lookup_service') }}">
            <input type="hidden" name="lookup_on" value="{{ request('lookup_on') }}">
        @endif
        <x-ui.input id="fee-search" :label="__('ui.header.search')" type="search" name="q" value="{{ $search }}" placeholder="{{ __('ui.fees.search_placeholder') }}" />
        <x-ui.select id="fee-service" name="service" :label="__('ui.fees.service')">
            <option value="">{{ __('ui.fees.all_services') }}</option>
            @foreach ($services as $service)
                <option value="{{ $service->id }}" @selected((string) $serviceId === (string) $service->id)>{{ $service->code }} — {{ $service->name }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.select id="fee-coverage" name="coverage" :label="__('ui.fees.coverage')">
            <option value="">{{ __('ui.fees.coverage_all') }}</option>
            <option value="current" @selected($coverage === 'current')>{{ __('ui.fees.coverage_current') }}</option>
            <option value="upcoming" @selected($coverage === 'upcoming')>{{ __('ui.fees.coverage_upcoming') }}</option>
            <option value="past" @selected($coverage === 'past')>{{ __('ui.fees.coverage_past') }}</option>
        </x-ui.select>
        <x-ui.select id="fee-status" name="status" :label="__('ui.status.label')">
            <option value="">{{ __('ui.status.all') }}</option>
            @foreach (\App\Enums\RecordStatus::cases() as $option)
                <option value="{{ $option->value }}" @selected($status === $option->value)>{{ $option->label() }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.button variant="secondary">{{ __('ui.fees.filter') }}</x-ui.button>
    </form>

    <x-ui.card class="w-full">
        @if ($schedules->isEmpty())
            <x-ui.empty-state :title="__('ui.fees.empty')" />
        @else
            <x-ui.selectable>
            <x-ui.table>
                <x-slot:head>
                    <th class="px-3 py-3" x-show="picking" x-cloak></th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.table.serial') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.fees.service') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.fees.effective_from') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.fees.effective_until') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.fees.government') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.fees.consultancy') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.fees.coverage') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.status.label') }}</th>
                    <th class="px-4 py-3 text-right font-medium">{{ __('ui.table.actions') }}</th>
                </x-slot:head>
                @foreach ($schedules as $schedule)
                    <tr class="hover:bg-forest-50">
                        <td class="px-3 py-3" x-show="picking" x-cloak>
                            <input type="checkbox" value="{{ $schedule->id }}" x-on:change="toggle($event)" class="rounded border-line text-forest-700 focus:ring-forest-700/30" aria-label="{{ $schedule->service->name }}">
                        </td>
                        <td class="px-4 py-3 text-muted">{{ $schedules->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route($portal.'.fees.show', $schedule) }}" class="font-medium text-forest-800 hover:underline">{{ $schedule->service->name }}</a>
                            <p class="text-xs text-muted">{{ $schedule->service->code }}</p>
                        </td>
                        <td class="px-4 py-3">{{ $schedule->effective_from->format('d-m-Y') }}</td>
                        <td class="px-4 py-3">{{ $schedule->effective_until?->format('d-m-Y') ?: __('ui.fees.open_ended') }}</td>
                        <td class="px-4 py-3">{{ \App\Support\Money::inr($schedule->government_fee) }}</td>
                        <td class="px-4 py-3">{{ \App\Support\Money::inr($schedule->consultancy_fee) }}</td>
                        <td class="px-4 py-3">{{ __('ui.fees.coverage_'.$schedule->period()) }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge :tone="$schedule->status === \App\Enums\RecordStatus::Active ? 'success' : 'neutral'">{{ $schedule->status->label() }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3">
                            <x-ui.row-actions :view="route($portal.'.fees.show', $schedule)" :edit="auth()->user()->can('update', $schedule) ? route($portal.'.fees.edit', $schedule) : null" />
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
            </x-ui.selectable>
            @if ($schedules->hasPages())
                <div class="border-t border-line px-4 py-3">{{ $schedules->links() }}</div>
            @endif
        @endif
    </x-ui.card>
@endsection
