<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkOrder;

class WorkOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('work-orders.view');
    }

    public function view(User $user, WorkOrder $workOrder): bool
    {
        return $user->hasPermission('work-orders.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('work-orders.manage');
    }

    public function update(User $user, WorkOrder $workOrder): bool
    {
        return $user->hasPermission('work-orders.manage');
    }

    public function delete(User $user, WorkOrder $workOrder): bool
    {
        return false;
    }
}
