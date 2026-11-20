<?php

namespace Database\Factories;

use App\Enums\ClientConstitution;
use App\Enums\RecordStatus;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'legal_name' => fake()->unique()->company(),
            'trade_name' => null,
            'constitution' => ClientConstitution::PrivateLimited,
            'status' => RecordStatus::Active,
            'pan' => null,
            'gstin' => null,
            'email' => fake()->unique()->safeEmail(),
            'phone' => '9876543210',
            'contact_name' => fake()->name(),
            'address_line' => fake()->streetAddress(),
            'city' => 'Pune',
            'district' => 'Pune',
            'state' => 'Maharashtra',
            'pincode' => '411001',
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'status' => RecordStatus::Inactive,
        ]);
    }
}
