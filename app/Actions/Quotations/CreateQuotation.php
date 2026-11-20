<?php

namespace App\Actions\Quotations;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\Audit\AuditLogger;
use App\Services\Numbers\DocumentNumber;
use App\Services\Quotations\QuotationComposer;
use App\Services\Quotations\RevisionWriter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CreateQuotation
{
    public function __construct(
        private AuditLogger $audit,
        private DocumentNumber $numbers,
        private QuotationComposer $composer,
        private RevisionWriter $revisions,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function execute(WorkOrder $workOrder, array $input, User $actor): Quotation
    {
        return DB::transaction(function () use ($workOrder, $input, $actor) {
            $locked = WorkOrder::query()->whereKey($workOrder->id)->lockForUpdate()->firstOrFail();
            $prepared = $this->composer->prepare($locked, $input);
            $serviceLine = $prepared->lines[0];

            $quotation = new Quotation;
            $quotation->quotation_number = $this->numbers->allocate(
                'quotation',
                (int) Carbon::parse($prepared->attributes['issue_date'])->year,
            );
            $quotation->work_order_id = $locked->id;
            $quotation->status = QuotationStatus::Draft;
            $quotation->created_by = $actor->id;
            $quotation->updated_by = $actor->id;
            $quotation->save();

            $revision = $this->revisions->write($quotation, 1, $prepared, $actor);
            $quotation->current_revision_id = $revision->id;
            $quotation->save();

            $snapshot = [
                'quotation_number' => $quotation->quotation_number,
                'work_order_id' => $locked->id,
                'client_id' => $locked->client_id,
                'industry_unit_id' => $locked->industry_unit_id,
                'service_id' => $locked->service_id,
                'revision_id' => $revision->id,
                'revision_number' => 1,
                'issue_date' => $prepared->attributes['issue_date'],
                'valid_until' => $prepared->attributes['valid_until'],
                'fee_schedule_id' => $prepared->schedule->id,
                'schedule_government_fee' => $serviceLine['schedule_government_fee'],
                'schedule_consultancy_fee' => $serviceLine['schedule_consultancy_fee'],
                'government_fee' => $serviceLine['government_fee'],
                'consultancy_fee' => $serviceLine['consultancy_fee'],
                'amounts_adjusted' => $prepared->amountsAdjusted,
                'government_total' => $prepared->attributes['government_total'],
                'consultancy_total' => $prepared->attributes['consultancy_total'],
                'discount_amount' => $prepared->attributes['discount_amount'],
                'tax_amount' => $prepared->attributes['tax_amount'],
                'grand_total' => $prepared->attributes['grand_total'],
            ];

            $this->audit->log(
                action: 'quotation.created',
                subject: $quotation,
                newValues: $snapshot,
                actor: $actor,
                portal: $actor->portal,
            );

            $this->audit->log(
                action: 'quotation.revision_created',
                subject: $quotation,
                newValues: $snapshot,
                actor: $actor,
                portal: $actor->portal,
            );

            return $quotation;
        });
    }
}
