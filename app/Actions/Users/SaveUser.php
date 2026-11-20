<?php

namespace App\Actions\Users;

use App\Enums\Portal;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaveUser
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array{name: string, email: string, portal: string, role_id: int, password?: string|null, is_active: bool}  $attributes
     */
    public function execute(User $user, array $attributes, User $actor): User
    {
        $role = Role::query()->findOrFail($attributes['role_id']);
        $portal = Portal::from($attributes['portal']);

        if ($role->portal !== $portal) {
            throw ValidationException::withMessages([
                'role_id' => __('ui.users.role_portal'),
            ]);
        }

        $this->guardAccount($user, $actor, (bool) $attributes['is_active'], $role);

        return DB::transaction(function () use ($user, $attributes, $actor, $role, $portal) {
            $creating = ! $user->exists;
            $previous = $creating ? [] : [
                'name' => $user->name,
                'email' => $user->email,
                'portal' => $user->portal?->value,
                'is_active' => $user->is_active,
                'role' => $user->roles()->pluck('slug')->join(', '),
            ];

            $user->fill([
                'name' => $attributes['name'],
                'email' => Str::lower($attributes['email']),
                'portal' => $portal,
                'is_active' => $attributes['is_active'],
                'locale' => $user->locale ?: 'en',
            ]);

            if (filled($attributes['password'] ?? null)) {
                $user->password = $attributes['password'];
            }

            $user->save();
            $user->roles()->sync([$role->id]);
            $user->clearPermissionCache();

            $this->audit->log(
                action: $creating ? 'user.created' : 'user.updated',
                subject: $user,
                oldValues: $previous,
                newValues: [
                    'name' => $user->name,
                    'email' => $user->email,
                    'portal' => $user->portal?->value,
                    'is_active' => $user->is_active,
                    'role' => $role->slug,
                ],
                actor: $actor,
                portal: $actor->portal,
            );

            return $user;
        });
    }

    private function guardAccount(User $user, User $actor, bool $active, Role $role): void
    {
        if ($user->is($actor) && ! $active) {
            throw ValidationException::withMessages([
                'is_active' => __('ui.users.self_inactive'),
            ]);
        }

        if (! $user->exists || ! $user->hasRole('super-admin')) {
            return;
        }

        $keepsAccess = $active && $role->slug === 'super-admin';

        if ($keepsAccess) {
            return;
        }

        $anotherAdmin = User::query()
            ->whereKeyNot($user->id)
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('slug', 'super-admin'))
            ->exists();

        if (! $anotherAdmin) {
            throw ValidationException::withMessages([
                'role_id' => __('ui.users.last_admin'),
            ]);
        }
    }
}
