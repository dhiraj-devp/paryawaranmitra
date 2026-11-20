<?php

namespace App\Services\FollowUps;

use App\Enums\FollowUpStatus;
use App\Models\Enquiry;
use App\Models\FollowUp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class SyncEnquiryFollowUpDate
{
    public function sync(Model $parent): void
    {
        if (! $parent instanceof Enquiry) {
            return;
        }

        $next = FollowUp::query()
            ->where('followable_type', $parent->getMorphClass())
            ->where('followable_id', $parent->getKey())
            ->where('status', FollowUpStatus::Pending)
            ->orderBy('scheduled_at')
            ->value('scheduled_at');

        $parent->next_follow_up_on = $next === null
            ? null
            : Carbon::parse($next)->toDateString();
        $parent->save();
    }
}
