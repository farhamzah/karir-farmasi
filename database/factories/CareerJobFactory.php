<?php

namespace Database\Factories;

use App\Models\CareerJob;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CareerJob> */
class CareerJobFactory extends Factory
{
    public function definition(): array
    {
        return [
            'created_by_type' => 'campus',
            'created_by_reference' => 'test-campus-operator',
            'employer_display_name' => fake()->company(),
            'title' => fake()->randomElement(['Apoteker Penanggung Jawab', 'QA Officer', 'Regulatory Affairs Associate']),
            'employment_type' => 'full_time',
            'work_mode' => 'onsite',
            'city' => 'Karawang',
            'location_text' => 'Karawang, Jawa Barat',
            'description' => 'Kesempatan berkarier bagi talenta farmasi yang teliti dan berorientasi pada mutu.',
            'requirements' => 'Lulusan Farmasi dan mampu bekerja dalam tim.',
            'status' => 'published',
            'application_method' => 'internal',
            'source_type' => 'campus_input',
            'source_name' => 'Pusat Karier Farmasi UBP',
            'received_at' => now(),
            'source_verified_at' => now(),
            'published_at' => now(),
            'expires_at' => now()->addMonth(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => 'draft', 'published_at' => null]);
    }

    public function expired(): static
    {
        return $this->state(fn () => ['status' => 'published', 'expires_at' => now()->subDay()]);
    }
}
