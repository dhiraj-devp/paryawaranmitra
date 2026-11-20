@extends('layouts.app')

@section('title', __('ui.profile.title'))

@section('content')
    <x-ui.page-header :title="__('ui.profile.title')" :description="__('ui.profile.intro')" />
    <x-ui.card class="p-5">
        <form method="post" action="{{ route(auth()->user()->portal->value.'.profile.update') }}" class="grid max-w-xl gap-4">
            @csrf
            @method('PUT')

            <x-ui.input id="profile-name" name="name" :label="__('ui.users.name')" :value="old('name', $account->name)" required :error="$errors->first('name')" />
            <x-ui.input id="profile-email" type="email" name="email" :label="__('ui.users.email')" :value="old('email', $account->email)" required :error="$errors->first('email')" />
            <x-ui.input id="profile-current-password" type="password" name="current_password" :label="__('ui.profile.current_password')" autocomplete="current-password" :error="$errors->first('current_password')" />
            <x-ui.input id="profile-password" type="password" name="password" :label="__('ui.users.password')" autocomplete="new-password" :error="$errors->first('password')" />
            <x-ui.input id="profile-password-confirmation" type="password" name="password_confirmation" :label="__('ui.users.password_confirmation')" autocomplete="new-password" />
            <p class="-mt-2 text-sm text-muted">{{ __('ui.profile.password_hint') }}</p>

            <div class="flex items-center gap-3">
                <x-ui.button>{{ __('ui.profile.save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
