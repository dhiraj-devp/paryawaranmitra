<?php

namespace Tests\Concerns;

use App\Actions\Access\AssignRole;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;

trait InteractsWithAccess
{
    protected function seedAccess(): void
    {
        $this->seed(AccessControlSeeder::class);
    }

    protected function makeUser(string $roleSlug, array $overrides = []): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();

        $user = User::factory()->create(array_merge([
            'portal' => $role->portal,
        ], $overrides));

        app(AssignRole::class)->execute($user, $role);

        return $user->fresh();
    }
}
