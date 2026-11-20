@extends('layouts.app')

@section('title', __('ui.dashboard.title'))

@section('content')
    <x-ui.page-header :title="__('ui.dashboard.welcome', ['name' => auth()->user()->name])" :description="__('ui.dashboard.intro')" />

    <div class="grid gap-4 sm:grid-cols-3">
        <x-ui.card class="p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-muted">{{ __('ui.dashboard.portal') }}</p>
            <p class="mt-2 text-lg font-semibold">{{ auth()->user()->portal->label() }}</p>
        </x-ui.card>
        <x-ui.card class="p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-muted">{{ __('ui.dashboard.language') }}</p>
            <p class="mt-2 text-lg font-semibold">{{ config('paryavaran.locales.'.app()->getLocale().'.native') }}</p>
        </x-ui.card>
        @if ($counts)
            <x-ui.card class="p-5">
                <p class="text-xs font-medium uppercase tracking-wide text-muted">{{ __('ui.dashboard.active_users') }}</p>
                <p class="mt-2 text-lg font-semibold">{{ $counts['active'] }} <span class="text-sm font-normal text-muted">/ {{ $counts['total'] }}</span></p>
            </x-ui.card>
        @endif
    </div>

    <x-ui.card class="mt-6">
        <div class="border-b border-line px-5 py-4">
            <h2 class="text-sm font-semibold">{{ __('ui.dashboard.recent_activity') }}</h2>
        </div>
        @if ($activity->isEmpty())
            <x-ui.empty-state :title="__('ui.dashboard.no_activity')" />
        @else
            <ul class="divide-y divide-line">
                @foreach ($activity as $log)
                    <li class="flex items-center justify-between gap-4 px-5 py-3 text-sm">
                        <span>{{ $log->label() }}</span>
                        <time class="text-muted" datetime="{{ $log->created_at?->toIso8601String() }}">{{ $log->created_at?->timezone(config('app.timezone'))->format('d-m-Y H:i') }}</time>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.card>
@endsection
