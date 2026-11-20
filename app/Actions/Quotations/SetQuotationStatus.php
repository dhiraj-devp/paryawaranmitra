<?php

namespace App\Actions\Quotations;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SetQuotationStatus
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(Quotation $quotation, QuotationStatus $status, ?string $reason, User $actor): Quotation
    {
        return DB::transaction(function () use ($quotation, $status, $reason, $actor) {
            $locked = Quotation::query()->whereKey($quotation->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->allows($status)) {
                throw ValidationException::withMessages([
                    'status' => __('ui.quotations.invalid_transition'),
                ]);
            }

            $reason = trim((string) $reason);

            if ($status->needsReason() && $reason === '') {
                throw ValidationException::withMessages([
                    'reason' => __('ui.quotations.reason_required'),
                ]);
            }

            $previous = $locked->status;
            $previousAccepted = $locked->accepted_revision_id;
            $locked->status = $status;
            $locked->updated_by = $actor->id;

            if ($status === QuotationStatus::Accepted) {
                $locked->accepted_revision_id = $locked->current_revision_id;
            }

            $locked->save();

            $this->audit->log(
                action: $this->action($status),
                subject: $locked,
                oldValues: [
                    'status' => $previous->value,
                    'accepted_revision_id' => $previousAccepted,
                ],
                newValues: [
                    'status' => $status->value,
                    'accepted_revision_id' => $locked->accepted_revision_id,
                    'current_revision_id' => $locked->current_revision_id,
                    'reason' => $reason !== '' ? $reason : null,
                    'changed_by' => $actor->id,
                ],
                actor: $actor,
                portal: $actor->portal,
            );

            return $locked;
        });
    }

    private function action(QuotationStatus $status): string
    {
        return match ($status) {
            QuotationStatus::Sent => 'quotation.sent',
            QuotationStatus::Accepted => 'quotation.accepted',
            QuotationStatus::Rejected => 'quotation.rejected',
            QuotationStatus::Cancelled => 'quotation.cancelled',
            QuotationStatus::Expired => 'quotation.expired',
            QuotationStatus::Draft => 'quotation.status_changed',
        };
    }
}
