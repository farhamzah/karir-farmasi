<?php

namespace Database\Factories;

use App\Models\CareerJobPreference;
use App\Models\CareerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CareerJobPreference>
 */
class CareerJobPreferenceFactory extends Factory
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
            'target_roles' => ['Apoteker'],
            'employment_types' => ['full_time'],
            'preferred_locations' => ['Karawang'],
            'willing_to_relocate' => false,
        ];
    }
}
