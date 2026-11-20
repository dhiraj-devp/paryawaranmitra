<?php

namespace Database\Factories;

use App\Enums\RecordStatus;
use App\Models\FeeSchedule;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeeSchedule>
 */
class FeeScheduleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'effective_from' => '2026-01-01',
            'effective_until' => null,
            'government_fee' => '1000.00',
            'consultancy_fee' => '2500.00',
            'status' => RecordStatus::Active,
            'internal_notes' => null,
        ];
    }
}
