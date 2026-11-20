@php
    $portal = auth()->user()->portal->value;
    $active = $schedule->status === \App\Enums\RecordStatus::Active;
@endphp

@extends('layouts.app')

@section('title', $schedule->service->name)

@section('content')
    <p class="mb-4 text-sm"><a href="{{ route($portal.'.fees.index') }}" class="text-forest-800 hover:underline">{{ __('ui.fees.back') }}</a></p>

    <x-ui.page-header :title="$schedule->service->name" :description="$schedule->service->code">
        <x-slot:actions>
            <x-ui.badge :tone="$active ? 'success' : 'neutral'">{{ $schedule->status->label() }}</x-ui.badge>
            <x-ui.badge tone="neutral">{{ __('ui.fees.coverage_'.$schedule->period()) }}</x-ui.badge>
            @can('update', $schedule)
                <x-ui.button variant="secondary" :href="route($portal.'.fees.edit', $schedule)">{{ __('ui.fees.edit') }}</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card>
        <dl class="grid gap-4 p-5 sm:grid-cols-2">
            <x-ui.definition :label="__('ui.fees.government')">{{ \App\Support\Money::inr($schedule->government_fee) }}</x-ui.definition>
            <x-ui.definition :label="__('ui.fees.consultancy')">{{ \App\Support\Money::inr($schedule->consultancy_fee) }}</x-ui.definition>
            <x-ui.definition :label="__('ui.fees.effective_from')">{{ $schedule->effective_from->format('d-m-Y') }}</x-ui.definition>
            <x-ui.definition :label="__('ui.fees.effective_until')">{{ $schedule->effective_until?->format('d-m-Y') ?: __('ui.fees.open_ended') }}</x-ui.definition>
        </dl>
    </x-ui.card>

    @if ($schedule->internal_notes)
        <x-ui.card class="mt-4 p-5">
            <h2 class="text-sm font-semibold">{{ __('ui.fees.notes') }}</h2>
            <p class="mt-2 whitespace-pre-line text-sm text-ink">{{ $schedule->internal_notes }}</p>
        </x-ui.card>
    @endif

    @can('view', $schedule->service)
        <p class="mt-4 text-sm"><a href="{{ route($portal.'.services.show', $schedule->service) }}" class="text-forest-800 hover:underline">{{ $schedule->service->code }} — {{ $schedule->service->name }}</a></p>
    @endcan

    @can('update', $schedule)
        <x-ui.card class="mt-4 p-5">
            <h2 class="text-sm font-semibold">{{ __('ui.status.change') }}</h2>
            <form method="post" action="{{ route($portal.'.fees.status', $schedule) }}" class="mt-4 grid gap-4 sm:grid-cols-[12rem_1fr_auto] sm:items-end">
                @csrf
                <x-ui.select id="fee-status-change" name="status" :label="__('ui.status.label')" :error="$errors->first('status')">
                    @foreach (\App\Enums\RecordStatus::cases() as $option)
                        <option value="{{ $option->value }}" @selected(old('status', $schedule->status->value) === $option->value)>{{ $option->label() }}</option>
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
