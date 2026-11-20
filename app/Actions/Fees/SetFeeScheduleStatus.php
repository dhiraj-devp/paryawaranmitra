<?php

namespace App\Actions\Fees;

use App\Enums\RecordStatus;
use App\Models\FeeSchedule;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Fees\FeeRangeConflict;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SetFeeScheduleStatus
{
    public function __construct(
        private AuditLogger $audit,
        private FeeRangeConflict $ranges,
    ) {}

    public function execute(FeeSchedule $schedule, RecordStatus $status, User $actor): FeeSchedule
    {
        if ($schedule->status === $status) {
            return $schedule;
        }

        return DB::transaction(function () use ($schedule, $status, $actor) {
            if ($status === RecordStatus::Active && $this->ranges->overlaps(
                $schedule->service_id,
                $schedule->effective_from->toDateString(),
                $schedule->effective_until?->toDateString(),
                $schedule->id,
            )) {
                throw ValidationException::withMessages([
                    'status' => __('ui.fees.overlap'),
                ]);
            }

            $previous = $schedule->status?->value;
            $schedule->status = $status;
            $schedule->updated_by = $actor->id;
            $schedule->save();

            $this->audit->log(
                action: 'fee_schedule.status_changed',
                subject: $schedule,
                oldValues: ['status' => $previous],
                newValues: ['status' => $status->value],
                actor: $actor,
                portal: $actor->portal,
            );

            return $schedule;
        });
    }
}
