<?php

namespace App\Actions\FollowUps;

use App\Enums\FollowUpStatus;
use App\Models\FollowUp;
use App\Notifications\FollowUpReminder;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

class SendDueFollowUpReminders
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(): int
    {
        $sent = 0;

        $ids = FollowUp::query()
            ->where('status', FollowUpStatus::Pending)
            ->whereNotNull('reminder_at')
            ->where('reminder_at', '<=', now())
            ->whereNull('reminded_at')
            ->whereNotNull('assigned_to')
            ->orderBy('id')
            ->pluck('id');

        foreach ($ids as $id) {
            $delivered = DB::transaction(function () use ($id): bool {
                    $locked = FollowUp::query()
                        ->with('assignee')
                        ->whereKey($id)
                        ->whereNull('reminded_at')
                        ->lockForUpdate()
                        ->first();

                    if ($locked === null || $locked->assignee === null) {
                        return false;
                    }

                    $locked->assignee->notify(new FollowUpReminder($locked));
                    $locked->reminded_at = now();
                    $locked->save();

                    $this->audit->log(
                        action: 'follow_up.reminder_sent',
                        subject: $locked,
                        newValues: [
                            'assigned_to' => $locked->assigned_to,
                            'reminded_at' => $locked->reminded_at?->toIso8601String(),
                        ],
                        actor: $locked->assignee,
                        portal: $locked->assignee->portal,
                    );

                    return true;
                });

                if ($delivered) {
                    $sent++;
                }
        }

        return $sent;
    }
}
