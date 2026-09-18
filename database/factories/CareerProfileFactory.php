<?php

namespace Database\Factories;

use App\Models\CareerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CareerProfile>
 */
class CareerProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'core_user_id' => fake()->unique()->uuid(),
            'professional_name' => fake()->name(),
            'alumni_number' => fake()->unique()->numerify('##.######'),
            'graduation_year' => fake()->numberBetween(2020, 2026),
            'visible_in_alumni_directory' => true,
            'headline' => 'Profesional Farmasi',
            'professional_email' => fake()->unique()->safeEmail(),
            'open_to_work' => false,
            'profile_visibility' => 'private',
        ];
    }
}
