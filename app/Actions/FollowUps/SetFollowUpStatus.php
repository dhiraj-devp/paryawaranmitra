<?php

namespace App\Actions\FollowUps;

use App\Enums\FollowUpStatus;
use App\Models\FollowUp;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\FollowUps\SyncEnquiryFollowUpDate;
use Illuminate\Support\Facades\DB;

class SetFollowUpStatus
{
    public function __construct(
        private AuditLogger $audit,
        private SyncEnquiryFollowUpDate $schedule,
    ) {}

    public function execute(FollowUp $followUp, FollowUpStatus $status, User $actor): FollowUp
    {
        return DB::transaction(function () use ($followUp, $status, $actor) {
            $previous = $followUp->status;
            $followUp->status = $status;

            if ($status === FollowUpStatus::Completed) {
                if ($previous !== FollowUpStatus::Completed || $followUp->completed_at === null) {
                    $followUp->completed_at = now();
                }
            } else {
                $followUp->completed_at = null;
            }

            $followUp->updated_by = $actor->id;
            $followUp->save();
            $followUp->loadMissing('followable');

            $action = match ($status) {
                FollowUpStatus::Completed => 'follow_up.completed',
                FollowUpStatus::Cancelled => 'follow_up.cancelled',
                FollowUpStatus::Pending => 'follow_up.reopened',
            };

            $this->audit->log(
                action: $action,
                subject: $followUp->followable,
                newValues: [
                    'follow_up_id' => $followUp->id,
                    'status' => $status->value,
                    'completed_at' => $followUp->completed_at?->toIso8601String(),
                ],
                oldValues: ['status' => $previous?->value],
                actor: $actor,
                portal: $actor->portal,
            );

            $this->schedule->sync($followUp->followable);

            return $followUp;
        });
    }
}
