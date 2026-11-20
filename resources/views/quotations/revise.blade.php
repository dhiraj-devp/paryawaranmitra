@php $portal = auth()->user()->portal->value; @endphp

@extends('layouts.app')

@section('title', __('ui.quotations.revise'))

@section('content')
    <p class="mb-4 text-sm"><a href="{{ route($portal.'.quotations.show', $quotation) }}" class="text-forest-800 hover:underline">{{ $quotation->quotation_number }}</a></p>

    <x-ui.page-header :title="__('ui.quotations.revise')" :description="$quotation->quotation_number" />

    @include('quotations._form', [
        'action' => route($portal.'.quotations.revisions.store', $quotation),
        'submitLabel' => __('ui.quotations.save_revision'),
        'cancel' => route($portal.'.quotations.show', $quotation),
    ])
@endsection
