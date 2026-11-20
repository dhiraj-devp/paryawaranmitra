<?php

namespace App\Actions\WorkOrders;

use App\Models\User;
use App\Models\WorkOrder;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignWorkOrder
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(WorkOrder $workOrder, ?int $assigneeId, User $actor): WorkOrder
    {
        if ($assigneeId !== null) {
            $assignee = User::query()->find($assigneeId);

            if (! $assignee || ! $assignee->is_active) {
                throw ValidationException::withMessages([
                    'assigned_to' => __('ui.enquiries.assignee_inactive'),
                ]);
            }
        }

        if ($workOrder->assigned_to === $assigneeId) {
            return $workOrder;
        }

        return DB::transaction(function () use ($workOrder, $assigneeId, $actor) {
            $previous = $workOrder->assigned_to;
            $workOrder->assigned_to = $assigneeId;
            $workOrder->updated_by = $actor->id;
            $workOrder->save();

            $this->audit->log(
                action: 'work_order.assignment_changed',
                subject: $workOrder,
                oldValues: ['assigned_to' => $previous],
                newValues: ['assigned_to' => $assigneeId],
                actor: $actor,
                portal: $actor->portal,
            );

            return $workOrder;
        });
    }
}
