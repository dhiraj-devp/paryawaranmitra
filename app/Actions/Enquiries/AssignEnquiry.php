<?php

namespace App\Actions\Enquiries;

use App\Models\Enquiry;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignEnquiry
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(Enquiry $enquiry, ?int $assigneeId, User $actor): Enquiry
    {
        if ($assigneeId !== null) {
            $assignee = User::query()->find($assigneeId);

            if (! $assignee || ! $assignee->is_active) {
                throw ValidationException::withMessages([
                    'assigned_to' => __('ui.enquiries.assignee_inactive'),
                ]);
            }
        }

        if ($enquiry->assigned_to === $assigneeId) {
            return $enquiry;
        }

        return DB::transaction(function () use ($enquiry, $assigneeId, $actor) {
            $previous = $enquiry->assigned_to;
            $enquiry->assigned_to = $assigneeId;
            $enquiry->updated_by = $actor->id;
            $enquiry->save();

            $this->audit->log(
                action: 'enquiry.assignment_changed',
                subject: $enquiry,
                oldValues: ['assigned_to' => $previous],
                newValues: ['assigned_to' => $assigneeId],
                actor: $actor,
                portal: $actor->portal,
            );

            return $enquiry;
        });
    }
}
