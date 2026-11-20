<?php

namespace App\Services\FollowUps;

use App\Enums\FollowUpMethod;
use App\Enums\FollowUpStatus;
use App\Enums\FollowUpType;
use App\Models\Enquiry;
use App\Models\EnquiryFollowUp;
use App\Models\FollowUp;
use Illuminate\Support\Str;

class ImportLegacyEnquiryFollowUps
{
    public function execute(): int
    {
        $copied = 0;

        EnquiryFollowUp::query()
            ->with('enquiry')
            ->orderBy('id')
            ->each(function (EnquiryFollowUp $row) use (&$copied): void {
                $key = 'legacy-'.$row->id;

                if (FollowUp::query()->where('import_key', $key)->exists()) {
                    return;
                }

                $method = $row->method instanceof FollowUpMethod
                    ? $row->method
                    : FollowUpMethod::from((string) $row->method);

                $followUp = new FollowUp([
                    'type' => FollowUpType::fromMethod($method),
                    'scheduled_at' => $row->followed_up_at,
                    'assigned_to' => $row->enquiry?->assigned_to,
                    'notes' => $this->notes($row->notes, $row->outcome),
                    'status' => FollowUpStatus::Completed,
                    'completed_at' => $row->followed_up_at,
                    'legacy_enquiry_follow_up_id' => $row->id,
                    'import_key' => $key,
                ]);
                $followUp->followable()->associate($row->enquiry);
                $followUp->created_by = $row->created_by;
                $followUp->updated_by = $row->created_by;
                $followUp->save();
                $copied++;
            });

        FollowUp::query()
            ->whereNotNull('legacy_enquiry_follow_up_id')
            ->orderBy('id')
            ->each(function (FollowUp $followUp): void {
                $lines = preg_split("/\r\n|\n|\r/", (string) $followUp->notes) ?: [];
                $lines = array_values(array_filter(array_map('trim', $lines), fn (string $line) => $line !== ''));

                if (count($lines) === 2 && $lines[0] === $lines[1]) {
                    $followUp->notes = $lines[0];
                    $followUp->save();
                }
            });

        Enquiry::query()
            ->whereNotNull('next_follow_up_on')
            ->orderBy('id')
            ->each(function (Enquiry $enquiry) use (&$copied): void {
                $key = 'enquiry-next-'.$enquiry->id;

                if (FollowUp::query()->where('import_key', $key)->exists()) {
                    return;
                }

                $followUp = new FollowUp([
                    'type' => FollowUpType::Call,
                    'scheduled_at' => $enquiry->next_follow_up_on->format('Y-m-d').' 09:00:00',
                    'assigned_to' => $enquiry->assigned_to,
                    'notes' => Str::limit(trim((string) $enquiry->requirement), 500, ''),
                    'status' => FollowUpStatus::Pending,
                    'import_key' => $key,
                ]);
                $followUp->followable()->associate($enquiry);
                $followUp->created_by = $enquiry->created_by;
                $followUp->updated_by = $enquiry->updated_by;
                $followUp->save();
                $copied++;
            });

        return $copied;
    }

    private function notes(string $notes, ?string $outcome): string
    {
        $notes = trim($notes);
        $outcome = trim((string) $outcome);

        if ($outcome === '' || $outcome === $notes || str_contains($notes, $outcome)) {
            return $notes !== '' ? $notes : $outcome;
        }

        return trim($notes."\n".$outcome);
    }
}
