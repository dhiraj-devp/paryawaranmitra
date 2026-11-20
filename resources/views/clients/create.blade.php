@php $portal = auth()->user()->portal->value; @endphp

@extends('layouts.app')

@section('title', __('ui.clients.create'))

@section('content')
    <x-ui.page-header :title="__('ui.clients.create')" :description="__('ui.clients.intro')" />

    @include('clients._form', [
        'action' => route($portal.'.clients.store'),
        'method' => 'post',
        'cancel' => route($portal.'.clients.index'),
    ])
@endsection
