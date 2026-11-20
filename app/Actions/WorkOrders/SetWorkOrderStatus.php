<?php

namespace App\Actions\WorkOrders;

use App\Enums\WorkOrderStatus;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

class SetWorkOrderStatus
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(WorkOrder $workOrder, WorkOrderStatus $status, User $actor): WorkOrder
    {
        if ($workOrder->status === $status) {
            return $workOrder;
        }

        return DB::transaction(function () use ($workOrder, $status, $actor) {
            $previous = $workOrder->status?->value;
            $workOrder->status = $status;
            $workOrder->updated_by = $actor->id;
            $workOrder->save();

            $this->audit->log(
                action: 'work_order.status_changed',
                subject: $workOrder,
                oldValues: ['status' => $previous],
                newValues: ['status' => $status->value],
                actor: $actor,
                portal: $actor->portal,
            );

            return $workOrder;
        });
    }
}
