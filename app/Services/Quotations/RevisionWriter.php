<?php

namespace App\Services\Quotations;

use App\Models\Quotation;
use App\Models\QuotationLine;
use App\Models\QuotationRevision;
use App\Models\User;

class RevisionWriter
{
    public function write(Quotation $quotation, int $number, PreparedRevision $prepared, User $actor): QuotationRevision
    {
        $revision = new QuotationRevision;
        $revision->quotation_id = $quotation->id;
        $revision->revision_number = $number;
        $revision->created_by = $actor->id;

        foreach ($prepared->attributes as $key => $value) {
            $revision->{$key} = $value;
        }

        $revision->save();

        foreach ($prepared->lines as $line) {
            $row = new QuotationLine;
            $row->quotation_revision_id = $revision->id;

            foreach ($line as $key => $value) {
                $row->{$key} = $value;
            }

            $row->save();
        }

        return $revision;
    }
}
