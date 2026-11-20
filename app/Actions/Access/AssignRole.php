<?php

namespace App\Actions\Access;

use App\Exceptions\PortalMismatchException;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditLogger;

class AssignRole
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(User $user, Role $role, ?User $actor = null): void
    {
        if ($role->portal !== $user->portal) {
            throw PortalMismatchException::forRole();
        }

        $user->roles()->syncWithoutDetaching([$role->id]);
        $user->clearPermissionCache();

        $this->audit->log(
            action: 'access.role_assigned',
            subject: $user,
            newValues: ['role' => $role->slug],
            actor: $actor,
            portal: $actor?->portal ?? $user->portal,
        );
    }
}
