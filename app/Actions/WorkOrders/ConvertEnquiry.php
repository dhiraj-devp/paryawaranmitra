<?php

namespace App\Actions\WorkOrders;

use App\Enums\EnquiryStatus;
use App\Enums\RecordStatus;
use App\Enums\WorkOrderStatus;
use App\Models\Client;
use App\Models\Enquiry;
use App\Models\IndustryUnit;
use App\Models\Service;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\Audit\AuditLogger;
use App\Services\Catalog\ServiceEligibility;
use App\Services\Numbers\DocumentNumber;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConvertEnquiry
{
    public function __construct(
        private AuditLogger $audit,
        private DocumentNumber $numbers,
        private ServiceEligibility $eligibility,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Enquiry $enquiry, array $attributes, User $actor): WorkOrder
    {
        try {
            return DB::transaction(function () use ($enquiry, $attributes, $actor) {
                $enquiry = Enquiry::query()->whereKey($enquiry->id)->lockForUpdate()->firstOrFail();

                if ($enquiry->status === EnquiryStatus::Converted || $enquiry->workOrder()->exists()) {
                    throw ValidationException::withMessages([
                        'enquiry' => __('ui.enquiries.already_converted'),
                    ]);
                }

                $client = $this->lockedParty(Client::class, $enquiry->client_id, $attributes['client_id'] ?? null, 'client_id', 'ui.enquiries.convert_needs_client', 'ui.enquiries.client_inactive');
                $unit = $this->lockedParty(IndustryUnit::class, $enquiry->industry_unit_id, $attributes['industry_unit_id'] ?? null, 'industry_unit_id', 'ui.enquiries.convert_needs_unit', 'ui.enquiries.unit_inactive');
                $service = $this->lockedParty(Service::class, $enquiry->service_id, $attributes['service_id'] ?? null, 'service_id', 'ui.enquiries.convert_needs_service', 'ui.enquiries.service_inactive');

                if ($unit->client_id !== $client->id) {
                    throw ValidationException::withMessages([
                        'industry_unit_id' => __('ui.enquiries.unit_mismatch'),
                    ]);
                }

                if (! $this->eligibility->allows($service, $unit->industry_category)) {
                    throw ValidationException::withMessages([
                        'service_id' => __('ui.enquiries.service_ineligible'),
                    ]);
                }

                $assigneeId = $this->nullableId($attributes['assigned_to'] ?? null);

                if ($assigneeId !== null) {
                    $assignee = User::query()->find($assigneeId);

                    if (! $assignee || ! $assignee->is_active) {
                        throw ValidationException::withMessages([
                            'assigned_to' => __('ui.enquiries.assignee_inactive'),
                        ]);
                    }
                }

                $previousStatus = $enquiry->status?->value;
                $startDate = Carbon::parse($attributes['start_date'])->toDateString();

                $workOrder = new WorkOrder;
                $workOrder->number = $this->numbers->allocate('work_order', Carbon::parse($startDate)->year);
                $workOrder->enquiry_id = $enquiry->id;
                $workOrder->client_id = $client->id;
                $workOrder->industry_unit_id = $unit->id;
                $workOrder->service_id = $service->id;
                $workOrder->assigned_to = $assigneeId;
                $workOrder->work_description = $attributes['work_description'];
                $workOrder->start_date = $startDate;
                $workOrder->target_completion_date = ($attributes['target_completion_date'] ?? null) ?: null;
                $workOrder->status = WorkOrderStatus::Active;
                $workOrder->internal_notes = ($attributes['internal_notes'] ?? null) ?: null;
                $workOrder->created_by = $actor->id;
                $workOrder->updated_by = $actor->id;
                $workOrder->save();

                $enquiry->client_id = $client->id;
                $enquiry->industry_unit_id = $unit->id;
                $enquiry->service_id = $service->id;
                $enquiry->status = EnquiryStatus::Converted;
                $enquiry->updated_by = $actor->id;
                $enquiry->save();

                $this->audit->log(
                    action: 'work_order.created',
                    subject: $workOrder,
                    newValues: $workOrder->fresh()->auditSnapshot(),
                    actor: $actor,
                    portal: $actor->portal,
                );

                $this->audit->log(
                    action: 'enquiry.converted',
                    subject: $enquiry,
                    oldValues: ['status' => $previousStatus],
                    newValues: [
                        'status' => EnquiryStatus::Converted->value,
                        'work_order_id' => $workOrder->id,
                        'work_order_number' => $workOrder->number,
                        'client_id' => $client->id,
                        'industry_unit_id' => $unit->id,
                        'service_id' => $service->id,
                    ],
                    actor: $actor,
                    portal: $actor->portal,
                );

                return $workOrder;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'enquiry' => __('ui.enquiries.already_converted'),
            ]);
        }
    }

    /**
     * @param  class-string<Client|IndustryUnit|Service>  $model
     */
    private function lockedParty(string $model, mixed $storedId, mixed $postedId, string $field, string $missingKey, string $inactiveKey): Client|IndustryUnit|Service
    {
        $stored = $this->nullableId($storedId);
        $posted = $this->nullableId($postedId);

        if ($stored !== null && $posted !== $stored) {
            throw ValidationException::withMessages([
                $field => __('ui.enquiries.locked_link'),
            ]);
        }

        $id = $stored ?? $posted;

        if ($id === null) {
            throw ValidationException::withMessages([
                $field => __($missingKey),
            ]);
        }

        /** @var Client|IndustryUnit|Service|null $record */
        $record = $model::query()->find($id);

        if (! $record || $record->status !== RecordStatus::Active) {
            throw ValidationException::withMessages([
                $field => __($inactiveKey),
            ]);
        }

        return $record;
    }

    private function nullableId(mixed $id): ?int
    {
        if ($id === null || $id === '') {
            return null;
        }

        return (int) $id;
    }
}
