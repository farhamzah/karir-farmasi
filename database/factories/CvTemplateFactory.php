<?php

namespace Database\Factories;

use App\Models\CvTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CvTemplate>
 */
class CvTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'fixture-'.$this->faker->unique()->numberBetween(100, 99999),
            'name' => 'Template Sintetis',
            'description' => 'Template hanya untuk pengujian lokal.',
            'active' => true,
            'category' => 'fixture',
        ];
    }
}
