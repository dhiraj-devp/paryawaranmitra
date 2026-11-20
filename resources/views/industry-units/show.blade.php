@php
    $portal = auth()->user()->portal->value;
    $active = $unit->status === \App\Enums\RecordStatus::Active;
@endphp

@extends('layouts.app')

@section('title', $unit->name)

@section('content')
    <p class="mb-4 text-sm"><a href="{{ route($portal.'.clients.show', $client) }}" class="text-forest-800 hover:underline">{{ $client->legal_name }}</a></p>

    <x-ui.page-header :title="$unit->name" :description="$client->legal_name">
        <x-slot:actions>
            <x-ui.badge :tone="$active ? 'success' : 'neutral'">{{ $unit->status->label() }}</x-ui.badge>
            @can('update', $unit)
                <x-ui.button variant="secondary" :href="route($portal.'.clients.units.edit', [$client, $unit])">{{ __('ui.units.edit') }}</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-4 lg:grid-cols-2">
        <x-ui.card>
            <h2 class="border-b border-line px-5 py-3 text-sm font-semibold">{{ __('ui.units.business') }}</h2>
            <dl class="grid gap-4 p-5">
                <x-ui.definition :label="__('ui.units.category')">{{ $unit->categoryLabel() }}</x-ui.definition>
                <x-ui.definition :label="__('ui.units.activity')">{{ $unit->activity }}</x-ui.definition>
            </dl>
        </x-ui.card>
        <x-ui.card>
            <h2 class="border-b border-line px-5 py-3 text-sm font-semibold">{{ __('ui.units.site') }}</h2>
            <dl class="grid gap-4 p-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-ui.definition :label="__('ui.units.site_address')">{{ $unit->site_address_line }}</x-ui.definition>
                </div>
                <x-ui.definition :label="__('ui.units.plot')">{{ $unit->plot_number }}</x-ui.definition>
                <x-ui.definition :label="__('ui.units.landmark')">{{ $unit->site_landmark }}</x-ui.definition>
                <x-ui.definition :label="__('ui.units.village')">{{ $unit->site_village }}</x-ui.definition>
                <x-ui.definition :label="__('ui.units.taluka')">{{ $unit->site_taluka }}</x-ui.definition>
                <x-ui.definition :label="__('ui.units.district')">{{ $unit->site_district }}</x-ui.definition>
                <x-ui.definition :label="__('ui.units.state')">{{ $unit->site_state }}</x-ui.definition>
                <x-ui.definition :label="__('ui.units.pincode')">{{ $unit->site_pincode }}</x-ui.definition>
            </dl>
        </x-ui.card>
    </div>

    <x-ui.card class="mt-4">
        <h2 class="border-b border-line px-5 py-3 text-sm font-semibold">{{ __('ui.units.site_contact') }}</h2>
        <dl class="grid gap-4 p-5 sm:grid-cols-2">
            <x-ui.definition :label="__('ui.units.contact_name')">{{ $unit->site_contact_name }}</x-ui.definition>
            <x-ui.definition :label="__('ui.units.contact_phone')">{{ $unit->site_contact_phone }}</x-ui.definition>
        </dl>
    </x-ui.card>

    @if ($unit->notes)
        <x-ui.card class="mt-4 p-5">
            <h2 class="text-sm font-semibold">{{ __('ui.clients.notes') }}</h2>
            <p class="mt-2 whitespace-pre-line text-sm">{{ $unit->notes }}</p>
        </x-ui.card>
    @endif

    @can('update', $unit)
        <x-ui.card class="mt-4 p-5">
            <h2 class="text-sm font-semibold">{{ __('ui.status.change') }}</h2>
            <form method="post" action="{{ route($portal.'.clients.units.status', [$client, $unit]) }}" class="mt-4 grid gap-4 sm:grid-cols-[12rem_1fr_auto] sm:items-end">
                @csrf
                <x-ui.select id="unit-status-change" name="status" :label="__('ui.status.label')" :error="$errors->first('status')">
                    @foreach (\App\Enums\RecordStatus::cases() as $option)
                        <option value="{{ $option->value }}" @selected(old('status', $unit->status->value) === $option->value)>{{ $option->label() }}</option>
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
@endsection
