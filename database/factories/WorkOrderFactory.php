<?php

namespace Database\Factories;

use App\Enums\WorkOrderStatus;
use App\Models\Client;
use App\Models\Enquiry;
use App\Models\IndustryUnit;
use App\Models\Service;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkOrder>
 */
class WorkOrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'WO-2026-'.fake()->unique()->numerify('####'),
            'enquiry_id' => Enquiry::factory(),
            'client_id' => Client::factory(),
            'industry_unit_id' => IndustryUnit::factory(),
            'service_id' => Service::factory(),
            'work_description' => 'Confirmed consultancy work.',
            'start_date' => '2026-11-18',
            'target_completion_date' => null,
            'status' => WorkOrderStatus::Active,
            'internal_notes' => null,
        ];
    }
}
