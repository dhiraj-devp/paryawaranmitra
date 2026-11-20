@extends('layouts.guest')

@section('title', __('ui.customer.title'))

@section('content')
    <x-ui.card class="mx-auto max-w-xl p-8">
        <h1 class="text-xl font-semibold">{{ __('ui.customer.title') }}</h1>
        <p class="mt-3 text-sm leading-6 text-muted">{{ __('ui.customer.body') }}</p>
        <x-ui.button :href="route('home')" variant="secondary" class="mt-6">{{ __('ui.customer.back') }}</x-ui.button>
    </x-ui.card>
@endsection
