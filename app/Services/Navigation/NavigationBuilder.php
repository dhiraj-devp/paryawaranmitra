<?php

namespace App\Services\Navigation;

use App\Enums\Portal;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class NavigationBuilder
{
    /**
     * @return list<array{key: string, label: string, items: list<array{key: string, label: string, icon: string, href: ?string, current: bool}>}>
     */
    public function groups(User $user, Portal $portal): array
    {
        $groups = [];

        foreach (config('navigation.groups') as $group) {
            $items = [];

            foreach ($group['items'] as $item) {
                $permission = $item['permission'] ?? null;

                if (is_string($permission) && ! $user->hasPermission($permission)) {
                    continue;
                }

                $route = $item['route'] ?? null;
                $name = $route ? $portal->value.'.'.$route : null;
                $href = $name && Route::has($name) ? route($name) : null;

                $items[] = [
                    'key' => $item['key'],
                    'label' => __('ui.nav.items.'.$item['key']),
                    'icon' => $item['icon'],
                    'href' => $href,
                    'current' => $this->isCurrent($portal, $item['active'] ?? $route),
                ];
            }

            if ($items === []) {
                continue;
            }

            $groups[] = [
                'key' => $group['key'],
                'label' => __('ui.nav.groups.'.$group['key']),
                'items' => $items,
            ];
        }

        return $groups;
    }

    /**
     * @return list<array{label: string, href: string}>
     */
    public function quickActions(User $user, Portal $portal): array
    {
        $actions = [];

        foreach (config('navigation.quick_actions') as $action) {
            $portals = $action['portals'] ?? [];

            if (! in_array($portal->value, $portals, true)) {
                continue;
            }

            $permission = $action['permission'] ?? null;

            if (is_string($permission) && ! $user->hasPermission($permission)) {
                continue;
            }

            $name = $portal->value.'.'.$action['route'];

            if (! Route::has($name)) {
                continue;
            }

            $href = route($name);

            if (($action['route'] ?? null) === 'enquiries.create') {
                $href = route($portal->value.'.enquiries.index', ['new' => 1]);
            }

            $actions[] = [
                'label' => __($action['label']),
                'href' => $href,
                'icon' => $action['icon'] ?? 'plus',
            ];
        }

        return $actions;
    }

    private function isCurrent(Portal $portal, ?string $pattern): bool
    {
        if ($pattern === null) {
            return false;
        }

        return request()->routeIs($portal->value.'.'.$pattern);
    }
}
