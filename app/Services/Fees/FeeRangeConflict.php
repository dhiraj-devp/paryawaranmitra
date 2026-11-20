<?php

namespace App\Services\Fees;

use App\Enums\RecordStatus;
use App\Models\FeeSchedule;

class FeeRangeConflict
{
    public function overlaps(int $serviceId, string $from, ?string $until, ?int $ignoreId = null): bool
    {
        return FeeSchedule::query()
            ->where('service_id', $serviceId)
            ->where('status', RecordStatus::Active->value)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->when($until !== null, fn ($query) => $query->whereDate('effective_from', '<=', $until))
            ->where(function ($query) use ($from) {
                $query->whereNull('effective_until')
                    ->orWhereDate('effective_until', '>=', $from);
            })
            ->exists();
    }
}
