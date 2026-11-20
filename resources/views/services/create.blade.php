@php $portal = auth()->user()->portal->value; @endphp

@extends('layouts.app')

@section('title', __('ui.services.create'))

@section('content')
    <x-ui.page-header :title="__('ui.services.create')" :description="__('ui.services.intro')" />

    @include('services._form', [
        'action' => route($portal.'.services.store'),
        'method' => 'post',
        'cancel' => route($portal.'.services.index'),
    ])
@endsection
