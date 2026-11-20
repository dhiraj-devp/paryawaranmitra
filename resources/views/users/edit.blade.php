@extends('layouts.app')

@section('title', __('ui.users.edit'))

@section('content')
    <x-ui.page-header :title="__('ui.users.edit')" :description="$account->email" />
    <x-ui.card class="p-5">
        @include('users._form')
    </x-ui.card>
@endsection
