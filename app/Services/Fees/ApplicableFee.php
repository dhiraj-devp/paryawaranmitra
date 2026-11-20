<?php

namespace App\Services\Fees;

use App\Enums\RecordStatus;
use App\Models\FeeSchedule;
use App\Models\Service;
use Illuminate\Support\Carbon;

class ApplicableFee
{
    public function on(Service $service, Carbon $date): ?FeeSchedule
    {
        if ($service->status !== RecordStatus::Active) {
            return null;
        }

        $day = $date->toDateString();

        return FeeSchedule::query()
            ->where('service_id', $service->id)
            ->where('status', RecordStatus::Active)
            ->whereDate('effective_from', '<=', $day)
            ->where(function ($query) use ($day) {
                $query->whereNull('effective_until')
                    ->orWhereDate('effective_until', '>=', $day);
            })
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();
    }
}
