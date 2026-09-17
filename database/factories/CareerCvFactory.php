<?php

namespace Database\Factories;

use App\Models\CareerCv;
use App\Models\CareerProfile;
use App\Models\CvTemplateVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CareerCv>
 */
class CareerCvFactory extends Factory
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
            'cv_template_version_id' => CvTemplateVersion::factory(),
            'name' => 'CV Sintetis',
            'custom_headline' => null,
            'custom_summary' => null,
            'status' => 'draft',
        ];
    }
}
