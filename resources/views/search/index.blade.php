@extends('layouts.app')

@section('title', __('ui.search.title'))

@section('content')
    <x-ui.page-header :title="__('ui.search.title')" :description="__('ui.search.prompt')" />

    <form method="get" class="mb-4 max-w-xl">
        <x-ui.input id="search-page" :label="__('ui.header.search')" type="search" name="q" value="{{ $term }}" />
    </form>

    @if (mb_strlen($term) < 2)
        <x-ui.alert>{{ __('ui.search.too_short') }}</x-ui.alert>
    @elseif ($results->isEmpty())
        <x-ui.card>
            <x-ui.empty-state :title="__('ui.search.no_results')" />
        </x-ui.card>
    @else
        <x-ui.card>
            <ul class="divide-y divide-line">
                @foreach ($results as $result)
                    <li>
                        <a href="{{ $result->url }}" class="block px-5 py-4 hover:bg-forest-50">
                            <p class="text-xs font-medium uppercase tracking-wide text-muted">{{ $result->type }}</p>
                            <p class="mt-1 text-sm font-medium">{{ $result->title }}</p>
                            @if ($result->subtitle)
                                <p class="mt-1 text-sm text-muted">{{ $result->subtitle }}</p>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    @endif
@endsection
