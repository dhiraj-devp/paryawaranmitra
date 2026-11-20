<?php

namespace App\Policies;

use App\Models\IndustryUnit;
use App\Models\User;

class IndustryUnitPolicy
{
    public function view(User $user, IndustryUnit $unit): bool
    {
        return $user->hasPermission('clients.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('clients.manage');
    }

    public function update(User $user, IndustryUnit $unit): bool
    {
        return $user->hasPermission('clients.manage');
    }

    public function delete(User $user, IndustryUnit $unit): bool
    {
        return false;
    }
}
