@php
    $user = auth()->user();
    $portal = $user->portal;
    $groups = app(\App\Services\Navigation\NavigationBuilder::class)->groups($user, $portal);
@endphp

<aside id="app-sidebar" class="flex h-full min-h-0 flex-col border-r border-white/10 bg-forest-900 text-white" :class="collapsed ? 'is-collapsed' : ''">
    <div class="flex h-16 items-center gap-3 border-b border-white/10 px-3">
        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-forest-700 text-sm font-semibold text-white">PM</span>
        <span class="brand-name min-w-0">
            <span class="block truncate text-sm font-semibold text-white">{{ config('app.name') }}</span>
            <span class="block truncate text-xs text-forest-100">{{ $portal->label() }}</span>
        </span>
        <label for="nav-open" class="ml-auto inline-flex h-8 w-8 items-center justify-center rounded-md text-forest-100 hover:bg-white/10 lg:hidden">
            <span class="sr-only">{{ __('ui.a11y.close_menu') }}</span>
            <x-ui.icon name="x" class="h-4 w-4" />
        </label>
    </div>

    <nav class="min-h-0 flex-1 space-y-6 overflow-y-auto px-3 py-4" aria-label="{{ __('ui.nav.primary') }}">
        @foreach ($groups as $group)
            <div>
                <p class="nav-group px-3 text-xs font-semibold uppercase tracking-wide text-forest-100">{{ $group['label'] }}</p>
                <ul class="mt-1 space-y-1">
                    @foreach ($group['items'] as $item)
                        <li>
                            @if ($item['href'])
                                <a href="{{ $item['href'] }}" @if ($item['current']) aria-current="page" @endif class="nav-link flex items-center gap-3 rounded-md px-3 py-2 text-sm {{ $item['current'] ? 'bg-forest-700 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}" title="{{ $item['label'] }}">
                                    <x-ui.icon :name="$item['icon']" />
                                    <span class="nav-label truncate">{{ $item['label'] }}</span>
                                </a>
                            @else
                                <span class="nav-link flex items-center gap-3 rounded-md px-3 py-2 text-sm text-white/45" title="{{ $item['label'] }} — {{ __('ui.nav.later') }}">
                                    <x-ui.icon :name="$item['icon']" />
                                    <span class="nav-label truncate">{{ $item['label'] }}</span>
                                    <span class="nav-badge ml-auto text-xs">{{ __('ui.nav.later') }}</span>
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    <div class="border-t border-white/10 p-3">
        <button type="button" class="hidden h-9 w-full items-center justify-center rounded-md text-forest-100 hover:bg-white/10 lg:inline-flex" @click="toggle()" :aria-label="collapsed ? @js(__('ui.nav.expand')) : @js(__('ui.nav.collapse'))">
            <x-ui.icon name="menu" class="h-4 w-4" />
        </button>
    </div>
</aside>
