@extends('layouts.app')

@section('title', __('ui.users.title'))

@section('content')
    <x-ui.page-header :title="__('ui.users.title')" :description="__('ui.users.intro')">
        @can('create', App\Models\User::class)
            <x-slot:actions>
                <x-ui.button :href="route(auth()->user()->portal->value.'.users.create')">{{ __('ui.users.create') }}</x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <form method="get" class="mb-4 max-w-sm">
        <x-ui.input id="user-search" :label="__('ui.header.search')" type="search" name="q" value="{{ $search }}" placeholder="{{ __('ui.users.search_placeholder') }}" />
    </form>

    <x-ui.card class="w-full">
        @if ($users->isEmpty())
            <x-ui.empty-state :title="__('ui.users.empty')" />
        @else
            <x-ui.selectable>
            <x-ui.table>
                <x-slot:head>
                    <th class="px-3 py-3" x-show="picking" x-cloak></th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.table.serial') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.users.name') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.users.email') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.users.portal') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.users.roles') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('ui.users.status') }}</th>
                    <th class="px-4 py-3 text-right font-medium">{{ __('ui.table.actions') }}</th>
                </x-slot:head>
                @foreach ($users as $account)
                    <tr class="hover:bg-forest-50">
                        <td class="px-3 py-3" x-show="picking" x-cloak>
                            <input type="checkbox" value="{{ $account->id }}" x-on:change="toggle($event)" class="rounded border-line text-forest-700 focus:ring-forest-700/30" aria-label="{{ $account->name }}">
                        </td>
                        <td class="px-4 py-3 text-muted">{{ $users->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3 font-medium">{{ $account->name }}</td>
                        <td class="px-4 py-3">{{ $account->email }}</td>
                        <td class="px-4 py-3">{{ $account->portal->label() }}</td>
                        <td class="px-4 py-3">{{ $account->roles->map->label()->join(', ') }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge :tone="$account->is_active ? 'success' : 'neutral'">
                                {{ $account->is_active ? __('ui.users.active') : __('ui.users.inactive') }}
                            </x-ui.badge>
                        </td>
                        <td class="px-4 py-3">
                            @can('update', $account)
                                <x-ui.row-actions :edit="route(auth()->user()->portal->value.'.users.edit', $account)" />
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
            </x-ui.selectable>
            @if ($users->hasPages())
                <div class="border-t border-line px-4 py-3">{{ $users->links() }}</div>
            @endif
        @endif
    </x-ui.card>
@endsection
