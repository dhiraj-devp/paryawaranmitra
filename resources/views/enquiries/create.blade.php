@extends('layouts.app')

@section('title', __('ui.enquiries.create'))

@section('content')
    <x-ui.page-header :title="__('ui.enquiries.create')" :description="__('ui.enquiries.intro')" />
    <x-ui.card class="p-5">
        @include('enquiries._form')
    </x-ui.card>
@endsection
