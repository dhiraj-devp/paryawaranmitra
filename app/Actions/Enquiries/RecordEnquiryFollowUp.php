<?php

namespace App\Actions\Enquiries;

use App\Enums\FollowUpStatus;
use App\Enums\FollowUpType;
use App\Models\Enquiry;
use App\Models\EnquiryFollowUp;
use App\Models\FollowUp;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\FollowUps\SyncEnquiryFollowUpDate;
use Illuminate\Support\Facades\DB;

class RecordEnquiryFollowUp
{
    public function __construct(
        private AuditLogger $audit,
        private SyncEnquiryFollowUpDate $schedule,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Enquiry $enquiry, array $attributes, User $actor): EnquiryFollowUp
    {
        return DB::transaction(function () use ($enquiry, $attributes, $actor) {
            $followUp = new EnquiryFollowUp;
            $followUp->enquiry_id = $enquiry->id;
            $followUp->fill($attributes);
            $followUp->created_by = $actor->id;
            $followUp->created_at = now();
            $followUp->save();

            if ($followUp->next_follow_up_on !== null) {
                $enquiry->next_follow_up_on = $followUp->next_follow_up_on;
                $enquiry->updated_by = $actor->id;
                $enquiry->save();
            }

            $this->audit->log(
                action: 'enquiry.follow_up_created',
                subject: $enquiry,
                newValues: [
                    'follow_up_id' => $followUp->id,
                    'followed_up_at' => $followUp->followed_up_at?->toIso8601String(),
                    'method' => $followUp->method?->value,
                    'outcome' => $followUp->outcome,
                    'next_follow_up_on' => $followUp->next_follow_up_on?->toDateString(),
                ],
                actor: $actor,
                portal: $actor->portal,
            );

            $this->mirror($followUp, $enquiry, $actor);

            if ($followUp->next_follow_up_on !== null) {
                $this->rememberNext($enquiry, $followUp, $actor);
                $this->schedule->sync($enquiry);
            }

            return $followUp;
        });
    }

    private function mirror(EnquiryFollowUp $followUp, Enquiry $enquiry, User $actor): void
    {
        $record = new FollowUp([
            'type' => FollowUpType::fromMethod($followUp->method),
            'scheduled_at' => $followUp->followed_up_at,
            'assigned_to' => $actor->id,
            'notes' => $this->combinedNotes($followUp->notes, $followUp->outcome),
            'status' => FollowUpStatus::Completed,
            'completed_at' => $followUp->followed_up_at,
            'legacy_enquiry_follow_up_id' => $followUp->id,
            'import_key' => 'legacy-'.$followUp->id,
        ]);
        $record->followable()->associate($enquiry);
        $record->created_by = $actor->id;
        $record->updated_by = $actor->id;
        $record->save();
    }

    private function rememberNext(Enquiry $enquiry, EnquiryFollowUp $followUp, User $actor): void
    {
        $key = 'enquiry-next-'.$enquiry->id;
        $pending = FollowUp::query()->where('import_key', $key)->first() ?? new FollowUp(['import_key' => $key]);
        $pending->followable()->associate($enquiry);
        $pending->type = FollowUpType::fromMethod($followUp->method);
        $pending->scheduled_at = $followUp->next_follow_up_on->format('Y-m-d').' 09:00:00';
        $pending->assigned_to = $actor->id;
        $pending->notes = $followUp->outcome;
        $pending->status = FollowUpStatus::Pending;
        $pending->completed_at = null;
        $pending->created_by ??= $actor->id;
        $pending->updated_by = $actor->id;
        $pending->save();
    }

    private function combinedNotes(string $notes, ?string $outcome): string
    {
        $notes = trim($notes);
        $outcome = trim((string) $outcome);

        if ($outcome === '' || $outcome === $notes || str_contains($notes, $outcome)) {
            return $notes !== '' ? $notes : $outcome;
        }

        return trim($notes."\n".$outcome);
    }
}
