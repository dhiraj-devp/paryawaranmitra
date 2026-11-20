@php
    $portal = auth()->user()->portal->value;
    $active = $client->status === \App\Enums\RecordStatus::Active;
@endphp

@extends('layouts.app')

@section('title', $client->legal_name)

@section('content')
    <p class="mb-4 text-sm"><a href="{{ route($portal.'.clients.index') }}" class="text-forest-800 hover:underline">{{ __('ui.clients.back') }}</a></p>

    <x-ui.page-header :title="$client->legal_name" :description="$client->trade_name">
        <x-slot:actions>
            <x-ui.badge :tone="$active ? 'success' : 'neutral'">{{ $client->status->label() }}</x-ui.badge>
            @can('update', $client)
                <x-ui.button variant="secondary" :href="route($portal.'.clients.edit', $client)">{{ __('ui.clients.edit') }}</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-4 lg:grid-cols-2">
        <x-ui.card>
            <dl class="grid gap-4 p-5 sm:grid-cols-2">
                <x-ui.definition :label="__('ui.clients.constitution')">{{ $client->constitution->label() }}</x-ui.definition>
                <x-ui.definition :label="__('ui.clients.pan')">{{ $client->pan }}</x-ui.definition>
                <x-ui.definition :label="__('ui.clients.gstin')">{{ $client->gstin }}</x-ui.definition>
                <x-ui.definition :label="__('ui.clients.contact_name')">{{ $client->contact_name }}</x-ui.definition>
                <x-ui.definition :label="__('ui.clients.phone')">{{ $client->phone }}</x-ui.definition>
                <x-ui.definition :label="__('ui.clients.email')">{{ $client->email }}</x-ui.definition>
            </dl>
        </x-ui.card>
        <x-ui.card>
            <dl class="grid gap-4 p-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-ui.definition :label="__('ui.clients.office')">{{ $client->address_line }}</x-ui.definition>
                </div>
                <x-ui.definition :label="__('ui.clients.city')">{{ $client->city }}</x-ui.definition>
                <x-ui.definition :label="__('ui.clients.district')">{{ $client->district }}</x-ui.definition>
                <x-ui.definition :label="__('ui.clients.state')">{{ $client->state }}</x-ui.definition>
                <x-ui.definition :label="__('ui.clients.pincode')">{{ $client->pincode }}</x-ui.definition>
            </dl>
        </x-ui.card>
    </div>

    @if ($client->notes)
        <x-ui.card class="mt-4 p-5">
            <h2 class="text-sm font-semibold">{{ __('ui.clients.notes') }}</h2>
            <p class="mt-2 whitespace-pre-line text-sm text-ink">{{ $client->notes }}</p>
        </x-ui.card>
    @endif

    @include('follow-ups._card', ['parent' => $client, 'records' => $client->followUpRecords, 'staff' => $staff, 'returnTo' => 'parent'])

    @can('update', $client)
        <x-ui.card class="mt-4 p-5">
            <h2 class="text-sm font-semibold">{{ __('ui.status.change') }}</h2>
            <form method="post" action="{{ route($portal.'.clients.status', $client) }}" class="mt-4 grid gap-4 sm:grid-cols-[12rem_1fr_auto] sm:items-end">
                @csrf
                <x-ui.select id="client-status-change" name="status" :label="__('ui.status.label')" :error="$errors->first('status')">
                    @foreach (\App\Enums\RecordStatus::cases() as $option)
                        <option value="{{ $option->value }}" @selected(old('status', $client->status->value) === $option->value)>{{ $option->label() }}</option>
                    @endforeach
                </x-ui.select>
                <label class="flex items-start gap-2 text-sm text-ink">
                    <input type="checkbox" name="confirm_status" value="1" class="mt-1" @checked(old('confirm_status'))>
                    <span>{{ __('ui.status.confirm') }}</span>
                </label>
                <x-ui.button variant="secondary">{{ __('ui.status.change') }}</x-ui.button>
            </form>
            @error('confirm_status')
                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
            @enderror
        </x-ui.card>
    @endcan

    <div class="mt-8 mb-3 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <h2 class="text-base font-semibold">{{ __('ui.clients.units') }}</h2>
        @can('create', App\Models\IndustryUnit::class)
            @if ($active)
                <x-ui.button :href="route($portal.'.clients.units.create', $client)">{{ __('ui.units.create') }}</x-ui.button>
            @else
                <p class="text-sm text-muted">{{ __('ui.units.client_inactive') }}</p>
            @endif
        @endcan
    </div>

    <form method="get" class="mb-4 grid gap-4 sm:grid-cols-[minmax(0,20rem)_12rem_auto] sm:items-end">
        <x-ui.input id="unit-search" :label="__('ui.header.search')" type="search" name="q" value="{{ $search }}" placeholder="{{ __('ui.units.search_placeholder') }}" />
        <x-ui.select id="unit-status" name="status" :label="__('ui.status.label')">
            <option value="">{{ __('ui.status.all') }}</option>
            @foreach (\App\Enums\RecordStatus::cases() as $option)
                <option value="{{ $option->value }}" @selected($status === $option->value)>{{ $option->label() }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.button variant="secondary">{{ __('ui.clients.filter') }}</x-ui.button>
    </form>

    <x-ui.card>
        @if ($units->isEmpty())
            <x-ui.empty-state :title="__('ui.units.empty')" />
        @else
            <x-ui.table>
                <x-slot:head>
                    <th class="px-4 py-3 font-medium">{{ __('ui.units.name') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.units.category') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.units.district') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.status.label') }}</th>
                </x-slot:head>
                @foreach ($units as $unit)
                    <tr class="hover:bg-forest-50">
                        <td class="px-4 py-3">
                            <a href="{{ route($portal.'.clients.units.show', [$client, $unit]) }}" class="font-medium text-forest-800 hover:underline">{{ $unit->name }}</a>
                        </td>
                        <td class="px-4 py-3">{{ $unit->categoryLabel() }}</td>
                        <td class="px-4 py-3">{{ $unit->site_district }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge :tone="$unit->status === \App\Enums\RecordStatus::Active ? 'success' : 'neutral'">{{ $unit->status->label() }}</x-ui.badge>
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
            @if ($units->hasPages())
                <div class="border-t border-line px-4 py-3">{{ $units->links() }}</div>
            @endif
        @endif
    </x-ui.card>

    <h2 class="mt-8 mb-3 text-base font-semibold">{{ __('ui.clients.history') }}</h2>
    <x-ui.card>
        @if ($history->isEmpty())
            <x-ui.empty-state :title="__('ui.clients.no_history')" />
        @else
            <x-ui.table>
                <x-slot:head>
                    <th class="px-4 py-3 font-medium">{{ __('ui.audit.when') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.audit.actor') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.audit.action') }}</th>
                </x-slot:head>
                @foreach ($history as $log)
                    <tr>
                        <td class="whitespace-nowrap px-4 py-3 text-muted">{{ $log->created_at?->timezone(config('app.timezone'))->format('d-m-Y H:i') }}</td>
                        <td class="px-4 py-3">{{ $log->actor?->name ?? __('ui.audit.system') }}</td>
                        <td class="px-4 py-3">{{ $log->label() }}</td>
                    </tr>
                @endforeach
            </x-ui.table>
        @endif
    </x-ui.card>
@endsection
