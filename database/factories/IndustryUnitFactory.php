<?php

namespace Database\Factories;

use App\Enums\RecordStatus;
use App\Models\Client;
use App\Models\IndustryUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IndustryUnit>
 */
class IndustryUnitFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'name' => 'Unit '.fake()->unique()->numerify('###'),
            'status' => RecordStatus::Active,
            'industry_category' => 'chemical',
            'activity' => 'Manufacturing',
            'site_address_line' => fake()->streetAddress(),
            'site_landmark' => null,
            'site_village' => null,
            'site_taluka' => 'Haveli',
            'site_district' => 'Pune',
            'site_state' => 'Maharashtra',
            'site_pincode' => '411041',
            'plot_number' => null,
            'site_contact_name' => null,
            'site_contact_phone' => null,
            'notes' => null,
        ];
    }
}
