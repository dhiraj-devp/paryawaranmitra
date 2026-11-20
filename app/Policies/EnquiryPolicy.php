<?php

namespace App\Policies;

use App\Enums\EnquiryStatus;
use App\Models\Enquiry;
use App\Models\User;

class EnquiryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('enquiries.view');
    }

    public function view(User $user, Enquiry $enquiry): bool
    {
        return $user->hasPermission('enquiries.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('enquiries.manage');
    }

    public function update(User $user, Enquiry $enquiry): bool
    {
        return $user->hasPermission('enquiries.manage');
    }

    public function convert(User $user, Enquiry $enquiry): bool
    {
        return $enquiry->status !== EnquiryStatus::Converted
            && $user->hasPermission('work-orders.manage');
    }

    public function delete(User $user, Enquiry $enquiry): bool
    {
        return $user->hasPermission('enquiries.manage') && $enquiry->isDeletable();
    }
}
