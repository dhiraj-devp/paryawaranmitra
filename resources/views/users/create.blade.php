@extends('layouts.app')

@section('title', __('ui.users.create'))

@section('content')
    <x-ui.page-header :title="__('ui.users.create')" :description="__('ui.users.form_intro')" />
    <x-ui.card class="p-5">
        @include('users._form')
    </x-ui.card>
@endsection
