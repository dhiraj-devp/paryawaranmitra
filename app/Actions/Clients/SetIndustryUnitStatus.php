<?php

namespace App\Actions\Clients;

use App\Enums\RecordStatus;
use App\Models\IndustryUnit;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

class SetIndustryUnitStatus
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(IndustryUnit $unit, RecordStatus $status, User $actor): IndustryUnit
    {
        if ($unit->status === $status) {
            return $unit;
        }

        return DB::transaction(function () use ($unit, $status, $actor) {
            $previous = $unit->status?->value;
            $unit->status = $status;
            $unit->save();

            $this->audit->log(
                action: 'industry_unit.status_changed',
                subject: $unit,
                oldValues: ['status' => $previous],
                newValues: ['status' => $status->value],
                actor: $actor,
                portal: $actor->portal,
            );

            return $unit;
        });
    }
}
