<?php

namespace Database\Factories;

use App\Models\CareerCv;
use App\Models\CvSectionPreference;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CvSectionPreference>
 */
class CvSectionPreferenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'career_cv_id' => CareerCv::factory(),
            'section_key' => 'summary',
            'enabled' => true,
            'sort_order' => 0,
            'display_title' => null,
        ];
    }
}
