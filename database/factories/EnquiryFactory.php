<?php

namespace Database\Factories;

use App\Enums\EnquirySource;
use App\Enums\EnquiryStatus;
use App\Models\Enquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enquiry>
 */
class EnquiryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'ENQ-2026-'.fake()->unique()->numerify('####'),
            'enquiry_date' => '2026-11-01',
            'contact_name' => fake()->name(),
            'mobile' => '9876543210',
            'email' => null,
            'source' => EnquirySource::Phone,
            'requirement' => 'A consultancy request recorded for the office.',
            'internal_notes' => null,
            'status' => EnquiryStatus::New,
        ];
    }
}
