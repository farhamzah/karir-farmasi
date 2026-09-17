<?php

namespace Database\Factories;

use App\Models\CareerProfile;
use App\Models\CareerSkill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CareerSkill>
 */
class CareerSkillFactory extends Factory
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
            'name' => 'Pelayanan Kefarmasian',
        ];
    }
}
