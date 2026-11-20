@php $portal = auth()->user()->portal->value; @endphp

@extends('layouts.app')

@section('title', __('ui.services.title'))

@section('content')
    <x-ui.page-header :title="__('ui.services.title')" :description="__('ui.services.intro')">
        @can('create', App\Models\Service::class)
            <x-slot:actions>
                <x-ui.button :href="route($portal.'.services.create')">{{ __('ui.services.create') }}</x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <form method="get" class="mb-4 grid gap-4 sm:grid-cols-[minmax(0,20rem)_12rem_12rem_auto] sm:items-end">
        <x-ui.input id="service-search" :label="__('ui.header.search')" type="search" name="q" value="{{ $search }}" placeholder="{{ __('ui.services.search_placeholder') }}" />
        <x-ui.select id="service-status" name="status" :label="__('ui.status.label')">
            <option value="">{{ __('ui.status.all') }}</option>
            @foreach (\App\Enums\RecordStatus::cases() as $option)
                <option value="{{ $option->value }}" @selected($status === $option->value)>{{ $option->label() }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.input id="service-category" :label="__('ui.services.category')" name="category" value="{{ $category }}" list="service-categories" />
        <datalist id="service-categories">
            @foreach ($categories as $option)
                <option value="{{ $option }}"></option>
            @endforeach
        </datalist>
        <x-ui.button variant="secondary">{{ __('ui.services.filter') }}</x-ui.button>
    </form>

    <x-ui.card class="w-full">
        @if ($services->isEmpty())
            <x-ui.empty-state :title="__('ui.services.empty')" />
        @else
            <x-ui.selectable>
            <x-ui.table>
                <x-slot:head>
                    <th class="px-3 py-3" x-show="picking" x-cloak></th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.table.serial') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.services.code') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.services.name') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.services.category') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.services.industries') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.status.label') }}</th>
                    <th class="px-4 py-3 text-right font-medium">{{ __('ui.table.actions') }}</th>
                </x-slot:head>
                @foreach ($services as $service)
                    <tr class="hover:bg-forest-50">
                        <td class="px-3 py-3" x-show="picking" x-cloak>
                            <input type="checkbox" value="{{ $service->id }}" x-on:change="toggle($event)" class="rounded border-line text-forest-700 focus:ring-forest-700/30" aria-label="{{ $service->name }}">
                        </td>
                        <td class="px-4 py-3 text-muted">{{ $services->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3">{{ $service->code }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route($portal.'.services.show', $service) }}" class="font-medium text-forest-800 hover:underline">{{ $service->name }}</a>
                        </td>
                        <td class="px-4 py-3">{{ $service->category }}</td>
                        <td class="px-4 py-3">{{ $service->industries_count }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge :tone="$service->status === \App\Enums\RecordStatus::Active ? 'success' : 'neutral'">{{ $service->status->label() }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3">
                            <x-ui.row-actions :view="route($portal.'.services.show', $service)" :edit="auth()->user()->can('update', $service) ? route($portal.'.services.edit', $service) : null" />
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
            </x-ui.selectable>
            @if ($services->hasPages())
                <div class="border-t border-line px-4 py-3">{{ $services->links() }}</div>
            @endif
        @endif
    </x-ui.card>
@endsection
