<?php

namespace App\Services\Quotations;

use App\Models\FeeSchedule;

class PreparedRevision
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<array<string, mixed>>  $lines
     */
    public function __construct(
        public FeeSchedule $schedule,
        public bool $amountsAdjusted,
        public array $attributes,
        public array $lines,
    ) {}
}
