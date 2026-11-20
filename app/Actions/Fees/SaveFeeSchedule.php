<?php

namespace App\Actions\Fees;

use App\Enums\RecordStatus;
use App\Models\FeeSchedule;
use App\Models\Service;
use App\Models\User;
use App\Services\Audit\AuditDiff;
use App\Services\Audit\AuditLogger;
use App\Services\Fees\FeeRangeConflict;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveFeeSchedule
{
    public function __construct(
        private AuditLogger $audit,
        private FeeRangeConflict $ranges,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes, User $actor, Service $service, ?FeeSchedule $schedule = null): FeeSchedule
    {
        if ($schedule === null && $service->status !== RecordStatus::Active) {
            throw ValidationException::withMessages([
                'service_id' => __('ui.fees.service_inactive'),
            ]);
        }

        if ($schedule !== null && $schedule->service_id !== $service->id) {
            abort(404);
        }

        $attributes['government_fee'] = Money::normalize((string) $attributes['government_fee']);
        $attributes['consultancy_fee'] = Money::normalize((string) $attributes['consultancy_fee']);

        return DB::transaction(function () use ($attributes, $actor, $service, $schedule) {
            $schedule ??= new FeeSchedule;
            $before = $schedule->exists ? $schedule->auditSnapshot() : null;

            $schedule->fill($attributes);
            $fieldsChanged = $schedule->isDirty();

            if ($schedule->exists && ! $fieldsChanged) {
                return $schedule;
            }

            if (! $schedule->exists) {
                $schedule->service_id = $service->id;
                $schedule->status = RecordStatus::Active;
                $schedule->created_by = $actor->id;
            }

            $schedule->updated_by = $actor->id;

            $active = $schedule->status === RecordStatus::Active;

            if ($active && $this->ranges->overlaps(
                $service->id,
                $schedule->effective_from->toDateString(),
                $schedule->effective_until?->toDateString(),
                $schedule->exists ? $schedule->id : null,
            )) {
                throw ValidationException::withMessages([
                    'effective_from' => __('ui.fees.overlap'),
                ]);
            }

            $schedule->save();

            $after = $schedule->fresh()->auditSnapshot();
            [$oldValues, $newValues] = $before === null ? [[], $after] : AuditDiff::changed($before, $after);

            if ($oldValues !== [] || $newValues !== []) {
                $this->audit->log(
                    action: $before === null ? 'fee_schedule.created' : 'fee_schedule.updated',
                    subject: $schedule,
                    oldValues: $oldValues,
                    newValues: $newValues,
                    actor: $actor,
                    portal: $actor->portal,
                );
            }

            return $schedule;
        });
    }
}
