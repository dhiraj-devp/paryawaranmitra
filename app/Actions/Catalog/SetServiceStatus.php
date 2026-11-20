<?php

namespace App\Actions\Catalog;

use App\Enums\RecordStatus;
use App\Models\Service;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

class SetServiceStatus
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(Service $service, RecordStatus $status, User $actor): Service
    {
        if ($service->status === $status) {
            return $service;
        }

        return DB::transaction(function () use ($service, $status, $actor) {
            $previous = $service->status?->value;
            $service->status = $status;
            $service->updated_by = $actor->id;
            $service->save();

            $this->audit->log(
                action: 'service.status_changed',
                subject: $service,
                oldValues: ['status' => $previous],
                newValues: ['status' => $status->value],
                actor: $actor,
                portal: $actor->portal,
            );

            return $service;
        });
    }
}
