@php $portal = auth()->user()->portal->value; @endphp

@extends('layouts.app')

@section('title', __('ui.units.create'))

@section('content')
    <p class="mb-4 text-sm"><a href="{{ route($portal.'.clients.show', $client) }}" class="text-forest-800 hover:underline">{{ $client->legal_name }}</a></p>
    <x-ui.page-header :title="__('ui.units.create')" :description="__('ui.units.intro')" />

    @include('industry-units._form', [
        'action' => route($portal.'.clients.units.store', $client),
        'method' => 'post',
        'cancel' => route($portal.'.clients.show', $client),
    ])
@endsection
