<?php

namespace App\Policies;

use App\Models\FeeSchedule;
use App\Models\User;

class FeeSchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('fees.view');
    }

    public function view(User $user, FeeSchedule $schedule): bool
    {
        return $user->hasPermission('fees.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('fees.manage');
    }

    public function update(User $user, FeeSchedule $schedule): bool
    {
        return $user->hasPermission('fees.manage');
    }

    public function delete(User $user, FeeSchedule $schedule): bool
    {
        return false;
    }
}
