@extends('layouts.guest')

@section('title', __('ui.home.heading'))

@section('content')
    <div class="mx-auto max-w-3xl">
        <h1 class="text-2xl font-semibold tracking-tight">{{ __('ui.home.heading') }}</h1>
        <p class="mt-2 text-sm text-muted">{{ __('ui.home.intro') }}</p>

        <div class="mt-8 grid gap-4 sm:grid-cols-3">
            <x-ui.card class="flex flex-col p-5">
                <h2 class="text-base font-semibold">{{ __('ui.home.admin_title') }}</h2>
                <p class="mt-2 flex-1 text-sm text-muted">{{ __('ui.home.admin_description') }}</p>
                <x-ui.button :href="route('admin.login')" variant="secondary" class="mt-4">{{ __('ui.home.open') }}</x-ui.button>
            </x-ui.card>
            <x-ui.card class="flex flex-col p-5">
                <h2 class="text-base font-semibold">{{ __('ui.home.staff_title') }}</h2>
                <p class="mt-2 flex-1 text-sm text-muted">{{ __('ui.home.staff_description') }}</p>
                <x-ui.button :href="route('staff.login')" class="mt-4">{{ __('ui.home.open') }}</x-ui.button>
            </x-ui.card>
            <x-ui.card class="flex flex-col p-5">
                <h2 class="text-base font-semibold">{{ __('ui.home.customer_title') }}</h2>
                <p class="mt-2 flex-1 text-sm text-muted">{{ __('ui.home.customer_description') }}</p>
                <x-ui.button :href="route('customer.login')" variant="secondary" class="mt-4">{{ __('ui.home.customer_unavailable') }}</x-ui.button>
            </x-ui.card>
        </div>
    </div>
@endsection
