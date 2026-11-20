@php $portal = auth()->user()->portal->value; @endphp

@extends('layouts.app')

@section('title', __('ui.services.edit'))

@section('content')
    <x-ui.page-header :title="__('ui.services.edit')" :description="$service->code" />

    @include('services._form', [
        'action' => route($portal.'.services.update', $service),
        'method' => 'put',
        'cancel' => route($portal.'.services.show', $service),
    ])
@endsection
