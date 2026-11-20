@php $portal = auth()->user()->portal->value; @endphp

@extends('layouts.app')

@section('title', __('ui.enquiries.title'))

@section('content')
    <x-ui.page-header :title="__('ui.enquiries.title')" :description="__('ui.enquiries.intro')">
        @can('create', App\Models\Enquiry::class)
            <x-slot:actions>
                <x-ui.button type="button" x-on:click="$dispatch('open-panel', 'new-enquiry')">{{ __('ui.enquiries.create') }}</x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <form method="get" class="mb-4 grid gap-4 lg:grid-cols-[minmax(0,18rem)_11rem_11rem_11rem_auto] lg:items-end">
        <x-ui.input id="enquiry-search" :label="__('ui.header.search')" type="search" name="q" value="{{ $search }}" placeholder="{{ __('ui.enquiries.search_placeholder') }}" />
        <x-ui.select id="enquiry-filter-status" name="status" :label="__('ui.status.label')">
            <option value="">{{ __('ui.status.all') }}</option>
            @foreach (\App\Enums\EnquiryStatus::cases() as $option)
                <option value="{{ $option->value }}" @selected($status === $option->value)>{{ $option->label() }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.select id="enquiry-filter-source" name="source" :label="__('ui.enquiries.source')">
            <option value="">{{ __('ui.enquiries.all_sources') }}</option>
            @foreach (\App\Enums\EnquirySource::cases() as $option)
                <option value="{{ $option->value }}" @selected($source === $option->value)>{{ $option->label() }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.select id="enquiry-filter-follow-up" name="follow_up" :label="__('ui.enquiries.next_follow_up')">
            <option value="">{{ __('ui.enquiries.follow_up_all') }}</option>
            <option value="overdue" @selected($followUp === 'overdue')>{{ __('ui.enquiries.follow_up_overdue') }}</option>
            <option value="upcoming" @selected($followUp === 'upcoming')>{{ __('ui.enquiries.follow_up_upcoming') }}</option>
            <option value="none" @selected($followUp === 'none')>{{ __('ui.enquiries.follow_up_none') }}</option>
        </x-ui.select>
        <x-ui.button variant="secondary">{{ __('ui.enquiries.filter') }}</x-ui.button>
    </form>

    <x-ui.card class="w-full">
        @if ($enquiries->isEmpty())
            <x-ui.empty-state :title="__('ui.enquiries.empty')" />
        @else
            <x-ui.selectable :action="auth()->user()->hasPermission('enquiries.manage') ? route($portal.'.enquiries.destroy-many') : null">
            <x-ui.table>
                <x-slot:head>
                    <th class="px-3 py-3" x-show="picking" x-cloak></th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.table.serial') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.enquiries.number') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.enquiries.contact') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.enquiries.service') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.enquiries.assignee') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.status.label') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.enquiries.next_follow_up') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.enquiries.date') }}</th>
                    <th class="px-4 py-3 text-right font-medium">{{ __('ui.table.actions') }}</th>
                </x-slot:head>
                @foreach ($enquiries as $enquiry)
                    <tr class="hover:bg-forest-50">
                        <td class="px-3 py-3" x-show="picking" x-cloak>
                            @can('delete', $enquiry)
                                <input type="checkbox" value="{{ $enquiry->id }}" x-on:change="toggle($event)" class="rounded border-line text-forest-700 focus:ring-forest-700/30" aria-label="{{ $enquiry->number }}">
                            @endcan
                        </td>
                        <td class="px-4 py-3 text-muted">{{ $enquiries->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route($portal.'.enquiries.show', $enquiry) }}" class="font-medium text-forest-800 hover:underline">{{ $enquiry->number }}</a>
                        </td>
                        <td class="px-4 py-3">
                            {{ $enquiry->contact_name }}
                            @if ($enquiry->client)
                                <p class="text-xs text-muted">{{ $enquiry->client->legal_name }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $enquiry->service?->name ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $enquiry->assignee?->name ?: '—' }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge :tone="$enquiry->status->tone()">{{ $enquiry->status->label() }}</x-ui.badge>
                            @if ($enquiry->followUpIsOverdue())
                                <x-ui.badge tone="danger" class="ml-1">{{ __('ui.enquiries.overdue') }}</x-ui.badge>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $enquiry->next_follow_up_on?->format('d-m-Y') ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $enquiry->enquiry_date?->format('d-m-Y') }}</td>
                        <td class="px-4 py-3">
                            <x-ui.row-actions :view="route($portal.'.enquiries.show', $enquiry)" :edit="auth()->user()->can('update', $enquiry) ? route($portal.'.enquiries.edit', $enquiry) : null">
                                @can('delete', $enquiry)
                                    <form method="post" action="{{ route($portal.'.enquiries.destroy', $enquiry) }}" onsubmit="return confirm(@js(__('ui.table.confirm_delete')))">
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
            @if ($enquiries->hasPages())
                <div class="border-t border-line px-4 py-3">{{ $enquiries->links() }}</div>
            @endif
        @endif
    </x-ui.card>

    @can('create', App\Models\Enquiry::class)
        <x-ui.panel name="new-enquiry" :title="__('ui.enquiries.create')" variant="drawer" :wide="true" :open="$openEnquiry">
            @php
                $enquiry = new App\Models\Enquiry([
                    'enquiry_date' => today(),
                    'source' => App\Enums\EnquirySource::Phone,
                ]);
            @endphp
            @include('enquiries._form')
        </x-ui.panel>
    @endcan
@endsection
