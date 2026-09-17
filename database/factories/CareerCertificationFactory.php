<?php

namespace Database\Factories;

use App\Models\CareerCertification;
use App\Models\CareerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CareerCertification>
 */
class CareerCertificationFactory extends Factory
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
            'title' => 'Sertifikasi Sintetis',
            'issuer' => 'Penerbit Sintetis',
        ];
    }
}
