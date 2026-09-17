<?php

namespace Database\Factories;

use App\Models\CareerCv;
use App\Models\CvItemPreference;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CvItemPreference>
 */
class CvItemPreferenceFactory extends Factory
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
            'section_key' => 'skills',
            'source_item_id' => 1,
            'enabled' => true,
            'sort_order' => 0,
        ];
    }
}
