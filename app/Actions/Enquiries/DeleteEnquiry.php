<?php

namespace App\Actions\Enquiries;

use App\Models\Enquiry;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DeleteEnquiry
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(Enquiry $enquiry, User $actor): void
    {
        if ($enquiry->hasDependents()) {
            throw new RuntimeException('Enquiries are not deleted. Close the enquiry with a status instead.');
        }

        DB::transaction(function () use ($enquiry, $actor) {
            $this->audit->log(
                action: 'enquiry.deleted',
                subject: $enquiry,
                oldValues: $enquiry->auditSnapshot(),
                actor: $actor,
                portal: $actor->portal,
            );

            $enquiry->delete();
        });
    }
}
