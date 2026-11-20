@php $portal = auth()->user()->portal->value; @endphp

@extends('layouts.app')

@section('title', __('ui.clients.edit'))

@section('content')
    <p class="mb-4 text-sm"><a href="{{ route($portal.'.clients.show', $client) }}" class="text-forest-800 hover:underline">{{ $client->legal_name }}</a></p>
    <x-ui.page-header :title="__('ui.clients.edit')" />

    @include('clients._form', [
        'action' => route($portal.'.clients.update', $client),
        'method' => 'put',
        'cancel' => route($portal.'.clients.show', $client),
    ])
@endsection
