<?php

namespace Database\Factories;

use App\Models\CareerEventCertificate;
use App\Models\CareerEventRegistration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CareerEventCertificate>
 */
class CareerEventCertificateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'career_event_registration_id' => CareerEventRegistration::factory(),
            'certificate_number' => 'CERT-'.fake()->unique()->numerify('########'),
            'verification_code' => fake()->unique()->sha256(),
            'issued_at' => now(),
            'file_path' => 'event-certificates/'.fake()->uuid().'.pdf',
            'file_mime' => 'application/pdf',
            'issued_by_core_user_id' => 'core-admin-fixture',
        ];
    }
}
