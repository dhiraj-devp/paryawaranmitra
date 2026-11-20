<?php

namespace App\Actions\FollowUps;

use App\Enums\FollowUpStatus;
use App\Enums\FollowUpType;
use App\Models\FollowUp;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\FollowUps\SyncEnquiryFollowUpDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SaveFollowUp
{
    public function __construct(
        private AuditLogger $audit,
        private SyncEnquiryFollowUpDate $schedule,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Model $parent, array $attributes, User $actor, ?FollowUp $followUp = null): FollowUp
    {
        return DB::transaction(function () use ($parent, $attributes, $actor, $followUp) {
            $creating = $followUp === null;
            $followUp ??= new FollowUp;
            $previousReminder = $followUp->exists ? $followUp->reminder_at?->toIso8601String() : null;
            $status = FollowUpStatus::from($attributes['status']);
            $wasCompleted = $followUp->status === FollowUpStatus::Completed;

            $followUp->followable()->associate($parent);
            $followUp->type = FollowUpType::from($attributes['type']);
            $followUp->scheduled_at = Carbon::parse($attributes['scheduled_at']);
            $followUp->assigned_to = $attributes['assigned_to'] ?? null;
            $followUp->notes = $attributes['notes'];
            $followUp->reminder_at = filled($attributes['reminder_at'] ?? null)
                ? Carbon::parse($attributes['reminder_at'])
                : null;
            $followUp->status = $status;
            $this->applyCompletion($followUp, $wasCompleted, $status);

            $reminder = $followUp->reminder_at?->toIso8601String();

            if ($reminder !== $previousReminder) {
                $followUp->reminded_at = null;
            }

            if ($creating) {
                $followUp->created_by = $actor->id;
            }

            $followUp->updated_by = $actor->id;
            $followUp->save();

            $this->audit->log(
                action: $creating ? 'follow_up.created' : 'follow_up.updated',
                subject: $parent,
                newValues: [
                    'follow_up_id' => $followUp->id,
                    'type' => $followUp->type->value,
                    'scheduled_at' => $followUp->scheduled_at?->toIso8601String(),
                    'status' => $followUp->status->value,
                    'assigned_to' => $followUp->assigned_to,
                ],
                actor: $actor,
                portal: $actor->portal,
            );

            $this->schedule->sync($parent);

            return $followUp;
        });
    }

    private function applyCompletion(FollowUp $followUp, bool $wasCompleted, FollowUpStatus $status): void
    {
        if ($status !== FollowUpStatus::Completed) {
            $followUp->completed_at = null;

            return;
        }

        if ($wasCompleted && $followUp->completed_at !== null) {
            return;
        }

        $scheduled = $followUp->scheduled_at;
        $followUp->completed_at = $scheduled instanceof Carbon && $scheduled->lte(now())
            ? $scheduled
            : now();
    }
}
