<?php

namespace Database\Factories;

use App\Models\CareerOrganization;
use App\Models\CareerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CareerOrganization>
 */
class CareerOrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'career_profile_id' => CareerProfile::factory(),
            'organization' => 'Organisasi Sintetis',
            'role' => 'Anggota',
        ];
    }
}
