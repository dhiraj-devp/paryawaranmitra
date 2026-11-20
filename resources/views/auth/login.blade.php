@extends('layouts.guest')

@section('title', $portal === \App\Enums\Portal::Admin ? __('ui.auth.admin_title') : __('ui.auth.staff_title'))

@section('fullscreen', '1')

@section('content')
    <div class="flex min-h-screen flex-col lg:flex-row">
        <section class="flex min-h-64 flex-col bg-forest-800 px-6 py-8 text-white sm:px-10 lg:min-h-screen lg:w-1/2 lg:px-14 lg:py-10">
            <a href="{{ route('home') }}" class="flex w-fit items-center gap-3">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-md bg-white/10 text-sm font-semibold">PM</span>
                <span class="text-sm font-semibold">{{ config('app.name') }}</span>
            </a>
            <div class="flex flex-1 items-center py-10">
                <div class="max-w-md">
                    <p class="text-sm text-forest-100">{{ __('ui.phase_note') }}</p>
                    <h1 class="mt-4 text-3xl font-semibold tracking-tight">{{ $portal === \App\Enums\Portal::Admin ? __('ui.auth.admin_title') : __('ui.auth.staff_title') }}</h1>
                    <p class="mt-4 text-sm leading-6 text-forest-100">
                        {{ $portal === \App\Enums\Portal::Admin ? __('ui.auth.admin_intro') : __('ui.auth.staff_intro') }}
                    </p>
                </div>
            </div>
        </section>
        <section class="flex flex-1 flex-col bg-white">
            <div class="flex justify-end px-6 py-4 sm:px-10">
                <x-nav.language-switcher context="guest" />
            </div>
            <div class="flex flex-1 items-center px-6 pb-12 sm:px-10 lg:px-16">
                <div class="mx-auto w-full max-w-md">
                    <livewire:auth.login-form :portal="$portal->value" />
                </div>
            </div>
        </section>
    </div>
@endsection
