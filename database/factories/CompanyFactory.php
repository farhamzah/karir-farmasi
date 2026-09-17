<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'legal_name' => fake()->company().' Indonesia',
            'display_name' => fake()->company(),
            'business_sector' => 'Industri Farmasi',
            'city' => 'Karawang',
            'verification_status' => 'pending',
            'active' => true,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn () => ['verification_status' => 'verified', 'verified_at' => now(), 'verified_by_core_user_id' => 'core-admin-fixture']);
    }
}
