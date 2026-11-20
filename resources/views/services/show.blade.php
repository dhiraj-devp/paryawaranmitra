@php
    $portal = auth()->user()->portal->value;
    $active = $service->status === \App\Enums\RecordStatus::Active;
@endphp

@extends('layouts.app')

@section('title', $service->name)

@section('content')
    <p class="mb-4 text-sm"><a href="{{ route($portal.'.services.index') }}" class="text-forest-800 hover:underline">{{ __('ui.services.back') }}</a></p>

    <x-ui.page-header :title="$service->name" :description="$service->code">
        <x-slot:actions>
            <x-ui.badge :tone="$active ? 'success' : 'neutral'">{{ $service->status->label() }}</x-ui.badge>
            @can('update', $service)
                <x-ui.button variant="secondary" :href="route($portal.'.services.edit', $service)">{{ __('ui.services.edit') }}</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-4 lg:grid-cols-2">
        <x-ui.card>
            <dl class="grid gap-4 p-5 sm:grid-cols-2">
                <x-ui.definition :label="__('ui.services.category')">{{ $service->category }}</x-ui.definition>
                <x-ui.definition :label="__('ui.services.days')">{{ $service->expected_processing_days }}</x-ui.definition>
                <div class="sm:col-span-2">
                    <x-ui.definition :label="__('ui.services.description')">{{ $service->description }}</x-ui.definition>
                </div>
            </dl>
        </x-ui.card>
        <x-ui.card class="p-5">
            <h2 class="text-sm font-semibold">{{ __('ui.services.eligibility') }}</h2>
            @if ($service->industries->isEmpty())
                <p class="mt-3 text-sm text-muted">{{ __('ui.services.eligibility_empty') }}</p>
            @else
                <ul class="mt-3 flex flex-wrap gap-2">
                    @foreach ($service->industries->sortBy('industry_category') as $industry)
                        <li><x-ui.badge>{{ __('ui.units.categories.'.$industry->industry_category) }}</x-ui.badge></li>
                    @endforeach
                </ul>
            @endif
            @can('viewAny', App\Models\FeeSchedule::class)
                <div class="mt-4 flex flex-wrap gap-2">
                    <x-ui.button variant="secondary" :href="route($portal.'.fees.index', ['service' => $service->id])">{{ __('ui.services.fees_link') }}</x-ui.button>
                    @can('create', App\Models\FeeSchedule::class)
                        @if ($active)
                            <x-ui.button :href="route($portal.'.fees.create', ['service' => $service->id])">{{ __('ui.services.new_fee') }}</x-ui.button>
                        @endif
                    @endcan
                </div>
            @endcan
        </x-ui.card>
    </div>

    @if ($service->detailed_description)
        <x-ui.card class="mt-4 p-5">
            <h2 class="text-sm font-semibold">{{ __('ui.services.detailed_description') }}</h2>
            <p class="mt-2 whitespace-pre-line text-sm text-ink">{{ $service->detailed_description }}</p>
        </x-ui.card>
    @endif

    @if ($service->internal_notes)
        <x-ui.card class="mt-4 p-5">
            <h2 class="text-sm font-semibold">{{ __('ui.services.notes') }}</h2>
            <p class="mt-2 whitespace-pre-line text-sm text-ink">{{ $service->internal_notes }}</p>
        </x-ui.card>
    @endif

    @unless ($active)
        <x-ui.alert class="mt-4" tone="warning">{{ __('ui.services.inactive_note') }}</x-ui.alert>
    @endunless

    @can('update', $service)
        <x-ui.card class="mt-4 p-5">
            <h2 class="text-sm font-semibold">{{ __('ui.status.change') }}</h2>
            <form method="post" action="{{ route($portal.'.services.status', $service) }}" class="mt-4 grid gap-4 sm:grid-cols-[12rem_1fr_auto] sm:items-end">
                @csrf
                <x-ui.select id="service-status-change" name="status" :label="__('ui.status.label')" :error="$errors->first('status')">
                    @foreach (\App\Enums\RecordStatus::cases() as $option)
                        <option value="{{ $option->value }}" @selected(old('status', $service->status->value) === $option->value)>{{ $option->label() }}</option>
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
