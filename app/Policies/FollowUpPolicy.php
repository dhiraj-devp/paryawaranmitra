<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\Enquiry;
use App\Models\FollowUp;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FollowUpPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('follow-ups.view');
    }

    public function view(User $user, FollowUp $followUp): bool
    {
        return $user->hasPermission('follow-ups.view') && $this->canSeeParent($user, $this->parent($followUp));
    }

    public function create(User $user, ?Model $parent = null): bool
    {
        if (! $user->hasPermission('follow-ups.create')) {
            return false;
        }

        return $parent === null || $this->canSeeParent($user, $parent);
    }

    public function update(User $user, FollowUp $followUp): bool
    {
        return $user->hasPermission('follow-ups.edit') && $this->canSeeParent($user, $this->parent($followUp));
    }

    public function complete(User $user, FollowUp $followUp): bool
    {
        return $user->hasPermission('follow-ups.complete') && $this->canSeeParent($user, $this->parent($followUp));
    }

    public function delete(User $user, FollowUp $followUp): bool
    {
        return $user->hasPermission('follow-ups.delete') && $this->canSeeParent($user, $this->parent($followUp));
    }

    private function parent(FollowUp $followUp): ?Model
    {
        $followUp->loadMissing('followable');

        return $followUp->followable;
    }

    private function canSeeParent(User $user, ?Model $parent): bool
    {
        return $parent instanceof Enquiry || $parent instanceof Client
            ? $user->can('view', $parent)
            : false;
    }
}
