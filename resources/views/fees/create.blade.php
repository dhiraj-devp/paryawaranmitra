@php $portal = auth()->user()->portal->value; @endphp

@extends('layouts.app')

@section('title', __('ui.fees.create'))

@section('content')
    <x-ui.page-header :title="__('ui.fees.create')" :description="__('ui.fees.intro')" />

    @include('fees._form', [
        'action' => route($portal.'.fees.store'),
        'method' => 'post',
        'cancel' => route($portal.'.fees.index'),
    ])
@endsection
