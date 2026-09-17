<?php

namespace Database\Factories;

use App\Models\CareerProfile;
use App\Models\CareerPublication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CareerPublication>
 */
class CareerPublicationFactory extends Factory
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
            'title' => 'Publikasi Sintetis',
            'publication_name' => 'Jurnal Sintetis',
        ];
    }
}
