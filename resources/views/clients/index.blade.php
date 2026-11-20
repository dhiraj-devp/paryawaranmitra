@php $portal = auth()->user()->portal->value; @endphp

@extends('layouts.app')

@section('title', __('ui.clients.title'))

@section('content')
    <x-ui.page-header :title="__('ui.clients.title')" :description="__('ui.clients.intro')">
        @can('create', App\Models\Client::class)
            <x-slot:actions>
                <x-ui.button :href="route($portal.'.clients.create')">{{ __('ui.clients.create') }}</x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <form method="get" class="mb-4 grid gap-4 sm:grid-cols-[minmax(0,20rem)_12rem_auto] sm:items-end">
        <x-ui.input id="client-search" :label="__('ui.header.search')" type="search" name="q" value="{{ $search }}" placeholder="{{ __('ui.clients.search_placeholder') }}" />
        <x-ui.select id="client-status" name="status" :label="__('ui.status.label')">
            <option value="">{{ __('ui.status.all') }}</option>
            @foreach (\App\Enums\RecordStatus::cases() as $option)
                <option value="{{ $option->value }}" @selected($status === $option->value)>{{ $option->label() }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.button variant="secondary">{{ __('ui.clients.filter') }}</x-ui.button>
    </form>

    <x-ui.card class="w-full">
        @if ($clients->isEmpty())
            <x-ui.empty-state :title="__('ui.clients.empty')" />
        @else
            <x-ui.selectable :action="auth()->user()->hasPermission('clients.manage') ? route($portal.'.clients.destroy-many') : null">
            <x-ui.table>
                <x-slot:head>
                    <th class="px-3 py-3" x-show="picking" x-cloak></th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.table.serial') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.clients.legal_name') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.clients.district') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.clients.gstin') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.clients.units') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.status.label') }}</th>
                    <th class="px-4 py-3 text-right font-medium">{{ __('ui.table.actions') }}</th>
                </x-slot:head>
                @foreach ($clients as $client)
                    <tr class="hover:bg-forest-50">
                        <td class="px-3 py-3" x-show="picking" x-cloak>
                            @can('delete', $client)
                                <input type="checkbox" value="{{ $client->id }}" x-on:change="toggle($event)" class="rounded border-line text-forest-700 focus:ring-forest-700/30" aria-label="{{ $client->legal_name }}">
                            @endcan
                        </td>
                        <td class="px-4 py-3 text-muted">{{ $clients->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route($portal.'.clients.show', $client) }}" class="font-medium text-forest-800 hover:underline">{{ $client->legal_name }}</a>
                            @if ($client->trade_name)
                                <p class="text-xs text-muted">{{ $client->trade_name }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $client->district ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $client->gstin ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $client->industry_units_count }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge :tone="$client->status === \App\Enums\RecordStatus::Active ? 'success' : 'neutral'">{{ $client->status->label() }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3">
                            <x-ui.row-actions :view="route($portal.'.clients.show', $client)" :edit="auth()->user()->can('update', $client) ? route($portal.'.clients.edit', $client) : null">
                                @can('delete', $client)
                                    <form method="post" action="{{ route($portal.'.clients.destroy', $client) }}" onsubmit="return confirm(@js(__('ui.table.confirm_delete')))">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="confirm_delete" value="1">
                                        <button type="submit" title="{{ __('ui.table.delete') }}" aria-label="{{ __('ui.table.delete') }}" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-red-700 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-700">
                                            <x-ui.icon name="trash" class="h-4 w-4" />
                                        </button>
                                    </form>
                                @endcan
                            </x-ui.row-actions>
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
            </x-ui.selectable>
            @if ($clients->hasPages())
                <div class="border-t border-line px-4 py-3">{{ $clients->links() }}</div>
            @endif
        @endif
    </x-ui.card>
@endsection
