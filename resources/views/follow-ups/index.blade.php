@php $portal = auth()->user()->portal->value; @endphp

@extends('layouts.app')

@section('title', __('ui.follow_ups.page_title'))

@section('content')
    <x-ui.page-header class="!mb-4" :title="__('ui.follow_ups.page_title')" :description="__('ui.follow_ups.intro')" />

    <form method="get" class="mb-4 grid gap-3 lg:grid-cols-6 lg:items-end">
        <x-ui.input id="follow-up-search" type="search" name="q" :label="__('ui.follow_ups.search')" :value="$search" />
        <x-ui.select id="follow-up-filter-status" name="status" :label="__('ui.follow_ups.status')">
            <option value="">{{ __('ui.follow_ups.all_statuses') }}</option>
            <option value="overdue" @selected($status === 'overdue')>{{ __('ui.follow_ups.overdue') }}</option>
            @foreach (\App\Enums\FollowUpStatus::cases() as $option)
                <option value="{{ $option->value }}" @selected($status === $option->value)>{{ $option->label() }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.select id="follow-up-filter-type" name="type" :label="__('ui.follow_ups.type')">
            <option value="">{{ __('ui.follow_ups.all_types') }}</option>
            @foreach (\App\Enums\FollowUpType::cases() as $option)
                <option value="{{ $option->value }}" @selected($type === $option->value)>{{ $option->label() }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.select id="follow-up-filter-staff" name="assigned_to" :label="__('ui.follow_ups.assignee')">
            <option value="">{{ __('ui.follow_ups.all_staff') }}</option>
            @foreach ($staff as $person)
                <option value="{{ $person->id }}" @selected($assignee === (string) $person->id)>{{ $person->name }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.select id="follow-up-filter-related" name="related" :label="__('ui.follow_ups.related')">
            <option value="">{{ __('ui.follow_ups.all_records') }}</option>
            <option value="enquiry" @selected($related === 'enquiry')>{{ __('ui.follow_ups.related_enquiry') }}</option>
            <option value="client" @selected($related === 'client')>{{ __('ui.follow_ups.related_client') }}</option>
        </x-ui.select>
        <x-ui.button variant="secondary">{{ __('ui.enquiries.filter') }}</x-ui.button>
    </form>

    <x-ui.card class="w-full">
        @if ($followUps->isEmpty())
            <p class="px-4 py-3 text-sm text-muted">{{ __('ui.follow_ups.empty') }}</p>
        @else
            <x-ui.selectable :action="auth()->user()->hasPermission('follow-ups.delete') ? route($portal.'.follow-ups.destroy-many') : null">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[48rem] text-left text-sm">
                        <thead class="bg-canvas text-xs font-medium text-muted">
                            <tr>
                                <th class="px-3 py-2" x-show="picking" x-cloak></th>
                                <th class="px-4 py-2 font-medium">{{ __('ui.table.serial') }}</th>
                                <th class="px-4 py-2 font-medium">{{ __('ui.follow_ups.col_when') }}</th>
                                <th class="px-4 py-2 font-medium">{{ __('ui.follow_ups.col_subject') }}</th>
                                <th class="px-4 py-2 font-medium">{{ __('ui.follow_ups.type') }}</th>
                                <th class="px-4 py-2 font-medium">{{ __('ui.follow_ups.col_owner') }}</th>
                                <th class="px-4 py-2 font-medium">{{ __('ui.follow_ups.status') }}</th>
                                <th class="px-4 py-2 text-right font-medium">{{ __('ui.table.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($followUps as $followUp)
                                @php
                                    $parentUrl = $followUp->followable instanceof \App\Models\Enquiry
                                        ? route($portal.'.enquiries.show', $followUp->followable)
                                        : ($followUp->followable instanceof \App\Models\Client ? route($portal.'.clients.show', $followUp->followable) : null);
                                    $subject = $followUp->followable instanceof \App\Models\Enquiry
                                        ? $followUp->followable->contact_name
                                        : ($followUp->followable?->legal_name ?? '—');
                                @endphp
                                <tr class="hover:bg-forest-50">
                                    <td class="px-3 py-2.5" x-show="picking" x-cloak>
                                        <input type="checkbox" value="{{ $followUp->id }}" x-on:change="toggle($event)" class="rounded border-line text-forest-700 focus:ring-forest-700/30" aria-label="{{ $subject }}">
                                    </td>
                                    <td class="px-4 py-2.5 text-muted">{{ $followUps->firstItem() + $loop->index }}</td>
                                    <td class="whitespace-nowrap px-4 py-2.5 align-top">
                                        <p>{{ $followUp->scheduled_at?->timezone(config('app.timezone'))->format('d-m-Y H:i') }}</p>
                                        @if ($followUp->isOverdue())
                                            <p class="text-xs font-medium text-red-700">{{ __('ui.follow_ups.overdue') }}</p>
                                        @endif
                                    </td>
                                    <td class="max-w-xs px-4 py-2.5 align-top">
                                        <p class="font-medium text-ink">
                                            @if ($parentUrl)
                                                <a href="{{ $parentUrl }}" class="hover:underline">{{ $subject }}</a>
                                            @else
                                                {{ $subject }}
                                            @endif
                                        </p>
                                        <p class="line-clamp-2 text-xs text-muted">{{ $followUp->notes }}</p>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2.5 align-top">{{ $followUp->type->label() }}</td>
                                    <td class="whitespace-nowrap px-4 py-2.5 align-top">{{ $followUp->assignee?->name ?: '—' }}</td>
                                    <td class="px-4 py-2.5 align-top">
                                        <x-ui.badge :tone="$followUp->displayTone()">{{ $followUp->displayStatus() }}</x-ui.badge>
                                    </td>
                                    <td class="px-4 py-2.5 align-top">
                                        <div class="flex items-center justify-end gap-1">
                                            @if ($parentUrl)
                                                <a href="{{ $parentUrl }}" title="{{ __('ui.table.view') }}" aria-label="{{ __('ui.table.view') }}" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-forest-800 hover:bg-forest-50 focus:outline-none focus:ring-2 focus:ring-forest-700">
                                                    <x-ui.icon name="eye" class="h-4 w-4" />
                                                </a>
                                            @endif
                                            @can('complete', $followUp)
                                                @if ($followUp->status === \App\Enums\FollowUpStatus::Pending)
                                                    <form method="post" action="{{ route($portal.'.follow-ups.complete', $followUp) }}">
                                                        @csrf
                                                        <input type="hidden" name="return_to" value="index">
                                                        <button type="submit" title="{{ __('ui.follow_ups.mark_done') }}" aria-label="{{ __('ui.follow_ups.mark_done') }}" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-green-700 hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-green-700">
                                                            <x-ui.icon name="check" class="h-4 w-4" />
                                                        </button>
                                                    </form>
                                                @endif
                                            @endcan
                                            @can('update', $followUp)
                                                <button type="button" title="{{ __('ui.follow_ups.edit') }}" aria-label="{{ __('ui.follow_ups.edit') }}" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-forest-800 hover:bg-forest-50 focus:outline-none focus:ring-2 focus:ring-forest-700" x-on:click="$dispatch('open-panel', 'follow-up-edit-{{ $followUp->id }}')">
                                                    <x-ui.icon name="pencil" class="h-4 w-4" />
                                                </button>
                                            @endcan
                                            @can('delete', $followUp)
                                                <form method="post" action="{{ route($portal.'.follow-ups.destroy', $followUp) }}" onsubmit="return confirm(@js(__('ui.follow_ups.confirm_delete')))">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="return_to" value="index">
                                                    <input type="hidden" name="confirm_delete" value="1">
                                                    <button type="submit" title="{{ __('ui.follow_ups.delete') }}" aria-label="{{ __('ui.follow_ups.delete') }}" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-red-700 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-700">
                                                        <x-ui.icon name="trash" class="h-4 w-4" />
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.selectable>
            <div class="border-t border-line px-4 py-3">{{ $followUps->links() }}</div>
        @endif
    </x-ui.card>

    @foreach ($followUps as $followUp)
        @can('update', $followUp)
            @if ($followUp->followable)
                <x-ui.panel name="follow-up-edit-{{ $followUp->id }}" :title="__('ui.follow_ups.edit')" variant="drawer" :open="(string) old('follow_up_id') === (string) $followUp->id">
                    @include('follow-ups._form', ['followUp' => $followUp, 'parent' => $followUp->followable, 'returnTo' => 'index', 'staff' => $staff])
                </x-ui.panel>
            @endif
        @endcan
    @endforeach
@endsection
