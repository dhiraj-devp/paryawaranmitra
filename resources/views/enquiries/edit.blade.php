@extends('layouts.app')

@section('title', __('ui.enquiries.edit'))

@section('content')
    <x-ui.page-header :title="$enquiry->number" :description="__('ui.enquiries.edit')" />
    <x-ui.card class="p-5">
        @include('enquiries._form')
    </x-ui.card>
@endsection
