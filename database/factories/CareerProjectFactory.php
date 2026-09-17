<?php

namespace Database\Factories;

use App\Models\CareerProfile;
use App\Models\CareerProject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CareerProject>
 */
class CareerProjectFactory extends Factory
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
            'title' => 'Proyek Sintetis',
            'category' => 'Akademik',
        ];
    }
}
