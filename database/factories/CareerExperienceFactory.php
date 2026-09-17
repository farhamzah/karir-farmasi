<?php

namespace Database\Factories;

use App\Models\CareerExperience;
use App\Models\CareerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CareerExperience>
 */
class CareerExperienceFactory extends Factory
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
            'type' => 'internship',
            'organization' => 'Apotek Sintetis',
            'title' => 'Peserta Magang',
        ];
    }
}
