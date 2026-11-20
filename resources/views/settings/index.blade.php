@extends('layouts.app')

@section('title', __('ui.settings.title'))

@section('content')
    <x-ui.page-header :title="__('ui.settings.title')" :description="__('ui.settings.intro')" />

    <div class="mb-4">
        <x-ui.button :href="route('admin.settings.audit-logs')" variant="secondary">{{ __('ui.settings.audit_link') }}</x-ui.button>
    </div>

    <x-ui.card class="divide-y divide-line">
        @foreach ([
            __('ui.settings.environment') => $environment,
            __('ui.settings.timezone') => $timezone,
            __('ui.settings.locales') => collect($locales)->pluck('native')->join(', '),
            __('ui.settings.queue') => $queue,
            __('ui.settings.cache') => $cache,
            __('ui.settings.session') => $session,
            __('ui.settings.two_factor') => $twoFactor ? __('ui.settings.two_factor_on') : __('ui.settings.two_factor_off'),
            __('ui.settings.documents') => $documentsDisk,
        ] as $label => $value)
            <div class="grid gap-1 px-5 py-4 sm:grid-cols-3">
                <dt class="text-sm text-muted">{{ $label }}</dt>
                <dd class="text-sm font-medium sm:col-span-2">{{ $value }}</dd>
            </div>
        @endforeach
    </x-ui.card>
@endsection
