<?php

namespace App\Services\Numbers;

use App\Models\NumberSequence;
use Illuminate\Database\UniqueConstraintViolationException;
use InvalidArgumentException;

class DocumentNumber
{
    public function allocate(string $series, int $year): string
    {
        $prefix = match ($series) {
            'enquiry' => 'ENQ',
            'work_order' => 'WO',
            'quotation' => 'QT',
            default => throw new InvalidArgumentException('Unknown number series.'),
        };

        $key = $series.':'.$year;

        try {
            $sequence = NumberSequence::query()->firstOrCreate(
                ['key' => $key],
                ['last_number' => 0],
            );
        } catch (UniqueConstraintViolationException) {
            $sequence = NumberSequence::query()->where('key', $key)->firstOrFail();
        }

        $locked = NumberSequence::query()->whereKey($sequence->id)->lockForUpdate()->firstOrFail();
        $locked->last_number++;
        $locked->save();

        return sprintf('%s-%d-%04d', $prefix, $year, $locked->last_number);
    }
}
