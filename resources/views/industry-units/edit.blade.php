@php $portal = auth()->user()->portal->value; @endphp

@extends('layouts.app')

@section('title', __('ui.units.edit'))

@section('content')
    <p class="mb-4 text-sm">
        <a href="{{ route($portal.'.clients.show', $client) }}" class="text-forest-800 hover:underline">{{ $client->legal_name }}</a>
        <span class="text-muted"> / </span>
        <a href="{{ route($portal.'.clients.units.show', [$client, $unit]) }}" class="text-forest-800 hover:underline">{{ $unit->name }}</a>
    </p>
    <x-ui.page-header :title="__('ui.units.edit')" />

    @include('industry-units._form', [
        'action' => route($portal.'.clients.units.update', [$client, $unit]),
        'method' => 'put',
        'cancel' => route($portal.'.clients.units.show', [$client, $unit]),
    ])
@endsection
