@extends('layouts.app')

@section('title', __('ui.audit.title'))

@section('content')
    <x-ui.page-header :title="__('ui.audit.title')" :description="__('ui.audit.intro')" />

    <form method="get" class="mb-4 max-w-sm">
        <x-ui.input id="audit-search" :label="__('ui.header.search')" type="search" name="q" value="{{ $search }}" />
    </form>

    <x-ui.card class="w-full">
        @if ($logs->isEmpty())
            <x-ui.empty-state :title="__('ui.audit.empty')" />
        @else
            <x-ui.selectable>
            <x-ui.table>
                <x-slot:head>
                    <th class="px-3 py-3" x-show="picking" x-cloak></th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.table.serial') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.audit.when') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.audit.actor') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.audit.action') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.audit.ip') }}</th>
                </x-slot:head>
                @foreach ($logs as $log)
                    <tr>
                        <td class="px-3 py-3" x-show="picking" x-cloak>
                            <input type="checkbox" value="{{ $log->id }}" x-on:change="toggle($event)" class="rounded border-line text-forest-700 focus:ring-forest-700/30" aria-label="{{ $log->label() }}">
                        </td>
                        <td class="px-4 py-3 text-muted">{{ $logs->firstItem() + $loop->index }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-muted">{{ $log->created_at?->timezone(config('app.timezone'))->format('d-m-Y H:i') }}</td>
                        <td class="px-4 py-3">{{ $log->actor?->name ?? __('ui.audit.system') }}</td>
                        <td class="px-4 py-3">{{ $log->label() }}</td>
                        <td class="px-4 py-3 text-muted">{{ $log->ip_address }}</td>
                    </tr>
                @endforeach
            </x-ui.table>
            </x-ui.selectable>
            @if ($logs->hasPages())
                <div class="border-t border-line px-4 py-3">{{ $logs->links() }}</div>
            @endif
        @endif
    </x-ui.card>
@endsection
