<?php

namespace App\Actions\Quotations;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use App\Models\QuotationRevision;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\Audit\AuditLogger;
use App\Services\Quotations\QuotationComposer;
use App\Services\Quotations\RevisionWriter;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviseQuotation
{
    public function __construct(
        private AuditLogger $audit,
        private QuotationComposer $composer,
        private RevisionWriter $revisions,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function execute(Quotation $quotation, array $input, User $actor): Quotation
    {
        return DB::transaction(function () use ($quotation, $input, $actor) {
            $locked = Quotation::query()->whereKey($quotation->id)->lockForUpdate()->firstOrFail();
            $reason = $this->guard($locked, $input);
            $workOrder = WorkOrder::query()->whereKey($locked->work_order_id)->lockForUpdate()->firstOrFail();
            $prepared = $this->composer->prepare($workOrder, $input);
            $next = (int) QuotationRevision::query()->where('quotation_id', $locked->id)->max('revision_number') + 1;

            try {
                $revision = $this->revisions->write($locked, $next, $prepared, $actor);
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages([
                    'revision' => __('ui.quotations.revision_conflict'),
                ]);
            }

            $previousStatus = $locked->status;
            $locked->current_revision_id = $revision->id;
            $locked->updated_by = $actor->id;

            if (in_array($previousStatus, [QuotationStatus::Sent, QuotationStatus::Accepted], true)) {
                $locked->status = QuotationStatus::Draft;
            }

            $locked->save();

            $this->audit->log(
                action: 'quotation.revision_created',
                subject: $locked,
                newValues: [
                    'revision_id' => $revision->id,
                    'revision_number' => $revision->revision_number,
                    'issue_date' => $prepared->attributes['issue_date'],
                    'valid_until' => $prepared->attributes['valid_until'],
                    'government_total' => $prepared->attributes['government_total'],
                    'consultancy_total' => $prepared->attributes['consultancy_total'],
                    'discount_amount' => $prepared->attributes['discount_amount'],
                    'tax_amount' => $prepared->attributes['tax_amount'],
                    'grand_total' => $prepared->attributes['grand_total'],
                    'fee_schedule_id' => $prepared->schedule->id,
                    'amounts_adjusted' => $prepared->amountsAdjusted,
                    'accepted_revision_id' => $locked->accepted_revision_id,
                    'reason' => $reason,
                ],
                actor: $actor,
                portal: $actor->portal,
            );

            if ($previousStatus !== $locked->status) {
                $this->audit->log(
                    action: 'quotation.status_changed',
                    subject: $locked,
                    oldValues: ['status' => $previousStatus->value],
                    newValues: [
                        'status' => $locked->status->value,
                        'accepted_revision_id' => $locked->accepted_revision_id,
                        'reason' => $reason,
                    ],
                    actor: $actor,
                    portal: $actor->portal,
                );
            }

            return $locked;
        });
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function guard(Quotation $quotation, array $input): ?string
    {
        if (! $quotation->status->canRevise()) {
            throw ValidationException::withMessages([
                'revision' => __('ui.quotations.revision_closed'),
            ]);
        }

        $reason = trim((string) ($input['revision_reason'] ?? ''));

        if ($quotation->status !== QuotationStatus::Accepted) {
            return $reason !== '' ? $reason : null;
        }

        $confirmed = in_array($input['confirm_revision'] ?? null, ['1', 1, true], true);

        if (! $confirmed) {
            throw ValidationException::withMessages([
                'confirm_revision' => __('ui.quotations.confirm_revision'),
            ]);
        }

        if ($reason === '') {
            throw ValidationException::withMessages([
                'revision_reason' => __('ui.quotations.reason_required'),
            ]);
        }

        return $reason;
    }
}
