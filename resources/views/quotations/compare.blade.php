@php $portal = auth()->user()->portal->value; @endphp

@extends('layouts.app')

@section('title', __('ui.quotations.compare'))

@section('content')
    <p class="mb-4 text-sm"><a href="{{ route($portal.'.quotations.show', $quotation) }}" class="text-forest-800 hover:underline">{{ $quotation->quotation_number }}</a></p>

    <x-ui.page-header :title="__('ui.quotations.compare')" :description="$quotation->quotation_number" />

    @if ($revisions->count() < 2)
        <x-ui.card>
            <x-ui.empty-state :title="__('ui.quotations.one_revision')" />
        </x-ui.card>
    @else
        <form method="get" class="mb-4 grid gap-4 sm:grid-cols-[14rem_14rem_auto] sm:items-end">
            <x-ui.select id="compare-from" name="from" :label="__('ui.quotations.earlier')">
                @foreach ($revisions as $revision)
                    <option value="{{ $revision->revision_number }}" @selected($from?->revision_number === $revision->revision_number)>{{ $revision->label() }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.select id="compare-to" name="to" :label="__('ui.quotations.later')">
                @foreach ($revisions as $revision)
                    <option value="{{ $revision->revision_number }}" @selected($to?->revision_number === $revision->revision_number)>{{ $revision->label() }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.button variant="secondary">{{ __('ui.quotations.compare') }}</x-ui.button>
        </form>

        <x-ui.card>
            <x-ui.table>
                <x-slot:head>
                    <th class="px-4 py-3 font-medium">{{ __('ui.quotations.description') }}</th>
                    <th class="px-4 py-3 font-medium">{{ $from?->label() }}</th>
                    <th class="px-4 py-3 font-medium">{{ $to?->label() }}</th>
                </x-slot:head>
                @foreach ($rows as $row)
                    @php
                        $leftTotal = $row['left']?->line_total;
                        $rightTotal = $row['right']?->line_total;
                        $changed = $leftTotal !== $rightTotal || ($row['left']?->description !== $row['right']?->description);
                    @endphp
                    <tr @class(['bg-amber-50' => $changed])>
                        <td class="px-4 py-3">{{ $row['right']->description ?? $row['left']?->description }}</td>
                        <td class="px-4 py-3">{{ $row['left'] ? \App\Support\Money::inr($row['left']->line_total) : '—' }}</td>
                        <td class="px-4 py-3">{{ $row['right'] ? \App\Support\Money::inr($row['right']->line_total) : '—' }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td class="px-4 py-3 font-medium">{{ __('ui.quotations.total') }}</td>
                    <td class="px-4 py-3">{{ \App\Support\Money::inr($from?->grand_total) }}</td>
                    <td class="px-4 py-3">{{ \App\Support\Money::inr($to?->grand_total) }}</td>
                </tr>
            </x-ui.table>
        </x-ui.card>
    @endif
@endsection
