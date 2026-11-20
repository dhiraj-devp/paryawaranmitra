<?php

namespace App\Services\Access;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AccessCatalog
{
    public function sync(): void
    {
        DB::transaction(function () {
            $permissionIds = [];

            foreach (config('access.permissions') as $permission) {
                $record = Permission::query()->updateOrCreate(
                    ['slug' => $permission['slug']],
                    [
                        'name' => $permission['name'],
                        'module' => $permission['module'],
                        'description' => $permission['description'] ?? null,
                    ],
                );

                $permissionIds[$record->slug] = $record->id;
            }

            foreach (config('access.roles') as $slug => $role) {
                $record = Role::query()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $role['name'],
                        'portal' => $role['portal'],
                        'description' => $role['description'] ?? null,
                        'is_system' => true,
                    ],
                );

                $slugs = $role['permissions'] === '*'
                    ? array_keys($permissionIds)
                    : $role['permissions'];

                $ids = array_map(function (string $permissionSlug) use ($permissionIds) {
                    if (! isset($permissionIds[$permissionSlug])) {
                        throw new RuntimeException("Unknown permission [{$permissionSlug}].");
                    }

                    return $permissionIds[$permissionSlug];
                }, $slugs);

                $record->permissions()->sync($ids);
            }
        });
    }
}
