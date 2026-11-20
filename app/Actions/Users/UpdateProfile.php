<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpdateProfile
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array{name: string, email: string, password?: string|null}  $attributes
     */
    public function execute(User $user, array $attributes): User
    {
        return DB::transaction(function () use ($user, $attributes) {
            $previous = [
                'name' => $user->name,
                'email' => $user->email,
            ];

            $user->fill([
                'name' => $attributes['name'],
                'email' => Str::lower($attributes['email']),
            ]);

            if (filled($attributes['password'] ?? null)) {
                $user->password = $attributes['password'];
            }

            $user->save();

            $this->audit->log(
                action: 'user.updated',
                subject: $user,
                oldValues: $previous,
                newValues: [
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                actor: $user,
                portal: $user->portal,
            );

            return $user;
        });
    }
}
