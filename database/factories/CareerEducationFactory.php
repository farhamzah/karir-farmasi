<?php

namespace Database\Factories;

use App\Models\CareerEducation;
use App\Models\CareerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CareerEducation>
 */
class CareerEducationFactory extends Factory
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
            'institution_name' => 'Universitas Buana Perjuangan Karawang',
            'program_name' => 'Farmasi',
            'degree' => 'S1',
            'source' => 'user_declared',
        ];
    }
}
