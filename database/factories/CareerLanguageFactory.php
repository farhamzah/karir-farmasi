<?php

namespace Database\Factories;

use App\Models\CareerLanguage;
use App\Models\CareerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CareerLanguage>
 */
class CareerLanguageFactory extends Factory
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
            'language' => 'Bahasa Indonesia',
            'proficiency' => 'Mahir',
        ];
    }
}
