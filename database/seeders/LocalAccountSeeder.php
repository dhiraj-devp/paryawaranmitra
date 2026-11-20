<?php

namespace Database\Seeders;

use App\Actions\Access\AssignRole;
use App\Enums\Portal;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

class LocalAccountSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAccount(
            label: 'ADMIN',
            email: env('ADMIN_EMAIL'),
            password: env('ADMIN_PASSWORD'),
            name: env('ADMIN_NAME', 'Super Admin'),
            portal: Portal::Admin,
            roleSlug: 'super-admin',
        );
    }

    private function seedAccount(string $label, mixed $email, mixed $password, mixed $name, Portal $portal, mixed $roleSlug): void
    {
        $hasEmail = is_string($email) && trim($email) !== '';
        $hasPassword = is_string($password) && $password !== '';

        if (! $hasEmail && ! $hasPassword) {
            $this->command?->warn("{$label}_EMAIL and {$label}_PASSWORD are not set. Skipping the {$label} account.");

            return;
        }

        if (! $hasEmail || ! $hasPassword || ! is_string($roleSlug) || $roleSlug === '') {
            throw new RuntimeException("Set both {$label}_EMAIL and {$label}_PASSWORD, or leave both empty.");
        }

        $email = Str::lower(trim($email));
        $role = Role::query()->where('slug', $roleSlug)->first();

        if (! $role || $role->portal !== $portal) {
            throw new RuntimeException("{$label}_ROLE [{$roleSlug}] is not a {$portal->value} role.");
        }

        $user = User::query()->firstOrNew(['email' => $email]);

        if ($user->exists && $user->portal !== $portal) {
            throw new RuntimeException("{$email} already belongs to the {$user->portal->value} portal.");
        }

        if (! $user->exists) {
            Validator::make(
                ['password' => $password],
                ['password' => ['required', 'string', Password::defaults()]],
            )->validate();

            $user->password = $password;
        }

        $user->fill([
            'name' => is_string($name) && $name !== '' ? $name : $label,
            'portal' => $portal,
            'locale' => $user->locale ?? 'en',
            'is_active' => $user->exists ? $user->is_active : true,
        ]);
        $user->save();

        $user->load('roles');

        if (! $user->roles->contains('id', $role->id)) {
            app(AssignRole::class)->execute($user, $role);
        }
    }
}
