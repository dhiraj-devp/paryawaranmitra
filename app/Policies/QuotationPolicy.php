<?php

namespace App\Policies;

use App\Models\Quotation;
use App\Models\User;

class QuotationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('quotations.view');
    }

    public function view(User $user, Quotation $quotation): bool
    {
        return $user->hasPermission('quotations.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('quotations.manage');
    }

    public function update(User $user, Quotation $quotation): bool
    {
        return $user->hasPermission('quotations.manage');
    }

    public function delete(User $user, Quotation $quotation): bool
    {
        return false;
    }
}
