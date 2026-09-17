<?php

namespace Database\Factories;

use App\Models\CareerEvent;
use App\Models\CareerEventRegistration;
use App\Models\CareerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CareerEventRegistration>
 */
class CareerEventRegistrationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'career_event_id' => CareerEvent::factory(),
            'career_profile_id' => CareerProfile::factory(),
            'role' => 'participant',
            'status' => 'registered',
            'registered_at' => now(),
        ];
    }
}
