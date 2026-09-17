<?php

namespace Database\Factories;

use App\Models\CareerEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CareerEvent>
 */
class CareerEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'slug' => fake()->unique()->slug(),
            'reference' => 'EVT-'.fake()->unique()->numerify('########'),
            'event_type' => 'seminar',
            'organizer' => 'Farmasi UBP',
            'description' => fake()->paragraph(),
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(3),
            'location_type' => 'onsite',
            'location_text' => 'Kampus UBP Karawang',
            'capacity' => 100,
            'registration_opens_at' => now()->subDay(),
            'registration_closes_at' => now()->addDays(5),
            'status' => 'published',
            'certificate_enabled' => true,
            'created_by_core_user_id' => 'core-admin-fixture',
        ];
    }
}
