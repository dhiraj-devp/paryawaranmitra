@php $portal = auth()->user()->portal->value; @endphp

@extends('layouts.app')

@section('title', __('ui.work_orders.edit'))

@section('content')
    <x-ui.page-header :title="$workOrder->number" :description="__('ui.work_orders.edit_hint')" />
    <x-ui.card class="p-5">
        <form method="post" action="{{ route($portal.'.work-orders.update', $workOrder) }}" class="grid gap-4">
            @csrf
            @method('PUT')
            <x-ui.textarea id="work-description" name="work_description" :label="__('ui.work_orders.description')" required :error="$errors->first('work_description')">{{ old('work_description', $workOrder->work_description) }}</x-ui.textarea>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.input id="work-start" type="date" name="start_date" :label="__('ui.work_orders.start_date')" :value="old('start_date', $workOrder->start_date?->format('Y-m-d'))" required :error="$errors->first('start_date')" />
                <x-ui.input id="work-target" type="date" name="target_completion_date" :label="__('ui.work_orders.target_date')" :value="old('target_completion_date', $workOrder->target_completion_date?->format('Y-m-d'))" :error="$errors->first('target_completion_date')" />
            </div>
            <x-ui.textarea id="work-notes" name="internal_notes" :label="__('ui.work_orders.notes')" :error="$errors->first('internal_notes')">{{ old('internal_notes', $workOrder->internal_notes) }}</x-ui.textarea>
            <div class="flex items-center gap-3">
                <x-ui.button>{{ __('ui.work_orders.save') }}</x-ui.button>
                <a href="{{ route($portal.'.work-orders.show', $workOrder) }}" class="text-sm text-muted hover:underline">{{ __('ui.enquiries.cancel') }}</a>
            </div>
        </form>
    </x-ui.card>
@endsection
