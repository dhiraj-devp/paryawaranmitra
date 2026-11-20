<?php

namespace App\Actions\Quotations;

use App\Models\Quotation;
use App\Models\QuotationRevision;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Quotations\TaxSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DeliverQuotationPdf
{
    public function __construct(
        private AuditLogger $audit,
        private TaxSettings $tax,
    ) {}

    public function execute(Quotation $quotation, QuotationRevision $revision, User $actor): string
    {
        if ($revision->quotation_id !== $quotation->id) {
            abort(404);
        }

        $quotation->load([
            'workOrder.client',
            'workOrder.industryUnit',
            'workOrder.service',
        ]);
        $revision->load('lines');

        $path = $quotation->id.'/r'.$revision->revision_number.'.pdf';
        $disk = Storage::disk('quotations');
        $created = false;

        if (! $disk->exists($path)) {
            $bytes = Pdf::loadView('quotations.pdf', [
                'quotation' => $quotation,
                'revision' => $revision,
                'company' => config('company'),
                'taxPending' => $this->tax->pending(),
            ])->setPaper('a4')->output();

            $disk->put($path, $bytes);
            $created = true;
        }

        try {
            DB::transaction(function () use ($created, $quotation, $revision, $actor, $path) {
                if ($created) {
                    $this->audit->log(
                        action: 'quotation.pdf_generated',
                        subject: $quotation,
                        newValues: [
                            'revision_id' => $revision->id,
                            'revision_number' => $revision->revision_number,
                            'path' => $path,
                        ],
                        actor: $actor,
                        portal: $actor->portal,
                    );
                }

                $this->audit->log(
                    action: 'quotation.pdf_downloaded',
                    subject: $quotation,
                    newValues: [
                        'revision_id' => $revision->id,
                        'revision_number' => $revision->revision_number,
                    ],
                    actor: $actor,
                    portal: $actor->portal,
                );
            });
        } catch (Throwable $exception) {
            if ($created) {
                $disk->delete($path);
            }

            throw $exception;
        }

        return $path;
    }
}
