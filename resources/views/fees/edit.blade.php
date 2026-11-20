@php $portal = auth()->user()->portal->value; @endphp

@extends('layouts.app')

@section('title', __('ui.fees.edit'))

@section('content')
    <x-ui.page-header :title="__('ui.fees.edit')" :description="$schedule->service->name" />

    @include('fees._form', [
        'action' => route($portal.'.fees.update', $schedule),
        'method' => 'put',
        'cancel' => route($portal.'.fees.show', $schedule),
    ])
@endsection
