@extends('layouts.guest')

@section('title', __('ui.errors.expired_title'))

@section('content')
    <x-ui.card class="mx-auto max-w-xl p-8">
        <h1 class="text-xl font-semibold">{{ __('ui.errors.expired_title') }}</h1>
        <p class="mt-3 text-sm text-muted">{{ __('ui.errors.expired_body') }}</p>
        <x-ui.button :href="route('home')" variant="secondary" class="mt-6">{{ __('ui.errors.home') }}</x-ui.button>
    </x-ui.card>
@endsection
