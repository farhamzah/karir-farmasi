<?php

namespace Database\Factories;

use App\Models\CareerEventTopic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CareerEventTopic>
 */
class CareerEventTopicFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(2),
            'label' => fake()->unique()->words(2, true),
        ];
    }
}
