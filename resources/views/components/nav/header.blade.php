@php
    $user = auth()->user();
    $portal = $user->portal;
    $actions = app(\App\Services\Navigation\NavigationBuilder::class)->quickActions($user, $portal);
@endphp

<header class="sticky top-0 z-20 border-b border-line bg-white">
    <div class="flex h-16 items-center gap-3 px-4">
        <label for="nav-open" class="inline-flex h-9 w-9 items-center justify-center rounded-md text-ink hover:bg-forest-50 lg:hidden">
            <span class="sr-only">{{ __('ui.a11y.open_menu') }}</span>
            <x-ui.icon name="menu" class="h-5 w-5" />
        </label>

        <form method="get" action="{{ route($portal->value.'.search') }}" role="search" class="min-w-0 flex-1">
            <label for="global-search" class="sr-only">{{ __('ui.header.search') }}</label>
            <div class="relative max-w-xl">
                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-muted">
                    <x-ui.icon name="search" class="h-4 w-4" />
                </span>
                <input id="global-search" type="search" name="q" value="{{ request()->routeIs($portal->value.'.search') ? request('q') : '' }}" placeholder="{{ __('ui.header.search_placeholder') }}" class="w-full rounded-md border border-line bg-canvas py-2 pl-9 pr-3 text-sm text-ink placeholder:text-muted focus:border-forest-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-forest-700/20">
            </div>
        </form>

        <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape.window="open = false">
            <button type="button" class="inline-flex h-9 min-w-9 items-center justify-center gap-2 rounded-md bg-forest-700 px-3 text-sm font-medium text-white hover:bg-forest-800 focus:outline-none focus:ring-2 focus:ring-forest-700" x-on:click="open = !open" aria-haspopup="true" :aria-expanded="open">
                <x-ui.icon name="plus" class="h-4 w-4" />
                <span class="hidden text-center sm:inline">{{ __('ui.header.quick_create') }}</span>
            </button>
            <div x-show="open" x-cloak class="absolute right-0 z-30 mt-2 w-72 overflow-hidden rounded-lg border border-line bg-white shadow-lg">
                @forelse ($actions as $action)
                    <a href="{{ $action['href'] }}" class="flex items-center gap-3 border-b border-line px-3 py-2.5 text-sm text-ink last:border-b-0 hover:bg-forest-50">
                        <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-forest-50 text-forest-800">
                            <x-ui.icon :name="$action['icon']" class="h-4 w-4" />
                        </span>
                        <span class="min-w-0 flex-1 text-center">{{ $action['label'] }}</span>
                    </a>
                @empty
                    <p class="px-3 py-3 text-center text-sm text-muted">{{ __('ui.header.no_quick_actions') }}</p>
                @endforelse
            </div>
        </div>

        <x-nav.language-switcher context="header" />

        <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape.window="open = false">
            <button type="button" class="inline-flex items-center gap-2 rounded-md px-1.5 py-1 hover:bg-forest-50 focus:outline-none focus:ring-2 focus:ring-forest-700" x-on:click="open = !open" aria-haspopup="true" :aria-expanded="open" aria-label="{{ __('ui.account.menu') }}">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-forest-700 text-white">
                    <x-ui.icon name="user" class="h-4 w-4" />
                </span>
                <span class="hidden max-w-32 truncate text-sm font-medium text-ink sm:inline">{{ $user->name }}</span>
            </button>
            <div x-show="open" x-cloak class="absolute right-0 z-30 mt-2 w-60 overflow-hidden rounded-lg border border-line bg-white shadow-lg">
                <div class="border-b border-line px-3 py-2.5">
                    <p class="truncate text-sm font-medium text-ink">{{ $user->name }}</p>
                    <p class="truncate text-xs text-muted">{{ $portal->label() }}</p>
                </div>
                <a href="{{ route($portal->value.'.profile.edit') }}" class="flex items-center gap-3 border-b border-line px-3 py-2.5 text-sm text-ink hover:bg-forest-50">
                    <x-ui.icon name="user" class="h-4 w-4 text-forest-800" />
                    {{ __('ui.profile.title') }}
                </a>
                @if ($user->hasPermission('users.view'))
                    <a href="{{ route($portal->value.'.users.index') }}" class="flex items-center gap-3 border-b border-line px-3 py-2.5 text-sm text-ink hover:bg-forest-50">
                        <x-ui.icon name="users" class="h-4 w-4 text-forest-800" />
                        {{ __('ui.account.manage') }}
                    </a>
                @endif
                <form method="post" action="{{ route($portal->value.'.logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 px-3 py-2.5 text-left text-sm text-red-700 hover:bg-red-50">
                        <x-ui.icon name="logout" class="h-4 w-4" />
                        {{ __('ui.auth.logout') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
