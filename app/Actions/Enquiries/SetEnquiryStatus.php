<?php

namespace App\Actions\Enquiries;

use App\Enums\EnquiryStatus;
use App\Models\Enquiry;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SetEnquiryStatus
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(Enquiry $enquiry, EnquiryStatus $status, User $actor): Enquiry
    {
        if ($status === EnquiryStatus::Converted) {
            throw ValidationException::withMessages([
                'status' => __('ui.enquiries.convert_only'),
            ]);
        }

        if ($enquiry->status === EnquiryStatus::Converted) {
            throw ValidationException::withMessages([
                'status' => __('ui.enquiries.converted_locked'),
            ]);
        }

        if ($enquiry->status === $status) {
            return $enquiry;
        }

        return DB::transaction(function () use ($enquiry, $status, $actor) {
            $previous = $enquiry->status?->value;
            $enquiry->status = $status;
            $enquiry->updated_by = $actor->id;
            $enquiry->save();

            $this->audit->log(
                action: 'enquiry.status_changed',
                subject: $enquiry,
                oldValues: ['status' => $previous],
                newValues: ['status' => $status->value],
                actor: $actor,
                portal: $actor->portal,
            );

            return $enquiry;
        });
    }
}
