@extends('layouts.app')

@section('title', __('ui.quotations.create'))

@section('content')
    <x-ui.page-header :title="__('ui.quotations.create')" :description="__('ui.quotations.create_intro')" />

    @include('quotations._form', [
        'action' => route(auth()->user()->portal->value.'.quotations.store'),
        'submitLabel' => __('ui.quotations.create'),
        'cancel' => route(auth()->user()->portal->value.'.quotations.index'),
    ])
@endsection
