<?php

namespace App\Actions\WorkOrders;

use App\Models\User;
use App\Models\WorkOrder;
use App\Services\Audit\AuditDiff;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

class SaveWorkOrder
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(WorkOrder $workOrder, array $attributes, User $actor): WorkOrder
    {
        return DB::transaction(function () use ($workOrder, $attributes, $actor) {
            $before = $workOrder->auditSnapshot();

            if (($attributes['target_completion_date'] ?? null) === '') {
                $attributes['target_completion_date'] = null;
            }

            if (($attributes['internal_notes'] ?? null) === '') {
                $attributes['internal_notes'] = null;
            }

            $workOrder->fill($attributes);

            if (! $workOrder->isDirty()) {
                return $workOrder;
            }

            $workOrder->updated_by = $actor->id;
            $workOrder->save();

            [$oldValues, $newValues] = AuditDiff::changed($before, $workOrder->fresh()->auditSnapshot());

            if ($oldValues !== [] || $newValues !== []) {
                $this->audit->log(
                    action: 'work_order.updated',
                    subject: $workOrder,
                    oldValues: $oldValues,
                    newValues: $newValues,
                    actor: $actor,
                    portal: $actor->portal,
                );
            }

            return $workOrder;
        });
    }
}
