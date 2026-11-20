<?php

namespace App\Actions\FollowUps;

use App\Models\FollowUp;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\FollowUps\SyncEnquiryFollowUpDate;
use Illuminate\Support\Facades\DB;

class DeleteFollowUp
{
    public function __construct(
        private AuditLogger $audit,
        private SyncEnquiryFollowUpDate $schedule,
    ) {}

    public function execute(FollowUp $followUp, User $actor): void
    {
        DB::transaction(function () use ($followUp, $actor) {
            $followUp->loadMissing('followable');
            $parent = $followUp->followable;

            $this->audit->log(
                action: 'follow_up.deleted',
                subject: $parent,
                oldValues: [
                    'follow_up_id' => $followUp->id,
                    'scheduled_at' => $followUp->scheduled_at?->toIso8601String(),
                    'status' => $followUp->status?->value,
                    'notes' => $followUp->notes,
                ],
                actor: $actor,
                portal: $actor->portal,
            );

            $followUp->delete();
            $this->schedule->sync($parent);
        });
    }
}
