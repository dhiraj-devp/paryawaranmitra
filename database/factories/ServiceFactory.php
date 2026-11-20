<?php

namespace Database\Factories;

use App\Enums\RecordStatus;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'SVC-'.fake()->unique()->numerify('####'),
            'name' => fake()->unique()->words(3, true),
            'category' => 'Consent',
            'description' => 'Office service used by the catalogue tests.',
            'detailed_description' => null,
            'expected_processing_days' => 30,
            'status' => RecordStatus::Active,
            'internal_notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'status' => RecordStatus::Inactive,
        ]);
    }
}
